# Bot de WhatsApp — instrucciones para montarlo en la VM

**Para:** quien tenga la máquina virtual `sanluquitas-srv` y vaya a subir los cambios (hoy, Alejandro Jimenez).
**De:** Samuel Guzmán (bloque 4). El bot se desarrolla fuera de la VM porque no tenemos una copia del `.ova`; todo se arma en la máquina que se va a entregar.

> **Estado al 14/11/2026:** el código del bot ya está completo (login con roles, panel `/tareas`, vinculación, webhook, colas, recordatorios y simulador) y tiene pruebas automáticas. Lo que **todavía no se ha probado en la VM** es la parte que depende de internet: ngrok y la API de Meta. Si un paso falla o hay que cambiarlo, corrige este archivo.

## Qué está listo y qué falta

| Pieza | Estado | Responsable |
|---|---|---|
| Entrada del webhook en Nginx (`nginx:8081`, solo `/webhook/whatsapp`) | Lista | Alejandro Jimenez |
| Proyecto Laravel en `portal/`, `compose.yaml`, servicio `ngrok`, `queue-worker` y `scheduler` | Listo | Alejandro Moreno |
| Intérprete de comandos del bot | Listo y probado | Samuel Guzmán |
| Login con roles, gestión de usuarios, panel `/tareas` y vinculación del número | Listo (rama `samuel-guzman/usuarios-tareas-bot`) | Samuel Guzmán |
| Webhook `GET/POST /webhook/whatsapp` con firma, jobs `whatsapp-in` / `whatsapp-out`, recordatorio diario | Listo | Samuel Guzmán |
| Cuenta de ngrok (dominio fijo) y app de Meta con el número de prueba | **Falta** (hay que hacerlo con las cuentas reales) | Samuel Guzmán |

Los pasos 1 a 3 y 5 se pueden hacer ya. Los pasos 4 y 6 necesitan la cuenta de ngrok y la app de Meta.

---

## Paso 1 — Traer el código a la VM

```bash
cd ~/InfraTech-San-Luquitas-Digital      # carpeta del repo en la VM
git switch main
git pull                                 # trae lo que ya esté unido a main
```

El repositorio se renombró. Si `git pull` avisa que "se movió", actualiza la dirección:

```bash
git remote set-url origin https://github.com/Alejo19092007/InfraTech-San-Luquitas-Digital.git
```

## Paso 2 — Comprobar que la VM sale a internet

ngrok y la API de Meta solo necesitan conexiones **de salida**. No hay que abrir ningún puerto nuevo en UFW ni en el router.

```bash
curl -sI https://graph.facebook.com | head -1     # debe responder HTTP/2 ...
curl -sI https://ngrok.com | head -1
```

Las reglas de `DOCKER-USER` ya dejan pasar las respuestas del tráfico de salida de los contenedores, así que no hay que tocarlas.

## Paso 3 — Variables secretas en el `.env` de la VM

Samuel entrega estos valores **por un canal privado** (nunca por el repo, ni en capturas, ni pegados en una IA):

```dotenv
# ngrok
NGROK_AUTHTOKEN=            # token de la cuenta de ngrok
NGROK_DOMAIN=               # dominio fijo gratuito, ej. algo-fijo.ngrok-free.app (sin https://)

# WhatsApp Cloud API (Meta)
WHATSAPP_TOKEN=             # token de acceso
WHATSAPP_PHONE_NUMBER_ID=   # id del número de prueba
WHATSAPP_APP_SECRET=        # secreto de la app (valida la firma del webhook)
WHATSAPP_VERIFY_TOKEN=      # texto inventado por nosotros para la verificación
```

```bash
nano .env            # pegar los valores
chmod 600 .env       # solo el dueño puede leerlo
git status --short   # .env NO debe aparecer (está en .gitignore)
```

**Ojo con el token de Meta:** el token temporal de la consola dura unas 24 horas. Para la sustentación hay que generar uno nuevo ese mismo día o usar un token permanente de "usuario del sistema".

