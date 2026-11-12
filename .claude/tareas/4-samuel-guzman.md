# Integrante 4 — Samuel Guzmán
## Usuarios y roles, panel de tareas y bot de WhatsApp

- **Rama:** `samuel-guzman/usuarios-tareas-bot`
- **Contexto general:** [`.claude/CONTEXTO_HACKATON.md`](../CONTEXTO_HACKATON.md) (leer primero; **la sección 7 es el diseño completo del bot**)
- **Rúbrica relacionada:** Arquitectura (10 %), Despliegue de Laravel (10 %), Uso ético de IA (5 %), Documentación (15 %)

> Construyes sobre el proyecto Laravel base de Alejandro Moreno. Empieza por los **usuarios y roles**: el panel y el bot dependen de eso.

---

## 1. Tareas técnicas

### 1.1 Usuarios y roles
- [ ] Instalar Laravel Breeze (Blade) para el login, adaptado al layout del Portal.
- [ ] Migración de `users`: `rol` (`admin|profesor|acudiente|estudiante`), `grado`, `telefono_whatsapp` (único, formato E.164) y `telefono_verificado_at`. **Acordarla con Alejandro Moreno.**
- [ ] Tabla `acudiente_estudiante` y relación de profesores con grados y materias.
- [ ] Middleware de rol y **Policies** (`TareaPolicy`): cada usuario ve solo lo que le corresponde (sección 7.4 del contexto).
- [ ] Gestión de usuarios para el admin: crear, editar, asignar rol y vincular acudientes.
- [ ] Seeders con datos **ficticios**: 1 admin, 2 profesores, 3 acudientes y 5 estudiantes.

### 1.2 Panel de tareas `/tareas`
- [ ] Tabla `tareas`: `estudiante_id`, `creada_por_id`, `materia`, `descripcion`, `fecha_entrega`, `estado` (`pendiente|entregada`), `origen` (`whatsapp|web`).
- [ ] Listado con filtros por estado, grado, materia y origen; resalta las tareas vencidas.
- [ ] Crear tareas desde la web (estudiante, acudiente para su hijo, profesor para un grado) y marcarlas como entregadas.
- [ ] Indicador visual de las tareas que llegaron por WhatsApp.

### 1.3 Vinculación del número
- [ ] Tabla `codigos_vinculacion` (`user_id`, `codigo_hash`, `expira_at`, `usado_at`).
- [ ] Pantalla "Vincular WhatsApp": genera un código de 6 dígitos (guardado con hash, válido 24 h) y muestra las instrucciones (`VINCULAR 123456`).

### 1.4 Bot de WhatsApp (Cloud API de Meta)
- [ ] Crear la app en Meta for Developers con el producto WhatsApp y el **número de prueba**. Autorizar los números del equipo como destinatarios.
- [ ] ngrok: crear la cuenta, reservar el dominio fijo gratuito y agregar el servicio `ngrok` con `NGROK_AUTHTOKEN` y `NGROK_DOMAIN` (pasárselos a Alejandro Moreno para el compose). El túnel apunta a `http://nginx:8081` (entrada del webhook que ya dejó Alejandro Jimenez).
- [ ] `GET /webhook/whatsapp`: verificación de la suscripción (`hub.verify_token`, `hub.challenge`).
- [ ] `POST /webhook/whatsapp`:
  - valida la firma `X-Hub-Signature-256` con `WHATSAPP_APP_SECRET`;
  - guarda el mensaje en `mensajes_whatsapp` (`wa_message_id` único para que no se duplique);
  - encola el job y responde `200` de inmediato;
  - va excluido de CSRF y tiene rate limit.
- [ ] Job `ProcesarMensajeWhatsApp` (cola `whatsapp-in`):
  - busca al usuario por número;
  - rechaza los números desconocidos o sin verificar;
  - interpreta el comando y aplica los permisos del rol.
- [ ] Comandos: `TAREA`, `TAREA @hijo`, `TAREA GRADO n`, `MIS TAREAS`, `HECHA <id>`, `VINCULAR <código>`, `AYUDA` (sección 7.5 del contexto).
- [ ] Job `EnviarMensajeWhatsApp` (cola `whatsapp-out`): `$tries` y `backoff` progresivo para cuando no haya internet; registra el estado `enviado` o `fallido`.
- [ ] `scheduler`: recordatorio diario de tareas próximas a vencer y limpieza de códigos vencidos.
- [ ] Variables en `.env.example` (sin valores reales): `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_APP_SECRET`, `WHATSAPP_VERIFY_TOKEN`, `NGROK_AUTHTOKEN`, `NGROK_DOMAIN`.
- [ ] Opcional: simulador local (`php artisan whatsapp:simular "+57300..." "TAREA ..."`) para la demo si falla internet en clase.

---

## 2. Archivos que te pertenecen
En `portal/`: autenticación, `app/Models/{User,Tarea,MensajeWhatsapp,CodigoVinculacion}`, `app/Policies/**`, `app/Jobs/*WhatsApp*`, `app/Http/Controllers/{Tarea,Webhook,Usuario}*`, vistas `tareas/**` y `usuarios/**`, y sus migraciones y seeders. Además `docs/arquitectura-infraestructura.mermaid` y `docs/evidencias/4-bot/**`.

## 3. Apartados del documento PDF (se los entregas a Julian Godoy)
- **5.** Arquitectura: **diagrama** exportado del `.mermaid` (PNG/SVG con mermaid.live o mermaid-cli) y su explicación
- **7.** Justificación de decisiones técnicas
- **14.** Pruebas de funcionamiento: **reúnes** las pruebas de todos en un solo formato (procedimiento / resultado / evidencia)
- **16.** Uso ético y adecuado de IA: consolida el registro de todo el equipo y escribe la reflexión
- **20.** Anexos (comandos, archivos de configuración, scripts)

## 4. Pruebas que documentas
- [ ] **Modo avión:** enviar `TAREA ...` con el celular en modo avión → quitar el modo avión → la tarea aparece en `/tareas` con origen `whatsapp`
- [ ] **Número no vinculado:** el bot responde con el rechazo y el mensaje queda como `rechazado`
- [ ] **Cola resistente:** `docker compose stop queue-worker` → enviar mensajes → revisar con `redis-cli llen queues:whatsapp-in` → `start` → todos se procesan sin pérdida
- [ ] **Permisos:** un acudiente no ve las tareas de un estudiante que no es su hijo

## 5. Dependencias
- **Necesitas de Alejandro Moreno:** proyecto Laravel base, layout, Redis, `queue-worker` / `scheduler` en el compose y la migración de `users` acordada.
- **Necesitas de Alejandro Jimenez:** ruta del webhook en Nginx.
- **Entregas a Alejandro Moreno:** el middleware de rol `admin` para proteger el CRUD de novedades.

## 6. Registro de IA
Además de tus filas, **mantienes la tabla del equipo** (sección 13 del contexto) y recuerdas a los demás que la llenen.

## 7. Definición de terminado
Login con 4 roles y Policies funcionando; panel `/tareas` usable; vinculación por código; bot recibe y responde mensajes reales por la Cloud API a través del túnel; las colas sobreviven a la caída del worker y a la falta de internet; apartados 5 (diagrama), 7, 14, 16 y 20 entregados.
