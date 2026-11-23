<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Forzar HTTPS: en producción siempre, y en cualquier entorno cuando la
        // conexión ya llega cifrada (detrás de Cloudflare). Evita enlaces http
        // y contenido mixto, y refuerza las cookies seguras.
        if ($this->app->environment('production') || request()->isSecure()) {
            URL::forceScheme('https');
        }

        // Autorización por rol. Se usan en rutas (middleware "can:") y en vistas (@can).
        Gate::define('gestionar-contenido', fn (User $u) => $u->esPersonal());
        Gate::define('administrar', fn (User $u) => $u->esAdministrador());

        // Máximo 5 intentos por minuto de ingreso o registro por correo + IP (fuerza bruta).
        RateLimiter::for('acceso', fn (Request $r) => Limit::perMinute(5)
            ->by(mb_strtolower((string) $r->input('email')).'|'.$r->ip()));

        // La API pública admite 60 consultas por minuto por IP.
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));

        Paginator::defaultView('parciales.paginacion');
    }
}
