@extends('layouts.app')
@section('titulo', 'Estado de las fuentes oficiales')
@section('contenido')
@php
    $nombres = [
        'senamhi_lluvia' => ['SENAMHI · Aviso por lluvias 24 h', config('alerta.senamhi.visor')],
        'senamhi_avisos' => ['SENAMHI · Avisos meteorológicos', config('alerta.senamhi.visor')],
        'senamhi_quebradas' => ['SENAMHI · Activación de quebradas', config('alerta.senamhi.visor')],
        'enfen' => ['ENFEN · Comunicado oficial', config('alerta.senamhi.pagina_nino')],
        'sinpad' => ['INDECI · Emergencias SINPAD', config('alerta.sinpad.portal')],
    ];
@endphp
<h1 class="bloque__cabeza">Estado de las fuentes oficiales</h1>
<div class="tabla-envoltura panel">
    <table class="tabla tabla--responsive">
        <thead><tr><th>Fuente</th><th>Última lectura</th><th>Resultado</th><th>Última lectura exitosa</th></tr></thead>
        <tbody>
        @foreach($lecturas as $clave => $l)
            <tr>
                <td data-label="Fuente"><a href="{{ $nombres[$clave][1] }}" target="_blank" rel="noopener">{{ $nombres[$clave][0] }}</a></td>
                <td data-label="Última lectura">{{ $l['ultima']?->created_at?->format('d/m/Y H:i') ?? 'Nunca' }}</td>
                <td data-label="Resultado">
                    @if($l['ultima'])
                        <span class="etiqueta {{ $l['ultima']->estado === 'error' ? 'etiqueta--error' : 'etiqueta--ok' }}">{{ $l['ultima']->estado }}</span>
                    @else
                        —
                    @endif
                </td>
                <td data-label="Exitosa">{{ $l['exitosa']?->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="panel" style="margin-top:16px">
    <h2>Cómo se obtienen los datos</h2>
    <ul class="acciones-lista">
        <li><strong>Nivel por distrito:</strong> capa oficial <code>{{ config('alerta.senamhi.capa_lluvia_24h') }}</code> de SENAMHI. Se comprueba qué zona del aviso contiene el punto de referencia de cada distrito y se copian su nivel, descripción y recomendación sin cambios.</li>
        <li><strong>Avisos automáticos:</strong> desde el Nivel 2 se publica un aviso con el texto oficial y se notifica a los ciudadanos del distrito.</li>
        <li><strong>ENFEN:</strong> se lee el último Comunicado Oficial (PDF) enlazado en la página de El Niño de SENAMHI.</li>
        <li><strong>INDECI:</strong> emergencias por lluvias, inundaciones, huaicos y deslizamientos registradas en la provincia de Arequipa.</li>
        <li><strong>Frecuencia:</strong> cada 30 minutos. «ok» = lectura directa; «espejo» = copia publicada desde Perú cuando SENAMHI no responde al servidor; «error» = no se pudo leer (se conserva el último dato con su hora).</li>
    </ul>
</div>
@endsection
