@extends('layouts.app')
@section('titulo', 'Gestionar distritos')
@section('contenido')
<div class="bloque__cabeza">
    <h1>Distritos</h1>
    <a class="btn" href="{{ route('panel.distritos.create') }}">Nuevo distrito</a>
</div>
@include('panel.nav')
<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Nombre</th><th>Punto de referencia</th><th>Nivel SENAMHI</th><th class="num">Avisos</th><th class="num">Usuarios</th><th class="acciones">Acciones</th></tr></thead>
        <tbody>
        @forelse($distritos as $d)
            <tr>
                <td data-label="Nombre"><a href="{{ route('panel.distritos.show', $d) }}">{{ $d->nombre }}</a>
                    @if($d->es_piloto) <span class="etiqueta">piloto</span>@endif @if($d->limita_rio) <span class="etiqueta">río Chili</span>@endif</td>
                <td data-label="Referencia">{{ number_format($d->latitud, 4) }}, {{ number_format($d->longitud, 4) }}</td>
                <td data-label="SENAMHI">@include('parciales.nivel', ['nivel' => $d->ultimaLectura?->nivel ?? 0, 'chica' => true])</td>
                <td data-label="Avisos" class="num">{{ $d->avisos_count }}</td>
                <td data-label="Usuarios" class="num">{{ $d->usuarios_count }}</td>
                <td class="acciones">
                    <a class="btn btn--sec btn--chico" href="{{ route('panel.distritos.edit', $d) }}">Editar</a>
                    @include('parciales.eliminar', ['accion' => route('panel.distritos.destroy', $d)])
                </td>
            </tr>
        @empty
            <tr><td colspan="6">No hay distritos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $distritos->links() }}
@endsection
