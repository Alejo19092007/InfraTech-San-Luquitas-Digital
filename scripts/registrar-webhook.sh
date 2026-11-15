#!/usr/bin/env bash
# Registra en Meta la URL actual de ngrok como webhook de WhatsApp.
# Uso (en la VM, tras levantar el perfil tunnel):  ./scripts/registrar-webhook.sh
# Hay que repetirlo cada vez que ngrok se reinicie, porque la URL aleatoria cambia.
# Lee los valores de .env (nada de secretos en el repo). Requiere:
#   WHATSAPP_APP_ID, WHATSAPP_APP_SECRET, WHATSAPP_VERIFY_TOKEN, WHATSAPP_API_VERSION (opcional)
set -euo pipefail
cd "$(dirname "$0")/.."

leer() { grep -E "^$1=" .env | tail -1 | cut -d= -f2- | tr -d '\r"' ; }
APP_ID=$(leer WHATSAPP_APP_ID)
APP_SECRET=$(leer WHATSAPP_APP_SECRET)
VERIFY=$(leer WHATSAPP_VERIFY_TOKEN)
VERSION=$(leer WHATSAPP_API_VERSION); VERSION=${VERSION:-v21.0}

for v in APP_ID APP_SECRET VERIFY; do
  [ -n "${!v}" ] || { echo "Falta ${v} en .env (WHATSAPP_APP_ID / WHATSAPP_APP_SECRET / WHATSAPP_VERIFY_TOKEN)"; exit 1; }
done

URL=$(docker compose logs ngrok 2>/dev/null | grep -o 'url=https://[^ ]*' | tail -1 | cut -d= -f2-)
[ -n "$URL" ] || { echo "No encuentro la URL de ngrok. ¿Está arriba el servicio ngrok?"; exit 1; }
echo "URL del túnel: $URL"

# Meta verifica la URL antes de guardarla, así que primero comprobamos que responde.
RESP=$(curl -s "$URL/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=$VERIFY&hub.challenge=ok123")
[ "$RESP" = "ok123" ] || { echo "El webhook no responde bien desde internet (respuesta: '$RESP')."; exit 1; }

curl -s -X POST "https://graph.facebook.com/$VERSION/$APP_ID/subscriptions" \
  --data-urlencode "object=whatsapp_business_account" \
  --data-urlencode "callback_url=$URL/webhook/whatsapp" \
  --data-urlencode "verify_token=$VERIFY" \
  --data-urlencode "fields=messages" \
  --data-urlencode "access_token=$APP_ID|$APP_SECRET"
echo
echo "Si arriba dice {\"success\":true}, el webhook quedó registrado."
