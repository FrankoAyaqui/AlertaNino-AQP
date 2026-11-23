@extends('layouts.app')
@section('titulo', 'Gestionar usuarios')
@section('contenido')
<div class="bloque__cabeza">
    <h1>Usuarios y roles</h1>
    <a class="btn" href="{{ route('panel.usuarios.create') }}">Nueva cuenta</a>
</div>
@include('panel.nav')
<form method="GET" class="filtros" role="search">
    <div class="campo"><label for="q">Buscar por nombre o correo</label><input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" maxlength="100"></div>
    <div class="campo"><label for="rol">Rol</label>
        <select id="rol" name="rol"><option value="">Todos</option>
            @foreach(\App\Models\User::ROLES as $r)<option value="{{ $r }}" @selected(($filtros['rol'] ?? '') === $r)>{{ ucfirst($r) }}</option>@endforeach
        </select></div>
    <button class="btn" type="submit">Filtrar</button>
</form>
<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Distrito</th><th class="acciones">Acciones</th></tr></thead>
        <tbody>
        @forelse($usuarios as $u)
            <tr>
                <td data-label="Nombre"><a href="{{ route('panel.usuarios.show', $u) }}">{{ $u->name }}</a></td>
                <td data-label="Correo">{{ $u->email }}</td>
                <td data-label="Rol"><span class="etiqueta">{{ $u->rol }}</span></td>
                <td data-label="Distrito">{{ $u->distrito?->nombre ?? '—' }}</td>
                <td class="acciones">
                    <a class="btn btn--sec btn--chico" href="{{ route('panel.usuarios.edit', $u) }}">Editar</a>
                    @unless($u->is(auth()->user()))
                        @include('parciales.eliminar', ['accion' => route('panel.usuarios.destroy', $u), 'pregunta' => '¿Eliminar la cuenta de '.$u->name.'?'])
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No hay usuarios.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $usuarios->links() }}
@endsection
