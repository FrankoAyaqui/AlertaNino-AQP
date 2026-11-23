@extends('layouts.app')
@section('titulo', $aviso->exists ? 'Editar aviso' : 'Registrar aviso')
@section('contenido')
<h1 class="bloque__cabeza">{{ $aviso->exists ? 'Editar aviso' : 'Registrar aviso' }}</h1>
@include('panel.nav')
<section class="panel">
    <p class="nota" style="margin-top:0">Copia el aviso tal como lo publica la entidad oficial y enlaza el documento original. No redactes avisos propios.</p>
    <form method="POST" action="{{ $aviso->exists ? route('panel.avisos.update', $aviso) : route('panel.avisos.store') }}" class="formulario" novalidate>
        @csrf
        @if($aviso->exists) @method('PUT') @endif
        @include('parciales.campo', ['nombre' => 'titulo', 'etiqueta' => 'Título', 'valor' => $aviso->titulo, 'requerido' => true, 'max' => 160])
        <div class="dos-columnas">
            @include('parciales.selector', ['nombre' => 'fuente', 'etiqueta' => 'Entidad emisora', 'valor' => $aviso->fuente, 'requerido' => true,
                'opciones' => array_combine(config('alerta.fuentes'), config('alerta.fuentes'))])
            @include('parciales.selector', ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'valor' => $aviso->tipo, 'requerido' => true,
                'opciones' => config('alerta.tipos_aviso')])
            @include('parciales.selector', ['nombre' => 'distrito_id', 'etiqueta' => 'Distrito', 'valor' => $aviso->distrito_id,
                'vacio' => 'Toda la provincia de Arequipa', 'opciones' => $distritos->pluck('nombre', 'id')->all()])
            @include('parciales.selector', ['nombre' => 'nivel', 'etiqueta' => 'Nivel (según la fuente)', 'valor' => $aviso->nivel, 'requerido' => true,
                'opciones' => [1 => 'Nivel 1', 2 => 'Nivel 2 (amarillo)', 3 => 'Nivel 3 (naranja)', 4 => 'Nivel 4 (rojo)']])
        </div>
        @include('parciales.campo', ['nombre' => 'mensaje', 'etiqueta' => 'Mensaje oficial', 'tipo' => 'textarea', 'valor' => $aviso->mensaje, 'requerido' => true, 'max' => 3000])
        @include('parciales.campo', ['nombre' => 'recomendacion', 'etiqueta' => 'Recomendación oficial (opcional)', 'tipo' => 'textarea', 'valor' => $aviso->recomendacion, 'max' => 1000])
        @include('parciales.campo', ['nombre' => 'fuente_url', 'etiqueta' => 'Enlace al documento oficial', 'tipo' => 'url', 'valor' => $aviso->fuente_url,
            'requerido' => true, 'ayuda' => 'Debe empezar con https://'])
        <div class="dos-columnas">
            @include('parciales.campo', ['nombre' => 'emitido_en', 'etiqueta' => 'Emitido', 'tipo' => 'datetime-local',
                'valor' => $aviso->emitido_en?->format('Y-m-d\TH:i'), 'requerido' => true])
            @include('parciales.campo', ['nombre' => 'vence_en', 'etiqueta' => 'Vence', 'tipo' => 'datetime-local',
                'valor' => $aviso->vence_en?->format('Y-m-d\TH:i'), 'requerido' => true])
        </div>
        @include('parciales.selector', ['nombre' => 'estado', 'etiqueta' => 'Estado', 'valor' => $aviso->estado, 'requerido' => true,
            'opciones' => ['borrador' => 'Borrador (no visible)', 'publicado' => 'Publicado (visible y notifica)', 'archivado' => 'Archivado'],
            'ayuda' => 'Al publicarlo se notifica una sola vez a los ciudadanos suscritos al distrito.'])
        <div class="fila-botones">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn--sec" href="{{ route('panel.avisos.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
