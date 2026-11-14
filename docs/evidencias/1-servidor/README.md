# Evidencias — Integrante 1: Servidor, red y seguridad

Responsable: Alejandro Jimenez · Rama `alejandro-jimenez/servidor-red-seguridad`
Insumo para los apartados **8** (instalación del servidor), **5** (red y direccionamiento) y **13** (seguridad) del documento.

## Datos del servidor

| Parámetro | Valor |
|---|---|
| Hipervisor | Oracle VirtualBox |
| Sistema operativo | Ubuntu Server 24.04 LTS (instalado desde 24.04.3, actualizado a 24.04.5; kernel 6.8) |
| Hostname | `sanluquitas-srv` |
| Recursos | 2 vCPU · 4 GB RAM · disco de 40 GB (LVM, `/` = 37,99 GB) |
| Red de la VM | Adaptador puente sobre la tarjeta Wi-Fi del anfitrión |
| Interfaz | `enp0s3` |
| IP fija | `192.168.1.50/24` |
| Puerta de enlace | `192.168.1.1` |
| DNS | `192.168.1.1`, `8.8.8.8` |
| Zona horaria | `America/Bogota` (UTC-5), sincronizada por NTP |
| Usuario administrador | `sladmin` (grupo `sudo`); SSH solo con llave pública |

## 1. Máquina virtual (VirtualBox)

| Captura | Qué muestra |
|---|---|
| `01-virtualbox-red-adaptador-puente.png` | Adaptador 1 en **modo puente**: la VM aparece en la LAN como un equipo más, accesible desde otros dispositivos. |
| `02-virtualbox-almacenamiento-iso.png` | ISO `ubuntu-24.04.3-live-server-amd64.iso` (3,08 GB) en la unidad óptica y disco `sanluquitas-srv.vdi`. |

## 2. Planeación del direccionamiento

| Captura | Qué muestra |
|---|---|
| `03-windows-ipconfig-red-lan.png` | La LAN es `192.168.1.0/24`, gateway `192.168.1.1` (equipo anfitrión `192.168.1.24`). |
| `04-windows-ping-ip-libre.png` | `ping 192.168.1.50` → "Host de destino inaccesible": la IP está libre y se reserva para el servidor. |

## 3. Instalación de Ubuntu Server

| Captura | Qué muestra |
|---|---|
| `05-instalador-tipo-instalacion.png` | Instalación estándar de Ubuntu Server (no la "minimized"). |
| `06-instalador-red-dhcp-inicial.png` | El modo puente funciona: el router entrega `192.168.1.31` por DHCP a `enp0s3`. La IP fija se configura después con Netplan. |
| `07-instalador-mirror.png` | Mirror oficial `archive.ubuntu.com` verificado. |
| `08-instalador-disco-lvm.png` | Disco completo de 40 GB con LVM, sin cifrado (el cifrado pediría clave en cada arranque y dificultaría el `.ova`). |
| `09-instalador-particiones-por-defecto.png` | Por defecto el instalador solo asigna 19 GB a `/` y deja 19 GB libres. |
| `10-instalador-particiones-ajustadas.png` | Se amplía `ubuntu-lv` a 37,996 GB para que Docker, Moodle y PostgreSQL tengan espacio. `/boot` = 2 GB. |
| `11-instalador-perfil-usuario.png` | Servidor `sanluquitas-srv`, usuario administrador `sladmin` (no root). |
| `12-instalador-progreso-openssh.png` | Se instala `openssh-server` y las actualizaciones de seguridad. No se marcó ningún snap (Docker se instalará desde el repositorio oficial). |
| `13-instalador-completado.png` | Instalación terminada. |
| `14-primer-login-consola.png` | Primer inicio de sesión: Ubuntu 24.04.3 LTS, `/` de 37,10 GB, IP inicial `192.168.1.31`. |

## 4. Configuración base

