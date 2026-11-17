#!/usr/bin/env bash
# Aplica (o quita) la apariencia personalizada de Moodle sin reconstruir la imagen.
#   bash scripts/aplicar-tema-moodle.sh           # aplica moodle/eva/tema.scss
#   bash scripts/aplicar-tema-moodle.sh --quitar  # vuelve al tema original
set -euo pipefail
cd "$(dirname "$0")/.."

if [ "${1:-}" = "--quitar" ]; then
  CSS=""
else
  CSS="$(cat moodle/eva/tema.scss)"
fi

printf '%s' "$CSS" | docker compose exec -T moodle php -r '
define("CLI_SCRIPT", true);
require("/var/www/moodle/config.php");
$scss = stream_get_contents(STDIN);
set_config("scss", $scss, "theme_boost");
theme_reset_all_caches();
echo $scss === "" ? "Tema original restaurado.\n" : "Tema San Luquitas aplicado (" . strlen($scss) . " caracteres).\n";
'
echo "Recarga https://moodle.sanluquitas.local con Ctrl+F5."
