<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvisoRequest;
use App\Models\Aviso;
use App\Models\Distrito;
use App\Services\PublicadorAvisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD de avisos (operador y administrador).
 *
 * Los avisos automáticos de SENAMHI se pueden consultar pero no editar ni
 * borrar: su texto es oficial y se actualiza solo en cada lectura.
 */
class AvisoController extends Controller
{
    public function __construct(private PublicadorAvisos $publicador) {}

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:borrador,publicado,archivado'],
            'origen' => ['nullable', 'in:manual,automatico'],
        ]);

        $avisos = Aviso::with(['distrito', 'autor'])
            ->when($filtros['q'] ?? null, fn ($q, $t) => $q->where('titulo', 'like', '%'.addcslashes($t, '%_\\').'%'))
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
            ->when($filtros['origen'] ?? null, fn ($q, $o) => $q->where('origen', $o))
            ->latest('emitido_en')
            ->paginate(15)
            ->withQueryString();

        return view('panel.avisos.index', compact('avisos', 'filtros'));
    }

    public function create(): View
    {
        return view('panel.avisos.form', [
            'aviso' => new Aviso(['estado' => 'borrador', 'nivel' => 2, 'emitido_en' => now(), 'vence_en' => now()->addDay()]),
            'distritos' => Distrito::orderBy('nombre')->get(),
        ]);
    }

    public function store(AvisoRequest $request): RedirectResponse
    {
        $aviso = new Aviso($request->validated());
        $aviso->user_id = $request->user()->id;
        $aviso->save();

        $enviados = $this->publicador->notificarSiCorresponde($aviso);

        return redirect()->route('panel.avisos.show', $aviso)
            ->with('estado', 'Aviso registrado.'.($enviados ? " Se notificó a {$enviados} persona(s)." : ''));
    }

    public function show(Aviso $aviso): View
    {
        return view('panel.avisos.show', ['aviso' => $aviso->load(['distrito', 'autor'])]);
    }

    public function edit(Aviso $aviso): View
    {
        $this->soloManual($aviso);

        return view('panel.avisos.form', ['aviso' => $aviso, 'distritos' => Distrito::orderBy('nombre')->get()]);
    }

    public function update(AvisoRequest $request, Aviso $aviso): RedirectResponse
    {
        $this->soloManual($aviso);
        $aviso->update($request->validated());

        // Si recién se publica, se notifica (una sola vez gracias a notificado_en).
        $enviados = $this->publicador->notificarSiCorresponde($aviso);

        return redirect()->route('panel.avisos.show', $aviso)
            ->with('estado', 'Aviso actualizado.'.($enviados ? " Se notificó a {$enviados} persona(s)." : ''));
    }

    public function destroy(Aviso $aviso): RedirectResponse
    {
        $this->soloManual($aviso);
        $aviso->delete();

        return redirect()->route('panel.avisos.index')->with('estado', 'Aviso eliminado.');
    }

    private function soloManual(Aviso $aviso): void
    {
        abort_if($aviso->origen === 'automatico', 403, 'Los avisos automáticos de SENAMHI no se modifican a mano.');
    }
}
