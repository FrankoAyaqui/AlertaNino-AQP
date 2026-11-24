#!/usr/bin/env sh
# Arranque de AlertaNiño AQP en un servidor (Render). El servidor está fuera de
# Perú: SENAMHI no responde, así que la lectura oficial usa el espejo publicado
# desde Perú; el mapa, INDECI y el pronóstico funcionan en vivo.
set -e
cd /app
[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --force --seed
php artisan alerta:admin-desde-entorno || true
php artisan alerta:sincronizar || true
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
