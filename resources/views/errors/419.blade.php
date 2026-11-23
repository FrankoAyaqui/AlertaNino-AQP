@extends('layouts.app')
@section('titulo', 'La sesión expiró')
@section('contenido')
<section class="panel" style="max-width:640px;margin:0 auto;text-align:center">
    <h1>La sesión expiró</h1>
    <p>Recarga el formulario e inténtalo de nuevo.</p>
    <a class="btn" href="{{ route('inicio') }}">Ir al inicio</a>
</section>
@endsection
