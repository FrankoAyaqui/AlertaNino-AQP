@extends('layouts.app')
@section('titulo', $aviso->titulo)
@section('contenido')
<h1 class="bloque__cabeza">Aviso</h1>
@include('panel.nav')
<article class="panel detalle">
    <h2>{{ $aviso->titulo }}</h2>
    <p>@include('parciales.nivel', ['nivel' => $aviso->nivel])</p>
    <p class="texto-oficial">{{ $aviso->mensaje }}</p>
    <dl>
        <dt>Recomendación</dt><dd>{{ $aviso->recomendacion ?? '—' }}</dd>
        <dt>Emisor</dt><dd>{{ $aviso->fuente }}</dd>
        <dt>Tipo</dt><dd>{{ $aviso->nombreTipo() }}</dd>
        <dt>Distrito</dt><dd>{{ $aviso->distrito?->nombre ?? 'Toda la provincia' }}</dd>
        <dt>Estado</dt><dd>{{ $aviso->estado }} @if($aviso->estaVigente()) <span class="etiqueta etiqueta--ok">vigente</span> @endif</dd>
        <dt>Emitido / vence</dt><dd>{{ $aviso->emitido_en->format('d/m/Y H:i') }} — {{ $aviso->vence_en->format('d/m/Y H:i') }}</dd>
        <dt>Origen</dt><dd>{{ $aviso->origen }} @if($aviso->referencia_externa) ({{ $aviso->referencia_externa }}) @endif</dd>
        <dt>Registrado por</dt><dd>{{ $aviso->autor?->name ?? 'Lectura automática' }}</dd>
        <dt>Notificado</dt><dd>{{ $aviso->notificado_en?->format('d/m/Y H:i') ?? 'No' }}</dd>
        <dt>Fuente</dt><dd><a href="{{ $aviso->fuente_url }}" target="_blank" rel="noopener">{{ $aviso->fuente_url }}</a></dd>
    </dl>
    <div class="fila-botones" style="margin-top:12px">
        <a class="btn btn--sec" href="{{ route('panel.avisos.index') }}">Volver</a>
        @if($aviso->origen === 'manual')
            <a class="btn" href="{{ route('panel.avisos.edit', $aviso) }}">Editar</a>
            @include('parciales.eliminar', ['accion' => route('panel.avisos.destroy', $aviso)])
        @else
            <span class="nota">Los avisos automáticos de SENAMHI no se editan: se actualizan en cada lectura oficial.</span>
        @endif
    </div>
</article>
@endsection
