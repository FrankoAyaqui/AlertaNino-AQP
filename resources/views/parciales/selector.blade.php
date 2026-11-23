{{-- Lista desplegable con opciones [valor => texto]. --}}
@php($actual = (string) old($nombre, $valor ?? ''))
<div class="campo">
    <label for="{{ $nombre }}">{{ $etiqueta }}</label>
    <select id="{{ $nombre }}" name="{{ $nombre }}" @if($requerido ?? false) required @endif
        @error($nombre) aria-invalid="true" aria-describedby="{{ $nombre }}-error" @enderror>
        @isset($vacio)<option value="">{{ $vacio }}</option>@endisset
        @foreach($opciones as $clave => $texto)
            <option value="{{ $clave }}" @selected($actual === (string) $clave)>{{ $texto }}</option>
        @endforeach
    </select>
    @isset($ayuda)<span class="ayuda">{{ $ayuda }}</span>@endisset
    @error($nombre)<span class="error" id="{{ $nombre }}-error">{{ $message }}</span>@enderror
</div>
