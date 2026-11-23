@extends('layouts.app')
@section('titulo', 'Registro de seguridad')
@section('contenido')
<h1 class="bloque__cabeza">Registro de seguridad</h1>
@include('panel.nav')

<p class="nota" style="margin-top:0">Trazabilidad defensiva: solicitudes de ataque detectadas, con su IP y navegador. Sirve para
bloquear al origen y conservar evidencia que pueda entregarse a la autoridad (DIVINDAT - PNP). La aplicación no realiza ninguna acción contra el origen.</p>

<div class="totales" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
    <div class="total"><strong>{{ $resumen['critico'] ?? 0 }}</strong><span>Críticos (24 h)</span></div>
    <div class="total"><strong>{{ $resumen['bloqueado'] ?? 0 }}</strong><span>Bloqueados (24 h)</span></div>
    <div class="total"><strong>{{ $resumen['advertencia'] ?? 0 }}</strong><span>Advertencias (24 h)</span></div>
</div>

@if($ips->isNotEmpty())
<div class="panel" style="margin-bottom:16px">
    <h2>IP con más eventos (24 h)</h2>
    <div class="fila-botones">
        @foreach($ips as $fila)
            <span class="etiqueta">{{ $fila->ip }} · {{ $fila->total }}</span>
        @endforeach
    </div>
</div>
@endif

<form method="GET" class="filtros" role="search">
    <div class="campo"><label for="severidad">Severidad</label>
        <select id="severidad" name="severidad">
            <option value="">Todas</option>
            @foreach(['critico' => 'Crítico', 'bloqueado' => 'Bloqueado', 'advertencia' => 'Advertencia'] as $v => $t)
                <option value="{{ $v }}" @selected(($filtros['severidad'] ?? '') === $v)>{{ $t }}</option>
            @endforeach
        </select></div>
    <button class="btn" type="submit">Filtrar</button>
</form>

<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Fecha</th><th>IP</th><th>Método</th><th>Ruta</th><th>Patrón</th><th>Severidad</th></tr></thead>
        <tbody>
        @forelse($eventos as $e)
            <tr>
                <td data-label="Fecha">{{ $e->created_at?->format('d/m/Y H:i:s') }}</td>
                <td data-label="IP">{{ $e->ip }}</td>
                <td data-label="Método">{{ $e->metodo }}</td>
                <td data-label="Ruta">{{ \Illuminate\Support\Str::limit($e->ruta, 60) }}</td>
                <td data-label="Patrón">{{ $e->patron }}</td>
                <td data-label="Severidad">
                    <span class="etiqueta {{ $e->severidad === 'advertencia' ? '' : 'etiqueta--error' }}">{{ $e->severidad }}</span>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Sin eventos registrados. Buena señal.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $eventos->links() }}
@endsection
