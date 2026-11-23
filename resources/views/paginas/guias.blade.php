@extends('layouts.app')
@section('titulo', 'Cómo prepararte')
@section('contenido')
<h1 class="bloque__cabeza">Cómo prepararte</h1>
@if($guias->isEmpty())
    <p class="vacio">Sin guías publicadas.</p>
@else
<div class="rejilla-2">
    @foreach(config('alerta.categorias_guia') as $clave => $nombre)
        @if($guias->has($clave))
            <details class="guia" open>
                <summary>{{ $nombre }}</summary>
                @foreach($guias[$clave] as $guia)
                    <h3 style="margin-top:10px">{{ $guia->titulo }}</h3>
                    <p>{{ $guia->contenido }}</p>
                    @if($guia->fuente_url)
                        <p class="nota">Fuente: <a href="{{ $guia->fuente_url }}" target="_blank" rel="noopener">{{ parse_url($guia->fuente_url, PHP_URL_HOST) }}</a></p>
                    @endif
                @endforeach
            </details>
        @endif
    @endforeach
</div>
@endif
@endsection