### Zona horaria — `15-zona-horaria-bogota.png`
```bash
sudo timedatectl set-timezone America/Bogota   # hora local de Colombia
timedatectl                                    # verificar: America/Bogota (-05), NTP activo
```

### Acceso remoto por SSH desde otro equipo — `16-acceso-ssh-desde-windows.png`
Desde PowerShell en el equipo Windows de la LAN:
```powershell
ssh sladmin@192.168.1.31   # se acepta la huella ED25519 del servidor la primera vez
```

### IP estática con Netplan — `17`, `18`, `19`
```bash
# Respaldo del archivo original (DHCP)
sudo cp /etc/netplan/50-cloud-init.yaml /etc/netplan/50-cloud-init.yaml.bak

# Evitar que cloud-init regenere la red con DHCP en el próximo arranque
echo "network: {config: disabled}" | sudo tee /etc/cloud/cloud.cfg.d/99-disable-network-config.cfg

# Nueva configuración con IP fija
sudo tee /etc/netplan/50-cloud-init.yaml > /dev/null << 'EOF'
network:
  version: 2
  ethernets:
    enp0s3:
      dhcp4: false                 # sin IP automática
      addresses:
        - 192.168.1.50/24          # IP fija, máscara 255.255.255.0
      routes:
        - to: default
          via: 192.168.1.1         # puerta de enlace (router)
      nameservers:
        addresses: [192.168.1.1, 8.8.8.8]
EOF

sudo chmod 600 /etc/netplan/50-cloud-init.yaml   # solo root puede leerlo
sudo netplan generate                            # valida la sintaxis sin aplicar
sudo netplan apply                               # aplica (la sesión SSH se corta)
```
Reconexión y verificación:
```bash
ssh sladmin@192.168.1.50
ip a show enp0s3          # inet 192.168.1.50/24, sin "dynamic"
ping -c 3 google.com      # salida a internet y DNS funcionando (0 % de pérdida)
```

## 5. Actualización y herramientas base

### Actualización completa — `20-apt-full-upgrade.png`
```bash
sudo apt update && sudo apt full-upgrade -y   # 77 paquetes; el sistema pasa a 24.04.5
```

### Herramientas — `21-instalacion-herramientas.png`, `22-paquetes-y-unattended-upgrades.png`
```bash
sudo apt install -y curl git htop ufw unattended-upgrades fail2ban
dpkg -l curl git htop ufw unattended-upgrades fail2ban | grep ^ii   # verificación: 6 paquetes "ii"
```
| Paquete | Uso en el proyecto |
|---|---|
| `curl` | Descargas y pruebas HTTP (instalación de Docker, pruebas a Nginx) |
| `git` | Clonar el repositorio del proyecto en el servidor |
| `htop` | Monitoreo de CPU y memoria |
| `ufw` | Firewall del host |
| `unattended-upgrades` | Parches de seguridad automáticos |
| `fail2ban` | Bloqueo de IPs con intentos fallidos de autenticación |

### Actualizaciones automáticas de seguridad — `22-paquetes-y-unattended-upgrades.png`
```bash
sudo dpkg-reconfigure -plow unattended-upgrades   # responder "Yes"
cat /etc/apt/apt.conf.d/20auto-upgrades          # ambas directivas en "1"
```

## 6. Acceso SSH con llave pública y endurecimiento

### Llave en el equipo cliente (Windows, PowerShell)
```powershell
ssh-keygen -t ed25519 -C "alejandro@sanluquitas"   # crea id_ed25519 (privada) e id_ed25519.pub (pública)

# Copia la llave pública al servidor (equivalente a ssh-copy-id, que no existe en Windows)
type $env:USERPROFILE\.ssh\id_ed25519.pub | ssh sladmin@192.168.1.50 "mkdir -p ~/.ssh && chmod 700 ~/.ssh && cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys"

ssh sladmin@192.168.1.50   # entra sin contraseña — 23-ssh-con-llave-sin-password.png
```
La llave privada nunca sale del equipo cliente ni se sube al repositorio.

