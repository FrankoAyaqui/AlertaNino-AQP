<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuiaRequest;
use App\Models\Guia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CRUD de guías de preparación (operador y administrador). */
class GuiaController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;
        $guias = Guia::when($q, fn ($b) => $b->where('titulo', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('categoria')->orderBy('orden')->paginate(15)->withQueryString();

        return view('panel.guias.index', compact('guias', 'q'));
    }

    public function create(): View
    {
        return view('panel.guias.form', ['guia' => new Guia(['publicado' => true, 'orden' => 0])]);
    }

    public function store(GuiaRequest $request): RedirectResponse
    {
        $guia = Guia::create($request->validated());

        return redirect()->route('panel.guias.show', $guia)->with('estado', 'Guía creada.');
    }

    public function show(Guia $guia): View
    {
        return view('panel.guias.show', compact('guia'));
    }

    public function edit(Guia $guia): View
    {
        return view('panel.guias.form', compact('guia'));
    }

    public function update(GuiaRequest $request, Guia $guia): RedirectResponse
    {
        $guia->update($request->validated());

        return redirect()->route('panel.guias.show', $guia)->with('estado', 'Guía actualizada.');
    }

    public function destroy(Guia $guia): RedirectResponse
    {
        $guia->delete();

        return redirect()->route('panel.guias.index')->with('estado', 'Guía eliminada.');
    }
}
