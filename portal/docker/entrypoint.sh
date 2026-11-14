#!/bin/sh
# ============================================================================
#  Arranque de los contenedores del Portal.
#  CONTAINER_ROLE=app       → migra la BD, carga datos de ejemplo y cachea config
#  CONTAINER_ROLE=worker    → solo cachea config (queue:work)
#  CONTAINER_ROLE=scheduler → solo cachea config (schedule:work)
# ============================================================================
set -e
cd /var/www/html

# La carpeta storage es un volumen: se asegura su estructura
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions \
         storage/framework/views storage/logs

if [ "${CONTAINER_ROLE:-app}" = "app" ]; then
    php artisan migrate --force                 # aplica migraciones pendientes
    php artisan db:seed --force                 # datos de ejemplo (solo si las tablas están vacías)
    php artisan storage:link --force >/dev/null 2>&1 || true
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
