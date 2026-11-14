# Evidencias — Moodle y EVA (Julian Godoy)

Lista de capturas que se toman en el servidor y se guardan en esta carpeta
(`NN-descripcion.png`). Cada una necesita un pie de figura en el documento técnico.

| N.º | Captura | Qué demuestra |
|---|---|---|
| 01 | `docker compose ps` con `moodle`, `moodle-cron` y `postgres` en marcha | Servicios del EVA |
| 02 | Registro de arranque (`docker compose logs moodle`) hasta "EVA listo" | Instalación y creación automática |
| 03 | `https://moodle.sanluquitas.local` en el navegador con candado | Acceso por HTTPS en la LAN |
| 04 | Página principal del sitio con nombre, logo y color | Identidad visual |
| 05 | Página del curso con sus 3 unidades | Estructura del curso |
| 06 | Presentación del curso | Nombre, descripción, población y objetivos |
| 07 | Cuestionario con preguntas y un intento resuelto | Actividad evaluativa |
| 08 | Foro con un mensaje de un estudiante | Comunicación |
| 09 | Lista de participantes con roles | Docente y 3 estudiantes matriculados |
| 10 | Vista del **administrador** (Administración del sitio) | Rol admin |
| 11 | Vista del **docente** (modo edición activado) | Rol docente |
| 12 | Vista del **estudiante** (sin controles de edición) | Rol estudiante |
| 13 | `psql`: `SELECT username, firstname FROM mdl_user;` en `moodle_db` | La BD de Moodle guarda los datos |
| 14 | Crear una actividad o usuario desde la interfaz y verlo en `psql` | Escritura y lectura en la BD |

Comandos útiles:
```bash
docker compose exec postgres psql -U moodle_user -d moodle_db -c "SELECT id, username, firstname, lastname FROM mdl_user ORDER BY id;"
docker compose exec postgres psql -U moodle_user -d moodle_db -c "SELECT id, fullname FROM mdl_course;"
```
(la contraseña de `moodle_user` es `MOODLE_DB_PASSWORD` del `.env`).
