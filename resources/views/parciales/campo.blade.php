{{-- Campo de formulario con etiqueta, valor anterior y error del servidor. --}}
@php($valor = old($nombre, $valor ?? ''))
<div class="campo">
    <label for="{{ $nombre }}">{{ $etiqueta }}</label>
    @if(($tipo ?? 'text') === 'textarea')
        <textarea id="{{ $nombre }}" name="{{ $nombre }}" @if($requerido ?? false) required @endif
            @if(isset($max)) maxlength="{{ $max }}" @endif
            @error($nombre) aria-invalid="true" aria-describedby="{{ $nombre }}-error" @enderror>{{ $valor }}</textarea>
    @else
        <input type="{{ $tipo ?? 'text' }}" id="{{ $nombre }}" name="{{ $nombre }}" value="{{ $valor }}"
            @if($requerido ?? false) required @endif @if(isset($max)) maxlength="{{ $max }}" @endif
            @error($nombre) aria-invalid="true" aria-describedby="{{ $nombre }}-error" @enderror>
    @endif
    @isset($ayuda)<span class="ayuda">{{ $ayuda }}</span>@endisset
    @error($nombre)<span class="error" id="{{ $nombre }}-error">{{ $message }}</span>@enderror
</div>
