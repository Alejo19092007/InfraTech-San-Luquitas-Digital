# syntax=docker/dockerfile:1
# ============================================================================
#  Imagen del Portal San Luquitas (Laravel 12 sobre PHP-FPM 8.3)
#  La usan 3 servicios: laravel-app (web), queue-worker (colas) y scheduler.
#  Build multi-stage: Composer solo existe en la etapa de construcción.
# ============================================================================

# --- Etapa 1: dependencias PHP con Composer -------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
# --no-dev: sin herramientas de desarrollo; --no-scripts: artisan aún no existe
RUN composer install --no-dev --no-interaction --no-progress --no-scripts \
        --prefer-dist --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# --- Etapa 2: imagen final ------------------------------------------------
FROM php:8.3.36-fpm-bookworm

# Extensiones: PostgreSQL, Redis, internacionalización, zip, bcmath y opcache
# (mbstring ya viene compilada en la imagen oficial)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libicu-dev libzip-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql intl zip bcmath opcache \
 && pecl install redis-6.2.0 \
 && docker-php-ext-enable redis \
 && apt-get purge -y --auto-remove libicu-dev libzip-dev \
 && apt-get install -y --no-install-recommends libpq5 libicu72 libzip4 \
 && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-sanluquitas.ini
COPY docker/entrypoint.sh /usr/local/bin/sanluquitas-entrypoint
RUN chmod +x /usr/local/bin/sanluquitas-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data --from=vendor /app /var/www/html

# Laravel solo escribe en storage/ y bootstrap/cache/
RUN chown -R www-data:www-data storage bootstrap/cache

# Sin privilegios de root en tiempo de ejecución
USER www-data

ENTRYPOINT ["sanluquitas-entrypoint"]
CMD ["php-fpm"]
