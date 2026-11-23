<?php

namespace App\Services;

use App\Models\Aviso;
use App\Models\ComunicadoEnfen;
use App\Models\Distrito;
use App\Models\Emergencia;
use App\Models\LecturaSenamhi;
use App\Models\Sincronizacion;
use App\Models\User;
use App\Notifications\EstadoEnfenCambio;
use App\Notifications\NivelSenamhiElevado;
use App\Services\Oficial\EnfenService;
use App\Services\Oficial\EspejoService;
use App\Services\Oficial\SenamhiService;
use App\Services\Oficial\SinpadService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lee todas las fuentes oficiales, guarda una copia local, crea los avisos
 * automáticos de SENAMHI y notifica a los ciudadanos cuando algo cambia.
 *
 * Cada fuente se procesa por separado: si una falla, las demás siguen y el
 * fallo queda registrado en la tabla "sincronizaciones". Nunca se inventa un dato.
 */
class SincronizadorOficial
{
    public function __construct(
        private SenamhiService $senamhi,
        private EnfenService $enfen,
        private SinpadService $sinpad,
        private EspejoService $espejo,
        private PublicadorAvisos $publicador,
    ) {}

    /** @return array<string, Sincronizacion> resultado por fuente */
    public function ejecutar(): array
    {
        return [
            'senamhi_lluvia' => $this->paso('senamhi_lluvia', fn () => $this->lluvia24h()),
            'senamhi_avisos' => $this->paso('senamhi_avisos', fn () => $this->avisosMeteorologicos()),
            'senamhi_quebradas' => $this->paso('senamhi_quebradas', fn () => $this->quebradas()),
            'enfen' => $this->paso('enfen', fn () => $this->comunicadoEnfen()),
            'sinpad' => $this->paso('sinpad', fn () => $this->emergencias()),
        ];
    }

    /** Ejecuta un paso y registra su resultado. Devuelve [estado, registros, detalle]. */
    private function paso(string $fuente, callable $accion): Sincronizacion
    {
        try {
            [$estado, $registros, $detalle] = $accion();
        } catch (Throwable $e) {
            Log::warning("Sincronización {$fuente} falló", ['error' => $e->getMessage()]);
            [$estado, $registros, $detalle] = ['error', 0, Str::limit($e->getMessage(), 500)];
        }

        return Sincronizacion::create(compact('fuente', 'estado', 'registros', 'detalle'));
    }

    // ------------------------------------------------------------------ SENAMHI

    private function lluvia24h(): array
    {
        $distritos = Distrito::all();
        try {
            $niveles = $this->senamhi->lluvia24hPorDistrito($distritos);
            $origen = 'directo';
            $consultado = now();
        } catch (Throwable $e) {
            // SENAMHI no responde a servidores fuera del Perú: se usa la copia publicada desde Perú.
            $espejo = $this->espejo->lluvia24h();
            $origen = 'espejo';
            $consultado = CarbonImmutable::parse($espejo['consultado']);
            $niveles = [];
            foreach ($distritos as $d) {
                if (isset($espejo['niveles'][$d->slug])) {
                    $niveles[$d->id] = $espejo['niveles'][$d->slug];
                }
            }
        }

        $guardadas = 0;
        foreach ($distritos as $distrito) {
            $dato = $niveles[$distrito->id] ?? null;
            if (! $dato) {
                continue;
            }
            $guardadas++;
            $anterior = $distrito->lecturas()->orderByDesc('fecha')->orderByDesc('id')->first();

            // whereDate: la columna guarda fecha y hora, así que se compara solo el día.
            $lectura = DB::transaction(function () use ($distrito, $dato, $consultado, $origen) {
                $lectura = LecturaSenamhi::where('distrito_id', $distrito->id)->whereDate('fecha', $dato['fecha'])->first()
                    ?? new LecturaSenamhi(['distrito_id' => $distrito->id, 'fecha' => $dato['fecha']]);
                $lectura->fill([
                    'nivel' => $dato['nivel'], 'descripcion' => $dato['descripcion'],
                    'recomendacion' => $dato['recomendacion'], 'consultado_en' => $consultado, 'origen' => $origen,
                ])->save();

                return $lectura;
            });

            $nivelAnterior = $anterior?->nivel ?? 0;
            if ($lectura->nivel > $nivelAnterior && $lectura->nivel >= 2) {
                $this->notificarDistrito($distrito, new NivelSenamhiElevado($lectura->load('distrito'), $nivelAnterior));
            }

            // Desde el Nivel 2 se publica además como aviso, con el texto oficial.
            if ($lectura->nivel >= 2) {
                $this->avisoAutomatico("senamhi24h:{$dato['fecha']}:{$distrito->slug}", [
                    'distrito_id' => $distrito->id,
                    'titulo' => "Aviso por lluvias de SENAMHI: Nivel {$lectura->nivel} en {$distrito->nombre}",
                    'fuente' => 'SENAMHI',
                    'tipo' => 'quebradas',
                    'nivel' => $lectura->nivel,
                    'mensaje' => $lectura->descripcion ?? 'SENAMHI incluye este distrito en su aviso por lluvias de las próximas 24 horas.',
                    'recomendacion' => $lectura->recomendacion,
                    'fuente_url' => config('alerta.senamhi.visor'),
                    'emitido_en' => CarbonImmutable::parse($dato['fecha'])->startOfDay(),
                    'vence_en' => CarbonImmutable::parse($dato['fecha'])->addDay()->startOfDay(),
                ]);
            }
        }

        return [$origen === 'directo' ? 'ok' : 'espejo', $guardadas, "Origen: {$origen}; consultado: {$consultado->toIso8601String()}"];
    }

