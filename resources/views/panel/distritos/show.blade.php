@extends('layouts.app')
@section('titulo', $distrito->nombre)
@section('contenido')
<h1 class="bloque__cabeza">Distrito</h1>
@include('panel.nav')
<article class="panel detalle">
    <h2>{{ $distrito->nombre }}</h2>
    <dl>
        <dt>Provincia</dt><dd>{{ $distrito->provincia }}</dd>
        <dt>Punto de referencia</dt><dd>{{ $distrito->latitud }}, {{ $distrito->longitud }}</dd>
        <dt>Limita con el río Chili</dt><dd>{{ $distrito->limita_rio ? 'Sí' : 'No' }}</dd>
        <dt>Piloto</dt><dd>{{ $distrito->es_piloto ? 'Sí' : 'No' }}</dd>
        <dt>Avisos / usuarios / contactos</dt><dd>{{ $distrito->avisos_count }} / {{ $distrito->usuarios_count }} / {{ $distrito->contactos_count }}</dd>
    </dl>
    <h3 style="margin-top:16px">Últimas lecturas de SENAMHI</h3>
    <div class="tabla-envoltura">
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Nivel</th><th>Consultado</th><th>Origen</th></tr></thead>
            <tbody>
            @forelse($lecturas as $l)
                <tr><td>{{ $l->fecha->format('d/m/Y') }}</td><td>@include('parciales.nivel', ['nivel' => $l->nivel, 'chica' => true])</td>
                    <td>{{ $l->consultado_en->format('d/m/Y H:i') }}</td><td>{{ $l->origen }}</td></tr>
            @empty
                <tr><td colspan="4">Sin lecturas.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="fila-botones" style="margin-top:12px">
        <a class="btn btn--sec" href="{{ route('panel.distritos.index') }}">Volver</a>
        <a class="btn" href="{{ route('panel.distritos.edit', $distrito) }}">Editar</a>
        @include('parciales.eliminar', ['accion' => route('panel.distritos.destroy', $distrito)])
    </div>
</article>
@endsection
