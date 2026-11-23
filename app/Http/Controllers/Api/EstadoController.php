<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\ComunicadoEnfen;
use App\Models\Distrito;
use App\Models\Emergencia;
use App\Models\Sincronizacion;
use Illuminate\Http\JsonResponse;

/**
 * API pública de solo lectura (JSON). La usa el mapa y la actualización
 * automática de la página cada pocos minutos.
 */
class EstadoController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $vigentes = Aviso::vigentes()->get(['id', 'distrito_id', 'nivel']);
        $provinciales = $vigentes->whereNull('distrito_id');

        $distritos = Distrito::with('ultimaLectura')->orderBy('nombre')->get()->map(function (Distrito $d) use ($vigentes, $provinciales) {
            $l = $d->ultimaLectura;
            $avisos = $vigentes->where('distrito_id', $d->id)->merge($provinciales);

            return [
                'slug' => $d->slug,
                'nombre' => $d->nombre,
                'latitud' => $d->latitud,
                'longitud' => $d->longitud,
                'senamhi' => $l ? [
                    'nivel' => $l->nivel,
                    'fecha' => $l->fecha->toDateString(),
                    'consultado_en' => $l->consultado_en->toIso8601String(),
                    'verificada' => $l->estaVerificada(),
                    'origen' => $l->origen,
                ] : null,
                'avisos_vigentes' => $avisos->count(),
                'nivel_max_avisos' => (int) $avisos->max('nivel'),
            ];
        });

        $enfen = ComunicadoEnfen::ultimo();

        return response()->json([
            'generado' => now()->toIso8601String(),
            'ultima_lectura_senamhi' => Sincronizacion::ultimaExitosa('senamhi_lluvia')?->created_at?->toIso8601String(),
            'enfen' => $enfen ? $enfen->only(['numero', 'estado', 'titular']) + ['fecha' => $enfen->fecha->toDateString(), 'pdf_url' => $enfen->pdf_url] : null,
            'distritos' => $distritos,
            'emergencias' => Emergencia::whereNotNull('latitud')->orderByDesc('fecha')->take(20)->get()
                ->map(fn (Emergencia $e) => [
                    'fecha' => $e->fecha->toDateString(), 'distrito' => $e->distrito, 'fenomeno' => $e->fenomeno,
                    'latitud' => $e->latitud, 'longitud' => $e->longitud,
                ]),
        ]);
    }
}
