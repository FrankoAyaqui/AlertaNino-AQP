@extends('layouts.app')
@section('titulo', 'Mi bandeja')
@section('contenido')
<div class="bloque__cabeza">
    <h1>Mi bandeja</h1>
    @if(auth()->user()->unreadNotifications()->exists())
        <form method="POST" action="{{ route('bandeja.todas') }}">
            @csrf
            <button type="submit" class="btn btn--sec">Marcar todo como leído</button>
        </form>
    @endif
</div>
<div class="avisos">
    @forelse($notificaciones as $n)
        @php($d = $n->data)
        <article class="aviso" data-nivel="n{{ $d['nivel'] ?? 0 }}">
            <h3>
                @if(($d['tipo'] ?? '') === 'aviso' && isset($d['aviso_id']))
                    <a href="{{ route('avisos.show', $d['aviso_id']) }}">{{ $d['titulo'] }}</a>
                @elseif(($d['tipo'] ?? '') === 'enfen' && isset($d['pdf_url']))
                    <a href="{{ $d['pdf_url'] }}" target="_blank" rel="noopener">{{ $d['titulo'] }}</a>
                @else
                    {{ $d['titulo'] ?? 'Aviso' }}
                @endif
            </h3>
            <p class="aviso__meta">
                @isset($d['nivel']) @include('parciales.nivel', ['nivel' => $d['nivel'], 'chica' => true]) @endisset
                {{ $d['distrito'] ?? '' }} · recibido {{ $n->created_at->format('d/m/Y H:i') }}
                @if($n->read_at) · <span class="etiqueta">Leído</span> @else · <span class="etiqueta etiqueta--error">Nuevo</span> @endif
            </p>
            @unless($n->read_at)
                <form method="POST" action="{{ route('bandeja.leida', $n->id) }}">
                    @csrf
                    <button type="submit" class="btn btn--sec btn--chico">Marcar como leído</button>
                </form>
            @endunless
        </article>
    @empty
        <p class="vacio">Tu bandeja está vacía. Aquí llegarán los avisos oficiales de tu distrito
            @unless(auth()->user()->distrito_id) (primero <a href="{{ route('cuenta.editar') }}">elige tu distrito</a>)@endunless.</p>
    @endforelse
</div>
{{ $notificaciones->links() }}
@endsection
