<?php

namespace App\Services\Oficial;

use Carbon\CarbonImmutable;
use RuntimeException;

/** Consulta las emergencias registradas por INDECI (GeoSINPAD) en la provincia de Arequipa. */
class SinpadService
{
    use ClienteHttp;

    /** @return array<int, array<string, mixed>> */
    public function ultimasEmergencias(int $cantidad = 30): array
    {
        // Los valores son fijos (de config), no vienen del usuario.
        $peligros = "'".implode("','", config('alerta.sinpad.peligros'))."'";
        $datos = $this->http()->get(config('alerta.sinpad.consulta'), [
            'f' => 'json',
            'where' => "DEPARTAMENTO='AREQUIPA' AND PROVINCIA='AREQUIPA' AND FENOMENO IN ({$peligros})",
            'outFields' => 'IDE_SINPAD,FECHA,DISTRITO,FENOMENO,SAFECTA,SDAMNI,SAFECTAVIVI',
            'orderByFields' => 'FECHA DESC',
            'resultRecordCount' => $cantidad,
            'returnGeometry' => 'true',
            'outSR' => 4326,
        ])->throw()->json();

        if (! is_array($datos['features'] ?? null) || isset($datos['error'])) {
            throw new RuntimeException('Respuesta inválida de SINPAD.');
        }

        $lista = [];
        foreach ($datos['features'] as $f) {
            $a = $f['attributes'] ?? [];
            if (! is_numeric($a['FECHA'] ?? null) || empty($a['IDE_SINPAD'])) {
                continue;
            }
            $x = $f['geometry']['x'] ?? null;
            $y = $f['geometry']['y'] ?? null;
            $valida = is_numeric($x) && is_numeric($y) && $x > -82 && $x < -68 && $y > -19 && $y < 0;
            $lista[] = [
                'ide_sinpad' => (string) (int) $a['IDE_SINPAD'],
                'fecha' => CarbonImmutable::createFromTimestampMs((int) $a['FECHA'])->setTimezone('America/Lima'),
                'distrito' => (string) ($a['DISTRITO'] ?? ''),
                'fenomeno' => (string) ($a['FENOMENO'] ?? ''),
                'afectados' => self::entero($a['SAFECTA'] ?? null),
                'damnificados' => self::entero($a['SDAMNI'] ?? null),
                'viviendas_afectadas' => self::entero($a['SAFECTAVIVI'] ?? null),
                'latitud' => $valida ? (float) $y : null,
                'longitud' => $valida ? (float) $x : null,
            ];
        }

        return $lista;
    }

    private static function entero(mixed $v): ?int
    {
        return is_numeric($v) && $v >= 0 ? (int) $v : null;
    }
}
