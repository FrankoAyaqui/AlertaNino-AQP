# Imagen para desplegar AlertaNiño AQP en Render (gratis, sin tu PC).
FROM php:8.3-cli

# Librerías del sistema y extensiones de PHP que necesita Laravel.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libonig-dev \
        libsqlite3-dev libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring intl zip curl \
    && rm -rf /var/lib/apt/lists/*

# Composer desde su imagen oficial.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . /app

# Dependencias de produccion y permisos de escritura.
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x docker/start.sh

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr

EXPOSE 8000
CMD ["sh", "docker/start.sh"]
