@extends('layouts.app')
@section('titulo', 'Contactos de emergencia')
@section('contenido')
<h1 class="bloque__cabeza">Contactos de emergencia</h1>
<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Entidad</th><th>Teléfono</th><th>Tipo</th><th>Ámbito</th><th>Descripción</th></tr></thead>
        <tbody>
        @forelse($contactos as $c)
            <tr>
                <td data-label="Entidad">{{ $c->nombre }}</td>
                <td data-label="Teléfono"><a href="tel:{{ preg_replace('/[^0-9+]/', '', $c->telefono) }}"><strong>{{ $c->telefono }}</strong></a></td>
                <td data-label="Tipo">{{ $c->nombreTipo() }}</td>
                <td data-label="Ámbito">{{ $c->distrito?->nombre ?? 'Nacional' }}</td>
                <td data-label="Descripción">{{ $c->descripcion }}
                    @if($c->fuente_url) <a href="{{ $c->fuente_url }}" target="_blank" rel="noopener">Fuente</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5">Sin contactos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
