<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\Contacto;
use App\Models\Distrito;
use App\Models\Guia;
use App\Models\RegistroSeguridad;
use App\Models\Sincronizacion;
use App\Models\User;
use App\Services\SincronizadorOficial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Tablero del personal y ejecución manual de la lectura oficial. */
class PanelController extends Controller
{
    public function index(): View
    {
        return view('panel.inicio', [
            'totales' => [
                'Avisos vigentes' => Aviso::vigentes()->count(),
                'Avisos manuales' => Aviso::where('origen', 'manual')->count(),
                'Guías' => Guia::count(),
                'Contactos' => Contacto::count(),
                'Distritos' => Distrito::count(),
                'Usuarios' => User::count(),
            ],
            'sincronizaciones' => Sincronizacion::latest('id')->take(15)->get(),
        ]);
    }

    /** Registro de seguridad (solo administrador): actividad sospechosa detectada. */
    public function seguridad(Request $request): View
    {
        $filtros = $request->validate(['severidad' => ['nullable', 'in:advertencia,critico,bloqueado']]);

        return view('panel.seguridad', [
            'eventos' => RegistroSeguridad::query()
                ->when($filtros['severidad'] ?? null, fn ($q, $s) => $q->where('severidad', $s))
                ->latest('id')->paginate(25)->withQueryString(),
            'resumen' => RegistroSeguridad::query()
                ->where('created_at', '>=', now()->subDay())
                ->select('severidad', DB::raw('count(*) as total'))
                ->groupBy('severidad')->pluck('total', 'severidad'),
            'ips' => RegistroSeguridad::query()
                ->where('created_at', '>=', now()->subDay())
                ->select('ip', DB::raw('count(*) as total'))
                ->groupBy('ip')->orderByDesc('total')->limit(10)->get(),
            'filtros' => $filtros,
        ]);
    }

    public function sincronizar(SincronizadorOficial $sincronizador): RedirectResponse
    {
        $resultados = collect($sincronizador->ejecutar());
        $resumen = $resultados->map(fn ($s) => "{$s->fuente}: {$s->estado}")->implode(' · ');

        return redirect()->route('panel.inicio')->with('estado', "Lectura oficial ejecutada. {$resumen}");
    }
}
