@extends('layouts.app')
@section('titulo', 'Gestionar contactos')
@section('contenido')
<div class="bloque__cabeza">
    <h1>Contactos</h1>
    <a class="btn" href="{{ route('panel.contactos.create') }}">Nuevo contacto</a>
</div>
@include('panel.nav')
<form method="GET" class="filtros" role="search">
    <div class="campo"><label for="q">Buscar por nombre</label><input type="search" id="q" name="q" value="{{ $q }}" maxlength="100"></div>
    <button class="btn" type="submit">Buscar</button>
</form>
<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Nombre</th><th>Teléfono</th><th>Tipo</th><th>Distrito</th><th class="acciones">Acciones</th></tr></thead>
        <tbody>
        @forelse($contactos as $c)
            <tr>
                <td data-label="Nombre"><a href="{{ route('panel.contactos.show', $c) }}">{{ $c->nombre }}</a></td>
                <td data-label="Teléfono">{{ $c->telefono }}</td>
                <td data-label="Tipo">{{ $c->nombreTipo() }}</td>
                <td data-label="Distrito">{{ $c->distrito?->nombre ?? 'Nacional' }}</td>
                <td class="acciones">
                    <a class="btn btn--sec btn--chico" href="{{ route('panel.contactos.edit', $c) }}">Editar</a>
                    @include('parciales.eliminar', ['accion' => route('panel.contactos.destroy', $c)])
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No hay contactos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $contactos->links() }}
@endsection
