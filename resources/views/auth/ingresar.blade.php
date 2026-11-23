@extends('layouts.app')
@section('titulo', 'Ingresar')
@section('contenido')
<section class="panel" style="max-width:520px;margin:0 auto">
    <h1>Ingresar</h1>
    <form method="POST" action="{{ route('ingresar') }}" class="formulario" novalidate>
        @csrf
        @include('parciales.campo', ['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true])
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <label class="casilla"><input type="checkbox" name="recordar" value="1"> Recordarme en este dispositivo</label>
        <button type="submit" class="btn">Ingresar</button>
        <p class="nota">¿No tienes cuenta? <a href="{{ route('registro') }}">Crea una</a> para recibir los avisos de tu distrito.</p>
    </form>
</section>
@endsection
