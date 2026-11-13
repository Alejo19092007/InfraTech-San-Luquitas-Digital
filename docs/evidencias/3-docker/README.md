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
