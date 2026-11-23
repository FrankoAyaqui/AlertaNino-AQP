@extends('layouts.app')
@section('titulo', 'Privacidad')
@section('contenido')
<article class="panel" style="max-width:820px">
    <h1>Política de privacidad</h1>
    <p>AlertaNiño AQP es un proyecto académico de la Universidad Continental. Tratamos los datos conforme a la Ley N.º 29733 de
        Protección de Datos Personales y su reglamento (D. S. N.º 016-2024-JUS).</p>
    <h2>Qué datos guardamos</h2>
    <ul>
        <li>Nombre, correo y contraseña (guardada solo como hash bcrypt, nunca en texto plano).</li>
        <li>El distrito que eliges y si deseas recibir avisos.</li>
        <li>Los avisos enviados a tu bandeja.</li>
    </ul>
    <p>No pedimos DNI, dirección, ubicación exacta, datos de salud ni de familiares. Las preferencias de lectura (tamaño de letra,
        tema y sonido) se guardan solo en tu navegador.</p>
    <h2>Para qué</h2>
    <p>Únicamente para mostrarte los avisos oficiales de tu distrito. No vendemos ni compartimos tus datos.</p>
    <h2>Servicios externos</h2>
    <p>El mapa y el pronóstico se cargan desde SENAMHI, OpenStreetMap, Open-Meteo y cdnjs. Estos servicios reciben la dirección IP de tu
        navegador, como en cualquier visita web.</p>
    <h2>Tus derechos</h2>
    <p>Puedes cambiar tus datos en «Mi cuenta» y pedir la eliminación de tu cuenta al equipo del proyecto.</p>
</article>
@endsection
