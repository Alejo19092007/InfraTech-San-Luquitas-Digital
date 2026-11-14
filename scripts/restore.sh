#!/usr/bin/env bash
# ============================================================================
#  Restauración de un respaldo de San Luquitas Digital
#  Uso:  scripts/restore.sh AAAA-MM-DD [-y]
#  - Verifica las sumas SHA-256
#  - Detiene las aplicaciones (PostgreSQL y Redis siguen arriba)
#  - pg_restore --clean de moodle_db y laravel_db
#  - Reemplaza el contenido de los volúmenes moodledata y laravel_storage
#  - Vuelve a levantar todos los servicios
#  Los secretos cifrados (env.gpg, moodle-config.php.gpg) NO se sobrescriben
#  solos; para recuperarlos:  gpg -d /srv/backups/FECHA/env.gpg > .env
# ============================================================================
set -euo pipefail

FECHA="${1:-}"
CONFIRMAR="${2:-}"
PROYECTO_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_ROOT="${BACKUP_ROOT:-/srv/backups}"
ORIGEN="$BACKUP_ROOT/$FECHA"
PROYECTO="sanluquitas"
IMAGEN_TAR="sanluquitas/portal:1.0"
APPS=(laravel-app queue-worker scheduler moodle moodle-cron)

log() { echo "[$(date '+%F %T')] $*"; }

if [[ ! "$FECHA" =~ ^[0-9]{4}-[0-9]{2}-[0-9]{2}$ ]] || [[ ! -d "$ORIGEN" ]]; then
    echo "Uso: $0 AAAA-MM-DD [-y]"
    echo "Respaldos disponibles:"; ls -1 "$BACKUP_ROOT" 2>/dev/null | sed 's/^/  /'
    exit 1
fi

cd "$PROYECTO_DIR"
set -a; . ./.env; set +a

log "Verificando integridad de $ORIGEN"
( cd "$ORIGEN" && sha256sum --quiet -c SHA256SUMS )

if [[ "$CONFIRMAR" != "-y" ]]; then
    read -r -p "Se reemplazarán las BD y los archivos actuales por los del $FECHA. ¿Continuar? (si/no) " r
    [[ "$r" == "si" ]] || { echo "Cancelado."; exit 1; }
fi

log "Deteniendo aplicaciones"
for app in "${APPS[@]}"; do            # uno por uno: moodle puede no estar activo
    docker compose stop "$app" >/dev/null 2>&1 || true
done

# 1) Bases de datos: --clean borra los objetos antes de recrearlos;
#    --no-owner/--role dejan todo a nombre del usuario de cada aplicación
for db in moodle_db laravel_db; do
    if [[ -f "$ORIGEN/$db.dump" ]]; then
        docker compose exec -T postgres pg_restore -U "$POSTGRES_SUPERUSER" -d "$db" \
            --clean --if-exists --no-owner --role="${db%_db}_user" < "$ORIGEN/$db.dump"
        log "  BD $db restaurada"
    fi
done

# 2) Volúmenes: se vacían y se extrae el respaldo conservando dueños y permisos
for vol in moodledata laravel_storage; do
    if [[ -f "$ORIGEN/$vol.tar.gz" ]]; then
        docker volume create "${PROYECTO}_${vol}" >/dev/null
        docker run --rm --user 0 --entrypoint sh \
            -v "${PROYECTO}_${vol}:/datos" -v "$ORIGEN:/respaldo:ro" "$IMAGEN_TAR" \
            -c "find /datos -mindepth 1 -delete && tar --numeric-owner -xzpf /respaldo/$vol.tar.gz -C /datos"
        log "  Volumen $vol restaurado"
    fi
done

log "Levantando servicios"
docker compose up -d
log "Restauración del $FECHA terminada"
