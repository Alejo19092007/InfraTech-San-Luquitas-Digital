#!/usr/bin/env bash
# Crea 4 cursos de ejemplo más en el EVA (lectura, matemáticas, ciencias e inglés).
# Se puede repetir: si un curso ya existe, lo omite. No reconstruye nada.
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose cp moodle/eva/cursos-extra.php moodle:/tmp/cursos-extra.php
docker compose exec -T moodle php /tmp/cursos-extra.php
