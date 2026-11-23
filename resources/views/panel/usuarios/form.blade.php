@extends('layouts.app')
@section('titulo', $usuario->exists ? 'Editar cuenta' : 'Nueva cuenta')
@section('contenido')
<h1 class="bloque__cabeza">{{ $usuario->exists ? 'Editar cuenta' : 'Nueva cuenta' }}</h1>
@include('panel.nav')
<section class="panel">
    <form method="POST" action="{{ $usuario->exists ? route('panel.usuarios.update', $usuario) : route('panel.usuarios.store') }}" class="formulario" novalidate>
        @csrf
        @if($usuario->exists) @method('PUT') @endif
        <div class="dos-columnas">
            @include('parciales.campo', ['nombre' => 'name', 'etiqueta' => 'Nombre', 'valor' => $usuario->name, 'requerido' => true, 'max' => 120])
            @include('parciales.campo', ['nombre' => 'email', 'etiqueta' => 'Correo', 'tipo' => 'email', 'valor' => $usuario->email, 'requerido' => true])
            @include('parciales.selector', ['nombre' => 'rol', 'etiqueta' => 'Rol', 'valor' => $usuario->rol ?? 'ciudadano', 'requerido' => true,
                'opciones' => ['ciudadano' => 'Ciudadano', 'operador' => 'Operador (gestiona avisos, guías y contactos)', 'administrador' => 'Administrador (además distritos y usuarios)']])
            @include('parciales.selector', ['nombre' => 'distrito_id', 'etiqueta' => 'Distrito', 'valor' => $usuario->distrito_id,
                'vacio' => 'Sin distrito', 'opciones' => $distritos->pluck('nombre', 'id')->all()])
            @include('parciales.campo', ['nombre' => 'password', 'etiqueta' => $usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña', 'tipo' => 'password',
                'valor' => '', 'requerido' => ! $usuario->exists, 'ayuda' => 'Mínimo 12 caracteres, con mayúscula, minúscula y número.'])
            @include('parciales.campo', ['nombre' => 'password_confirmation', 'etiqueta' => 'Repetir contraseña', 'tipo' => 'password', 'valor' => ''])
        </div>
        <label class="casilla"><input type="checkbox" name="recibir_alertas" value="1" @checked(old('recibir_alertas', $usuario->recibir_alertas))> Recibe avisos en su bandeja</label>
        <div class="fila-botones">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn--sec" href="{{ route('panel.usuarios.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
