<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Aviso;
use App\Models\Contacto;
use App\Models\Distrito;
use App\Models\Guia;
use App\Models\Sincronizacion;
use App\Models\User;
use App\Services\SincronizadorOficial;
use Illuminate\Http\RedirectResponse;
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

    public function sincronizar(SincronizadorOficial $sincronizador): RedirectResponse
    {
        $resultados = collect($sincronizador->ejecutar());
        $resumen = $resultados->map(fn ($s) => "{$s->fuente}: {$s->estado}")->implode(' · ');

        return redirect()->route('panel.inicio')->with('estado', "Lectura oficial ejecutada. {$resumen}");
    }
}
