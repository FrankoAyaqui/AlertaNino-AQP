<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\DistritoRequest;
use App\Models\Distrito;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** CRUD de distritos (solo administrador). */
class DistritoController extends Controller
{
    public function index(): View
    {
        $distritos = Distrito::withCount(['avisos', 'usuarios'])->with('ultimaLectura')->orderBy('nombre')->paginate(20);

        return view('panel.distritos.index', compact('distritos'));
    }

    public function create(): View
    {
        return view('panel.distritos.form', ['distrito' => new Distrito(['provincia' => 'Arequipa'])]);
    }

    public function store(DistritoRequest $request): RedirectResponse
    {
        $distrito = Distrito::create($request->validated());

        return redirect()->route('panel.distritos.show', $distrito)->with('estado', 'Distrito creado.');
    }

    public function show(Distrito $distrito): View
    {
        $distrito->loadCount(['avisos', 'usuarios', 'contactos']);
        $lecturas = $distrito->lecturas()->orderByDesc('fecha')->take(10)->get();

        return view('panel.distritos.show', compact('distrito', 'lecturas'));
    }

    public function edit(Distrito $distrito): View
    {
        return view('panel.distritos.form', compact('distrito'));
    }

    public function update(DistritoRequest $request, Distrito $distrito): RedirectResponse
    {
        $distrito->update($request->validated());

        return redirect()->route('panel.distritos.show', $distrito)->with('estado', 'Distrito actualizado.');
    }

    public function destroy(Distrito $distrito): RedirectResponse
    {
        // Un distrito con avisos no se borra: se perdería el contexto de esos avisos.
        if ($distrito->avisos()->exists()) {
            return back()->with('error', 'No se puede eliminar: el distrito tiene avisos registrados.');
        }
        $distrito->delete();

        return redirect()->route('panel.distritos.index')->with('estado', 'Distrito eliminado.');
    }
}