### Endurecimiento de `sshd` — `24-ssh-endurecido-config.png`
En Ubuntu 24.04, `/etc/ssh/sshd_config.d/50-cloud-init.conf` trae `PasswordAuthentication yes`. `sshd` usa el **primer** valor que encuentra y lee los archivos de `sshd_config.d/` en orden alfabético, por eso la configuración propia lleva el prefijo `10-` para tener prioridad:
```bash
sudo tee /etc/ssh/sshd_config.d/10-sanluquitas.conf > /dev/null << 'EOF'
# Endurecimiento SSH - San Luquitas
PermitRootLogin no               # root no puede entrar por SSH
PasswordAuthentication no        # no se aceptan contraseñas
KbdInteractiveAuthentication no  # ni autenticación interactiva
PubkeyAuthentication yes         # solo llave pública
EOF

sudo sshd -t && sudo systemctl restart ssh   # valida la sintaxis y reinicia el servicio
sudo sshd -T | grep -Ei "permitrootlogin|passwordauthentication|pubkeyauthentication"   # valores efectivos
```

### Pruebas desde otro equipo
| Prueba | Resultado esperado | Evidencia |
|---|---|---|
| `ssh sladmin@192.168.1.50` con llave | Entra sin pedir contraseña | `25-ssh-llave-tras-endurecimiento.png` |
| `ssh -o PubkeyAuthentication=no sladmin@192.168.1.50` | `Permission denied (publickey)` sin ofrecer contraseña | `26-ssh-rechaza-password.png` |

## 7. Reinicio y persistencia de la configuración

```bash
sudo reboot                     # requerido por la actualización del kernel
ssh sladmin@192.168.1.50        # 27-ssh-tras-reinicio.png
ip -br a show enp0s3 && timedatectl | grep "Time zone" && sudo sshd -T | grep passwordauthentication
```
`28-verificacion-tras-reinicio.png`: tras el arranque se mantienen la IP fija `192.168.1.50/24` (cloud-init ya no regenera la red), la zona horaria y `passwordauthentication no`.

## 8. Firewall UFW — `29-ufw-reglas-lan.png`

Política de mínimo privilegio: se bloquea todo el tráfico entrante y solo se abren los puertos de administración y web, **únicamente para la red local** del colegio. La regla del puerto 22 se crea **antes** de activar UFW para no perder la sesión SSH.
```bash
sudo ufw default deny incoming     # todo lo que entra se bloquea
sudo ufw default allow outgoing    # el servidor puede salir (actualizaciones, Docker Hub)
sudo ufw allow from 192.168.1.0/24 to any port 22  proto tcp comment 'SSH LAN'
sudo ufw allow from 192.168.1.0/24 to any port 80  proto tcp comment 'HTTP LAN'
sudo ufw allow from 192.168.1.0/24 to any port 443 proto tcp comment 'HTTPS LAN'
sudo ufw enable                    # activo y persistente al arranque
sudo ufw status verbose
```
| Puerto | Servicio | Origen permitido |
|---|---|---|
| 22/tcp | SSH (administración) | `192.168.1.0/24` |
| 80/tcp | HTTP (redirige a HTTPS) | `192.168.1.0/24` |
| 443/tcp | HTTPS (Moodle y Portal vía Nginx) | `192.168.1.0/24` |
| Cualquier otro (5432 PostgreSQL, 6379 Redis…) | — | Bloqueado |

> **Pendiente con Docker:** Docker escribe sus propias reglas de iptables y puede publicar puertos saltándose UFW. Se documenta la mitigación en la sección de Docker.

## 9. fail2ban — `30-fail2ban-y-repo-docker.png`

```bash
sudo systemctl enable --now fail2ban
sudo fail2ban-client status sshd     # jail sshd activo: bloquea IPs tras intentos fallidos
```

## 10. Docker Engine y Compose (repositorio oficial)

