@extends('layouts.app')
@section('titulo', 'Demasiados intentos')
@section('contenido')
<section class="panel" style="max-width:640px;margin:0 auto;text-align:center">
    <h1>Demasiados intentos</h1>
    <p>Espera un minuto antes de volver a intentarlo.</p>
    <a class="btn" href="{{ route('inicio') }}">Ir al inicio</a>
</section>
@endsection
