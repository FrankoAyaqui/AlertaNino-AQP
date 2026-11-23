<?php

namespace App\Services\Oficial;

use App\Models\Distrito;
use App\Support\Geometria;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Lee los servicios públicos WFS de SENAMHI (IDESEP).
 *
 * Los textos oficiales se devuelven sin cambios; este servicio solo decide qué
 * zona de cada aviso contiene el punto de referencia de cada distrito.
 */
class SenamhiService
{
    use ClienteHttp;

    /**
     * Aviso por lluvias de las próximas 24 horas, nivel por distrito.
     *
     * @param  Collection<int, Distrito>  $distritos
     * @return array<int, array{nivel:int, fecha:string, descripcion:?string, recomendacion:?string}>
     */
    public function lluvia24hPorDistrito(Collection $distritos): array
    {
        $zonas = $this->capa(config('alerta.senamhi.capa_lluvia_24h'));
        $resultado = [];

        foreach ($distritos as $distrito) {
            $mejor = null;
            foreach ($zonas as $zona) {
                if (Geometria::contiene($zona['geometry'] ?? null, $distrito->longitud, $distrito->latitud)) {
                    $p = $zona['properties'] ?? [];
                    $nivel = self::numeroNivel($p['nivel'] ?? null);
                    if ($mejor === null || $nivel > $mejor['nivel']) {
                        $mejor = [
                            'nivel' => $nivel,
                            'fecha' => substr((string) ($p['fecha'] ?? now()->toDateString()), 0, 10),
                            'descripcion' => self::limpio($p['descripcio'] ?? null),
                            'recomendacion' => self::limpio($p['recomendac'] ?? null),
                        ];
                    }
                }
            }
            // Sin zona = SENAMHI no incluye ese punto en el aviso de hoy (nivel 0).
            $resultado[$distrito->id] = $mejor ?? [
                'nivel' => 0, 'fecha' => now()->toDateString(), 'descripcion' => null, 'recomendacion' => null,
            ];
        }

        return $resultado;
    }

    /**
     * Avisos meteorológicos nacionales vigentes que incluyen algún distrito.
     *
     * @param  Collection<int, Distrito>  $distritos
     */
    public function avisosVigentes(Collection $distritos): array
    {
        $desde = now()->format('Y-m-d\TH:i:s');
        $avisos = [];
        foreach ($this->capa(config('alerta.senamhi.capa_avisos'), ['CQL_FILTER' => "fech_fin >= '{$desde}'"]) as $f) {
            $p = $f['properties'] ?? [];
            $incluidos = $distritos->filter(fn (Distrito $d) => Geometria::contiene($f['geometry'] ?? null, $d->longitud, $d->latitud));
            if ($incluidos->isEmpty()) {
                continue;
            }
            $avisos[] = [
                'numero' => (string) ($p['nro_aviso'] ?? ''),
                'nivel' => self::numeroNivel($p['nivel'] ?? null),
                'fenomeno' => self::limpio($p['cod_fen'] ?? null),
                'inicio' => $p['fech_ini'] ?? null,
                'fin' => $p['fech_fin'] ?? null,
                'distritos' => $incluidos->pluck('id')->all(),
            ];
        }

        return $avisos;
    }

    /** Avisos de activación de quebradas en el departamento de Arequipa. */
    public function quebradasArequipa(): array
    {
        $lista = [];
        foreach ($this->capa(config('alerta.senamhi.capa_quebradas'), ['CQL_FILTER' => "nombdep='AREQUIPA'", 'count' => 300]) as $f) {
            $p = $f['properties'] ?? [];
            $lista[] = [
                'fecha' => substr((string) ($p['fecha'] ?? ''), 0, 10),
                'provincia' => self::limpio($p['nombprov'] ?? null),
                'distrito' => self::limpio($p['nombdist'] ?? null),
                'nivel' => self::numeroNivel($p['nivel'] ?? null),
                'descripcion' => self::limpio($p['descripcion'] ?? null),
                'recomendacion' => self::limpio($p['recomendacion'] ?? null),
            ];
        }

        return $lista;
    }

    /** Descarga una capa WFS en GeoJSON. */
    private function capa(string $nombre, array $extra = []): array
    {
        $respuesta = $this->http()->get(config('alerta.senamhi.wfs'), array_merge([
            'service' => 'WFS', 'version' => '2.0.0', 'request' => 'GetFeature',
            'typeNames' => $nombre, 'outputFormat' => 'application/json',
        ], $extra))->throw();

        $datos = $respuesta->json();
        if (($datos['type'] ?? null) !== 'FeatureCollection' || ! is_array($datos['features'] ?? null)) {
            throw new RuntimeException("Respuesta inesperada de SENAMHI para {$nombre}");
        }

        return $datos['features'];
    }

    /** "Nivel 3" -> 3 */
    public static function numeroNivel(mixed $texto): int
    {
        return preg_match('/\d/', (string) $texto, $m) ? min(4, (int) $m[0]) : 0;
    }

    private static function limpio(mixed $texto): ?string
    {
        $t = trim(preg_replace('/\s+/u', ' ', (string) $texto));

        return $t === '' ? null : Str::limit($t, 3000, '');
    }
}
