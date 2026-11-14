#!/usr/bin/env bash
# ============================================================================
#  Descarga UNA vez las fotografías ilustrativas del Portal y las deja en
#  portal/public/img/fotos/, desde donde Nginx las sirve sin necesidad de
#  internet. Licencias y créditos: config/portal.php (clave "fotos").
#  Uso:  scripts/descargar-fotos.sh
# ============================================================================
set -euo pipefail
DESTINO="$(cd "$(dirname "$0")/.." && pwd)/portal/public/img/fotos"
AGENTE="SanLuquitasPortal/1.0 (proyecto academico; contacto en el repositorio)"
mkdir -p "$DESTINO"

descargar() {  # descargar <archivo> <url>
    if [[ -s "$DESTINO/$1" ]]; then echo "  ya existe: $1"; return; fi
    curl -fsSL -A "$AGENTE" --retry 3 -o "$DESTINO/$1" "$2" && echo "  descargada: $1 ($(du -h "$DESTINO/$1" | cut -f1))"
}

echo "Descargando fotos en $DESTINO"
# Charlotte Kesl / Banco Mundial — CC BY-NC-ND 2.0
descargar estudiante-corre-a-clase.jpg   https://live.staticflickr.com/1036/4725004476_795a5760f8_b.jpg
descargar clase-de-computadores.jpg      https://live.staticflickr.com/1087/4725033296_f3b023b984_b.jpg
descargar estudiantes-colegio-rural.jpg  https://live.staticflickr.com/1279/4699804389_a14ae1b081_b.jpg
descargar estudiante-zona-rural.jpg      https://live.staticflickr.com/1062/4725000160_4dfbda8e8c_b.jpg
# Neil Palmer (CIAT) — CC BY-SA 2.0, Wikimedia Commons
descargar paisaje-rural.jpg "https://upload.wikimedia.org/wikipedia/commons/thumb/7/78/NP_rural_colombia_%285867722386%29.jpg/1280px-NP_rural_colombia_%285867722386%29.jpg"
echo "Listo."
