{{-- Insignia de nivel oficial de SENAMHI. Siempre número + nombre (no depende del color). --}}
@php($n = (int) ($nivel ?? 0))
<span class="insignia {{ ($chica ?? false) ? 'insignia--chica' : '' }}" data-nivel="n{{ $n }}">
    <span class="insignia__icono" aria-hidden="true">{{ $n ?: '–' }}</span>
    <span>{{ config('alerta.niveles.'.$n.'.nombre', 'Nivel '.$n) }}</span>
</span>