Se usa el repositorio oficial de Docker y **no** el paquete `docker.io` de Ubuntu ni el snap, para tener versiones actuales del motor y del plugin Compose.
```bash
# Llave GPG del repositorio
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

# Repositorio (formato deb822); Suites toma el nombre de la versión: noble
sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null << EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

sudo systemctl enable --now docker   # arranca con el sistema
sudo usermod -aG docker sladmin      # usar docker sin sudo (requiere cerrar y abrir sesión)
```
| Evidencia | Resultado |
|---|---|
| `31-docker-servicio-y-grupo.png` | Servicio habilitado y `sladmin` agregado al grupo `docker` |
| `32-docker-y-compose-version.png` | Docker 29.9.3 · Compose v5.7.1 · `enabled` · `active` |
| `33-docker-hello-world.png` | `docker run --rm hello-world` sin `sudo`: el motor descarga la imagen y ejecuta el contenedor |

> Nota de seguridad: pertenecer al grupo `docker` equivale a tener privilegios de root en el host. Por eso solo `sladmin` está en ese grupo.

## 11. Docker y UFW: filtro en la cadena `DOCKER-USER`

**Problema.** Docker publica puertos con reglas propias de iptables (NAT y cadena `FORWARD`) que se evalúan antes que UFW. Un puerto publicado queda abierto aunque UFW no lo permita.

**Demostración del problema** — `34-docker-salta-ufw-puerto-8080.png`
```bash
docker run -d --name prueba-ufw -p 8080:80 nginx:alpine
# Desde Windows: http://192.168.1.50:8080 → "Welcome to nginx!" aunque UFW no permite el 8080
```

**Solución.** Docker crea la cadena `DOCKER-USER` y la evalúa antes de sus propias reglas. Se definen ahí las reglas en `/etc/ufw/after.rules`, para que UFW las cargue al arrancar y con cada `ufw reload`. Decisión de diseño acordada con el `compose.yaml` en [`nginx/README.md`](../../../nginx/README.md).
```bash
sudo cp /etc/ufw/after.rules /etc/ufw/after.rules.bak

sudo tee -a /etc/ufw/after.rules > /dev/null << 'EOF'

# BEGIN San Luquitas - filtro de puertos publicados por Docker
*filter
:DOCKER-USER - [0:0]
-A DOCKER-USER -i enp0s3 -m conntrack --ctstate RELATED,ESTABLISHED -j RETURN
-A DOCKER-USER -i enp0s3 -s 192.168.1.0/24 -p tcp -m conntrack --ctorigdstport 80 -j RETURN
-A DOCKER-USER -i enp0s3 -s 192.168.1.0/24 -p tcp -m conntrack --ctorigdstport 443 -j RETURN
-A DOCKER-USER -i enp0s3 -j DROP
-A DOCKER-USER -j RETURN
COMMIT
# END San Luquitas
EOF

sudo ufw reload
sudo iptables -S DOCKER-USER
```
| Regla | Función |
|---|---|
| `RELATED,ESTABLISHED` | Deja pasar las respuestas al tráfico que los contenedores inician hacia internet |
| `--ctorigdstport 80` / `443` desde `192.168.1.0/24` | Solo la LAN puede abrir conexiones nuevas a la web (Nginx) |
| `-i enp0s3 -j DROP` | Cualquier otra conexión que llegue de fuera hacia un contenedor se descarta |
| `-j RETURN` final | El tráfico entre contenedores y redes internas no se altera |

`--ctorigdstport` compara con el puerto **original** al que se conectó el cliente (el del host), antes de que Docker lo traduzca al puerto del contenedor.

