<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Inicio y cierre de sesión. */
class SesionController extends Controller
{
    public function create(): View
    {
        return view('auth.ingresar');
    }

    public function store(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt compara contra el hash bcrypt guardado; nunca contra texto plano.
        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        // Nuevo identificador de sesión: evita la fijación de sesión.
        $request->session()->regenerate();

        return redirect()->intended(route('inicio'))->with('estado', 'Sesión iniciada.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio')->with('estado', 'Sesión cerrada.');
    }
}
