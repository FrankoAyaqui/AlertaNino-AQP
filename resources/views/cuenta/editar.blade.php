@extends('layouts.app')
@section('titulo', 'Mi cuenta')
@section('contenido')
<section class="panel" style="max-width:640px">
    <h1>Mi cuenta</h1>
    <p class="nota">{{ $usuario->email }} · rol: <strong>{{ $usuario->rol }}</strong></p>
    <form method="POST" action="{{ route('cuenta.actualizar') }}" class="formulario">
        @csrf
        @method('PUT')
        @include('parciales.campo', ['nombre' => 'name', 'etiqueta' => 'Nombre', 'valor' => $usuario->name, 'requerido' => true, 'max' => 120])
        @include('parciales.selector', ['nombre' => 'distrito_id', 'etiqueta' => 'Mi distrito', 'vacio' => 'Sin distrito',
            'opciones' => $distritos->pluck('nombre', 'id')->all(), 'valor' => $usuario->distrito_id,
            'ayuda' => 'Recibirás en tu bandeja los avisos oficiales de este distrito y los de toda la provincia.'])
        <label class="casilla"><input type="checkbox" name="recibir_alertas" value="1" @checked(old('recibir_alertas', $usuario->recibir_alertas))>
            Recibir avisos en mi bandeja</label>
        <button type="submit" class="btn">Guardar</button>
    </form>
</section>
@endsection
