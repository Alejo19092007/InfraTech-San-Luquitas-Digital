# Respaldo y restauración

| Script | Qué hace |
|---|---|
| `backup.sh` | `pg_dump -Fc` de `moodle_db` y `laravel_db`, `tar.gz` de `moodledata` y `laravel_storage`, `.env` y `config.php` cifrados con GPG (AES-256), `SHA256SUMS` y borrado de copias con más de 7 días. Destino: `/srv/backups/AAAA-MM-DD/`. |
| `restore.sh AAAA-MM-DD [-y]` | Verifica las sumas, detiene las aplicaciones, `pg_restore --clean` de cada BD, reemplaza los volúmenes y levanta todo con `docker compose up -d`. |

## Preparación (una sola vez, en el servidor)
```bash
sudo mkdir -p /srv/backups && sudo chown sladmin:sladmin /srv/backups && sudo chmod 700 /srv/backups
sudo touch /var/log/sanluquitas-backup.log && sudo chown sladmin:sladmin /var/log/sanluquitas-backup.log
```

## Respaldo automático (cron de `sladmin`, todos los días a las 02:00)
```bash
crontab -e
# agregar la línea:
0 2 * * * /home/sladmin/sanluquitas/scripts/backup.sh >> /var/log/sanluquitas-backup.log 2>&1
```

## Recuperar los secretos cifrados
```bash
gpg -d /srv/backups/AAAA-MM-DD/env.gpg > .env                         # pide BACKUP_PASSPHRASE
gpg -d /srv/backups/AAAA-MM-DD/moodle-config.php.gpg > config.php
```

## Estrategia 3-2-1
- **3 copias:** datos vivos en los volúmenes + `/srv/backups` en el servidor + copia en USB/disco externo.
- **2 medios:** disco del servidor y USB.
- **1 fuera del sitio:** la USB se guarda en la rectoría (otra edificación) y se rota semanalmente.

Copia a USB (montada en `/media/sladmin/RESPALDO`):
```bash
rsync -a --delete /srv/backups/ /media/sladmin/RESPALDO/sanluquitas/
```
