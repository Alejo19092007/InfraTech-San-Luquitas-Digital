#!/bin/sh
# ============================================================================
#  Arranque de los contenedores de Moodle.
#  Solo el contenedor web (php-fpm) prepara el sitio; moodle-cron únicamente
#  espera a que exista config.php y ejecuta las tareas programadas.
#   1. Genera config.php desde las variables de entorno (sin secretos en el repo)
#   2. Espera a PostgreSQL
#   3. Si la BD está vacía, instala Moodle por línea de comandos
#   4. Una sola vez, crea el curso, los usuarios y la identidad visual (eva/crear-eva.php)
# ============================================================================
set -e
MOODLE=/var/www/moodle
DATOS=/var/www/moodledata
cd "$MOODLE"

if [ "$1" = "php-fpm" ]; then
    : "${MOODLE_URL:?falta MOODLE_URL}" "${MOODLE_DB_PASSWORD:?falta MOODLE_DB_PASSWORD}" "${MOODLE_ADMIN_PASSWORD:?falta MOODLE_ADMIN_PASSWORD}"

    # --- 1. config.php (se regenera en cada arranque con los valores actuales)
    cat > "$MOODLE/config.php" <<PHP
<?php
unset(\$CFG);
global \$CFG;
\$CFG = new stdClass();
\$CFG->dbtype    = 'pgsql';
\$CFG->dblibrary = 'native';
\$CFG->dbhost    = 'postgres';
\$CFG->dbname    = 'moodle_db';
\$CFG->dbuser    = 'moodle_user';
\$CFG->dbpass    = getenv('MOODLE_DB_PASSWORD');
\$CFG->prefix    = 'mdl_';
\$CFG->dboptions = ['dbpersist' => 0, 'dbport' => 5432, 'dbsocket' => ''];
\$CFG->wwwroot   = '${MOODLE_URL}';
\$CFG->dataroot  = '${DATOS}';
\$CFG->admin     = 'admin';
\$CFG->directorypermissions = 02777;
\$CFG->noemailever = true;   // sin servidor de correo en el laboratorio
require_once(__DIR__ . '/lib/setup.php');
PHP

    # --- 2. Esperar a PostgreSQL (el healthcheck del compose ya lo garantiza; esto es un seguro)
    i=0
    until php -r 'try { new PDO("pgsql:host=postgres;dbname=moodle_db", "moodle_user", getenv("MOODLE_DB_PASSWORD")); } catch (Exception $e) { exit(1); }'; do
        i=$((i+1)); [ "$i" -gt 30 ] && { echo "PostgreSQL no responde"; exit 1; }
        sleep 2
    done

    # --- 3. Instalación inicial (solo si la BD no tiene tablas)
    TABLAS=$(php -r '$p = new PDO("pgsql:host=postgres;dbname=moodle_db", "moodle_user", getenv("MOODLE_DB_PASSWORD")); echo $p->query("SELECT count(*) FROM information_schema.tables WHERE table_name = \$q\$mdl_config\$q\$")->fetchColumn();')
    if [ "$TABLAS" = "0" ]; then
        echo ">> Instalando Moodle (primera vez, tarda unos minutos)…"
        php admin/cli/install_database.php --lang=en --agree-license \
            --adminuser=admin --adminpass="$MOODLE_ADMIN_PASSWORD" \
            --adminemail="admin@sanluquitas.local" --supportemail="soporte@sanluquitas.local" \
            --fullname="San Luquitas Digital" --shortname="SLD" \
            --summary="Aula virtual de la Institución Educativa San Luquitas"
    fi

    # --- 4. Contenido del EVA (una sola vez; la marca queda en moodledata)
    if [ ! -f "$DATOS/.eva-creada" ]; then
        echo ">> Creando el EVA: identidad, usuarios y curso…"
        if php /opt/eva/crear-eva.php; then
            touch "$DATOS/.eva-creada"
        else
            echo "!! crear-eva.php terminó con errores: revisar el mensaje y repetir con 'docker compose restart moodle'"
        fi
    fi
fi

if [ "$1" = "moodle-cron" ]; then
    # Espera a que el contenedor web haya instalado el sitio
    until [ -f "$MOODLE/config.php" ] && php admin/cli/cfg.php --name=version >/dev/null 2>&1; do sleep 10; done
    while true; do
        php admin/cli/cron.php >/dev/null 2>&1 || true
        sleep 60
    done
fi

exec "$@"
