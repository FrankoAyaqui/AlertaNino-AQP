@extends('layouts.app')
@section('titulo', 'Panel de gestión')
@section('contenido')
<h1 class="bloque__cabeza">Panel de gestión</h1>
@include('panel.nav')

<div class="totales" style="margin-bottom:20px">
    @foreach($totales as $nombre => $valor)
        <div class="total"><strong>{{ $valor }}</strong><span>{{ $nombre }}</span></div>
    @endforeach
</div>

<section class="panel">
    <div class="bloque__cabeza">
        <h2>Lecturas de las fuentes oficiales</h2>
        <form method="POST" action="{{ route('panel.sincronizar') }}" data-ocupado="Leyendo SENAMHI, ENFEN e INDECI…">
            @csrf
            <button type="submit" class="btn">Ejecutar lectura oficial ahora</button>
        </form>
    </div>
    <p class="nota" style="margin-top:0">La lectura también se ejecuta sola cada 30 minutos con el programador de Laravel.</p>
    <div class="tabla-envoltura">
        <table class="tabla tabla--responsive">
            <thead><tr><th>Fecha</th><th>Fuente</th><th>Resultado</th><th class="num">Registros</th><th>Detalle</th></tr></thead>
            <tbody>
            @forelse($sincronizaciones as $s)
                <tr>
                    <td data-label="Fecha">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                    <td data-label="Fuente">{{ $s->fuente }}</td>
                    <td data-label="Resultado"><span class="etiqueta {{ $s->estado === 'error' ? 'etiqueta--error' : 'etiqueta--ok' }}">{{ $s->estado }}</span></td>
                    <td data-label="Registros" class="num">{{ $s->registros }}</td>
                    <td data-label="Detalle">{{ \Illuminate\Support\Str::limit($s->detalle, 120) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Todavía no se ha ejecutado ninguna lectura.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
