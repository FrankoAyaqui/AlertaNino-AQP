@extends('layouts.app')
@section('titulo', $aviso->titulo)
@section('contenido')
<p><a href="{{ route('avisos.index') }}">← Volver a los avisos</a></p>
<article class="panel detalle aviso" data-nivel="n{{ $aviso->nivel }}">
    <h1>{{ $aviso->titulo }}</h1>
    <p>@include('parciales.nivel', ['nivel' => $aviso->nivel])
        @unless($aviso->estaVigente()) <span class="etiqueta etiqueta--error">Vencido o archivado</span> @endunless</p>
    <p class="texto-oficial">{{ $aviso->mensaje }}</p>
    @if($aviso->recomendacion)<p><strong>Recomendación:</strong> {{ $aviso->recomendacion }}</p>@endif
    <dl>
        <dt>Emisor</dt><dd>{{ $aviso->fuente }}</dd>
        <dt>Tipo</dt><dd>{{ $aviso->nombreTipo() }}</dd>
        <dt>Ámbito</dt><dd>{{ $aviso->distrito?->nombre ?? 'Provincia de Arequipa' }}</dd>
        <dt>Emitido</dt><dd>{{ $aviso->emitido_en->format('d/m/Y H:i') }}</dd>
        <dt>Vence</dt><dd>{{ $aviso->vence_en->format('d/m/Y H:i') }}</dd>
        <dt>Origen</dt><dd>{{ $aviso->origen === 'automatico' ? 'Lectura automática del servicio oficial' : 'Registrado por un operador a partir de la fuente oficial' }}</dd>
        <dt>Documento</dt><dd><a href="{{ $aviso->fuente_url }}" target="_blank" rel="noopener">Ver fuente oficial</a></dd>
    </dl>
    <p class="nota">Ante cualquier duda, sigue las indicaciones de la entidad que emitió el aviso.</p>
</article>
@endsection