## Paso 4 — Servicio `ngrok` en el `compose.yaml`

El servicio ya está en el `compose.yaml` (perfil `tunnel`, no arranca solo). Para referencia, esto es lo que hace:

```yaml
  ngrok:
    image: ngrok/ngrok:<versión fija>      # no usar latest
    restart: unless-stopped
    command: http nginx:8081 --url=${NGROK_DOMAIN}
    environment:
      NGROK_AUTHTOKEN: ${NGROK_AUTHTOKEN}
    networks: [frontend, egress]
    depends_on: [nginx]
    # Sin "ports:": el túnel es una conexión de salida
```

- Apunta a `nginx:8081`, la entrada que solo responde `/webhook/whatsapp`. **No** apuntarlo al 443: expondría el portal y el login a internet.
- Si el contenedor no acepta `--url`, revisar `docker compose run --rm ngrok http --help` (en versiones anteriores la opción era `--domain`).

```bash
docker compose --profile tunnel up -d ngrok
docker compose logs --tail=20 ngrok      # debe decir que el túnel está en línea, sin errores de autenticación
```

## Paso 5 — Servicios del bot

Usan la misma imagen que `laravel-app` y ya están en el `compose.yaml`:

| Servicio | Qué hace |
|---|---|
| `queue-worker` | Procesa `whatsapp-in` (interpreta el mensaje) y `whatsapp-out` (responde con reintentos y espera creciente) |
| `scheduler` | Cada día a las 6:30 a. m. (hora de Bogotá) avisa por WhatsApp de las tareas que vencen hoy o mañana, y a las 3:00 a. m. borra códigos de vinculación vencidos |

```bash
git switch main && git pull                      # cuando la rama del bot ya esté unida a main
docker compose build laravel-app                 # reconstruye la imagen con el código nuevo
docker compose up -d laravel-app queue-worker scheduler
docker compose ps        # todos "running"; postgres y redis "healthy"
```

Al arrancar, `laravel-app` aplica las migraciones y carga las **cuentas ficticias** de demostración (el mismo arranque que ya cargaba las noticias):

| Rol | Correo | Número ficticio (ya verificado) |
|---|---|---|
| Administración | `secretaria@sanluquitas.local` | — |
| Docente (grados 4 y 5) | `docente.martha@sanluquitas.local` | +573000000011 |
| Docente (grados 1 a 3) | `docente.jorge@sanluquitas.local` | +573000000012 |
| Estudiante, grado 5 | `est.ana@sanluquitas.local` | +573000000001 |
| Estudiante, grado 4 | `est.juan@sanluquitas.local` | +573000000002 |
| Acudiente de Ana y Sofía | `acudiente.luz@sanluquitas.local` | +573000000021 |

La contraseña de todas es la de `PORTAL_CLAVE_DEMO` del `.env` (si está vacía, `SanLuquitas#2026`). Para usar el bot con un celular real, cada persona entra al portal → **Mi WhatsApp**, escribe su número y envía el código por WhatsApp.

**Probar el bot sin Meta ni internet** (sirve de respaldo para la demostración):

```bash
docker compose exec -e HOME=/tmp laravel-app php artisan whatsapp:simular +573000000001 "TAREA Matemáticas | mañana | Página 32"
docker compose exec -e HOME=/tmp laravel-app php artisan whatsapp:simular +573000000001 "MIS TAREAS"
docker compose exec -e HOME=/tmp laravel-app php artisan tareas:recordatorios --simular
```

Las pruebas automáticas (en el contenedor, PHP 8.3) se corren con `docker compose exec laravel-app php artisan test`; las dependencias de desarrollo no vienen en la imagen de producción, así que si falla por `phpunit`, se corren en el equipo de desarrollo con `composer install && php artisan test`.

## Paso 6 — Registrar el webhook en Meta

En la consola de Meta for Developers → la app → WhatsApp → Configuración → Webhook:

| Campo | Valor |
|---|---|
| URL de devolución de llamada | `https://<NGROK_DOMAIN>/webhook/whatsapp` |
| Token de verificación | el mismo valor de `WHATSAPP_VERIFY_TOKEN` |
| Campos suscritos | `messages` |

Al guardar, Meta hace una petición de verificación; si el servidor responde bien, queda en verde. Los celulares que van a escribirle al bot deben estar agregados como destinatarios del número de prueba.

## Paso 7 — Comprobaciones

```bash
# 1. La verificación responde desde internet (debe devolver: hola123)
curl -s "https://$NGROK_DOMAIN/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=$WHATSAPP_VERIFY_TOKEN&hub.challenge=hola123"

# 2. El resto del portal NO es visible por el túnel (debe dar 404)
curl -s -o /dev/null -w '%{http_code}\n' "https://$NGROK_DOMAIN/ingresar"

# 3. Una petición sin firma válida se rechaza (debe dar 403)
curl -s -o /dev/null -w '%{http_code}\n' -X POST "https://$NGROK_DOMAIN/webhook/whatsapp" -d '{}'

# 4. El bot responde sin pasar por Meta (debe mostrar la lista de comandos)
docker compose exec -e HOME=/tmp laravel-app php artisan whatsapp:simular +573000000001 AYUDA

# 5. Cola de entrada (0 cuando todo está procesado)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" llen queues:whatsapp-in
```

## Paso 8 — Evidencias que hay que capturar

Guardarlas en `docs/evidencias/4-bot/` (capturas de la terminal o del celular, sin que se vean tokens):

- [ ] `docker compose ps` con `ngrok`, `queue-worker` y `scheduler` en ejecución
- [ ] Login y panel `/tareas` con cada rol (el estudiante solo ve lo suyo; el acudiente, lo de sus hijos; el docente, sus grados)
- [ ] Pantalla «Mi WhatsApp» con el código y la respuesta «Número vinculado»
- [ ] Webhook verificado (en verde) en la consola de Meta
- [ ] Las comprobaciones 1, 2 y 3 del paso 7
- [ ] **Modo avión:** enviar `TAREA ...` con el celular en modo avión → quitarlo → la tarea aparece en `/tareas`
- [ ] **Número no vinculado:** el bot responde con el rechazo
- [ ] **Cola resistente:** `docker compose stop queue-worker` → enviar 3 mensajes → `llen queues:whatsapp-in` muestra 3 → `docker compose start queue-worker` → vuelve a 0 y aparecen las 3 tareas
- [ ] `docker compose logs --tail=50 queue-worker` con mensajes procesados

## Antes de exportar el `.ova`

- Los valores de `NGROK_AUTHTOKEN` y `WHATSAPP_*` son secretos reales: **se borran del `.env`** antes de exportar (dejar las variables vacías) y se vuelven a poner el día de la demo.
- Sin esos valores, Moodle y el Portal funcionan igual en la red local; solo el bot queda inactivo.
- Anotar en `docs/INSTRUCCIONES_OVA.md` que el bot necesita internet y estos valores para funcionar.

## Si algo falla

| Síntoma | Qué revisar |
|---|---|
| `ngrok` se reinicia sin parar | `docker compose logs ngrok`: token mal copiado o dominio que no es de esa cuenta |
| Meta no verifica el webhook | Que el paso 7.1 devuelva `hola123`; que `WHATSAPP_VERIFY_TOKEN` sea igual en `.env` y en Meta; `docker compose logs nginx laravel-app` |
| Llegan mensajes pero no se crean tareas | `docker compose logs queue-worker`; que el número esté vinculado (portal → Mi WhatsApp); `llen queues:whatsapp-in`; la tabla `mensajes_whatsapp` guarda el estado de cada mensaje (`recibido`, `procesado`, `rechazado`, `enviado`, `fallido`) |
| El bot no responde | Token de Meta vencido (dura 24 h) o el celular no está en la lista de destinatarios |
| 502 en el webhook | `laravel-app` no está en ejecución o no está en la red `frontend` |
