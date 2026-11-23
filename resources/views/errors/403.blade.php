@extends('layouts.app')
@section('titulo', 'No tienes permiso')
@section('contenido')
<section class="panel" style="max-width:640px;margin:0 auto;text-align:center">
    <h1>No tienes permiso</h1>
    <p>Tu cuenta no tiene el rol necesario para esta sección.</p>
    <a class="btn" href="{{ route('inicio') }}">Ir al inicio</a>
</section>
@endsection
