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

## Pendiente de evidenciar

- Webhook recibiendo un mensaje real de Meta a través de ngrok
- Mensaje enviado en modo avión que llega al reconectar
- Número no vinculado rechazado
- Cola de Redis que conserva los mensajes con el `queue-worker` detenido
- Permisos: un acudiente no ve tareas de otro estudiante
