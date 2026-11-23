@extends('layouts.app')
@section('titulo', $distrito->exists ? 'Editar distrito' : 'Nuevo distrito')
@section('contenido')
<h1 class="bloque__cabeza">{{ $distrito->exists ? 'Editar distrito' : 'Nuevo distrito' }}</h1>
@include('panel.nav')
<section class="panel">
    <form method="POST" action="{{ $distrito->exists ? route('panel.distritos.update', $distrito) : route('panel.distritos.store') }}" class="formulario" novalidate>
        @csrf
        @if($distrito->exists) @method('PUT') @endif
        <div class="dos-columnas">
            @include('parciales.campo', ['nombre' => 'nombre', 'etiqueta' => 'Nombre', 'valor' => $distrito->nombre, 'requerido' => true, 'max' => 100])
            @include('parciales.campo', ['nombre' => 'provincia', 'etiqueta' => 'Provincia', 'valor' => $distrito->provincia, 'requerido' => true, 'max' => 60])
            @include('parciales.campo', ['nombre' => 'latitud', 'etiqueta' => 'Latitud del punto de referencia', 'tipo' => 'number', 'valor' => $distrito->latitud,
                'requerido' => true, 'ayuda' => 'Ejemplo: -16.3989 (departamento de Arequipa)'])
            @include('parciales.campo', ['nombre' => 'longitud', 'etiqueta' => 'Longitud del punto de referencia', 'tipo' => 'number', 'valor' => $distrito->longitud,
                'requerido' => true, 'ayuda' => 'Ejemplo: -71.5369'])
        </div>
        <p class="nota">El punto se usa para saber en qué zona del aviso de SENAMHI cae el distrito. Elige un punto del área urbana principal.</p>
        <label class="casilla"><input type="checkbox" name="limita_rio" value="1" @checked(old('limita_rio', $distrito->limita_rio))> Limita con el río Chili</label>
        <label class="casilla"><input type="checkbox" name="es_piloto" value="1" @checked(old('es_piloto', $distrito->es_piloto))> Distrito del piloto</label>
        <div class="fila-botones">
            <button type="submit" class="btn">Guardar</button>
            <a class="btn btn--sec" href="{{ route('panel.distritos.index') }}">Cancelar</a>
        </div>
    </form>
</section>
@endsection
