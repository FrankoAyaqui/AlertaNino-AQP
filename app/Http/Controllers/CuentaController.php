<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreferenciasRequest;
use App\Models\Distrito;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Preferencias del usuario y bandeja de avisos recibidos. */
class CuentaController extends Controller
{
    public function edit(Request $request): View
    {
        return view('cuenta.editar', [
            'usuario' => $request->user(),
            'distritos' => Distrito::orderBy('nombre')->get(),
        ]);
    }

    public function update(PreferenciasRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('cuenta.editar')->with('estado', 'Preferencias guardadas.');
    }

    public function bandeja(Request $request): View
    {
        return view('cuenta.bandeja', [
            // Solo las notificaciones del propio usuario.
            'notificaciones' => $request->user()->notifications()->paginate(15),
        ]);
    }

    public function marcarLeida(Request $request, string $id): RedirectResponse
    {
        // La búsqueda se hace dentro de las notificaciones del usuario: una ajena da 404.
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return back()->with('estado', 'Aviso marcado como leído.');
    }

    public function marcarTodas(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('estado', 'Todos los avisos quedaron como leídos.');
    }
}
