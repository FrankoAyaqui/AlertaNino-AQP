@extends('layouts.app')
@section('titulo', 'Página no encontrada')
@section('contenido')
<section class="panel" style="max-width:640px;margin:0 auto;text-align:center">
    <h1>Página no encontrada</h1>
    <p>La página o el registro que buscas no existe.</p>
    <a class="btn" href="{{ route('inicio') }}">Ir al inicio</a>
</section>
@endsection
