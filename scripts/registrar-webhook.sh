#!/usr/bin/env bash
# Registra en Meta la URL actual de ngrok como webhook de WhatsApp.
# Uso (en la VM, tras levantar el perfil tunnel):  ./scripts/registrar-webhook.sh
# Hay que repetirlo cada vez que ngrok se reinicie, porque la URL aleatoria cambia.
# Lee los valores de .env (nada de secretos en el repo). Requiere:
#   WHATSAPP_APP_ID, WHATSAPP_APP_SECRET, WHATSAPP_VERIFY_TOKEN, WHATSAPP_API_VERSION (opcional)
# Además, si hay WHATSAPP_TOKEN y WHATSAPP_PHONE_NUMBER_ID, reemplaza la URL propia del número
# (Meta le da prioridad sobre la de la app). Esto SUSTITUYE la URL que tuviera ese número.
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

TOKEN=$(leer WHATSAPP_TOKEN); PHONE=$(leer WHATSAPP_PHONE_NUMBER_ID)
if [ -n "$TOKEN" ] && [ -n "$PHONE" ]; then
  echo "Reemplazando la URL propia del número $PHONE..."
  curl -s -X POST "https://graph.facebook.com/$VERSION/$PHONE" \
    -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d "{\"webhook_configuration\":{\"override_callback_uri\":\"$URL/webhook/whatsapp\",\"verify_token\":\"$VERIFY\"}}"
  echo
  echo "URL que Meta usa ahora para el número:"
  curl -s "https://graph.facebook.com/$VERSION/$PHONE?fields=webhook_configuration" -H "Authorization: Bearer $TOKEN"
  echo
fi
echo "Si arriba dice {\"success\":true}, el webhook quedó registrado."
