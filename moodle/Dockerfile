# syntax=docker/dockerfile:1
# ============================================================================
#  Imagen de Moodle 5.1.9 para San Luquitas Digital (PHP-FPM 8.3)
#  La usan 2 servicios: moodle (PHP-FPM) y moodle-cron (tareas programadas).
#  Moodle 5.1: PHP 8.2–8.4 y PostgreSQL ≥ 15 (el proyecto usa PHP 8.3 y PostgreSQL 16).
#  Para cambiar de versión: modificar MOODLE_VERSION y revisar README.md.
# ============================================================================
ARG MOODLE_VERSION=5.1.9

# --- Etapa 1: código de Moodle + dependencias PHP (Composer) ---------------
FROM composer:2 AS codigo
ARG MOODLE_VERSION
WORKDIR /var/www/moodle
# El código oficial se toma de la etiqueta del repositorio de Moodle en GitHub.
ADD https://github.com/moodle/moodle/archive/refs/tags/v${MOODLE_VERSION}.tar.gz /tmp/moodle.tar.gz
RUN tar -xzf /tmp/moodle.tar.gz --strip-components=1 -C /var/www/moodle && rm /tmp/moodle.tar.gz
# Las extensiones se instalan en la etapa 2: aquí solo se resuelven las librerías.
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --classmap-authoritative --ignore-platform-req=ext-*

# --- Etapa 2: imagen final ---------------------------------------------------
FROM php:8.3.36-fpm-bookworm

# Extensiones que exige Moodle: PostgreSQL, intl, zip, gd, soap, exif, opcache…
# (mbstring, curl, xml, sodium, iconv y ctype ya vienen en la imagen oficial)
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libpq-dev libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl zip gd soap exif opcache \
 && apt-get purge -y --auto-remove libpq-dev libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
 && apt-get install -y --no-install-recommends libpq5 libicu72 libzip4 libpng16-16 libjpeg62-turbo libfreetype6 libxml2 \
 && rm -rf /var/lib/apt/lists/*

COPY php.ini /usr/local/etc/php/conf.d/zz-sanluquitas.ini
COPY docker/entrypoint.sh /usr/local/bin/moodle-entrypoint
COPY eva /opt/eva
RUN chmod +x /usr/local/bin/moodle-entrypoint

# El código queda en /var/www/moodle (volumen compartido con Nginx, solo lectura allí)
# y los archivos de los usuarios en /var/www/moodledata, FUERA de la raíz web.
COPY --chown=www-data:www-data --from=codigo /var/www/moodle /var/www/moodle
RUN mkdir -p /var/www/moodledata && chown www-data:www-data /var/www/moodledata

WORKDIR /var/www/moodle
USER www-data
ENTRYPOINT ["moodle-entrypoint"]
CMD ["php-fpm"]
