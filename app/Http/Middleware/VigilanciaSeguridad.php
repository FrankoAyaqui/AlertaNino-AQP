<?php

namespace App\Http\Middleware;

use App\Models\RegistroSeguridad;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Vigilancia defensiva: detecta solicitudes de ataque típicas, las registra
 * con su IP y navegador, limita el ritmo por IP y bloquea temporalmente al
 * origen que insiste. Es defensa: observa, registra y bloquea; nunca ataca
 * ni rastrea de vuelta al origen.
 */
class VigilanciaSeguridad
{
    /** Patrones de ataque buscados en la ruta y la cadena de consulta (no en el cuerpo, para no estorbar al panel). */
    private const PATRONES = [
        'traversal' => '#(\.\./|\.\.\\\\|%2e%2e|/etc/passwd|/proc/self)#i',
        'xss' => '#(<script\b|javascript:|onerror\s*=|onload\s*=)#i',
        'sqli' => '#(\bunion\b.{0,40}\bselect\b|\bor\s+1\s*=\s*1\b|;\s*drop\s+table|\bsleep\s*\(|information_schema)#i',
        'archivos' => '#(/\.env|/\.git|/wp-login|/wp-admin|/phpmyadmin|/\.aws|php://|data://|/vendor/.*\.php)#i',
        'inyeccion' => '#(\$\{jndi:|\{\{.*\}\}|<\?php|%00)#i',
    ];

    // Límites por IP (ventanas en segundos).
    private const MAX_POR_MINUTO = 240;     // ritmo normal de un visitante

    private const MAX_SOSPECHAS = 8;        // eventos sospechosos antes de bloquear

    private const BLOQUEO_SEG = 1800;       // 30 minutos de bloqueo

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        if (Cache::get("sec:bloqueo:$ip")) {
            RegistroSeguridad::anota($request, 'bloqueado', 'ip-bloqueada', 429);

            return response('Actividad bloqueada temporalmente por seguridad.', 429)
                ->header('Retry-After', (string) self::BLOQUEO_SEG);
        }

        // Límite global de peticiones por IP (defensa ante inundación / fuerza bruta distribuida).
        $porMinuto = Cache::add("sec:rate:$ip", 0, 60) ? 0 : Cache::increment("sec:rate:$ip");
        if ($porMinuto > self::MAX_POR_MINUTO) {
            $this->bloquear($ip);
            RegistroSeguridad::anota($request, 'critico', 'inundacion', 429);

            return response('Demasiadas solicitudes.', 429)->header('Retry-After', '60');
        }

        // Búsqueda de patrones de ataque en la URL (decodificada).
        $objetivo = rawurldecode('/'.$request->path().'?'.(string) $request->getQueryString());
        foreach (self::PATRONES as $nombre => $regex) {
            if (preg_match($regex, $objetivo)) {
                RegistroSeguridad::anota($request, 'critico', $nombre, 403);
                $this->sumarSospecha($ip);

                return response('Solicitud rechazada.', 403);
            }
        }

        $respuesta = $next($request);

        // Respuestas que delatan intentos de acceso o manipulación.
        if (in_array($respuesta->getStatusCode(), [401, 403, 419, 429], true)) {
            RegistroSeguridad::anota($request, 'advertencia', 'rechazo-'.$respuesta->getStatusCode(), $respuesta->getStatusCode());
            $this->sumarSospecha($ip);
        }

        return $respuesta;
    }

    /** Cuenta eventos sospechosos por IP y bloquea al superar el umbral. */
    private function sumarSospecha(string $ip): void
    {
        $n = Cache::add("sec:sosp:$ip", 0, 600) ? 0 : Cache::increment("sec:sosp:$ip");
        if ($n + 1 >= self::MAX_SOSPECHAS) {
            $this->bloquear($ip);
        }
    }

    private function bloquear(string $ip): void
    {
        Cache::put("sec:bloqueo:$ip", true, self::BLOQUEO_SEG);
    }
}