**Verificación**
| Prueba | Resultado | Evidencia |
|---|---|---|
| `http://192.168.1.50:8080` con la regla activa | `ERR_CONNECTION_TIMED_OUT`; 39 paquetes contados en `DROP` | `35-docker-user-contadores.png`, `36-docker-8080-bloqueado.png` |
| `docker run -d --name prueba-80 -p 80:80 nginx:alpine` → `http://192.168.1.50` | Carga normalmente desde la LAN | `37-docker-puerto-80-permitido-lan.png` |
| `sudo iptables -S DOCKER-USER` | Reglas cargadas | `38-docker-user-reglas.png` |
| `sudo reboot` → `sudo iptables -S DOCKER-USER` | Las 5 reglas se cargan solas al arrancar (UFW las aplica desde `after.rules`) | `42-docker-user-tras-reinicio.png` |

Los contenedores de prueba se eliminan al terminar (`docker rm -f prueba-ufw prueba-80`).

## 12. Nginx (reverse proxy HTTPS)

Configuración en [`nginx/`](../../../nginx/) y decisiones de diseño en [`nginx/README.md`](../../../nginx/README.md).

### Certificado autofirmado para la LAN — `39-nginx-certificado-y-validacion.png`
```bash
git clone -b alejandro-jimenez/servidor-red-seguridad https://github.com/Alejo19092007/InfraTech-San-Luquitas-Digital.git ~/sanluquitas

# Un solo certificado válido para los dos nombres (SAN), 825 días
openssl req -x509 -nodes -newkey rsa:2048 -days 825 \
  -keyout ~/sanluquitas/nginx/certs/sanluquitas.key \
  -out    ~/sanluquitas/nginx/certs/sanluquitas.crt \
  -subj "/C=CO/O=Colegio San Luquitas/CN=sanluquitas.local" \
  -addext "subjectAltName=DNS:moodle.sanluquitas.local,DNS:portal.sanluquitas.local"
chmod 600 ~/sanluquitas/nginx/certs/sanluquitas.key   # la llave privada no se sube al repositorio

# Validación con la imagen real (los nombres de PHP-FPM se simulan con --add-host)
docker run --rm --add-host moodle:127.0.0.1 --add-host laravel-app:127.0.0.1 \
  -v ~/sanluquitas/nginx/conf.d:/etc/nginx/conf.d:ro -v ~/sanluquitas/nginx/certs:/etc/nginx/certs:ro \
  nginx:1.27-alpine nginx -t     # syntax is ok / test is successful
```

### Prueba de funcionamiento — `41-nginx-https-portal-502.png`
```bash
docker run -d --name prueba-nginx -p 80:80 -p 443:443 \
  --add-host moodle:127.0.0.1 --add-host laravel-app:127.0.0.1 \
  -v ~/sanluquitas/nginx/conf.d:/etc/nginx/conf.d:ro -v ~/sanluquitas/nginx/certs:/etc/nginx/certs:ro \
  nginx:1.27-alpine
```
`http://portal.sanluquitas.local` redirige a `https://`, el navegador avisa del certificado autofirmado y Nginx responde **502 Bad Gateway**: el proxy, TLS y los nombres funcionan; el 502 indica solo que aún no existe el contenedor de Laravel. El contenedor de prueba se elimina después; el servicio definitivo lo levanta `compose.yaml`.

## 13. Resolución de nombres en la LAN

No hay servidor DNS en el colegio, así que cada cliente asocia los nombres a la IP del servidor:

| Sistema | Archivo / método | Línea a agregar |
|---|---|---|
| **Windows** (PowerShell como administrador) | `C:\Windows\System32\drivers\etc\hosts` | `192.168.1.50 moodle.sanluquitas.local portal.sanluquitas.local` |
| **Linux / macOS** | `sudo nano /etc/hosts` | igual |
| **Android** | El archivo `hosts` requiere root. Alternativas: configurar el DNS del router (abajo) o, para la demo, una app de DNS local (p. ej. "Virtual Hosts") | — |
| **Router (recomendado)** | Sección DNS local / *Static DNS* / *Host mapping* del router, si la ofrece | `moodle.sanluquitas.local` y `portal.sanluquitas.local` → `192.168.1.50` |

