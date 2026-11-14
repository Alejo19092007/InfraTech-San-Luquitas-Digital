# Evidencias — Integrante 4: Usuarios, tareas y bot de WhatsApp

Responsable: Samuel Guzmán · Rama `samuel-guzman/usuarios-tareas-bot`
Insumo para los apartados **7**, **14** y **20** del documento.

Los archivos `.txt` son la salida real de los comandos, guardada en el momento de ejecutarlos. No son capturas de pantalla: para el PDF, repetir el comando indicado y capturar la terminal.

## 1. Intérprete de comandos del bot

Código: `portal/app/Bot/` · Pruebas: `portal/tests/Unit/Bot/InterpreteComandosTest.php`

Convierte el texto de un mensaje de WhatsApp (`TAREA`, `MIS TAREAS`, `HECHA`, `VINCULAR`, `AYUDA`) en un comando con sus datos, o en un mensaje de error que explica al usuario cómo corregirlo. No usa la base de datos ni Laravel, por eso se pudo probar antes de tener el proyecto del Portal.

| Evidencia | Qué muestra |
|---|---|
| `01-pruebas-interprete-comandos.txt` | 18 pruebas unitarias (101 comprobaciones) en verde con PHPUnit 11.5 |
| `02-demo-mensajes-de-ejemplo.txt` | 13 mensajes de ejemplo y cómo los entiende el bot, incluidos los errores |

**Entorno de estas pruebas:** equipo de desarrollo (WSL) con PHP 8.5. El contenedor del Portal usará PHP 8.3; el código no usa funciones posteriores a esa versión, pero hay que **repetir las pruebas dentro del contenedor** cuando exista.

**Cómo repetirlas cuando exista el proyecto Laravel:**

```bash
# Dentro del contenedor del Portal
docker compose exec laravel-app php artisan test --filter=InterpreteComandosTest
```

## 2. Usuarios, roles y panel de tareas

Código: `portal/app/Models/`, `portal/app/Policies/TareaPolicy.php`, `portal/app/Http/Controllers/{Auth,Tarea,Usuario,Vinculacion}Controller.php`, vistas en `portal/resources/views/{auth,tareas,usuarios,vinculacion}/`.

- Login con correo y contraseña (límite de 6 intentos por minuto; el mensaje de error no revela si el correo existe).
- 4 roles. Cada uno ve solo lo suyo: el estudiante, sus tareas; el acudiente, las de sus hijos; el docente, las de los grados que dicta; la administración, todo y además gestiona usuarios.
- Panel `/tareas` con resumen (pendientes, vencidas, entregadas), filtros por estado, grado, materia y origen (WhatsApp o portal), y las tareas vencidas resaltadas.
- Cuentas ficticias de demostración: ver `docs/INSTRUCCIONES_BOT_VM.md`.

## 3. Bot de WhatsApp completo

Código: `portal/app/Http/Controllers/WebhookWhatsAppController.php`, `portal/app/Bot/ProcesadorMensajes.php`, `portal/app/Jobs/`, `portal/app/Services/WhatsApp/`, `portal/app/Console/Commands/`.

| Pieza | Qué hace |
|---|---|
| `GET /webhook/whatsapp` | Verificación de la suscripción con `hub.verify_token` |
| `POST /webhook/whatsapp` | Valida la firma `X-Hub-Signature-256`, guarda el mensaje (`wa_message_id` único: no se duplica), lo encola y responde 200 |
| `ProcesarMensajeWhatsApp` (cola `whatsapp-in`) | Identifica a la persona por su número, aplica el comando según su rol y encola la respuesta |
| `EnviarMensajeWhatsApp` (cola `whatsapp-out`) | Responde por la API de Meta; si falla reintenta a los 30 s, 2, 5, 15 y 30 min |
| `tareas:recordatorios` | Cada día a las 6:30 a. m. avisa de lo que vence hoy o mañana |
| `whatsapp:simular` | Prueba el bot sin Meta ni internet |

Todo mensaje que entra o sale queda en la tabla `mensajes_whatsapp` con su estado (`recibido`, `procesado`, `rechazado`, `enviado`, `fallido`).

## 4. Pruebas automáticas

`portal/tests/Feature/` y `portal/tests/Unit/`: acceso y permisos, panel de tareas, comandos del bot por rol, vinculación del número (código vigente, vencido y con intentos agotados), webhook (firma, duplicados, idempotencia, envío con y sin respuesta de Meta), usuarios y comandos programados. Se corren con:

```bash
cd portal && php artisan test
```

## Pendiente de evidenciar

- Webhook recibiendo un mensaje real de Meta a través de ngrok
- Mensaje enviado en modo avión que llega al reconectar
- Número no vinculado rechazado
- Cola de Redis que conserva los mensajes con el `queue-worker` detenido
- Permisos: un acudiente no ve tareas de otro estudiante
