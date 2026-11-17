# Moodle — San Luquitas Digital

Responsable: **Julian Godoy**. Se activa con el perfil `moodle` del `compose.yaml`.

## Versión elegida y compatibilidad

| Componente | Versión | Por qué |
|---|---|---|
| **Moodle** | **5.1.9** | Última versión menor de la rama 5.1. Publica solo la carpeta `public/`, como espera `nginx/conf.d/moodle.conf`. |
| PHP | 8.3.36 (FPM) | Moodle 5.0 y 5.1 admiten PHP 8.2 a 8.4. |
| PostgreSQL | 16.16 | Moodle 5.1 exige PostgreSQL 15 o superior. |

**Por qué no Moodle 5.3 (LTS, octubre de 2026):** exige PostgreSQL 17 como mínimo y el proyecto usa PostgreSQL 16. Para subir a 5.3 habría que migrar la base de datos primero.
Fuentes: moodledev.io (releases 5.1 y 5.3) y docs.moodle.org (PHP).

## Qué hay en esta carpeta

| Archivo | Para qué sirve |
|---|---|
| `Dockerfile` | Descarga Moodle 5.1.9 (etiqueta oficial de GitHub), instala las librerías con Composer y arma la imagen PHP-FPM 8.3 con las extensiones que exige Moodle (`pgsql`, `intl`, `zip`, `gd`, `soap`, `exif`, `opcache`…). |
| `php.ini` | `max_input_vars=5000`, `memory_limit=256M`, subidas de 100 MB (igual que Nginx), opcache. |
| `config.php.example` | Ejemplo documentado. El `config.php` real se genera al arrancar, así la contraseña de la BD no queda en el repositorio. |
| `docker/entrypoint.sh` | Genera `config.php`, espera a PostgreSQL, instala Moodle si la BD está vacía y crea el EVA una sola vez. También ejecuta el cron cada minuto en `moodle-cron`. |
| `eva/crear-eva.php` | Crea por código la identidad visual, los usuarios, el curso, las actividades y las matrículas. |
| `eva/preguntas.gift` | Plan B para cargar las preguntas del cuestionario desde la interfaz. |
| `eva/escudo.png` | Logo del sitio. |

## Puesta en marcha

En el `.env` del servidor (nunca en el repositorio):
```bash
COMPOSE_PROFILES=moodle          # o moodle,tunnel
MOODLE_ADMIN_PASSWORD=...        # 8+ caracteres con mayúscula, minúscula, número y símbolo
MOODLE_USERS_PASSWORD=...        # contraseña de los usuarios ficticios
```
Luego:
```bash
docker compose up -d --build
docker compose logs -f moodle     # la primera vez tarda varios minutos
docker compose ps                 # moodle debe quedar "healthy"
```
Abrir `https://moodle.sanluquitas.local` (la entrada en `hosts` es la misma de `docs/evidencias/1-servidor`).

La instalación se hace sola. Está lista cuando existe `/var/www/moodledata/.eva-creada`, que es lo que mira el healthcheck.

## Usuarios ficticios

| Usuario | Rol | Contraseña |
|---|---|---|
| `admin` | Administrador | `MOODLE_ADMIN_PASSWORD` |
| `docente.martha` | Docente con permiso de edición | `MOODLE_USERS_PASSWORD` |
| `est.ana`, `est.juan`, `est.sofia` | Estudiantes | `MOODLE_USERS_PASSWORD` |

## Curso "Mis primeros pasos en programación"

| Sección | Contenido |
|---|---|
| Bienvenida | Página de presentación (objetivos y población) y foro de preguntas |
| Unidad 1 — Pensamiento computacional | Página "¿Qué es el pensamiento computacional?" y enlace a CS Unplugged |
| Unidad 2 — Algoritmos cotidianos | Página "Algoritmos cotidianos" y tarea "Escribe tu algoritmo" |
| Unidad 3 — Programación visual | Página "Primeros pasos con Scratch", enlace a Scratch y **Cuestionario final** (4 preguntas) |

## Si algo falla

- **El script de creación del curso termina con `[FALLO]` en algún paso.** Cada paso informa por separado y uno fallido no detiene los demás. Se arregla y se repite con `docker compose restart moodle` (es idempotente: omite lo que ya existe).
- **El cuestionario quedó sin preguntas:** en el curso, abrir el cuestionario → *Preguntas* → menú → *Importar* → formato **GIFT** → `eva/preguntas.gift`.
- **Contenido de `/var/www/moodle` vacío (Nginx da 404):** el volumen se creó antes que la imagen. Corregir con
  `docker compose down && docker volume rm sanluquitas_moodle_code && docker compose up -d --build`.
- **Actualizar Moodle:** el código vive en el volumen `moodle_code`, así que reconstruir la imagen no lo cambia. Hay que seguir el procedimiento de actualización de Moodle; no basta con cambiar `MOODLE_VERSION`.
- **Reiniciar desde cero (borra todo el EVA):** `docker compose down`, borrar los volúmenes `moodle_code` y `moodledata`, y borrar la base con
  `docker compose exec postgres psql -U $POSTGRES_SUPERUSER -c "DROP DATABASE moodle_db" -c "CREATE DATABASE moodle_db OWNER moodle_user"`.

## Respaldo

`scripts/backup.sh` ya incluye la base `moodle_db`, el volumen `moodledata` y el `config.php` cifrado. El código de Moodle no se respalda porque se reconstruye desde la imagen.

## Apariencia

El aspecto del sitio (barra azul con borde dorado, tarjetas redondeadas, portada y pantalla de acceso) está en `moodle/eva/tema.scss`.
En un servidor que ya está funcionando se aplica sin reconstruir:

```bash
bash scripts/aplicar-tema-moodle.sh           # aplica el tema
bash scripts/aplicar-tema-moodle.sh --quitar  # vuelve al tema original
```
Luego se recarga el navegador con Ctrl+F5. En una instalación nueva, `crear-eva.php` lo aplica solo.
