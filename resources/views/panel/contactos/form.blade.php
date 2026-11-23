@extends('layouts.app')
@section('titulo', $contacto->exists ? 'Editar contacto' : 'Nuevo contacto')
@section('contenido')
<h1 class="bloque__cabeza">{{ $contacto->exists ? 'Editar contacto' : 'Nuevo contacto' }}</h1>
@include('panel.nav')
<section class="panel">
    <p class="nota" style="margin-top:0">Verifica el teléfono en la página oficial de la entidad antes de guardarlo.</p>
    <form method="POST" action="{{ $contacto->exists ? route('panel.contactos.update', $contacto) : route('panel.contactos.store') }}" class="formulario" novalidate>
        @csrf
        @if($contacto->exists) @method('PUT') @endif
        <div class="dos-columnas">
            @include('parciales.campo', ['nombre' => 'nombre', 'etiqueta' => 'Entidad', 'valor' => $contacto->nombre, 'requerido' => true, 'max' => 120])
            @include('parciales.campo', ['nombre' => 'telefono', 'etiqueta' => 'Teléfono', 'valor' => $contacto->telefono, 'requerido' => true, 'max' => 20])
            @include('parciales.selector', ['nombre' => 'tipo', 'etiqueta' => 'Tipo', 'valor' => $contacto->tipo, 'requerido' => true, 'opciones' => config('alerta.tipos_contacto')])
            @include('parciales.selector', ['nombre' => 'distrito_id', 'etiqueta' => 'Distrito', 'valor' => $contacto->distrito_id,
                'vacio' => 'Nacional / toda la provincia', 'opciones' => $distritos->pluck('nombre', 'id')->all()])
        </div>
        @include('parciales.campo', ['nombre' => 'descripcion', 'etiqueta' => 'Descripción (opcional)', 'valor' => $contacto->descripcion, 'max' => 255])
        @include('parciales.campo', ['nombre' => 'fuente_url', 'etiqueta' => 'Enlace a la fuente (opcional)', 'tipo' => 'url', 'valor' => $contacto->fuente_url])
        <div class="fila-botones">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn--sec" href="{{ route('panel.contactos.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
