<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactoRequest;
use App\Models\Contacto;
use App\Models\Distrito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CRUD de contactos de emergencia (operador y administrador). */
class ContactoController extends Controller
{
    public function index(Request $request): View
    {
        $q = $request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? null;
        $contactos = Contacto::with('distrito')
            ->when($q, fn ($b) => $b->where('nombre', 'like', '%'.addcslashes($q, '%_\\').'%'))
            ->orderBy('tipo')->orderBy('nombre')->paginate(15)->withQueryString();

        return view('panel.contactos.index', compact('contactos', 'q'));
    }

    public function create(): View
    {
        return view('panel.contactos.form', ['contacto' => new Contacto, 'distritos' => Distrito::orderBy('nombre')->get()]);
    }

    public function store(ContactoRequest $request): RedirectResponse
    {
        $contacto = Contacto::create($request->validated());

        return redirect()->route('panel.contactos.show', $contacto)->with('estado', 'Contacto creado.');
    }

    public function show(Contacto $contacto): View
    {
        return view('panel.contactos.show', ['contacto' => $contacto->load('distrito')]);
    }

    public function edit(Contacto $contacto): View
    {
        return view('panel.contactos.form', ['contacto' => $contacto, 'distritos' => Distrito::orderBy('nombre')->get()]);
    }

    public function update(ContactoRequest $request, Contacto $contacto): RedirectResponse
    {
        $contacto->update($request->validated());

        return redirect()->route('panel.contactos.show', $contacto)->with('estado', 'Contacto actualizado.');
    }

    public function destroy(Contacto $contacto): RedirectResponse
    {
        $contacto->delete();

        return redirect()->route('panel.contactos.index')->with('estado', 'Contacto eliminado.');
    }
}
