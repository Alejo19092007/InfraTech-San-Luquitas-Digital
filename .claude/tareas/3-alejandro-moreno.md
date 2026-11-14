# Integrante 3 — Alejandro Moreno
## Infraestructura Docker, Portal Institucional (base) y respaldo

- **Rama:** `alejandro-moreno/docker-portal-respaldo`
- **Contexto general:** [`.claude/CONTEXTO_HACKATON.md`](../CONTEXTO_HACKATON.md) (leer primero; la sección 8 tiene la tabla de servicios)
- **Rúbrica relacionada:** Despliegue de Laravel en Docker (10 %), Implementación técnica (20 %), Seguridad, respaldo y persistencia (10 %)

> Eres el dueño del `compose.yaml`: todos agregan sus servicios a través de ti. **Objetivo: `compose.yaml` base con PostgreSQL y Redis funcionando lo antes posible.**

---

## 1. Tareas técnicas

### 1.1 `compose.yaml` (monolito estructurado)
- [ ] Servicios: `nginx`, `moodle`, `moodle-cron`, `laravel-app`, `queue-worker`, `scheduler`, `postgres`, `redis`, `cloudflared` (los de Moodle y el bot los aportan Julian y Samuel; tú los integras).
- [ ] Redes: `frontend`, `backend` (`internal: true`) y `egress` (salida a internet solo para `laravel-app`, `queue-worker` y `cloudflared`).
- [ ] Volúmenes con nombre: `pg_data`, `redis_data`, `moodle_code`, `moodledata`, `laravel_storage`.
- [ ] `restart: unless-stopped`, `healthcheck` en `postgres` y `redis`, `depends_on: condition: service_healthy`.
- [ ] Imágenes con versión fija, sin `privileged`. **Solo Nginx publica puertos** (coordinar con Alejandro Jimenez lo de UFW y Docker).
- [ ] `.env.example` con **todas** las variables y valores de ejemplo. El `.env` real nunca va al repo.

### 1.2 PostgreSQL 16
- [ ] `postgres/init/01-init.sql`: crea `moodle_db`/`moodle_user` y `laravel_db`/`laravel_user`. Cada BD es propiedad de su usuario y se aplica `REVOKE CONNECT ... FROM PUBLIC` para que un usuario no entre a la BD del otro.
- [ ] Contraseñas tomadas del `.env` (script `.sh` en `docker-entrypoint-initdb.d` si hace falta interpolar).
- [ ] Evidencia: `\l`, `\du` y un intento fallido de `laravel_user` contra `moodle_db`.

### 1.3 Redis 7
- [ ] `requirepass` desde `.env`, `appendonly yes`, sin puerto publicado.
- [ ] Laravel: `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`.

### 1.4 Proyecto Laravel 12 (carpeta `portal/`)
- [ ] `portal/Dockerfile`: `php:8.3-fpm` con `pdo_pgsql`, `redis`, `intl`, `zip`, `bcmath`, `mbstring`, `opcache` + Composer (build multi-stage). Corre como `www-data`.
- [ ] Esta **misma imagen** la usan `laravel-app`, `queue-worker` y `scheduler` (solo cambia el `command`).
- [ ] Script de arranque: `php artisan migrate --force`, `config:cache`, `storage:link`.
- [ ] Layout base (Blade + Tailwind o Bootstrap) con navegación. Samuel lo usará para el login y el panel `/tareas`.
- [ ] Módulos públicos:
  - [ ] **Inicio:** presentación de la IE San Luquitas (ficticia).
  - [ ] **Información institucional:** misión, visión, datos generales y sedes.
  - [ ] **Novedades:** CRUD (crear, listar, ver, editar, eliminar) con validación y paginación. Tabla `novedades`.
  - [ ] **Recursos educativos:** listado de enlaces (Scratch, Code.org…). Tabla `recursos`.
  - [ ] **Enlace a Moodle:** botón visible hacia `https://moodle.sanluquitas.local`.
- [ ] Seeders con datos ficticios (novedades y recursos).
- [ ] La edición de novedades y recursos queda protegida por el rol `admin` cuando Samuel tenga listo el login (coordinar el middleware).

### 1.5 Respaldo y restauración
- [ ] `scripts/backup.sh`:
  - `pg_dump -Fc` de `moodle_db` y de `laravel_db` (por separado).
  - `tar.gz` de `moodledata` y `laravel_storage`.
  - `.env` y `config.php` cifrados con `gpg --symmetric` o `age`.
  - Todo en `/srv/backups/AAAA-MM-DD/`, con borrado de las copias de más de 7 días.
- [ ] `scripts/restore.sh <fecha>`: `pg_restore --clean`, extracción de los volúmenes y `docker compose restart`.
- [ ] Cron diario a las 02:00 (`crontab -e` de `sladmin`) con log en `/var/log/sanluquitas-backup.log`.
- [ ] Copia a disco externo/USB documentada (estrategia 3-2-1).
- [ ] **Prueba de restauración obligatoria:** crear una novedad → respaldar → borrarla → restaurar → la novedad vuelve. Igual con un usuario de Moodle (con Julian). Capturas de cada paso.

---

## 2. Archivos que te pertenecen
`compose.yaml`, `.env.example`, `postgres/**`, `portal/**` (excepto los módulos de usuarios, tareas y WhatsApp de Samuel), `scripts/**`, `docs/evidencias/3-docker/**`

## 3. Apartados del documento PDF (se los entregas a Julian Godoy)
- **6.** Tecnologías utilizadas (todas las versiones exactas)
- **11.** Implementación de Laravel en Docker (Dockerfile y `compose.yaml` explicados línea a línea; imagen vs. contenedor, redes, puertos, volúmenes)
- **12.** Configuración de bases de datos
- **13.** Seguridad, respaldo y recuperación: parte de **respaldo y restauración**

## 4. Pruebas que documentas
- [ ] BD de Laravel: registrar y consultar novedades desde la web y verificarlo en `psql`
- [ ] Persistencia: `docker compose down && docker compose up -d` (sin `-v`) → las novedades siguen ahí
- [ ] Moodle y Laravel funcionando al mismo tiempo (`docker compose ps` + ambos navegadores)
- [ ] Reinicio de servicios: `docker compose restart` y `docker compose logs`
- [ ] Respaldo y restauración (descrita arriba)

## 5. Dependencias
- **Necesitas de Alejandro Jimenez:** servidor con Docker y la configuración de Nginx.
- **Entregas a Julian:** PostgreSQL con `moodle_db`.
- **Entregas a Samuel:** proyecto Laravel base, layout, Redis y los servicios `queue-worker` y `scheduler` en el compose. Acordar con él la migración de `users` (columnas `rol`, `grado`, `telefono_whatsapp`, `telefono_verificado_at`).

## 6. Registro de IA
Agrega tus filas en la tabla de la sección 13 del contexto (por ejemplo, la generación del Dockerfile o del compose y qué corregiste).

## 7. Definición de terminado
`docker compose up -d` levanta todo desde cero; PostgreSQL con 2 BD aisladas; Redis con contraseña; Portal con los 5 módulos públicos y el CRUD de novedades; datos persistentes tras recrear los contenedores; respaldo automático y restauración demostrada; apartados 6, 11, 12 y 13 (respaldo) entregados.
