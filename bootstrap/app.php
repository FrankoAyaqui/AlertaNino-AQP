<?php

use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\VigilanciaSeguridad;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Detrás de Cloudflare / un proxy inverso: usar la IP y el esquema (https) reales
        // de las cabeceras X-Forwarded-*. Así la trazabilidad guarda la IP del visitante
        // y las cookies y enlaces se generan como https.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
            | Request::HEADER_X_FORWARDED_AWS_ELB);

        // Vigilancia: bloquea y registra solicitudes de ataque antes que nada.
        $middleware->prepend(VigilanciaSeguridad::class);
        // Cabeceras de seguridad en todas las respuestas (web y API).
        $middleware->append(CabecerasSeguridad::class);

        $middleware->redirectGuestsTo(fn () => route('ingresar'));
        $middleware->redirectUsersTo(fn () => route('inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
