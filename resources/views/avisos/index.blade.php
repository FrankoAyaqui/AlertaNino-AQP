@extends('layouts.app')
@section('titulo', 'Avisos oficiales')
@section('contenido')
<div class="bloque__cabeza"><h1>Avisos oficiales</h1></div>

<form method="GET" action="{{ route('avisos.index') }}" class="filtros" role="search">
    <div class="campo">
        <label for="q">Buscar</label>
        <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" maxlength="100" placeholder="Palabra del título o mensaje">
    </div>
    <div class="campo">
        <label for="distrito">Distrito</label>
        <select id="distrito" name="distrito">
            <option value="">Todos</option>
            @foreach($distritos as $d)
                <option value="{{ $d->slug }}" @selected(($filtros['distrito'] ?? '') === $d->slug)>{{ $d->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="fuente">Fuente</label>
        <select id="fuente" name="fuente">
            <option value="">Todas</option>
            @foreach(config('alerta.fuentes') as $f)
                <option value="{{ $f }}" @selected(($filtros['fuente'] ?? '') === $f)>{{ $f }}</option>
            @endforeach
        </select>
    </div>
    <div class="campo">
        <label for="ver">Mostrar</label>
        <select id="ver" name="ver">
            <option value="vigentes">Solo vigentes</option>
            <option value="todos" @selected($verTodos)>Vigentes y vencidos</option>
        </select>
    </div>
    <button type="submit" class="btn">Filtrar</button>
    <a class="btn btn--sec" href="{{ route('avisos.index') }}">Limpiar</a>
</form>

<div class="avisos">
    @forelse($avisos as $aviso)
        @include('avisos.tarjeta', ['aviso' => $aviso])
    @empty
        <p class="vacio">No hay avisos con esos filtros. Que no haya resultados no significa ausencia de riesgo: consulta también el
            <a href="{{ config('alerta.senamhi.visor') }}" target="_blank" rel="noopener">visor de SENAMHI</a>.</p>
    @endforelse
</div>
{{ $avisos->links() }}
@endsection
