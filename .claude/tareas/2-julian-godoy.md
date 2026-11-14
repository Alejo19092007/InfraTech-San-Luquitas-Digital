# Integrante 2 — Julian Godoy
## Moodle, EVA "San Luquitas Digital" y documento PDF

- **Rama:** `julian-godoy/moodle-eva-documento`
- **Contexto general:** [`.claude/CONTEXTO_HACKATON.md`](../CONTEXTO_HACKATON.md) (leer primero)
- **Rúbrica relacionada:** Configuración del EVA (10 %), Documentación técnica (15 %), Análisis comparativo (10 %)

> Puedes empezar desde el día 1 en tu propio equipo con Docker. Cuando el servidor esté listo, lo llevas allí.

---

## 1. Tareas técnicas

### 1.1 Contenedor de Moodle
Archivos: `moodle/Dockerfile`, `moodle/php.ini`, `moodle/config.php.example`.
- [ ] Imagen basada en `php:8.3-fpm` (versión fija) con las extensiones `pgsql`, `pdo_pgsql`, `intl`, `zip`, `gd`, `soap`, `xml`, `mbstring`, `curl`, `opcache`, `sodium`, `exif` y, opcionalmente, `redis`.
- [ ] `php.ini`: `max_input_vars = 5000`, `memory_limit = 256M`, `upload_max_filesize` / `post_max_size` acordes con Nginx, opcache activo.
- [ ] Descargar Moodle 5.x (versión estable concreta; anotarla) en el volumen `moodle_code` (`/var/www/moodle`). `moodledata` va en `/var/www/moodledata`, **fuera** de la raíz web.
- [ ] Verificar la compatibilidad Moodle 5.x ↔ PHP 8.3 ↔ PostgreSQL 16 y dejarla documentada.
- [ ] Servicio `moodle-cron`: misma imagen, ejecuta `php admin/cli/cron.php` cada minuto (bucle o cron interno).
- [ ] Agregar los servicios `moodle` y `moodle-cron` al `compose.yaml` **en coordinación con Alejandro Moreno** (él es el dueño del archivo).

### 1.2 Instalación y configuración
- [ ] Instalar por CLI (`admin/cli/install.php`) con `dbtype=pgsql`, `dbhost=postgres`, `dbname=moodle_db`, `dbuser=moodle_user`, `wwwroot=https://moodle.sanluquitas.local`.
- [ ] `config.php` lee la contraseña de una variable de entorno, o queda fuera del repo. En el repo solo va `config.php.example`.
- [ ] Opcional (suma puntos): caché MUC en Redis.

### 1.3 EVA — curso "Mis primeros pasos en programación"
- [ ] Sitio con el nombre **"San Luquitas Digital"**, logo, colores, página de inicio y descripción (identidad visual).
- [ ] Presentación del curso: nombre, descripción, población (4.º–5.º de primaria) y objetivos de aprendizaje.
- [ ] **Unidad 1:** pensamiento computacional — secuencias y patrones.
- [ ] **Unidad 2:** algoritmos cotidianos — instrucciones y resolución de problemas.
- [ ] **Unidad 3:** programación visual — primeros pasos con Scratch.
- [ ] Mínimo **3 recursos** (página, PDF, video o enlace a Scratch).
- [ ] Mínimo **1 cuestionario** (con banco de preguntas) y, si se puede, una tarea.
- [ ] **1 foro** de interacción.
- [ ] Usuarios ficticios: `admin`, `docente.martha` (profesor), `est.ana`, `est.juan`, `est.sofia` (estudiantes), matriculados.
- [ ] Evidencias de los roles: qué ve el admin, qué puede editar el docente y qué ve el estudiante (capturas comparativas).
- [ ] Opcional: exportar una copia del curso (`.mbz`) como respaldo adicional.

---

## 2. Archivos que te pertenecen
`moodle/**`, `docs/documento-tecnico/**`, `docs/evidencias/2-moodle/**`

## 3. Documento técnico PDF (eres el responsable de armarlo)
**Escribes tú:**
- **1.** Portada · **2.** Introducción · **3.** Objetivos · **4.** Análisis de requerimientos
- **9.** Implementación de Moodle · **10.** Configuración del EVA
- **15.** Análisis comparativo (matriz; borrador en la sección 12 del contexto, con fuentes)
- **17.** Limitaciones y recomendaciones · **18.** Conclusiones · **19.** Referencias

**Reúnes los apartados de los demás:**
- Alejandro Jimenez: 5 (red), 8, 13 (seguridad)
- Alejandro Moreno: 6, 11, 12, 13 (respaldo)
- Samuel Guzmán: 5 (diagrama), 7, 14, 16, 20

Formato único (plantilla, numeración, estilo de capturas con pie de figura). No se aceptan secciones hechas solo de capturas.

## 4. Pruebas que documentas
- [ ] Acceso a Moodle desde el navegador
- [ ] BD de Moodle: crear y consultar un usuario o actividad sin errores (y verificarlo en `psql`)
- [ ] Acceso por roles: admin, docente y estudiante con permisos distintos

## 5. Dependencias
- **Necesitas de Alejandro Moreno:** PostgreSQL con `moodle_db` / `moodle_user` y el `compose.yaml` base.
- **Necesitas de Alejandro Jimenez:** el sitio Nginx de `moodle.sanluquitas.local`.
- **Fechas:** acordar con el equipo una fecha límite para recibir los apartados y tener margen para armar el PDF.

## 6. Registro de IA
Agrega tus filas en la tabla de la sección 13 del contexto (por ejemplo, los contenidos del curso generados con IA y cómo los revisaste).

## 7. Definición de terminado
Moodle accesible por HTTPS en la LAN con su propia BD; curso completo con 3 unidades, recursos, cuestionario y foro; 5 usuarios con roles comprobados; PDF final con los 20 apartados reunidos y revisados.
