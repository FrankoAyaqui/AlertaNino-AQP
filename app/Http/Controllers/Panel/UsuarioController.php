<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Models\Distrito;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CRUD de cuentas y roles (solo administrador). */
class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'rol' => ['nullable', 'in:'.implode(',', User::ROLES)],
        ]);
        $usuarios = User::with('distrito')
            ->when($filtros['q'] ?? null, fn ($b, $t) => $b->where(fn ($w) => $w
                ->where('name', 'like', '%'.addcslashes($t, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($t, '%_\\').'%')))
            ->when($filtros['rol'] ?? null, fn ($b, $r) => $b->where('rol', $r))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('panel.usuarios.index', compact('usuarios', 'filtros'));
    }

    public function create(): View
    {
        return view('panel.usuarios.form', ['usuario' => new User(['recibir_alertas' => true]), 'distritos' => Distrito::orderBy('nombre')->get()]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $usuario = new User($datos);
        $usuario->rol = $datos['rol'];
        $usuario->save();

        return redirect()->route('panel.usuarios.show', $usuario)->with('estado', 'Cuenta creada.');
    }

    public function show(User $usuario): View
    {
        return view('panel.usuarios.show', ['usuario' => $usuario->load('distrito')->loadCount('avisos')]);
    }

    public function edit(User $usuario): View
    {
        return view('panel.usuarios.form', ['usuario' => $usuario, 'distritos' => Distrito::orderBy('nombre')->get()]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();

        // Un administrador no puede quitarse su propio rol (evita quedarse sin administradores).
        if ($usuario->is($request->user()) && $datos['rol'] !== 'administrador') {
            return back()->withInput()->with('error', 'No puedes quitarte tu propio rol de administrador.');
        }

        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        $usuario->fill($datos);
        $usuario->rol = $datos['rol'];
        $usuario->save();

        return redirect()->route('panel.usuarios.show', $usuario)->with('estado', 'Cuenta actualizada.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta desde el panel.');
        }
        $usuario->delete();

        return redirect()->route('panel.usuarios.index')->with('estado', 'Cuenta eliminada.');
    }
}
