#!/usr/bin/env sh
# Arranque de AlertaNiño AQP en Render. El servidor está fuera de Perú: SENAMHI
# no responde, así que la lectura oficial usa el espejo publicado desde Perú;
# el mapa, INDECI y el pronóstico funcionan en vivo.
set -e
cd /app

# Clave de cifrado: si no se definió en Render, se genera una temporal para poder
# arrancar (conviene definir APP_KEY en Render para que las sesiones no se reinicien).
if [ -z "$APP_KEY" ]; then
  APP_KEY="$(php artisan key:generate --show)"
  export APP_KEY
  echo ">> APP_KEY no definido: usando una clave temporal."
fi

[ -f database/database.sqlite ] || touch database/database.sqlite
php artisan migrate --force --seed
php artisan alerta:admin-desde-entorno || true
php artisan alerta:sincronizar || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
