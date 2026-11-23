{{-- Mensajes de confirmación y errores de validación del servidor --}}
@if(session('estado'))
    <div class="mensaje mensaje--ok" role="status">{{ session('estado') }}</div>
@endif
@if(session('error'))
    <div class="mensaje mensaje--error" role="alert">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mensaje mensaje--error" role="alert">
        <strong>Revisa los datos del formulario:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
