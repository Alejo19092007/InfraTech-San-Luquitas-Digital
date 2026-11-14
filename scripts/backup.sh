#!/usr/bin/env bash
# ============================================================================
#  Respaldo diario de San Luquitas Digital
#  - pg_dump (formato custom) de moodle_db y laravel_db, por separado
#  - tar.gz de los volúmenes moodledata y laravel_storage
#  - .env y config.php de Moodle cifrados con GPG (AES-256)
#  - sumas SHA-256 para verificar la integridad
#  - borra los respaldos de más de 7 días
#  Uso:   scripts/backup.sh            (cron: todos los días a las 02:00)
#  Salida: /srv/backups/AAAA-MM-DD/
# ============================================================================
set -euo pipefail

PROYECTO_DIR="$(cd "$(dirname "$0")/.." && pwd)"
BACKUP_ROOT="${BACKUP_ROOT:-/srv/backups}"
RETENCION_DIAS="${RETENCION_DIAS:-7}"
FECHA="$(date +%F)"
DEST="$BACKUP_ROOT/$FECHA"
PROYECTO="sanluquitas"                       # prefijo de los volúmenes (name: en compose.yaml)
IMAGEN_TAR="sanluquitas/portal:1.0"          # imagen local con GNU tar

log() { echo "[$(date '+%F %T')] $*"; }
trap 'log "ERROR en la línea $LINENO: el respaldo NO se completó"; exit 1' ERR

cd "$PROYECTO_DIR"
# Variables del .env (superusuario de PostgreSQL y clave de cifrado)
set -a; . ./.env; set +a
: "${POSTGRES_SUPERUSER:?falta en .env}" "${BACKUP_PASSPHRASE:?falta en .env}"

mkdir -p "$DEST"
chmod 700 "$BACKUP_ROOT" "$DEST"
log "Inicio del respaldo en $DEST"

# 1) Bases de datos: un archivo por BD para poder restaurarlas por separado
for db in moodle_db laravel_db; do
    docker compose exec -T postgres pg_dump -U "$POSTGRES_SUPERUSER" -Fc "$db" > "$DEST/$db.dump"
    log "  BD $db → $db.dump ($(du -h "$DEST/$db.dump" | cut -f1))"
done

# 2) Volúmenes de archivos (se copian en modo solo lectura)
for vol in moodledata laravel_storage; do
    if docker volume inspect "${PROYECTO}_${vol}" >/dev/null 2>&1; then
        # tar escribe en la salida estándar: el archivo lo crea sladmin, no root
        docker run --rm --user 0 --entrypoint tar \
            -v "${PROYECTO}_${vol}:/datos:ro" \
            "$IMAGEN_TAR" --numeric-owner -czf - -C /datos . > "$DEST/$vol.tar.gz"
        log "  Volumen $vol → $vol.tar.gz ($(du -h "$DEST/$vol.tar.gz" | cut -f1))"
    else
        log "  Volumen $vol no existe todavía: se omite"
    fi
done

# 3) Secretos cifrados (nunca se guardan en texto plano dentro del respaldo)
cifrar() {  # cifrar <archivo_salida.gpg>   (lee de la entrada estándar)
    gpg --batch --yes --quiet --symmetric --cipher-algo AES256 \
        --pinentry-mode loopback --passphrase-fd 3 --output "$1" 3<<<"$BACKUP_PASSPHRASE"
}
cifrar "$DEST/env.gpg" < .env
log "  .env → env.gpg (cifrado)"
if docker volume inspect "${PROYECTO}_moodle_code" >/dev/null 2>&1 \
   && docker run --rm --user 0 --entrypoint test -v "${PROYECTO}_moodle_code:/m:ro" "$IMAGEN_TAR" -f /m/config.php; then
    docker run --rm --user 0 --entrypoint cat -v "${PROYECTO}_moodle_code:/m:ro" "$IMAGEN_TAR" /m/config.php \
        | cifrar "$DEST/moodle-config.php.gpg"
    log "  config.php de Moodle → moodle-config.php.gpg (cifrado)"
fi

# 4) Integridad
( cd "$DEST" && sha256sum -- * > SHA256SUMS )
chmod 600 "$DEST"/*

# 5) Retención: se borran los respaldos con más de N días
find "$BACKUP_ROOT" -mindepth 1 -maxdepth 1 -type d -name '20??-??-??' -mtime +"$RETENCION_DIAS" -print -exec rm -rf {} + \
    | sed 's/^/  Borrado por antigüedad: /' || true

log "Respaldo completo: $(du -sh "$DEST" | cut -f1) en $DEST"