    private function avisosMeteorologicos(): array
    {
        $distritos = Distrito::all()->keyBy('id');
        $creados = 0;
        foreach ($this->senamhi->avisosVigentes($distritos->values()) as $a) {
            foreach ($a['distritos'] as $id) {
                $d = $distritos[$id];
                $this->avisoAutomatico("senamhiaviso:{$a['numero']}:{$d->slug}", [
                    'distrito_id' => $id,
                    'titulo' => "Aviso meteorológico N.º {$a['numero']} de SENAMHI (Nivel {$a['nivel']})",
                    'fuente' => 'SENAMHI',
                    'tipo' => 'lluvias',
                    'nivel' => max(1, $a['nivel']),
                    'mensaje' => "SENAMHI emitió el aviso meteorológico N.º {$a['numero']}, de nivel {$a['nivel']}"
                        .($a['fenomeno'] ? " (fenómeno {$a['fenomeno']})" : '').", que incluye a {$d->nombre}.",
                    'recomendacion' => 'Consulte el detalle en el visor oficial de SENAMHI y siga las indicaciones de las autoridades.',
                    'fuente_url' => config('alerta.senamhi.visor'),
                    'emitido_en' => CarbonImmutable::parse($a['inicio'] ?? now()),
                    'vence_en' => CarbonImmutable::parse($a['fin'] ?? now()->addDay()),
                ]);
                $creados++;
            }
        }

        return ['ok', $creados, null];
    }

    private function quebradas(): array
    {
        $distritos = Distrito::all()->keyBy(fn (Distrito $d) => Str::upper(Str::ascii($d->nombre)));
        $lista = $this->senamhi->quebradasArequipa();
        foreach ($lista as $q) {
            $d = $distritos[Str::upper(Str::ascii((string) $q['distrito']))] ?? null;
            $fecha = $q['fecha'] ?: now()->toDateString();
            $this->avisoAutomatico('quebrada:'.$fecha.':'.Str::slug($q['provincia'].'-'.$q['distrito']), [
                'distrito_id' => $d?->id,
                'titulo' => "Activación de quebradas: {$q['distrito']} ({$q['provincia']})",
                'fuente' => 'SENAMHI',
                'tipo' => 'quebradas',
                'nivel' => max(1, $q['nivel']),
                'mensaje' => $q['descripcion'] ?? "SENAMHI emitió un aviso de activación de quebradas para {$q['distrito']}.",
                'recomendacion' => $q['recomendacion'],
                'fuente_url' => config('alerta.senamhi.visor'),
                'emitido_en' => CarbonImmutable::parse($fecha)->startOfDay(),
                'vence_en' => CarbonImmutable::parse($fecha)->addDay()->startOfDay(),
            ]);
        }

        return ['ok', count($lista), null];
    }

    /** Crea o actualiza un aviso automático (identificado por su referencia externa) y lo notifica. */
    private function avisoAutomatico(string $referencia, array $datos): Aviso
    {
        $aviso = Aviso::firstOrNew(['referencia_externa' => $referencia]);
        $aviso->fill($datos + ['estado' => 'publicado'])
            ->forceFill(['origen' => 'automatico', 'referencia_externa' => $referencia])
            ->save();
        $this->publicador->notificarSiCorresponde($aviso);

        return $aviso;
    }

    // -------------------------------------------------------------------- ENFEN

    private function comunicadoEnfen(): array
    {
        try {
            $datos = $this->enfen->ultimoComunicado();
            $estado = 'ok';
        } catch (Throwable $e) {
            $datos = $this->espejo->enfen();
            $estado = 'espejo';
        }

        $previo = ComunicadoEnfen::ultimo();
        $comunicado = ComunicadoEnfen::updateOrCreate(['numero' => $datos['numero']], [
            'fecha' => $datos['fecha'], 'estado' => $datos['estado'], 'titular' => $datos['titular'],
            'resumen' => $datos['resumen'], 'lluvias' => $datos['lluvias'], 'pdf_url' => $datos['pdf_url'],
            'consultado_en' => now(),
        ]);

        if ($previo && $previo->numero !== $comunicado->numero && $previo->estado !== $comunicado->estado) {
            Notification::send(User::where('recibir_alertas', true)->get(), new EstadoEnfenCambio($comunicado, $previo->estado));
        }

        return [$estado, 1, "Comunicado N.º {$comunicado->numero}: {$comunicado->estado}"];
    }

    // -------------------------------------------------------------- INDECI SINPAD

    private function emergencias(): array
    {
        $lista = $this->sinpad->ultimasEmergencias();
        foreach ($lista as $e) {
            Emergencia::updateOrCreate(['ide_sinpad' => $e['ide_sinpad']], $e);
        }

        return ['ok', count($lista), null];
    }

    private function notificarDistrito(Distrito $distrito, $notificacion): void
    {
        Notification::send(
            User::where('recibir_alertas', true)->where('distrito_id', $distrito->id)->get(),
            $notificacion,
        );
    }
}