Windows — `40-hosts-windows-y-ping.png`:
```powershell
Add-Content -Path "$env:windir\System32\drivers\etc\hosts" -Value "192.168.1.50 moodle.sanluquitas.local portal.sanluquitas.local"
ipconfig /flushdns
ping portal.sanluquitas.local     # responde 192.168.1.50
```
Con el DNS del router todos los dispositivos (incluidos celulares) resuelven los nombres sin tocar cada equipo.

## 14. Segundo adaptador NAT: el servidor funciona en cualquier red

**Problema.** La IP fija `192.168.1.50` solo sirve en la red donde se configuró. En otra red (la de la universidad, `172.17.96.0/19`) la VM quedó sin internet y sin acceso por SSH.

**Solución.** Se conserva el adaptador puente (`enp0s3`, acceso desde otros dispositivos de la LAN) y se agrega un **Adaptador 2 en NAT** (`enp0s8`) con reenvío de puertos solo hacia `127.0.0.1` del equipo anfitrión:

| Regla | Anfitrión | Invitado |
|---|---|---|
| ssh | `127.0.0.1:2222` | `10.0.3.15:22` |
| http | `127.0.0.1:80` | `10.0.3.15:80` |
| https | `127.0.0.1:443` | `10.0.3.15:443` |

```powershell
# En el anfitrión (PowerShell), con la VM encendida — 43-nat-reenvio-puertos.png
$vbm = "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe"
& $vbm controlvm sanluquitas-srv natpf2 "ssh,tcp,127.0.0.1,2222,10.0.3.15,22"
& $vbm controlvm sanluquitas-srv natpf2 "http,tcp,127.0.0.1,80,10.0.3.15,80"
& $vbm controlvm sanluquitas-srv natpf2 "https,tcp,127.0.0.1,443,10.0.3.15,443"
ssh -p 2222 sladmin@127.0.0.1        # 45-ssh-por-nat-2222.png
```
```bash
# En el servidor — 46-netplan-nat-y-metricas.png
sudo tee /etc/netplan/60-nat.yaml > /dev/null << 'EOF'
network:
  version: 2
  ethernets:
    enp0s8:
      dhcp4: true
      dhcp4-overrides:
        route-metric: 50        # salida a internet preferida: funciona en cualquier red
EOF
# La ruta de la LAN de casa queda como respaldo (metric 200)
sudo sed -i 's/via: 192.168.1.1$/via: 192.168.1.1\n          metric: 200/' /etc/netplan/50-cloud-init.yaml
sudo chmod 600 /etc/netplan/*.yaml && sudo netplan generate && sudo netplan apply
sudo ufw allow from 10.0.3.0/24 to any port 22 proto tcp comment 'SSH NAT'
ip route | grep default && ping -c 3 google.com     # 47-rutas-y-internet-por-nat.png
```

**Error encontrado** (`44-nat-diagnostico-red-10-0-3.png`): se asignó a `enp0s8` la IP `10.0.2.15` y el `ping 10.0.2.2` respondía *Destination Host Unreachable*. VirtualBox usa una red NAT distinta por adaptador: `10.0.2.0/24` para el Adaptador 1 y **`10.0.3.0/24` para el Adaptador 2**. Se corrigió la IP, la regla de UFW y el reenvío de puertos.

En el equipo anfitrión, el archivo `hosts` apunta a `127.0.0.1` (`moodle.sanluquitas.local portal.sanluquitas.local`); los demás dispositivos de la LAN siguen usando `192.168.1.50`.

## Pendiente
- [ ] Levantar el stack completo con `compose.yaml` (Alejandro Moreno) y verificar Moodle y Portal reales
- [ ] Pruebas: acceso desde otro dispositivo (celular), reinicio con todos los contenedores, `ss -tulpn` y `nmap`
- [ ] Exportar `.ova` y `docs/INSTRUCCIONES_OVA.md`
- [ ] Diapositivas y guion de la sustentación
