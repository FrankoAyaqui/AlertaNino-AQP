@extends('layouts.app')
@section('titulo', $guia->titulo)
@section('contenido')
<h1 class="bloque__cabeza">Guía</h1>
@include('panel.nav')
<article class="panel detalle">
    <h2>{{ $guia->titulo }}</h2>
    <p>{{ $guia->contenido }}</p>
    <dl>
        <dt>Categoría</dt><dd>{{ $guia->nombreCategoria() }}</dd>
        <dt>Orden</dt><dd>{{ $guia->orden }}</dd>
        <dt>Publicada</dt><dd>{{ $guia->publicado ? 'Sí' : 'No' }}</dd>
        <dt>Fuente</dt><dd>@if($guia->fuente_url)<a href="{{ $guia->fuente_url }}" target="_blank" rel="noopener">{{ $guia->fuente_url }}</a>@else — @endif</dd>
        <dt>Actualizada</dt><dd>{{ $guia->updated_at->format('d/m/Y H:i') }}</dd>
    </dl>
    <div class="fila-botones" style="margin-top:12px">
        <a class="btn btn--sec" href="{{ route('panel.guias.index') }}">Volver</a>
        <a class="btn" href="{{ route('panel.guias.edit', $guia) }}">Editar</a>
        @include('parciales.eliminar', ['accion' => route('panel.guias.destroy', $guia)])
    </div>
</article>
@endsection
