# Nginx — acuerdo de integración con `compose.yaml`

Responsable de esta carpeta: **Alejandro Jimenez**.
Dueño de `compose.yaml`: **Alejandro Moreno**. Este archivo fija lo que el compose debe respetar para que Nginx, UFW y Docker funcionen juntos.

## Decisión 1 — Exposición de puertos y firewall

**Problema.** Docker publica puertos escribiendo reglas propias de iptables (NAT + cadena `FORWARD`), que se evalúan antes que las de UFW. Un `ports: "80:80"` quedaría abierto a cualquier red aunque UFW diga "solo LAN".

**Opciones evaluadas**

| Opción | Resultado |
|---|---|
| Publicar con IP fija (`192.168.1.50:80:80`) | Amarra el compose a una IP; al importar el `.ova` en otra red hay que editarlo. Además no filtra por origen. |
| Script `ufw-docker` | Funciona, pero añade una herramienta de terceros sin necesidad. |
| `network_mode: host` en Nginx | Nginx dejaría de resolver `moodle` y `laravel-app` por nombre en las redes Docker. Descartada. |
| **Reglas en la cadena `DOCKER-USER`** | **Elegida.** Es el mecanismo oficial de Docker para filtrar tráfico hacia contenedores, no toca el compose y protege también ante un puerto publicado por error. |

**Regla adoptada.** En `/etc/ufw/after.rules` del servidor, la cadena `DOCKER-USER` solo deja pasar, desde la interfaz externa `enp0s3`:
- conexiones ya establecidas (respuestas del tráfico de salida de los contenedores);
- conexiones nuevas desde `192.168.1.0/24` hacia los puertos **80 y 443** del host.

Todo lo demás que llegue por `enp0s3` hacia un contenedor se descarta. El tráfico entre contenedores y la salida a internet no se ven afectados.

### Lo que el `compose.yaml` debe cumplir
1. **Solo el servicio `nginx` tiene `ports:`**, con la forma corta, sin IP:
   ```yaml
   ports:
     - "80:80"
     - "443:443"
   ```
2. `postgres`, `redis`, `moodle`, `laravel-app`, `queue-worker`, `scheduler`, `moodle-cron` y `cloudflared` **no llevan `ports:`**. Se comunican por las redes internas usando el nombre del servicio.
3. Si para depurar hace falta abrir un puerto (por ejemplo `5432`), quedará bloqueado desde la LAN por la regla anterior. Usar `docker compose exec` en su lugar.

## Decisión 2 — Montajes del servicio `nginx`

| Origen en el repositorio / volumen | Destino en el contenedor | Modo |
|---|---|---|
| `./nginx/conf.d` | `/etc/nginx/conf.d` | `ro` |
| `./nginx/certs` | `/etc/nginx/certs` | `ro` |
| volumen `moodle_code` | `/var/www/moodle` | `ro` (Nginx sirve `/var/www/moodle/public`, ver Decisión 4) |
| `./portal/public` | `/var/www/html/public` | `ro` |

Las rutas de destino deben coincidir con las que usan los contenedores PHP-FPM (`moodle` en `/var/www/moodle`, `laravel-app` en `/var/www/html`), porque Nginx envía a FastCGI la ruta del script (`SCRIPT_FILENAME`) y PHP-FPM la busca en su propio sistema de archivos.

## Decisión 3 — Nombres que usa la configuración de Nginx

| Elemento | Valor |
|---|---|
| Servicio Moodle (PHP-FPM) | `moodle:9000` |
| Servicio Laravel (PHP-FPM) | `laravel-app:9000` |
| Red compartida con Nginx | `frontend` |
| Sitios | `moodle.sanluquitas.local`, `portal.sanluquitas.local` |
| Webhook WhatsApp | `cloudflared` → `http://nginx:8081/webhook/whatsapp` → Laravel (ver Decisión 5) |
| Imagen | `nginx:1.27-alpine` |

Si alguno de estos nombres cambia en el compose, avisar para ajustar `nginx/conf.d/*.conf`.

## Decisión 4 — Raíz web de Moodle

Desde **Moodle 5.1** solo la carpeta `public/` se publica en el servidor web; `config.php`, `admin/`, `lib/` y el resto del código quedan fuera de la raíz web. Por eso `moodle.conf` usa `root /var/www/moodle/public;` y envía las rutas que no son archivos al router de Moodle (`r.php`).
Si el equipo instala **Moodle 5.0**, se cambia esa línea a `root /var/www/moodle;` (Julian Godoy confirma la versión exacta).

`client_max_body_size 100M` debe coincidir con `upload_max_filesize` y `post_max_size` del `php.ini` de Moodle.

## Decisión 5 — Webhook de WhatsApp aislado en un puerto interno

`cloudflared` no entra por el 443 público del portal. Nginx tiene un bloque aparte en el **puerto 8081**, que **no se publica** al host: solo es alcanzable dentro de la red Docker `frontend`. Ese bloque solo responde `/webhook/whatsapp` (GET de verificación y POST de eventos de Meta) y devuelve 404 para cualquier otra ruta.

Así, lo único del sistema expuesto a internet es el webhook; el portal, el login y Moodle siguen siendo solo de la LAN.

Configuración de ingreso del túnel (Samuel Guzmán): `service: http://nginx:8081`.

## Archivos de esta carpeta

| Archivo | Contenido |
|---|---|
| `conf.d/00-general.conf` | `server_tokens off`, tamaño máximo de subida, TLS 1.2/1.3, buffers de FastCGI |
| `conf.d/includes/security-headers.inc` | Cabeceras `nosniff`, `SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy` |
| `conf.d/10-default.conf` | Redirección 80 → 443 y cierre (444) de peticiones a nombres desconocidos |
| `conf.d/moodle.conf` | `moodle.sanluquitas.local` → FastCGI `moodle:9000`, slash arguments, bloqueo de archivos internos |
| `conf.d/portal.conf` | `portal.sanluquitas.local` → FastCGI `laravel-app:9000` (front controller de Laravel) |
| `conf.d/webhook.conf` | Puerto interno 8081 solo para `/webhook/whatsapp` |
| `certs/` | `sanluquitas.crt` y `sanluquitas.key` (autofirmados). **La llave no se sube**: `*.key` está en `.gitignore` |

La imagen `nginx:1.27-alpine` incluye todos los `*.conf` de `/etc/nginx/conf.d`; el archivo `.inc` no se carga solo, se incluye en cada sitio.

### Verificación de la configuración
Probada con `nginx -t` y peticiones de prueba (sin PHP-FPM detrás, por eso el 502):

| Petición | Resultado |
|---|---|
| `http://portal.sanluquitas.local/novedades` | `301` → `https://portal.sanluquitas.local/novedades` |
| `https://<IP>/` (nombre desconocido) | Conexión cerrada (444) |
| `https://portal.sanluquitas.local/` | `502` + cabeceras de seguridad, `Server: nginx` sin versión |
| `https://moodle.sanluquitas.local/.git/config` | `403` |
| `https://moodle.sanluquitas.local/composer.json` | `404` |
| `http://nginx:8081/login` | `404` (el túnel solo ve el webhook) |
| `http://nginx:8081/webhook/whatsapp` | Se envía a Laravel (`502` sin backend) |

## Al importar el `.ova` en otra red
La regla de `DOCKER-USER` y las de UFW usan `192.168.1.0/24`. Si la red de destino es otra, se cambia esa subred en `/etc/ufw/after.rules` y en `ufw` (ver `docs/INSTRUCCIONES_OVA.md`). El `compose.yaml` no requiere cambios.
