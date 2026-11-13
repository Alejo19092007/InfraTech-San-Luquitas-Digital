# Evidencias — Integrante 3: Docker, Portal y respaldo

Responsable: Alejandro Moreno · Rama `alejandro-moreno/docker-portal-respaldo`
Insumo para los apartados **6**, **11**, **12** y **13 (respaldo)** del documento.

## 1. Despliegue en el servidor `sanluquitas-srv`

### Código y variables de entorno — `01-rama-y-env-en-servidor.png`
```bash
cd ~/sanluquitas && git fetch origin && git checkout alejandro-moreno/docker-portal-respaldo && git pull
cp .env.example .env
# Contraseñas aleatorias para cada servicio (el .env nunca se sube al repositorio)
sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|; \
        s|^POSTGRES_SUPERUSER_PASSWORD=.*|POSTGRES_SUPERUSER_PASSWORD=$(openssl rand -hex 16)|; \
        s|^MOODLE_DB_PASSWORD=.*|MOODLE_DB_PASSWORD=$(openssl rand -hex 16)|; \
        s|^LARAVEL_DB_PASSWORD=.*|LARAVEL_DB_PASSWORD=$(openssl rand -hex 16)|; \
        s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=$(openssl rand -hex 16)|; \
        s|^BACKUP_PASSPHRASE=.*|BACKUP_PASSPHRASE=$(openssl rand -hex 16)|" .env
chmod 600 .env        # solo el dueño puede leer los secretos
```

### Construcción y arranque — `02-compose-up-build.png`
```bash
docker compose up -d --build
```
| Elemento | Resultado |
|---|---|
| Imagen `postgres:16.16-alpine` | Descargada |
| Imagen `sanluquitas/portal:1.0` | Construida (multi-stage: Composer + PHP 8.3-FPM) |
| Volúmenes | `pg_data`, `redis_data`, `moodle_code`, `laravel_storage` creados |
| Redes | `frontend`, `backend` (interna), `egress` creadas |
| `postgres`, `redis` | **Healthy** (healthcheck) antes de arrancar las aplicaciones |
| `laravel-app`, `queue-worker`, `scheduler`, `nginx` | Iniciados |

Los servicios de Moodle (perfil `moodle`) y `cloudflared` (perfil `tunnel`) no se levantan hasta que Julian y Samuel integren su parte.

### Error encontrado y solución — `03-error-dependencias-php-8-4.png`
**Síntoma:** `laravel-app`, `queue-worker` y `scheduler` quedaban en `Restarting (255)`. El log mostraba:
`Composer detected issues in your platform: Your Composer dependencies require a PHP version ">= 8.4.1". You are running 8.3.36.`

**Causa:** la etapa de construcción usa la imagen `composer:2`, que trae una PHP más nueva que la imagen final. Con `--ignore-platform-reqs`, Composer resolvía las dependencias para *su* PHP e instalaba paquetes que exigen PHP 8.4.

**Solución:** fijar en `portal/composer.json` la plataforma de destino y dejar de ignorar la versión de PHP (solo se ignoran las extensiones, que se instalan en la etapa final):
```json
"config": { "platform": { "php": "8.3.36" } }
```
```dockerfile
RUN composer install --no-dev ... --ignore-platform-req=ext-*
```
```bash
git pull && docker compose up -d --build
```

### Arranque correcto — `04-portal-arriba-migraciones-y-seeders.png`
```bash
docker compose ps                         # los 6 servicios en "Up"; postgres y redis "healthy"
docker compose logs laravel-app --tail=30 # migraciones, seeders, cachés y "ready to handle connections"
```
El aviso `'user' directive is ignored when FPM is not running as root` confirma que PHP-FPM corre como `www-data`, sin privilegios de root.

### Portal en el navegador — `05-portal-inicio-https.png`
`https://portal.sanluquitas.local` muestra la página de inicio con las últimas novedades leídas de PostgreSQL, el acceso a Moodle y los recursos. Bootstrap se sirve desde el propio servidor, así que la página carga aunque no haya internet.
