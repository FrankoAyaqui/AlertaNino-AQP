@extends('layouts.app')
@section('titulo', 'Gestionar avisos')
@section('contenido')
<div class="bloque__cabeza">
    <h1>Avisos</h1>
    <a class="btn" href="{{ route('panel.avisos.create') }}">Registrar aviso</a>
</div>
@include('panel.nav')

<form method="GET" class="filtros" role="search">
    <div class="campo"><label for="q">Buscar por título</label>
        <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" maxlength="100"></div>
    <div class="campo"><label for="estado">Estado</label>
        <select id="estado" name="estado"><option value="">Todos</option>
            @foreach(\App\Models\Aviso::ESTADOS as $e)<option value="{{ $e }}" @selected(($filtros['estado'] ?? '') === $e)>{{ ucfirst($e) }}</option>@endforeach
        </select></div>
    <div class="campo"><label for="origen">Origen</label>
        <select id="origen" name="origen"><option value="">Todos</option>
            <option value="manual" @selected(($filtros['origen'] ?? '') === 'manual')>Manual</option>
            <option value="automatico" @selected(($filtros['origen'] ?? '') === 'automatico')>Automático (SENAMHI)</option>
        </select></div>
    <button class="btn" type="submit">Filtrar</button>
</form>

<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Título</th><th>Nivel</th><th>Distrito</th><th>Estado</th><th>Vence</th><th>Origen</th><th class="acciones">Acciones</th></tr></thead>
        <tbody>
        @forelse($avisos as $a)
            <tr>
                <td data-label="Título"><a href="{{ route('panel.avisos.show', $a) }}">{{ $a->titulo }}</a></td>
                <td data-label="Nivel">@include('parciales.nivel', ['nivel' => $a->nivel, 'chica' => true])</td>
                <td data-label="Distrito">{{ $a->distrito?->nombre ?? 'Provincia' }}</td>
                <td data-label="Estado">{{ $a->estado }} @if($a->estado === 'publicado' && ! $a->estaVigente()) <span class="etiqueta">vencido</span>@endif</td>
                <td data-label="Vence">{{ $a->vence_en->format('d/m/Y H:i') }}</td>
                <td data-label="Origen">{{ $a->origen }}</td>
                <td class="acciones">
                    <a class="btn btn--sec btn--chico" href="{{ route('panel.avisos.show', $a) }}">Ver</a>
                    @if($a->origen === 'manual')
                        <a class="btn btn--sec btn--chico" href="{{ route('panel.avisos.edit', $a) }}">Editar</a>
                        @include('parciales.eliminar', ['accion' => route('panel.avisos.destroy', $a)])
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7">No hay avisos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $avisos->links() }}
@endsection
