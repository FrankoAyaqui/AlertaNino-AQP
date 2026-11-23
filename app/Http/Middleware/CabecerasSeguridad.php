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
            'upgrade-insecure-requests',
        ]);

        $respuesta->headers->set('Content-Security-Policy', $csp);
        $respuesta->headers->set('X-Content-Type-Options', 'nosniff');
        $respuesta->headers->set('X-Frame-Options', 'DENY');
        $respuesta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $respuesta->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=(), usb=()');
        $respuesta->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $respuesta->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS: obliga a HTTPS durante un año en toda conexión segura (OWASP A02/A05).
        if ($request->isSecure()) {
            $respuesta->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // No revelar el software del servidor.
        $respuesta->headers->remove('X-Powered-By');
        $respuesta->headers->remove('Server');

        return $respuesta;
    }
}
