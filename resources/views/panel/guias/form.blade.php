@extends('layouts.app')
@section('titulo', $guia->exists ? 'Editar guía' : 'Nueva guía')
@section('contenido')
<h1 class="bloque__cabeza">{{ $guia->exists ? 'Editar guía' : 'Nueva guía' }}</h1>
@include('panel.nav')
<section class="panel">
    <form method="POST" action="{{ $guia->exists ? route('panel.guias.update', $guia) : route('panel.guias.store') }}" class="formulario" novalidate>
        @csrf
        @if($guia->exists) @method('PUT') @endif
        @include('parciales.campo', ['nombre' => 'titulo', 'etiqueta' => 'Título', 'valor' => $guia->titulo, 'requerido' => true, 'max' => 160])
        <div class="dos-columnas">
            @include('parciales.selector', ['nombre' => 'categoria', 'etiqueta' => 'Categoría', 'valor' => $guia->categoria, 'requerido' => true,
                'opciones' => config('alerta.categorias_guia')])
            @include('parciales.campo', ['nombre' => 'orden', 'etiqueta' => 'Orden', 'tipo' => 'number', 'valor' => $guia->orden, 'requerido' => true])
        </div>
        @include('parciales.campo', ['nombre' => 'contenido', 'etiqueta' => 'Contenido', 'tipo' => 'textarea', 'valor' => $guia->contenido, 'requerido' => true, 'max' => 5000,
            'ayuda' => 'Se muestra como texto; no admite HTML.'])
        @include('parciales.campo', ['nombre' => 'fuente_url', 'etiqueta' => 'Enlace a la fuente (opcional)', 'tipo' => 'url', 'valor' => $guia->fuente_url])
        <label class="casilla"><input type="checkbox" name="publicado" value="1" @checked(old('publicado', $guia->publicado))> Publicada</label>
        <div class="fila-botones">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn--sec" href="{{ route('panel.guias.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
