@extends('layouts.app')
@section('titulo', 'Crear cuenta')
@section('contenido')
<section class="panel" style="max-width:620px;margin:0 auto">
    <h1>Crear cuenta</h1>
    <p class="nota">Con una cuenta recibirás en tu bandeja los avisos oficiales de tu distrito.</p>
    <form method="POST" action="{{ route('registro') }}" class="formulario" novalidate>
        @csrf
        @include('parciales.campo', ['nombre' => 'name', 'etiqueta' => 'Nombre', 'requerido' => true, 'max' => 120])
        @include('parciales.campo', ['nombre' => 'email', 'etiqueta' => 'Correo electrónico', 'tipo' => 'email', 'requerido' => true])
        @include('parciales.selector', ['nombre' => 'distrito_id', 'etiqueta' => 'Mi distrito', 'vacio' => 'Elegir después',
            'opciones' => $distritos->pluck('nombre', 'id')->all()])
        <div class="dos-columnas">
            @include('parciales.campo', ['nombre' => 'password', 'etiqueta' => 'Contraseña', 'tipo' => 'password', 'requerido' => true, 'valor' => '',
                'ayuda' => 'Mínimo 12 caracteres, con mayúscula, minúscula y número.'])
            @include('parciales.campo', ['nombre' => 'password_confirmation', 'etiqueta' => 'Repetir contraseña', 'tipo' => 'password', 'requerido' => true, 'valor' => ''])
        </div>
        <div class="campo">
            <label class="casilla"><input type="checkbox" name="acepta_privacidad" value="1" @checked(old('acepta_privacidad'))>
                Acepto la <a href="{{ route('privacidad') }}" target="_blank">política de privacidad</a></label>
            @error('acepta_privacidad')<span class="error">{{ $message }}</span>@enderror
        </div>
        <button type="submit" class="btn">Crear cuenta</button>
    </form>
</section>
@endsection
