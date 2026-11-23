<?php

namespace App\Http\Controllers;

use App\Models\Contacto;
use App\Models\Guia;
use App\Models\Sincronizacion;
use Illuminate\View\View;

/** Páginas informativas: guías, contactos, privacidad y fuentes. */
class PaginaController extends Controller
{
    public function guias(): View
    {
        $guias = Guia::where('publicado', true)->orderBy('orden')->orderBy('titulo')->get()->groupBy('categoria');

        return view('paginas.guias', compact('guias'));
    }

    public function contactos(): View
    {
        $contactos = Contacto::with('distrito')->orderBy('tipo')->orderBy('nombre')->get();

        return view('paginas.contactos', compact('contactos'));
    }

    public function privacidad(): View
    {
        return view('paginas.privacidad');
    }

    public function fuentes(): View
    {
        $fuentes = ['senamhi_lluvia', 'senamhi_avisos', 'senamhi_quebradas', 'enfen', 'sinpad'];
        $lecturas = collect($fuentes)->mapWithKeys(fn ($f) => [$f => [
            'ultima' => Sincronizacion::where('fuente', $f)->latest('id')->first(),
            'exitosa' => Sincronizacion::ultimaExitosa($f),
        ]]);

        return view('paginas.fuentes', compact('lecturas'));
    }
}
