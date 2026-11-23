@extends('layouts.app')
@section('titulo', $contacto->nombre)
@section('contenido')
<h1 class="bloque__cabeza">Contacto</h1>
@include('panel.nav')
<article class="panel detalle">
    <h2>{{ $contacto->nombre }}</h2>
    <dl>
        <dt>Teléfono</dt><dd><strong>{{ $contacto->telefono }}</strong></dd>
        <dt>Tipo</dt><dd>{{ $contacto->nombreTipo() }}</dd>
        <dt>Distrito</dt><dd>{{ $contacto->distrito?->nombre ?? 'Nacional' }}</dd>
        <dt>Descripción</dt><dd>{{ $contacto->descripcion ?? '—' }}</dd>
        <dt>Fuente</dt><dd>@if($contacto->fuente_url)<a href="{{ $contacto->fuente_url }}" target="_blank" rel="noopener">{{ $contacto->fuente_url }}</a>@else — @endif</dd>
    </dl>
    <div class="fila-botones" style="margin-top:12px">
        <a class="btn btn--sec" href="{{ route('panel.contactos.index') }}">Volver</a>
        <a class="btn" href="{{ route('panel.contactos.edit', $contacto) }}">Editar</a>
        @include('parciales.eliminar', ['accion' => route('panel.contactos.destroy', $contacto)])
    </div>
</article>
@endsection
