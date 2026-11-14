# Integrante 1 — Alejandro Jimenez
## Servidor, red, seguridad y sustentación

- **Rama:** `alejandro-jimenez/servidor-red-seguridad`
- **Contexto general:** [`.claude/CONTEXTO_HACKATON.md`](../CONTEXTO_HACKATON.md) (leer primero)
- **Rúbrica relacionada:** Implementación técnica (20 %), Seguridad (10 %), Sustentación (10 %)

> Eres el primer eslabón: los demás necesitan el servidor con Docker listo. **Objetivo: VM operativa el día 1.**

---

## 1. Tareas técnicas

### 1.1 Máquina virtual
- [ ] Crear la VM en VirtualBox: Ubuntu Server 24.04 LTS, 2 vCPU, 4 GB RAM (mínimo), disco de 40 GB, red en **modo puente** (bridge).
- [ ] Hostname `sanluquitas-srv`, zona horaria `America/Bogota`.
- [ ] Configurar una **IP estática** con Netplan (`/etc/netplan/50-cloud-init.yaml`). Ejemplo: `192.168.1.50/24`, gateway y DNS.
- [ ] `apt update && apt full-upgrade`. Instalar `curl`, `git`, `htop`, `ufw`, `unattended-upgrades` y `fail2ban` (opcional).

### 1.2 Usuarios y acceso
- [ ] Usuario administrador no root (`sladmin`) en el grupo `sudo`.
- [ ] SSH con llave pública. En `/etc/ssh/sshd_config`: `PermitRootLogin no` y `PasswordAuthentication no` (cuando ya funcione la llave).
- [ ] Activar `unattended-upgrades` para los parches de seguridad.

### 1.3 Firewall (UFW)
- [ ] `ufw default deny incoming` / `ufw default allow outgoing`.
- [ ] Permitir 22/tcp, 80/tcp y 443/tcp **solo desde la LAN** (`ufw allow from 192.168.1.0/24 to any port 80 proto tcp`, y lo mismo para los demás puertos).
- [ ] **Ojo:** Docker modifica iptables y puede saltarse UFW en los puertos publicados. Documentar la solución: publicar con IP (`192.168.1.50:80:80`) o usar la cadena `DOCKER-USER` / `ufw-docker`. Coordinar con Alejandro Moreno (dueño del `compose.yaml`).

### 1.4 Docker
- [ ] Instalar Docker Engine y el plugin Compose desde el **repositorio oficial de Docker** (no el paquete `docker.io`).
- [ ] Agregar `sladmin` al grupo `docker` y ejecutar `systemctl enable --now docker`.
- [ ] Evidencia: `docker version`, `docker compose version`, `systemctl status docker`.

### 1.5 Nginx (reverse proxy)
Archivos: `nginx/conf.d/moodle.conf`, `nginx/conf.d/portal.conf` y, si aplica, `nginx/nginx.conf`.
- [ ] Sitio `moodle.sanluquitas.local` → FastCGI a `moodle:9000` (raíz `/var/www/moodle`, revisar las reglas de `slash arguments` para Moodle).
- [ ] Sitio `portal.sanluquitas.local` → FastCGI a `laravel-app:9000` (raíz `/var/www/html/public`).
- [ ] Ruta `/webhook/whatsapp` accesible desde `cloudflared` (coordinar con Samuel Guzmán).
- [ ] **HTTPS autofirmado** para la LAN (`openssl` o `mkcert`), con redirección 80 → 443. Certificados en `nginx/certs/` (**no** subir la llave privada; está en `.gitignore`).
- [ ] Cabeceras de seguridad básicas, `client_max_body_size` adecuado para Moodle y `server_tokens off`.

### 1.6 Resolución de nombres en la LAN
- [ ] Documentar cómo agregar `192.168.1.50 moodle.sanluquitas.local portal.sanluquitas.local` en el archivo `hosts` de Windows, Linux y Android, o en el DNS del router.

### 1.7 Exportación del .ova (al final)
- [ ] Verificar que Moodle y Laravel funcionan a la vez.
- [ ] Reemplazar los secretos reales por credenciales de laboratorio.
- [ ] Apagado limpio (`sudo shutdown -h now`) y comprobar que arranca otra vez sin perder datos.
- [ ] Exportar `.ova` (Archivo → Exportar servicio virtualizado, OVF 2.0).
- [ ] Escribir `docs/INSTRUCCIONES_OVA.md`: cómo importar, ajustar la red, IP, iniciar los servicios y acceder.

---

## 2. Archivos que te pertenecen
`nginx/**`, `docs/INSTRUCCIONES_OVA.md`, `docs/evidencias/1-servidor/**`, `docs/presentacion/**`

## 3. Apartados del documento PDF (se los entregas a Julian Godoy)
- **8.** Instalación y configuración del servidor Linux
- **5.** Arquitectura: parte de **red y direccionamiento** (IP, puertos, nombres de host)
- **13.** Seguridad, respaldo y recuperación: parte de **seguridad** (UFW, SSH, usuarios, permisos, puertos de Docker)

Cada apartado con comandos comentados, explicación y capturas.

## 4. Pruebas que documentas (procedimiento + resultado + evidencia)
- [ ] Acceso al servidor por SSH desde otro equipo
- [ ] Acceso a Moodle y al Portal desde **otro dispositivo** de la LAN (celular o PC)
- [ ] Reinicio completo de la VM → todos los contenedores vuelven solos
- [ ] Seguridad: `sudo ss -tulpn` y `nmap 192.168.1.50` desde otro equipo muestran solo 22/80/443. PostgreSQL (5432) y Redis (6379) **no** son accesibles

## 5. Sustentación (responsable)
- [ ] Diapositivas en `docs/presentacion/` (10–12 diapositivas): problema, arquitectura, decisiones, demo y contexto rural.
- [ ] Guion de la demo con el orden y quién habla en cada parte.
- [ ] Preparar la respuesta a la **pregunta central** (sección 14 del contexto).
- [ ] Ensayo general con el equipo y el `.ova` importado en otro equipo.

## 6. Dependencias
- **Entregas a todos:** servidor con Docker (día 1).
- **Coordinas con Alejandro Moreno:** puertos publicados de Nginx y volúmenes de código montados en Nginx.
- **Coordinas con Samuel Guzmán:** ruta del webhook a través de Nginx/cloudflared.

## 7. Registro de IA
Agrega tus filas en la tabla de la sección 13 del contexto (herramienta, prompt, resultado, validación, ajustes, uso final).

## 8. Definición de terminado
VM accesible por SSH con IP fija; UFW activo; Docker funcionando al arrancar; Nginx sirviendo los dos sitios por HTTPS; `.ova` exportado y probado; apartados 8, 5 (red) y 13 (seguridad) entregados; presentación lista.
