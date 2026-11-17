#!/usr/bin/env bash
# Aplica (o quita) la apariencia personalizada de Moodle sin reconstruir la imagen.
#   bash scripts/aplicar-tema-moodle.sh           # escudo + tema (moodle/eva/tema.scss)
#   bash scripts/aplicar-tema-moodle.sh --quitar  # vuelve al tema original
set -euo pipefail
cd "$(dirname "$0")/.."

if [ "${1:-}" = "--quitar" ]; then
  CSS=""
else
  CSS="$(cat moodle/eva/tema.scss)"
  # el escudo se copia al contenedor para guardarlo como logo del sitio
  docker compose cp moodle/eva/escudo.png moodle:/tmp/escudo.png
fi

printf '%s' "$CSS" | docker compose exec -T moodle php -r '
define("CLI_SCRIPT", true);
require("/var/www/moodle/config.php");
$scss = stream_get_contents(STDIN);
if ($scss !== "" && is_readable("/tmp/escudo.png")) {
    $ctx = context_system::instance();
    $fs = get_file_storage();
    $fs->delete_area_files($ctx->id, "core_admin", "logo");
    $fs->create_file_from_pathname(["contextid" => $ctx->id, "component" => "core_admin",
        "filearea" => "logo", "itemid" => 0, "filepath" => "/", "filename" => "escudo.png"], "/tmp/escudo.png");
    set_config("logo", "/escudo.png", "core_admin");
}
set_config("scss", $scss, "theme_boost");
theme_reset_all_caches();
purge_all_caches();
echo $scss === "" ? "Tema original restaurado.\n" : "Tema San Luquitas aplicado (" . strlen($scss) . " caracteres).\n";
'
echo "Recarga https://moodle.sanluquitas.local con Ctrl+F5."
