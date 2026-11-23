<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad para todas las respuestas.
 *
 * La política de contenido (CSP) solo permite scripts propios y Leaflet desde
 * cdnjs (con verificación de integridad SRI en la vista), y solo conecta con
 * las fuentes de datos que usa la aplicación. Así, aunque se colara texto con
 * <script>, el navegador no lo ejecutaría.
 */
class CabecerasSeguridad
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://cdnjs.cloudflare.com",
            "style-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'", // Leaflet posiciona capas con estilos en línea
            "img-src 'self' data: https://tile.openstreetmap.org https://*.tile.openstreetmap.org https://idesep.senamhi.gob.pe",
            "connect-src 'self' https://api.open-meteo.com",
            "font-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);

        $respuesta->headers->set('Content-Security-Policy', $csp);
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'DENY');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        return $respuesta;
    }
}
