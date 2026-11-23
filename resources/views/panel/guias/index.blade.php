@extends('layouts.app')
@section('titulo', 'Gestionar guías')
@section('contenido')
<div class="bloque__cabeza">
    <h1>Guías</h1>
    <a class="btn" href="{{ route('panel.guias.create') }}">Nueva guía</a>
</div>
@include('panel.nav')
<form method="GET" class="filtros" role="search">
    <div class="campo"><label for="q">Buscar por título</label><input type="search" id="q" name="q" value="{{ $q }}" maxlength="100"></div>
    <button class="btn" type="submit">Buscar</button>
</form>
<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Título</th><th>Categoría</th><th class="num">Orden</th><th>Publicada</th><th class="acciones">Acciones</th></tr></thead>
        <tbody>
        @forelse($guias as $g)
            <tr>
                <td data-label="Título"><a href="{{ route('panel.guias.show', $g) }}">{{ $g->titulo }}</a></td>
                <td data-label="Categoría">{{ $g->nombreCategoria() }}</td>
                <td data-label="Orden" class="num">{{ $g->orden }}</td>
                <td data-label="Publicada">{{ $g->publicado ? 'Sí' : 'No' }}</td>
                <td class="acciones">
                    <a class="btn btn--sec btn--chico" href="{{ route('panel.guias.edit', $g) }}">Editar</a>
                    @include('parciales.eliminar', ['accion' => route('panel.guias.destroy', $g)])
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No hay guías.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $guias->links() }}
@endsection
