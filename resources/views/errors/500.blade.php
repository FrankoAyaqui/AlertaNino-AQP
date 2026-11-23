@extends('layouts.app')
@section('titulo', 'Error del servidor')
@section('contenido')
<section class="panel" style="max-width:640px;margin:0 auto;text-align:center">
    <h1>Error del servidor</h1>
    <p>Ocurrió un error inesperado. Inténtalo más tarde.</p>
    <a class="btn" href="{{ route('inicio') }}">Ir al inicio</a>
</section>
@endsection
