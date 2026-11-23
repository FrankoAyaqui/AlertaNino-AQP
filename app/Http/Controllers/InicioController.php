<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\ComunicadoEnfen;
use App\Models\Distrito;
use App\Models\Emergencia;
use App\Models\Sincronizacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Página principal: situación oficial del distrito elegido, mapa y reportes. */
class InicioController extends Controller
{
    public function __invoke(Request $request): View
    {
        $distritos = Distrito::with('ultimaLectura')->orderBy('nombre')->get();

        // Distrito elegido: el de la URL, el del usuario o Arequipa (Cercado).
        $slug = $request->string('distrito')->toString();
        $distrito = $distritos->firstWhere('slug', $slug)
            ?? $distritos->firstWhere('id', $request->user()?->distrito_id)
            ?? $distritos->firstWhere('slug', 'cercado')
            ?? $distritos->first();

        return view('inicio', [
            'distritos' => $distritos,
            'distrito' => $distrito,
            'lectura' => $distrito?->ultimaLectura,
            'avisos' => $distrito
                ? Aviso::vigentes()->paraDistrito($distrito->id)->with('distrito')->orderByDesc('nivel')->latest('emitido_en')->take(6)->get()
                : collect(),
            'comunicado' => ComunicadoEnfen::ultimo(),
            'emergencias' => Emergencia::orderByDesc('fecha')->take(8)->get(),
            'ultimaLectura' => Sincronizacion::ultimaExitosa('senamhi_lluvia'),
        ]);
    }
}
