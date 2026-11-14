# Cabeceras de seguridad comunes. Se incluyen en cada bloque server.
# Ojo: si un "location" define su propio add_header, deja de heredar estas.
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
# Sin HSTS a propósito: con certificado autofirmado en la LAN puede bloquear
# el acceso de los navegadores si el certificado cambia.
