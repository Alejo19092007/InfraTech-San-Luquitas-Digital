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

## Pendiente
- [ ] Reinicio por actualización del kernel y verificación de IP fija tras el arranque
- [ ] UFW (22/80/443 solo desde `192.168.1.0/24`)
- [ ] Docker Engine + Compose desde el repositorio oficial
- [ ] Nginx (Moodle y Portal por HTTPS)
- [ ] Pruebas: acceso desde otro dispositivo, reinicio, `ss -tulpn` y `nmap`
