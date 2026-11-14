#!/usr/bin/env bash
# ============================================================================
#  Descarga las fotografías ilustrativas del Portal desde Wikimedia Commons
#  (solo imágenes con licencia Creative Commons o dominio público) y guarda
#  sus créditos en portal/public/img/fotos/creditos.json, que el Portal lee
#  para mostrar autor y licencia bajo cada foto.
#  Las fotos quedan en el servidor: el portal funciona sin internet.
#
#  Uso:  scripts/descargar-fotos.sh            (no repite las que ya existen)
#        scripts/descargar-fotos.sh --forzar   (vuelve a buscarlas todas)
#  Para cambiar una foto: edita la búsqueda de abajo, borra el archivo y repite.
#  Requiere: curl y python3 (vienen en Ubuntu Server).
# ============================================================================
set -euo pipefail
DESTINO="$(cd "$(dirname "$0")/.." && pwd)/portal/public/img/fotos"
mkdir -p "$DESTINO"
[[ "${1:-}" == "--forzar" ]] && rm -f "$DESTINO"/*.jpg "$DESTINO/creditos.json"

DESTINO="$DESTINO" python3 - <<'PY'
import json, os, re, sys, urllib.parse, urllib.request

DEST = os.environ["DESTINO"]
API = "https://commons.wikimedia.org/w/api.php"
UA = {"User-Agent": "SanLuquitasPortal/1.0 (proyecto academico; contacto en el repositorio)"}
LICENCIAS_OK = re.compile(r"^(CC BY|CC BY-SA|CC0|Public domain|PD)", re.I)

# clave -> (archivo, búsquedas en orden de preferencia, texto alternativo)
FOTOS = {
    "paisaje": ("paisaje-rural.jpg", ["paisaje rural Colombia montañas", "Colombian rural landscape farm mountains"], "Paisaje rural de Colombia con montañas y cultivos"),
    "estudiantes": ("estudiantes.jpg", ["escuela rural Colombia estudiantes", "Colombia rural school students"], "Estudiantes de una escuela rural de Colombia"),
    "a-clase": ("a-clase.jpg", ["niños camino escuela Colombia rural", "Colombia children walking to school rural"], "Niños camino a la escuela en una zona rural de Colombia"),
    "vereda": ("vereda.jpg", ["vereda Colombia campesinos", "Colombia countryside campesino village"], "Vereda y vida campesina en Colombia"),
    "sala-computo": ("sala-computo.jpg", ["Colombia niños computadores escuela", "Colombia students computers classroom"], "Estudiantes usando computadores en una escuela de Colombia"),
}

def api(**p):
    url = API + "?" + urllib.parse.urlencode({**p, "format": "json"})
    with urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=30) as r:
        return json.load(r)

def limpio(t):
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", "", t or "")).strip()

def buscar(consulta, usados):
    d = api(action="query", generator="search", gsrsearch=consulta + " filetype:bitmap", gsrnamespace=6, gsrlimit=30,
            prop="imageinfo", iiprop="url|size|mime|extmetadata", iiurlwidth=1600)
    paginas = sorted(d.get("query", {}).get("pages", {}).values(), key=lambda p: p.get("index", 0))
    for p in paginas:
        i = (p.get("imageinfo") or [{}])[0]
        meta = i.get("extmetadata", {})
        lic = limpio(meta.get("LicenseShortName", {}).get("value"))
        if i.get("mime") != "image/jpeg" or i.get("width", 0) < 1200 or i["width"] < i.get("height", 0) * 1.2:
            continue            # solo horizontales y de buena resolución
        if not LICENCIAS_OK.match(lic) or "NC" in lic or "ND" in lic or p["title"] in usados:
            continue
        return {
            "titulo": p["title"], "url": i.get("thumburl") or i["url"], "licencia": lic,
            "autor": limpio(meta.get("Artist", {}).get("value")) or "Autor no indicado",
            "fuente": i.get("descriptionurl"), "descripcion": limpio(meta.get("ImageDescription", {}).get("value"))[:160],
        }
    return None

ruta_c = os.path.join(DEST, "creditos.json")
creditos = json.load(open(ruta_c)) if os.path.exists(ruta_c) else {}
usados = {c["titulo"] for c in creditos.values()}

for clave, (archivo, consultas, alt) in FOTOS.items():
    if os.path.exists(os.path.join(DEST, archivo)) and clave in creditos:
        print(f"  ya existe: {archivo}"); continue
    for q in consultas:
        try:
            r = buscar(q, usados)
        except Exception as e:
            print(f"  error buscando '{q}': {e}"); continue
        if r: break
    if not r:
        print(f"  SIN RESULTADO para {clave}: el portal mostrará un fondo liso"); continue
    req = urllib.request.Request(r["url"], headers=UA)
    with urllib.request.urlopen(req, timeout=60) as resp, open(os.path.join(DEST, archivo), "wb") as f:
        f.write(resp.read())
    usados.add(r["titulo"])
    creditos[clave] = {**r, "archivo": "img/fotos/" + archivo, "alt": alt}
    print(f"  descargada: {archivo}  <- {r['titulo']} ({r['licencia']})")

json.dump(creditos, open(ruta_c, "w"), ensure_ascii=False, indent=2)
print("Listo. Revisa las fotos elegidas en el navegador; si alguna no te gusta, borra el .jpg y la entrada en creditos.json y repite.")
PY
