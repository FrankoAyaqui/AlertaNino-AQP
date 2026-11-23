<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistroRequest;
use App\Models\Distrito;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Registro público de ciudadanos. */
class RegistroController extends Controller
{
    public function create(): View
    {
        return view('auth.registro', ['distritos' => Distrito::orderBy('nombre')->get()]);
    }

    public function store(RegistroRequest $request): RedirectResponse
    {
        // Solo se toman los campos validados; el rol queda en "ciudadano" por defecto.
        $usuario = User::create($request->safe()->only(['name', 'email', 'password', 'distrito_id']));

        Auth::login($usuario);
        $request->session()->regenerate();

        return redirect()->route('inicio')->with('estado', 'Cuenta creada. Recibirás en tu bandeja los avisos oficiales de tu distrito.');
    }
}
