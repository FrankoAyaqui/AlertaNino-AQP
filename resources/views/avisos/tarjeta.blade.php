{{-- Resumen de un aviso en listas --}}
<article class="aviso" data-nivel="n{{ $aviso->nivel }}">
    <h3><a href="{{ route('avisos.show', $aviso) }}">{{ $aviso->titulo }}</a></h3>
    <p class="aviso__meta">
        @include('parciales.nivel', ['nivel' => $aviso->nivel, 'chica' => true])
        {{ $aviso->fuente }} · {{ $aviso->nombreTipo() }} · {{ $aviso->distrito?->nombre ?? 'Provincia de Arequipa' }} ·
        vigente hasta {{ $aviso->vence_en->format('d/m/Y H:i') }}
        @if($aviso->origen === 'automatico') · <span class="etiqueta">Lectura automática</span> @endif
    </p>
    <p>{{ \Illuminate\Support\Str::limit($aviso->mensaje, 220) }}</p>
</article>
