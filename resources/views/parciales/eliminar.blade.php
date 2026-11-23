{{-- Botón de eliminación: pide confirmación (JS) y el servidor vuelve a autorizar. --}}
<form method="POST" action="{{ $accion }}" class="en-linea" data-confirmar="{{ $pregunta ?? '¿Eliminar este registro? Esta acción no se puede deshacer.' }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn--peligro btn--chico">Eliminar</button>
</form>
