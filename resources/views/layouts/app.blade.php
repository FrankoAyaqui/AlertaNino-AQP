<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('titulo')@yield('titulo') · @endif AlertaNiño AQP</title>
    <meta name="description" content="Avisos oficiales de SENAMHI, ENFEN e INDECI sobre lluvias, El Niño y emergencias para los distritos de Arequipa.">
    <meta name="theme-color" content="#0d366b">
    <link rel="icon" href="{{ asset('icono.svg') }}" type="image/svg+xml">
    @stack('estilos')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/preferencias.js') }}"></script>
</head>
<body data-api="{{ route('api.estado') }}"
      data-distrito-usuario="{{ auth()->user()?->distrito?->slug }}">
<a class="saltar" href="#principal">Saltar al contenido</a>

<header class="cabecera">
    <div class="contenedor cabecera__fila">
        <a class="marca" href="{{ route('inicio') }}">
            <img src="{{ asset('icono.svg') }}" alt="" width="36" height="36">
            <span><strong>AlertaNiño</strong> AQP</span>
        </a>
        <button type="button" class="btn-icono menu-movil" id="menu-movil" aria-expanded="false" aria-controls="nav-principal">☰<span class="oculto-visual"> Menú</span></button>
        <nav class="nav" id="nav-principal" aria-label="Principal">
            <a href="{{ route('inicio') }}" @if(request()->routeIs('inicio')) aria-current="page" @endif>Inicio</a>
            <a href="{{ route('avisos.index') }}" @if(request()->routeIs('avisos.*')) aria-current="page" @endif>Avisos</a>
            <a href="{{ route('guias') }}" @if(request()->routeIs('guias')) aria-current="page" @endif>Guías</a>
            <a href="{{ route('contactos') }}" @if(request()->routeIs('contactos')) aria-current="page" @endif>Contactos</a>
            <a href="{{ route('fuentes') }}" @if(request()->routeIs('fuentes')) aria-current="page" @endif>Fuentes</a>
            @auth
                <a href="{{ route('bandeja') }}" @if(request()->routeIs('bandeja')) aria-current="page" @endif>Bandeja
                    @php($sinLeer = auth()->user()->unreadNotifications()->count())
                    @if($sinLeer)<span class="contador" aria-label="{{ $sinLeer }} sin leer">{{ $sinLeer }}</span>@endif
                </a>
                <a href="{{ route('cuenta.editar') }}" @if(request()->routeIs('cuenta.*')) aria-current="page" @endif>Mi cuenta</a>
                @can('gestionar-contenido')
                    <a href="{{ route('panel.inicio') }}" @if(request()->routeIs('panel.*')) aria-current="page" @endif>Panel</a>
                @endcan
                <form method="POST" action="{{ route('salir') }}" class="en-linea">
                    @csrf
                    <button type="submit" class="enlace">Salir</button>
                </form>
            @else
                <a href="{{ route('ingresar') }}" @if(request()->routeIs('ingresar')) aria-current="page" @endif>Ingresar</a>
                <a href="{{ route('registro') }}" @if(request()->routeIs('registro')) aria-current="page" @endif>Crear cuenta</a>
            @endauth
        </nav>
        <div class="herramientas" role="group" aria-label="Opciones de lectura">
            <button type="button" class="btn-icono" id="texto-menos" aria-label="Reducir tamaño de texto">A−</button>
            <button type="button" class="btn-icono" id="texto-mas" aria-label="Aumentar tamaño de texto">A+</button>
            <button type="button" class="btn-icono" id="tema" aria-label="Cambiar entre modo claro y oscuro">◐</button>
        </div>
    </div>
</header>

<div class="aviso-legal">
    <div class="contenedor">
        <strong>Datos oficiales</strong> de <a href="{{ config('alerta.senamhi.visor') }}" target="_blank" rel="noopener">SENAMHI</a>,
        <a href="{{ config('alerta.senamhi.pagina_nino') }}" target="_blank" rel="noopener">ENFEN</a> e
        <a href="{{ config('alerta.sinpad.portal') }}" target="_blank" rel="noopener">INDECI</a>, reunidos por un proyecto académico.
        Ante una emergencia sigue las indicaciones de las autoridades.
        Emergencias: <a href="tel:115">115</a> Defensa Civil · <a href="tel:116">116</a> Bomberos · <a href="tel:105">105</a> Policía · <a href="tel:106">106</a> SAMU.
    </div>
</div>

<main id="principal" class="contenedor">
    @include('parciales.mensajes')
    @yield('contenido')
</main>

<footer class="pie">
    <div class="contenedor">
        <p><strong>AlertaNiño AQP</strong> · Proyecto de impacto social, Programación Web, Universidad Continental, Arequipa.</p>
        <p>Fuentes: SENAMHI (IDESEP), ENFEN, INDECI (GeoSINPAD), Open-Meteo (CC BY 4.0). Mapa © colaboradores de OpenStreetMap.
            <a href="{{ route('privacidad') }}">Privacidad</a> · <a href="{{ route('fuentes') }}">Estado de las fuentes</a></p>
    </div>
</footer>

<div class="toast" id="toast" role="alert" hidden></div>
<script type="module" src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
