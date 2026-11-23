<?php

/*
|--------------------------------------------------------------------------
| CORS restringido
|--------------------------------------------------------------------------
| Solo la API pública de solo lectura admite peticiones de otro origen, y solo
| con GET. El resto de la aplicación (formularios, panel) no permite CORS, así
| que ningún sitio externo puede enviar acciones en nombre del usuario.
*/

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET'],
    // Orígenes permitidos: la propia aplicación. Añadir aquí un dominio extra solo si se necesita.
    'allowed_origins' => array_filter([env('APP_URL')]),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Accept', 'Content-Type'],
    'exposed_headers' => [],
    'max_age' => 3600,
    // Sin credenciales entre orígenes: la sesión nunca viaja a un sitio externo.
    'supports_credentials' => false,
];
