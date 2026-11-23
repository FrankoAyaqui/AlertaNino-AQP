@extends('layouts.app')

@section('titulo', 'Situación en mi distrito')

@push('estilos')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
          integrity="sha512-h9FcoyWjHcOcmEVkxOfTLnmZFWIH0iZhZT1H2TbOq55xssQGEJHEaIm+PgoUaZbRvQTNTluNOEfb1ZRy6D3BOw=="
          crossorigin="anonymous" referrerpolicy="no-referrer">
@endpush

@section('contenido')
@php
    $n = $lectura?->nivel ?? 0;
    $verificada = $lectura?->estaVerificada() ?? false;
@endphp

<section class="bloque" aria-labelledby="t-distrito">
    <div class="bloque__cabeza">
        <h1 id="t-distrito">Situación en mi distrito</h1>
        <form method="GET" action="{{ route('inicio') }}" class="filtros" id="form-distrito" style="margin:0">
            <div class="campo">
                <label for="distrito">Distrito</label>
                <select id="distrito" name="distrito" data-auto-enviar>
                    @foreach($distritos as $d)
                        <option value="{{ $d->slug }}" @selected($d->is($distrito))>{{ $d->nombre }}{{ $d->es_piloto ? ' · piloto' : '' }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn--sec">Ver</button>
        </form>
    </div>

    @if($distrito)
    <article class="tarjeta-estado" data-nivel="n{{ $n }}" aria-live="polite">
        <div>
            @include('parciales.nivel', ['nivel' => $n])
            @if($lectura)
                <p class="tarjeta-estado__titulo">
                    @if($n > 0)
                        Aviso de SENAMHI por lluvias: Nivel {{ $n }} en {{ $distrito->nombre }}
                    @else
                        SENAMHI no incluye a {{ $distrito->nombre }} en el aviso por lluvias de hoy
                    @endif
                </p>
                @if($lectura->descripcion)
                    <p class="texto-oficial">«{{ $lectura->descripcion }}»</p>
                @endif
                <p class="nota">
                    Vigencia: {{ $lectura->fecha->translatedFormat('j \d\e F \d\e Y') }} ·
                    SENAMHI consultado {{ $lectura->consultado_en->format('d/m/Y H:i') }}
                    @if($lectura->origen === 'espejo') (copia publicada desde Perú) @endif
                </p>
                @unless($verificada)
                    <p class="mensaje mensaje--error">Lectura oficial no verificada en las últimas {{ config('alerta.horas_vigencia_lectura') }} horas. Confirma en el
                        <a href="{{ config('alerta.senamhi.visor') }}" target="_blank" rel="noopener">visor de SENAMHI</a>.</p>
                @endunless
            @else
                <p class="tarjeta-estado__titulo">Todavía no hay una lectura oficial de SENAMHI</p>
                <p class="nota">Un operador debe ejecutar la lectura oficial. La falta de datos no significa que no haya riesgo.</p>
            @endif
        </div>

        <dl class="cifras">
            <div><dt>Avisos vigentes para el distrito</dt><dd>{{ $avisos->count() }}</dd></div>
            <div><dt>ENFEN</dt><dd>{{ $comunicado?->estado ?? '—' }} @if($comunicado)<small>(N.º {{ $comunicado->numero }})</small>@endif</dd></div>
            <div><dt>Ahora (pronóstico)</dt><dd id="clima-actual">—</dd></div>
            <div><dt>Lluvia prevista hoy (modelo)</dt><dd id="lluvia-hoy">—</dd></div>
        </dl>

        <div class="tarjeta-estado__acciones">
            <h2 style="font-size:1rem;margin-bottom:8px">Qué hacer ahora</h2>
            <ul class="acciones-lista">
                @if($lectura?->recomendacion)
                    <li>SENAMHI recomienda: «{{ $lectura->recomendacion }}»</li>
                @endif
                @foreach(config('alerta.acciones.'.$n, []) as $accion)
                    <li>{{ $accion }}</li>
                @endforeach
            </ul>
        </div>
    </article>
    @endif
</section>

<section class="bloque" aria-labelledby="t-avisos">
    <div class="bloque__cabeza">
        <h2 id="t-avisos">Avisos vigentes para {{ $distrito?->nombre }}</h2>
        <a class="btn btn--sec" href="{{ route('avisos.index', ['distrito' => $distrito?->slug]) }}">Ver todos</a>
    </div>
    <div class="avisos">
        @forelse($avisos as $aviso)
            @include('avisos.tarjeta', ['aviso' => $aviso])
        @empty
            <p class="vacio">No hay avisos oficiales vigentes para este distrito. Esto no significa ausencia de riesgo: revisa las fuentes oficiales.</p>
        @endforelse
    </div>
</section>

<section class="bloque" aria-labelledby="t-mapa">
    <div class="bloque__cabeza">
        <h2 id="t-mapa">Mapa de distritos</h2>
        <div class="leyenda" aria-label="Leyenda de niveles de SENAMHI">
            <span class="nota" style="margin:0">SENAMHI:</span>
            @foreach([0, 1, 2, 3, 4] as $nv) @include('parciales.nivel', ['nivel' => $nv, 'chica' => true]) @endforeach
        </div>
    </div>
    <div class="mapa-y-lista">
        <div id="mapa" class="mapa" data-seleccionado="{{ $distrito?->slug }}"
             data-wms="{{ config('alerta.senamhi.wfs') }}" data-capa="{{ config('alerta.senamhi.capa_lluvia_24h') }}"
             aria-label="Mapa con el aviso por lluvias de SENAMHI. La misma información está en la tabla."></div>
        <div class="tabla-envoltura">
            <table class="tabla">
                <caption>Aviso por lluvias de SENAMHI por distrito</caption>
                <thead><tr><th scope="col">Distrito</th><th scope="col">Nivel</th><th scope="col">Vigencia</th></tr></thead>
                <tbody>
                @foreach($distritos->sortByDesc(fn ($d) => $d->ultimaLectura?->nivel ?? -1) as $d)
                    <tr @if($d->is($distrito)) aria-current="true" @endif>
                        <td><a href="{{ route('inicio', ['distrito' => $d->slug]) }}">{{ $d->nombre }}</a></td>
                        <td>@include('parciales.nivel', ['nivel' => $d->ultimaLectura?->nivel ?? 0, 'chica' => true])</td>
                        <td>{{ $d->ultimaLectura?->fecha?->format('d/m/Y') ?? 'Sin lectura' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="nota">El mapa muestra en vivo la capa oficial de SENAMHI; los círculos blancos son emergencias registradas por INDECI. Los puntos son una referencia de cada distrito, no límites oficiales. Una zona sin aviso no significa «zona segura».</p>
</section>

<section class="bloque rejilla-2" aria-label="Reportes oficiales y pronóstico">
    <article class="panel">
        <h2>ENFEN · El Niño</h2>
        @if($comunicado)
            <p class="estado-enfen">{{ $comunicado->estado }}</p>
            <p><strong>{{ $comunicado->titular }}</strong></p>
            <p>{{ $comunicado->resumen }}</p>
            @if($comunicado->lluvias)<p class="texto-oficial">{{ $comunicado->lluvias }}</p>@endif
            <p class="nota">Comunicado Oficial ENFEN N.º {{ $comunicado->numero }} del {{ $comunicado->fecha->format('d/m/Y') }}.
                <a href="{{ $comunicado->pdf_url }}" target="_blank" rel="noopener">Ver PDF oficial</a></p>
        @else
            <p class="vacio">Aún no se ha leído el comunicado de ENFEN.</p>
        @endif
    </article>

    <article class="panel">
        <h2>Pronóstico de 7 días · {{ $distrito?->nombre }}</h2>
        <p class="nota" style="margin-top:0">Pronóstico numérico de referencia (Open-Meteo). No es un aviso oficial.</p>
        <div class="grafico" id="g-semana" role="img" aria-label="Lluvia diaria prevista para 7 días"
             data-lat="{{ $distrito?->latitud }}" data-lon="{{ $distrito?->longitud }}"></div>
        <div class="tabla-envoltura"><table class="tabla" id="tabla-semana"><caption class="oculto-visual">Pronóstico en texto</caption><tbody></tbody></table></div>
    </article>

    <article class="panel">
        <h2>INDECI · Emergencias registradas</h2>
        @forelse($emergencias as $e)
            @if($loop->first)<ul class="acciones-lista">@endif
            <li>{{ $e->fecha->format('d/m/Y') }} · {{ $e->distrito }} · {{ mb_strtolower($e->fenomeno) }}
                @if($e->damnificados) · {{ $e->damnificados }} damnificados @endif
                @if($e->afectados) · {{ $e->afectados }} afectados @endif</li>
            @if($loop->last)</ul>@endif
        @empty
            <p class="vacio">Sin registros de SINPAD todavía.</p>
        @endforelse
        <p class="nota">Provincia de Arequipa, servicio GeoSINPAD de INDECI.
            <a href="{{ config('alerta.sinpad.portal') }}" target="_blank" rel="noopener">Reportes del COEN</a></p>
    </article>

    <article class="panel">
        <h2>Alertas en este dispositivo</h2>
        <label class="casilla"><input type="checkbox" id="opt-notificar"> Avisarme cuando suba el nivel oficial de mi distrito</label>
        <p class="nota" id="notif-estado">La página revisa los datos oficiales cada 5 minutos mientras esté abierta.</p>
        <label class="casilla" style="margin-top:10px"><input type="checkbox" id="opt-sonido"> Sonar al recibir un aviso</label>
        <div class="fila-botones" style="margin-top:8px">
            <label for="tono" class="oculto-visual">Tono</label>
            <select id="tono" class="btn btn--sec btn--chico">
                <option value="campana">Campana suave</option>
                <option value="pitido">Pitido intermitente</option>
                <option value="sirena">Sirena</option>
            </select>
            <button type="button" class="btn btn--sec btn--chico" id="probar-sonido">Probar sonido</button>
            <button type="button" class="btn btn--sec btn--chico" id="detener-sonido">Detener</button>
        </div>
        <p class="nota">El sonido nunca se reproduce sin tu permiso. @guest <a href="{{ route('registro') }}">Crea una cuenta</a> para recibir los avisos en tu bandeja. @endguest</p>
    </article>
</section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"
            integrity="sha512-puJW3E/qXDqYp9IfhAI54BJEaWIfloJ7JWs7OeD5i6ruC9JZL1gERT1wjtwXFlh7CjE7ZJ+/vcRZRkIYIb6p4g=="
            crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script type="module" src="{{ asset('js/inicio.js') }}"></script>
@endpush
