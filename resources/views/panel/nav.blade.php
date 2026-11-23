{{-- Menú del panel: los módulos de administrador solo se muestran a ese rol --}}
<nav class="subnav" aria-label="Panel de gestión">
    <a href="{{ route('panel.inicio') }}" @if(request()->routeIs('panel.inicio')) aria-current="page" @endif>Tablero</a>
    <a href="{{ route('panel.avisos.index') }}" @if(request()->routeIs('panel.avisos.*')) aria-current="page" @endif>Avisos</a>
    <a href="{{ route('panel.guias.index') }}" @if(request()->routeIs('panel.guias.*')) aria-current="page" @endif>Guías</a>
    <a href="{{ route('panel.contactos.index') }}" @if(request()->routeIs('panel.contactos.*')) aria-current="page" @endif>Contactos</a>
    @can('administrar')
        <a href="{{ route('panel.distritos.index') }}" @if(request()->routeIs('panel.distritos.*')) aria-current="page" @endif>Distritos</a>
        <a href="{{ route('panel.usuarios.index') }}" @if(request()->routeIs('panel.usuarios.*')) aria-current="page" @endif>Usuarios</a>
    @endcan
</nav>
