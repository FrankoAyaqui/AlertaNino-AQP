@extends('layout')
@section('content')
<section class="hero"><div><p class="eyebrow">AREQUIPA · PILOTO COMUNITARIO</p><h1>Prepárate con información<br>de tu distrito.</h1><p>Consulta los avisos de práctica, identifica su vigencia y encuentra una orientación clara para tu familia.</p><a class="button" href="#zonas-afectadas">Ver zonas afectadas →</a><a class="textlink" href="#guias">Ver guías de preparación</a></div><div class="heroart" aria-hidden="true"><div class="sun"></div><div class="mountain back"></div><div class="mountain"></div><div class="city">AREQUIPA</div></div></section>
<div class="context"><strong>El Niño y el tiempo local son distintos</strong><p>Este prototipo no calcula un estado actual de El Niño. Revisa ENFEN para el fenómeno y SENAMHI para cada aviso meteorológico. La ausencia de avisos no significa ausencia de riesgo.</p></div>
<section id="zonas-afectadas" aria-labelledby="zonas-title">
<p class="eyebrow">SEGUIMIENTO POR DISTRITO</p><h2 id="zonas-title">Zonas afectadas y avisos por distrito</h2>
<div class="context"><strong>DEMOSTRACIÓN · Sin reportes reales conectados</strong><p>Los siguientes distritos pertenecen a avisos de práctica vigentes. No confirman daños actuales ni una relación con El Niño. Esa relación solo se indicará cuando esté respaldada por la fuente oficial.</p></div>
<p>Se aplican los filtros de distrito y título de la sección de avisos. Se resumen todas las coincidencias, no solo la página actual.</p>
<div class="grid">
@forelse($zoneSummaries as $zoneAlerts)
<article class="card"><p class="badge">DATOS DE PRUEBA</p><h3>{{ $zoneAlerts->first()->zone->name }}</h3><p>{{ $zoneAlerts->count() }} aviso(s) de demostración vigente(s)</p>
@foreach($zoneAlerts->take(3) as $zoneAlert)
<div><h4>{{ $zoneAlert->title }}</h4><p>Nivel del aviso de práctica: {{ $zoneAlert->level }}</p><p class="meta">Emisión: {{ $zoneAlert->issued_at->format('d/m/Y H:i') }}<br>Vigencia hasta: {{ $zoneAlert->expires_at->format('d/m/Y H:i') }} · Lima</p><a class="textlink" href="/alertas/{{ $zoneAlert->id }}">Ver detalle</a> · <a href="{{ $zoneAlert->source_url }}" rel="noopener noreferrer">Referencia del aviso</a></div>
@endforeach
@if($zoneAlerts->count()>3)<p>Se muestran los tres avisos más recientes de este distrito.</p>@endif
<a class="textlink" href="/practica?zone={{ $zoneAlerts->first()->zone_id }}#avisos">Consultar distrito →</a>
</article>
@empty
<p class="empty">No hay avisos de demostración vigentes para los filtros elegidos. No significa que las zonas estén libres de riesgo.</p>
@endforelse
</div></section>

<section id="avisos"><div class="sectiontop"><div><p class="eyebrow">CONSULTA POR ZONA</p><h2>Avisos de demostración vigentes</h2></div><button id="text-size" class="secondary" type="button" aria-pressed="false">Aumentar texto</button></div>
<form class="filters" method="get" action="/practica"><label>Distrito<select name="zone"><option value="">Todos los distritos</option>@foreach($zones as $zone)<option value="{{ $zone->id }}" @selected(request('zone')==$zone->id)>{{ $zone->name }}</option>@endforeach</select></label><label>Buscar por título<input name="q" maxlength="100" value="{{ request('q') }}" placeholder="Ej. preparación"></label><button>Buscar</button><a href="/practica#avisos">Limpiar</a></form>
<div class="grid">@forelse($alerts as $alert)<article class="card"><p class="badge">DEMOSTRACIÓN · {{ strtoupper($alert->level) }}</p><p class="eyebrow">{{ $alert->zone->name }}</p><h3><a href="/alertas/{{ $alert->id }}">{{ $alert->title }}</a></h3><p>{{ Str::limit($alert->message,150) }}</p><p class="meta">Vence {{ $alert->expires_at->format('d/m/Y H:i') }} · Lima</p><a class="textlink" href="/alertas/{{ $alert->id }}">Leer el aviso →</a></article>@empty<p class="empty">No hay avisos de demostración vigentes para esta búsqueda. Esto no significa que tu zona esté libre de riesgo.</p>@endforelse</div>{{ $alerts->links('pagination') }}</section>
<section id="guias"><p class="eyebrow">ANTES DE UNA EMERGENCIA</p><h2>Preparación en pasos sencillos</h2><div class="grid">@foreach($guides as $guide)<article class="card"><h3>{{ $guide->title }}</h3><p class="preline">{{ $guide->body }}</p><a href="{{ $guide->source_url }}" rel="noopener noreferrer">Consultar referencia</a></article>@endforeach</div></section>
<section><h2>Directorio de contactos</h2><p>Verifica la vigencia en la fuente antes de usar un contacto. En este prototipo no se realizan llamadas automáticamente.</p>@forelse($contacts as $contact)<p><strong>{{ $contact->name }}</strong> · {{ $contact->phone }} · <a href="{{ $contact->source_url }}" rel="noopener noreferrer">Fuente</a></p>@empty<p class="empty">Aún no hay contactos verificados cargados. Consulta el directorio oficial de tu municipalidad.</p>@endforelse</section>
@endsection
