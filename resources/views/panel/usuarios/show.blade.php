@extends('layouts.app')
@section('titulo', $usuario->name)
@section('contenido')
<h1 class="bloque__cabeza">Cuenta</h1>
@include('panel.nav')
<article class="panel detalle">
    <h2>{{ $usuario->name }}</h2>
    <dl>
        <dt>Correo</dt><dd>{{ $usuario->email }}</dd>
        <dt>Rol</dt><dd>{{ $usuario->rol }}</dd>
        <dt>Distrito</dt><dd>{{ $usuario->distrito?->nombre ?? '—' }}</dd>
        <dt>Recibe avisos</dt><dd>{{ $usuario->recibir_alertas ? 'Sí' : 'No' }}</dd>
        <dt>Avisos registrados</dt><dd>{{ $usuario->avisos_count }}</dd>
        <dt>Creada</dt><dd>{{ $usuario->created_at->format('d/m/Y H:i') }}</dd>
    </dl>
    <p class="nota">La contraseña se guarda solo como hash bcrypt y no se puede ver.</p>
    <div class="fila-botones" style="margin-top:12px">
        <a class="btn btn--sec" href="{{ route('panel.usuarios.index') }}">Volver</a>
        <a class="btn" href="{{ route('panel.usuarios.edit', $usuario) }}">Editar</a>
        @unless($usuario->is(auth()->user()))
            @include('parciales.eliminar', ['accion' => route('panel.usuarios.destroy', $usuario)])
        @endunless
    </div>
</article>
@endsection
