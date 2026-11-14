#!/bin/sh
# ============================================================================
#  Se ejecuta UNA sola vez, cuando el volumen pg_data está vacío.
#  Crea una base de datos y un usuario por aplicación y las aísla entre sí.
#  Las contraseñas llegan como variables de entorno desde el .env (compose.yaml).
# ============================================================================
set -eu

psql -v ON_ERROR_STOP=1 \
     --username "$POSTGRES_USER" --dbname postgres \
     -v moodle_pass="$MOODLE_DB_PASSWORD" \
     -v laravel_pass="$LARAVEL_DB_PASSWORD" <<'SQL'
-- Usuarios de aplicación: sin privilegios de administración
CREATE ROLE moodle_user  LOGIN PASSWORD :'moodle_pass'  NOSUPERUSER NOCREATEDB NOCREATEROLE;
CREATE ROLE laravel_user LOGIN PASSWORD :'laravel_pass' NOSUPERUSER NOCREATEDB NOCREATEROLE;

-- Cada base de datos pertenece a su usuario (Moodle exige UTF8)
CREATE DATABASE moodle_db  OWNER moodle_user  ENCODING 'UTF8' TEMPLATE template0;
CREATE DATABASE laravel_db OWNER laravel_user ENCODING 'UTF8' TEMPLATE template0;

-- Aislamiento: por defecto cualquier rol puede conectarse a cualquier BD (PUBLIC).
-- Se quita ese permiso y solo se concede al dueño.
REVOKE CONNECT ON DATABASE moodle_db  FROM PUBLIC;
REVOKE CONNECT ON DATABASE laravel_db FROM PUBLIC;
GRANT  CONNECT ON DATABASE moodle_db  TO moodle_user;
GRANT  CONNECT ON DATABASE laravel_db TO laravel_user;
SQL

# Esquema public: solo el dueño de cada BD puede crear tablas
for db in moodle_db laravel_db; do
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$db" \
       -c "REVOKE ALL ON SCHEMA public FROM PUBLIC;" \
       -c "ALTER SCHEMA public OWNER TO ${db%_db}_user;"
done

echo "San Luquitas: moodle_db y laravel_db creadas y aisladas."
