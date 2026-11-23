<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Distrito;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Consulta pública de avisos oficiales con filtros. */
class AvisoPublicoController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'distrito' => ['nullable', 'string', 'max:100'],
            'fuente' => ['nullable', 'string', 'max:60'],
            'q' => ['nullable', 'string', 'max:100'],
            'ver' => ['nullable', 'in:vigentes,todos'],
        ]);

        $distrito = isset($filtros['distrito']) ? Distrito::where('slug', $filtros['distrito'])->first() : null;
        $verTodos = ($filtros['ver'] ?? 'vigentes') === 'todos';

        $avisos = Aviso::query()
            ->with('distrito')
            ->where('estado', 'publicado')
            ->when(! $verTodos, fn ($q) => $q->vigentes())
            ->when($distrito, fn ($q) => $q->paraDistrito($distrito->id))
            ->when($filtros['fuente'] ?? null, fn ($q, $f) => $q->where('fuente', $f))
            // Búsqueda con parámetros enlazados (Eloquent escapa el valor: sin inyección SQL).
            ->when($filtros['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w
                ->where('titulo', 'like', '%'.addcslashes($t, '%_\\').'%')
                ->orWhere('mensaje', 'like', '%'.addcslashes($t, '%_\\').'%')))
            ->orderByDesc('emitido_en')
            ->paginate(12)
            ->withQueryString();

        return view('avisos.index', [
            'avisos' => $avisos,
            'distritos' => Distrito::orderBy('nombre')->get(),
            'filtros' => $filtros,
            'verTodos' => $verTodos,
        ]);
    }

    public function show(Aviso $aviso): View
    {
        // Los borradores no son públicos.
        abort_unless($aviso->estado !== 'borrador', 404);

        return view('avisos.show', ['aviso' => $aviso->load('distrito')]);
    }
}
