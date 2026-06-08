Perfecto. Vamos de a uno. Este es el **resumen ordenado de lo que ya tenemos cerrado hasta ahora** para `landini-server`.

# Resumen actual — landini-server

## 1. Identidad del servidor

Servidor destinado a infraestructura propia bajo marca **Landini**.

```text
Hostname: landini-server
Prompt visual: [ASUS-P8B75] Alejandro:~ >
IP LAN: 192.168.0.210
Interfaz: enp3s0
MAC: 50:46:5d:a0:32:15
```

Uso previsto:

```text
servidor empresarial propio
NAS interno
backups
Samba LAN
futura publicación web controlada
futuro acceso remoto seguro
base para servicios Landini
```

---

## 2. Hardware confirmado

```text
Motherboard: ASUS P8B75-M LE
CPU: Intel i7-3770
RAM: 16 GB
SSD sistema: Crucial CT240BX500SSD1, 240 GB aprox.
GPU: Intel HD Graphics 4000 integrada
Fuente: 600 W
BIOS: American Megatrends 0703, 24/08/2012
```

Notas:

* La NVIDIA GeForce 210 fue retirada.
* La gráfica integrada quedó funcionando.
* Queda pendiente evaluar BIOS más adelante, sin flashear nada hasta confirmar archivo exacto para `P8B75-M LE`.

---

## 3. Sistema operativo

```text
OS: openSUSE Tumbleweed
Snapshot observado: 20260603
Kernel observado: Linux 7.0.10-2-default
Root filesystem: Btrfs
Snapper: activo
firewalld: activo
SELinux: enabled / enforcing
```

Servicios críticos post-reboot:

```text
sshd      active / enabled
firewalld active / enabled
smb       active / enabled
nmb       active / enabled
```

No hay servicios fallidos:

```text
0 loaded units listed
```

---

## 4. Red

```text
Router LAN: 192.168.0.1
landini-server: 192.168.0.210
MacBook: 192.168.0.118
ThinkPad: 192.168.0.200
```

Reglas vigentes:

```text
No DMZ.
No port forwarding por ahora.
No abrir SSH ni Samba a internet.
```

---

## 5. SSH

SSH quedó funcionando y endurecido.

Archivo:

```text
/etc/ssh/sshd_config.d/10-landini-hardening.conf
```

Configuración esperada:

```text
PermitRootLogin no
PubkeyAuthentication yes
PasswordAuthentication no
KbdInteractiveAuthentication no
X11Forwarding no
AllowUsers alejandro
```

Validado:

* SSH activo.
* Acceso por clave desde MacBook.
* Se corrigió `~/.ssh/config` de la MacBook para usar la clave correcta `~/.ssh/landini_server`.

---

## 6. Suspensión / energía

Se desactivaron suspensiones para uso servidor.

Targets masked:

```text
sleep.target
suspend.target
hibernate.target
hybrid-sleep.target
suspend-then-hibernate.target
```

`/etc/systemd/logind.conf` corregido con:

```text
HandleSuspendKey=ignore
HandleHibernateKey=ignore
HandleLidSwitch=ignore
HandleLidSwitchExternalPower=ignore
IdleAction=ignore
```

---

## 7. Microcode

Se instaló:

```text
ucode-intel-20260512-1.1.x86_64
```

Microcode pasó de:

```text
0x12 → 0x21
```

---

## 8. KDE / entorno gráfico

Hubo problemas recurrentes con KDE/Plasma.

Hallazgos:

* `plasmashell` arrancaba.
* Existía un problema con `org.kde.plasma.icontasks`.
* Se detectó que la configuración del panel llamaba a:

```text
plugin=org.kde.plasma.icontasks
```

* Se eliminó ese applet de la configuración activa.
* Luego se detectó que el problema final estaba relacionado con **themes**.
* El usuario informó que ya encontró y solucionó el problema de KDE.

Estado actual:

```text
KDE resuelto por tema/theme.
No queda como bloqueo de infraestructura.
```

---

## 9. Firewall

`firewalld` activo.

Zona activa:

```text
public
interfaces: enp3s0
```

Servicios permitidos actualmente:

```text
dhcpv6-client
ssh
samba
```

Se limpió la zona `docker` fantasma. Actualmente no aparece zona docker activa.

---

## 10. EFI random-seed

Se corrigió warning de permisos sobre:

```text
/boot/efi/loader/random-seed
```

Antes:

```text
fmask=0022
-rwxr-xr-x
```

Ahora:

```text
fmask=0077,dmask=0077
-rwx------
```

Línea actual en `/etc/fstab`:

```text
UUID=602D-37DA /boot/efi vfat utf8,fmask=0077,dmask=0077 0 2
```

Validado post-reboot.

---

## 11. Discos

### SSD sistema

```text
/dev/sda
Modelo: Crucial CT240BX500SSD1
Uso: sistema operativo
SMART: sano
```

### WD Green 2 TB

```text
Modelo: WDC WD20EZRX-00D8PB0
Estado: NO APTO para datos importantes
```

SMART malo:

```text
Reallocated_Sector_Ct: 1765
Current_Pending_Sector: 180
Offline_Uncorrectable: 133
Power_On_Hours: 47618
```

Decisión:

```text
No usar para NAS.
No usar para backups.
No usar para staging.
Solo laboratorio/descarte/lectura puntual read-only.
```

### WD Blue 1 TB

```text
/dev/sdb
Modelo: WDC WD10EARZ-00C5XB0
Serial: WD-WX12DA3KUSAK
SMART: PASSED
Long test: Completed without error
```

Aprobado para NAS inicial.

---

## 12. NAS interno

Disco:

```text
/dev/sdb1
Filesystem: ext4
Label: LANDINI_NAS
UUID: 92818a4e-1c6b-4ace-a2df-f96485779017
Montaje: /srv/nas
```

Línea en `/etc/fstab`:

```text
UUID=92818a4e-1c6b-4ace-a2df-f96485779017  /srv/nas  ext4  defaults,nofail,x-systemd.device-timeout=10s  0  2
```

Validado post-reboot:

```text
/srv/nas /dev/sdb1 ext4 rw,relatime,seclabel
```

Espacio:

```text
916G total
870G libres aprox.
1% usado
```

Estructura creada:

```text
/srv/nas/landini
/srv/nas/landini/backups
/srv/nas/landini/compartido
/srv/nas/landini/documentos
/srv/nas/landini/staging
```

Permisos:

```text
/srv/nas/landini → alejandro:users 775
```

---

## 13. Samba LAN

Samba quedó instalado, configurado, activo y validado.

Paquetes:

```text
samba
samba-client
samba-libs
```

Servicios:

```text
smb active / enabled
nmb active / enabled
```

Share configurado:

```ini
[landini]
	comment = Landini NAS
	path = /srv/nas/landini
	browseable = yes
	read only = no
	guest ok = no
	valid users = alejandro
	force user = alejandro
	create mask = 0664
	directory mask = 0775
```

Acceso desde MacBook:

```text
smb://192.168.0.210/landini
```

Usuario:

```text
alejandro
```

Validaciones realizadas:

* El share aparece con `smbclient -L localhost`.
* Conexión local a `//localhost/landini` OK.
* Escritura vía Samba OK.
* Acceso desde MacBook detectado como cliente `192.168.0.118`.
* Prueba eliminada luego.

---

## 14. SELinux para Samba

SELinux está activo:

```text
Enforcing
```

Problema inicial:

```text
/srv/nas → unlabeled_t
```

Samba fallaba con:

```text
canonicalize_connect_path failed
AVC denied { search } ... unlabeled_t
```

Corrección aplicada:

```text
/srv/nas → samba_share_t
/srv/nas/landini → samba_share_t
```

Reglas persistentes agregadas con `semanage`:

```text
/srv/nas(/.*)?              samba_share_t
/srv/nas/landini(/.*)?      samba_share_t
```

Nota:

* La regla específica de `/srv/nas/landini(/.*)?` quedó redundante porque `/srv/nas(/.*)?` ya cubre todo.
* No molesta porque ambas apuntan a `samba_share_t`.
* Podría limpiarse más adelante, pero no es necesario ahora.

Validado post-reboot:

```text
/srv/nas              samba_share_t
/srv/nas/landini      samba_share_t
```

---

## 15. Estado fuerte post-reboot

Después del reinicio final se validó:

```text
systemctl --failed → 0 loaded units
sshd active/enabled
firewalld active/enabled
smb active/enabled
nmb active/enabled
/srv/nas montado
Samba accesible
SELinux context correcto
EFI random-seed corregido
firewall con ssh + samba
```

Esto cierra la etapa:

```text
Infraestructura LAN/NAS base: CERRADA
```

---

# Decisiones futuras tomadas

## Servidor web público

Se decidió que el servidor web será:

```text
público de acceso externo
```

Y que se puede montar un segundo SSD de 240 GB dedicado.

Diseño recomendado:

```text
SSD sistema actual → /
WD Blue 1 TB → /srv/nas
SSD nuevo 240 GB → /srv/web
```

Uso de `/srv/web`:

```text
servidor web público
sitios
apps
deployments
logs
runtime web
```

No mezclar:

```text
NAS/backups ≠ runtime web público
```

---

## Hosting como servicio

Apareció una idea estratégica fuerte:

```text
vender servicios de hosting de internet
```

Recomendación conceptual:

```text
No vender hosting genérico masivo.
Vender hosting gestionado / presencia web / sistemas alojados Landini.
```

Enfoque posible:

```text
Landini Web Gestionada
Landini Hosting Gestionado
app-base + hosting administrado + mantenimiento + backups
```

No vender inicialmente:

```text
cPanel libre
correo propio
FTP abierto
SSH a clientes
hosting compartido para código desconocido
SLA tipo datacenter
```

---

## Acceso remoto seguro

Para administración privada:

```text
Tailscale
```

Para publicación web pública:

```text
Cloudflare Tunnel
```

Lectura recomendada:

```text
Tailscale = administración privada.
Cloudflare Tunnel = publicación web pública.
```

Regla vigente:

```text
No abrir puertos del router como primera opción.
No exponer SSH.
No exponer Samba.
No DMZ.
```

---

# Backups — cierre operativo validado

Estado general:

```text
NAS local: /srv/nas
Raíz de backups: /srv/nas/landini/backups
Restic: instalado
smartmontools: instalado
rsync: instalado
SELinux: activo
/srv/nas: samba_share_t
Samba: operativo para share landini
Espacio NAS observado: 916G total, 4,8G usados, 865G disponibles, 1% uso
```

Al momento de la configuración, el NAS estaba disponible con amplio espacio libre.

Regla:

```text
NAS local no reemplaza copia offsite.
No registrar contraseñas ni secretos.
Las password files existen y quedan fuera de este documento.
```

## Estado global de timers Landini

Validado con:

```bash
systemctl list-timers --all | grep landini
```

Timers activos:

```text
landini-backup-server.timer
Service: landini-backup-server.service
Próxima ejecución observada: Mon 2026-06-08 03:38:56 EDT
Uso: backup diario servidor

landini-backup-thinkpad-home.timer
Service: landini-backup-thinkpad-home.service
Próxima ejecución observada: Mon 2026-06-08 04:09:58 EDT
Uso: backup diario ThinkPad

landini-backup-macbook-home.timer
Service: landini-backup-macbook-home.service
Próxima ejecución observada: Mon 2026-06-08 04:37:15 EDT
Uso: backup diario MacBook selectivo

landini-restic-maintenance.timer
Service: landini-restic-maintenance.service
Próxima ejecución observada: Sun 2026-06-14 04:40:34 EDT
Uso: mantenimiento semanal repo servidor

landini-restic-maintenance-thinkpad.timer
Service: landini-restic-maintenance-thinkpad.service
Próxima ejecución observada: Sun 2026-06-14 05:16:26 EDT
Uso: mantenimiento semanal repo ThinkPad

landini-restic-maintenance-macbook.timer
Service: landini-restic-maintenance-macbook.service
Próxima ejecución observada: Sun 2026-06-14 05:54:06 EDT
Uso: mantenimiento semanal repo MacBook
```

Systemd units existentes validadas:

```text
/etc/systemd/system/landini-backup-macbook-home.service
/etc/systemd/system/landini-backup-server.service
/etc/systemd/system/landini-backup-thinkpad-home.service
/etc/systemd/system/landini-restic-maintenance-macbook.service
/etc/systemd/system/landini-restic-maintenance.service
/etc/systemd/system/landini-restic-maintenance-thinkpad.service
/etc/systemd/system/landini-backup-macbook-home.timer
/etc/systemd/system/landini-backup-server.timer
/etc/systemd/system/landini-backup-thinkpad-home.timer
/etc/systemd/system/landini-restic-maintenance-macbook.timer
/etc/systemd/system/landini-restic-maintenance-thinkpad.timer
/etc/systemd/system/landini-restic-maintenance.timer
```

Scripts operativos validados:

```text
/usr/local/sbin/landini-backup-macbook-home.sh              3,7K  -rwxr-x---  root:root
/usr/local/sbin/landini-backup-server.sh                    2,7K  -rwxr-x---  root:root
/usr/local/sbin/landini-backup-thinkpad-home.sh             3,4K  -rwxr-x---  root:root
/usr/local/sbin/landini-restic-maintenance-macbook.sh       1,4K  -rwxr-x---  root:root
/usr/local/sbin/landini-restic-maintenance.sh               1,4K  -rwxr-x---  root:root
/usr/local/sbin/landini-restic-maintenance-thinkpad.sh      1,4K  -rwxr-x---  root:root
```

## Backup landini-server

Repositorio Restic:

```text
/srv/nas/landini/backups/landini-server/restic
Repo ID observado: 1708d73cbb
Password file: /root/.config/restic/landini-server.pass
```

Script estable:

```text
/usr/local/sbin/landini-backup-server.sh
```

Incluye:

```text
/etc
/root
/var/lib/samba
reportes generados del sistema
```

Salidas:

```text
Reportes: /srv/nas/landini/backups/landini-server/reports/
Logs: /srv/nas/landini/backups/_logs/
```

Snapshots validados:

```text
f5778f6c
Time: 2026-06-07 15:35:28
Host: landini-server
Tags: landini-server, manual, system-config
Paths: /etc, /root, /tmp/landini-server-report-20260607_153526, /var/lib/samba
Size: 59.344 MiB

79be230d
Time: 2026-06-07 15:40:21
Host: landini-server
Tags: landini-server, scheduled, system-config
Paths: /etc, /root, /tmp/landini-server-report-20260607_154019, /var/lib/samba
Size: 59.344 MiB
```

Validaciones:

```text
restic snapshots OK
restic check OK
restore test OK restaurando /etc/hostname
contenido restaurado: landini-server
```

Timer diario:

```text
Timer: landini-backup-server.timer
Service: landini-backup-server.service
Horario: diario 03:30 con RandomizedDelaySec=10m
Persistent: true
Estado validado: active waiting
Próxima ejecución observada: Mon 2026-06-08 03:38:56 EDT
```

## Mantenimiento Restic landini-server

Script:

```text
/usr/local/sbin/landini-restic-maintenance.sh
```

Política:

```text
keep-daily 7
keep-weekly 4
keep-monthly 6
prune
```

Validación:

```text
ejecución manual OK
restic check OK
no errors were found
```

Timer semanal:

```text
Timer: landini-restic-maintenance.timer
Service: landini-restic-maintenance.service
Horario: domingo 04:30 con RandomizedDelaySec=15m
Persistent: true
Estado validado: active waiting
Próxima ejecución observada: Sun 2026-06-14 04:40:34 EDT
```

## Backup ThinkPad

Equipo:

```text
Host: thinkpad-e450
IP: 192.168.0.200
Usuario remoto: alejandro
```

Acceso SSH dedicado:

```text
Origen: root@landini-server
Destino: alejandro@192.168.0.200
Clave privada: /root/.ssh/landini_backup_thinkpad
Clave pública: autorizada en ThinkPad
Validación SSH: hostname thinkpad-e450, whoami alejandro
```

Repositorio Restic:

```text
/srv/nas/landini/backups/thinkpad/restic
Repo ID observado: a04787ab17
Password file: /root/.config/restic/thinkpad-home.pass
```

Rutas:

```text
Staging: /srv/nas/landini/backups/thinkpad/staging-home
Reportes: /srv/nas/landini/backups/thinkpad/reports/
Script: /usr/local/sbin/landini-backup-thinkpad-home.sh
```

Origen y método:

```text
Origen: /home/alejandro de ThinkPad
Método: rsync por SSH hacia staging local en NAS
Restic: respalda staging + reporte
Tipo: pull desde landini-server
```

Exclusiones vigentes:

```text
/Descargas
/.cache
/.local/share/Trash
/.git del home
/.var
/.arduino15
/.lmstudio
/.npm
/.pnpm-store
/.composer/cache
/.config/composer/cache
/.cache/composer
/.vscode-server
/.vscode/extensions
/.codeium
/htdocs/app-base/storage/app/private
/htdocs/app-base/storage/framework
/htdocs/app-base/storage/logs
/htdocs/app-base/bootstrap/cache
node_modules
vendor
*.iso
*.img
*.qcow2
*.vdi
*.vmdk
```

Criterio:

```text
Se respalda Home depurado.
No se respaldan dependencias regenerables de Node/Composer.
No se respaldan Descargas.
No se respalda .git ubicado directamente en el Home porque no debería estar allí.
No se mezcla storage privado/runtime de app-base con el backup general del Home.
app-base runtime/data queda como frente futuro separado si corresponde.
```

Snapshots validados:

```text
c3ff03b7
Time: 2026-06-07 16:20:14
Host: landini-server
Tags: thinkpad, manual, home
Paths: /srv/nas/landini/backups/thinkpad/reports/20260607_161856, /srv/nas/landini/backups/thinkpad/staging-home
Size: 2.727 GiB
Check: OK

09ae650c
Time: 2026-06-07 16:24:57
Host: landini-server
Tags: thinkpad, scheduled, home
Paths: /srv/nas/landini/backups/thinkpad/reports/20260607_162445, /srv/nas/landini/backups/thinkpad/staging-home
Size: 2.727 GiB
Check: OK
```

Restore test:

```text
OK
archivo restaurado: .bashrc desde staging-home
contenido legible validado
limpieza OK
```

Timer diario:

```text
Timer: landini-backup-thinkpad-home.timer
Service: landini-backup-thinkpad-home.service
Horario: diario 04:00 con RandomizedDelaySec=10m
Persistent: true
Estado validado: active waiting
Próxima ejecución observada: Mon 2026-06-08 04:09:58 EDT
```

## Mantenimiento Restic ThinkPad

Script:

```text
/usr/local/sbin/landini-restic-maintenance-thinkpad.sh
```

Política:

```text
keep-daily 7
keep-weekly 4
keep-monthly 6
prune
```

Validación:

```text
ejecución manual OK
restic check OK
no errors were found
```

Timer semanal:

```text
Timer: landini-restic-maintenance-thinkpad.timer
Service: landini-restic-maintenance-thinkpad.service
Horario: domingo 05:15 con RandomizedDelaySec=15m
Persistent: true
Estado validado: active waiting
Próxima ejecución observada: Sun 2026-06-14 05:16:26 EDT
```

## Backup MacBook

Equipo:

```text
Host: MacBook-Ale.local
IP: 192.168.0.118
Usuario remoto: alejandro
```

Acceso SSH dedicado:

```text
Origen: root@landini-server
Destino: alejandro@192.168.0.118
Clave privada: /root/.ssh/landini_backup_macbook
Validación SSH: hostname MacBook-Ale.local, whoami alejandro
```

Repositorio Restic:

```text
/srv/nas/landini/backups/macbook/restic
Repo ID observado: a5292db8
Password file: /root/.config/restic/macbook-home.pass
```

Rutas:

```text
Staging: /srv/nas/landini/backups/macbook/staging-home
Reportes: /srv/nas/landini/backups/macbook/reports/
Script: /usr/local/sbin/landini-backup-macbook-home.sh
```

Origen y método:

```text
Tipo: pull desde landini-server
Método: rsync selectivo por SSH desde MacBook hacia staging local en NAS
Restic: respalda staging + reporte
```

Decisión de criterio:

```text
MacBook NO usa backup completo de /Users/alejandro.
/Users/alejandro observado: 25G.
/Users/alejandro/Library observado: 24G.
Se detectaron errores y permisos protegidos en zonas internas de macOS.
Zonas observadas: Library/Application Support/FileProvider, Library/Group Containers.
No se respalda Library completa.
No se respalda .codex completo.
No se respalda Downloads.
```

Criterio MacBook:

```text
Backup selectivo de configuración mínima y carpetas personales reales.
```

Incluye:

```text
/Users/alejandro/.ssh
/Users/alejandro/.zshrc
/Users/alejandro/.zprofile
/Users/alejandro/.config
/Users/alejandro/Desktop
/Users/alejandro/Documents
/Users/alejandro/Pictures
/Users/alejandro/Movies
/Users/alejandro/Music
/Users/alejandro/Public
```

Excluye:

```text
/Users/alejandro/.codex
/Users/alejandro/Library completo
/Users/alejandro/Downloads
caches internas de macOS
FileProvider
Group Containers
node_modules
vendor
*.dmg
*.iso
*.img
*.qcow2
*.vdi
*.vmdk
```

Rsync selectivo validado:

```text
Number of files: 662
Number of regular files transferred: 496
Total file size: 43.193.792 bytes
Total transferred file size: 43.193.346 bytes
Staging final: 44M
```

Estructura staging validada:

```text
/srv/nas/landini/backups/macbook/staging-home
/srv/nas/landini/backups/macbook/staging-home/.config
/srv/nas/landini/backups/macbook/staging-home/.config/Synergy
/srv/nas/landini/backups/macbook/staging-home/.ssh
/srv/nas/landini/backups/macbook/staging-home/.ssh/agent
/srv/nas/landini/backups/macbook/staging-home/Desktop
/srv/nas/landini/backups/macbook/staging-home/Documents
/srv/nas/landini/backups/macbook/staging-home/Movies
/srv/nas/landini/backups/macbook/staging-home/Movies/TV
/srv/nas/landini/backups/macbook/staging-home/Music
/srv/nas/landini/backups/macbook/staging-home/Music/Music
/srv/nas/landini/backups/macbook/staging-home/Pictures
/srv/nas/landini/backups/macbook/staging-home/Pictures/Fototeca de Photo Booth
/srv/nas/landini/backups/macbook/staging-home/Pictures/Photos Library.photoslibrary
/srv/nas/landini/backups/macbook/staging-home/Public
/srv/nas/landini/backups/macbook/staging-home/Public/Drop Box
```

Snapshots validados:

```text
9d2ae9d6
Time: 2026-06-07 17:39:24
Host: landini-server
Tags: macbook, manual, selective-home
Paths: /srv/nas/landini/backups/macbook/reports/20260607_173919, /srv/nas/landini/backups/macbook/staging-home
Size: 41.194 MiB

e8990720
Time: 2026-06-07 17:41:16
Host: landini-server
Tags: macbook, scheduled, selective-home
Paths: /srv/nas/landini/backups/macbook/reports/20260607_174114, /srv/nas/landini/backups/macbook/staging-home
Size: 41.194 MiB
```

Validaciones:

```text
script manual ejecutado OK
snapshot scheduled e8990720 creado
restic check OK
no errors were found
mantenimiento Restic MacBook ejecutado manualmente OK
forget + prune OK
restic check OK
restore test OK desde latest, resuelto a e8990720
archivo restaurado: /srv/nas/landini/backups/macbook/staging-home/.zshrc
destino temporal: /tmp/restic-restore-test-macbook
archivo temporal validado: /tmp/restic-restore-test-macbook/srv/nas/landini/backups/macbook/staging-home/.zshrc
tamaño validado: 187 bytes
sha256 validado: 292a6bde8229cf7649f6f52f81757612304f7613f6f79019be37cefaafdf557b
limpieza temporal OK
no se tocaron datos reales de la MacBook
timer backup diario active waiting
timer mantenimiento semanal active waiting
```

Timer diario:

```text
Timer: landini-backup-macbook-home.timer
Service: landini-backup-macbook-home.service
Estado validado: active waiting
Próxima ejecución observada: Mon 2026-06-08 04:37:15 EDT
```

## Mantenimiento Restic MacBook

Script:

```text
/usr/local/sbin/landini-restic-maintenance-macbook.sh
```

Política:

```text
keep-daily 7
keep-weekly 4
keep-monthly 6
prune
```

Validación:

```text
ejecución manual OK
forget + prune OK
restic check OK
no errors were found
```

Timer semanal:

```text
Timer: landini-restic-maintenance-macbook.timer
Service: landini-restic-maintenance-macbook.service
Estado validado: active waiting
Próxima ejecución observada: Sun 2026-06-14 05:54:06 EDT
```

## Logs y medición de espacio

Logs observados:

```text
Directorio: /srv/nas/landini/backups/_logs
landini-backup-macbook-home-20260607_174114.log
landini-backup-server-20260607_154019.log
landini-backup-thinkpad-home-20260607_162445.log
landini-restic-maintenance-20260607_154843.log
landini-restic-maintenance-macbook-20260607_175119.log
landini-restic-maintenance-thinkpad-20260607_162917.log
```

Observación:

```text
Los logs están creados como root:root.
El directorio _logs figura como alejandro:alejandro.
```

Espacio NAS validado:

```text
Comando: df -hT /srv/nas
Filesystem: /dev/sdb1
Type: ext4
Size: 916G
Used: 4,8G
Avail: 865G
Use%: 1%
Mount: /srv/nas
```

Medición visible sin sudo:

```text
/srv/nas/landini/backups/landini-server: 212K visible, incompleto por permission denied en repo/reportes root
/srv/nas/landini/backups/_logs: 44K
/srv/nas/landini/backups/macbook: 44M visible
/srv/nas/landini/backups/thinkpad: 2,9G visible
```

Regla operativa:

```text
Para medición completa de repositorios Restic creados por root, usar sudo du -sh.
Permission denied en du sin sudo no invalida backups; snapshots y restic check ya fueron validados.
```

## Estado global final de backups

Backups diarios:

```text
landini-server: activo
ThinkPad: activo
MacBook: activo
```

Mantenimientos semanales:

```text
landini-server: activo
ThinkPad: activo
MacBook: activo
```

Repositorios Restic:

```text
landini-server: validado
ThinkPad: validado
MacBook: validado
```

Restore tests:

```text
landini-server: realizado y validado restaurando /etc/hostname
ThinkPad: realizado y validado restaurando .bashrc desde staging-home
MacBook: realizado y validado restaurando .zshrc desde snapshot latest resuelto a e8990720 hacia /tmp en landini-server
```

Pendientes:

```text
Definir backup específico de app-base runtime/data si corresponde:
- base de datos
- storage privado
- archivos tenant
- dumps
Guardar contraseñas Restic fuera del servidor.
Evaluar permisos de logs si se quieren consultar cómodamente por Samba.
Evaluar si Pictures/Photos Library.photoslibrary debe mantenerse incluida en MacBook si crece mucho en el futuro.
```

---

## Offsite backup OneDrive

Frente offsite cerrado con copia selectiva semanal vía `rclone`.

Lectura doctrinal:

```text
OneDrive funciona como offsite backup selectivo semanal.
NAS local sigue siendo el backup principal.
Repos Restic locales siguen siendo la base de recuperación principal.
OneDrive no reemplaza restore tests.
OneDrive no reemplaza backup local.
OneDrive no guarda por ahora repos Restic completos.
La copia remota vigente es Landini/offsite/friday.
El contenido remoto se actualiza por rclone sync desde offsite-selection.
La automatización queda programada los viernes a las 12:00.
El timer es infraestructura de resguardo, no sistema SaaS app-base.
```

Remote rclone configurado:

```text
onedrive-landini:
```

Validación de lectura:

```text
rclone listremotes mostró onedrive-landini:
rclone lsd onedrive-landini: listó correctamente la raíz de OneDrive.
```

Prueba mínima de escritura/borrado:

```text
Archivo temporal local: /tmp/landini-rclone-test.txt
Destino remoto: onedrive-landini:Landini/_test
Validación remota: 48 landini-rclone-test.txt
Resultado: [OK] Prueba mínima rclone OneDrive finalizada
```

El archivo remoto de prueba fue eliminado y el archivo local fue limpiado.

Script:

```text
/usr/local/sbin/landini-offsite-onedrive-friday.sh
```

Lectura del script:

```text
Origen selectivo: /srv/nas/landini/offsite-selection
Destino remoto: onedrive-landini:Landini/offsite/friday
Logs: /srv/nas/landini/backups/_logs/offsite_onedrive_friday_*.log
Método: rclone sync
```

El script prepara la selección local y copia contenido seleccionado desde:

```text
/srv/nas/landini/documentos
/srv/nas/landini/compartido
/srv/nas/landini/backups/_logs
```

Reglas operativas del script:

```text
No sube repos Restic completos.
No reemplaza backup local.
No borra datos reales fuera de offsite-selection.
```

Corrección aplicada al script:

```bash
export RCLONE_CONFIG="/home/alejandro/.config/rclone/rclone.conf"
```

Motivo:

```text
El script corre con sudo.
Sin esa variable, rclone buscaba /root/.config/rclone/rclone.conf.
```

Error real observado:

```text
Config file "/root/.config/rclone/rclone.conf" not found
didn't find section in config file ("onedrive-landini")
```

Validación:

```bash
sudo RCLONE_CONFIG="/home/alejandro/.config/rclone/rclone.conf" rclone listremotes
```

Salida:

```text
onedrive-landini:
```

Ejecución manual validada:

```bash
sudo /usr/local/sbin/landini-offsite-onedrive-friday.sh
rclone lsf onedrive-landini:Landini/offsite/friday --recursive | sort
```

Resultado remoto validado:

```text
backups_logs/
compartido/
documentos/
archivos reales dentro de backups_logs
compartido/prueba_nas.txt
```

Service systemd:

```text
/etc/systemd/system/landini-offsite-onedrive-friday.service
```

Contenido funcional:

```text
Ejecuta: /usr/local/sbin/landini-offsite-onedrive-friday.sh
Tipo: oneshot
Espera red: network-online.target
```

Timer systemd:

```text
/etc/systemd/system/landini-offsite-onedrive-friday.timer
```

Frecuencia:

```text
Viernes 12:00
OnCalendar=Fri 12:00
Persistent=true
```

Validación por systemd:

```text
Service: landini-offsite-onedrive-friday.service
Estado: inactive (dead) luego de ejecutar
Resultado: code=exited, status=0/SUCCESS
TriggeredBy: landini-offsite-onedrive-friday.timer
```

Log de ejecución validado por systemd:

```text
/srv/nas/landini/backups/_logs/offsite_onedrive_friday_20260608_113243.log
```

Validación remota posterior a ejecución systemd:

```bash
rclone lsf onedrive-landini:Landini/offsite/friday --recursive | sort
```

Resultado incluyó:

```text
backups_logs/
backups_logs/landini-backup-macbook-home-20260607_174114.log
backups_logs/landini-backup-macbook-home-20260608_101858.log
backups_logs/landini-backup-server-20260607_154019.log
backups_logs/landini-backup-server-20260608_102002.log
backups_logs/landini-backup-thinkpad-home-20260607_162445.log
backups_logs/landini-backup-thinkpad-home-20260608_102701.log
backups_logs/landini-restic-maintenance-20260607_154843.log
backups_logs/landini-restic-maintenance-macbook-20260607_175119.log
backups_logs/landini-restic-maintenance-thinkpad-20260607_162917.log
backups_logs/offsite_onedrive_friday_20260608_112328.log
backups_logs/offsite_onedrive_friday_20260608_112808.log
backups_logs/offsite_onedrive_friday_20260608_113243.log
compartido/
compartido/prueba_nas.txt
documentos/
```

---

## Synergy operativo

Synergy quedó operativo para evitar uso físico de teclado/mouse en el servidor.

Servidor:

```text
Hostname: landini-server
Sistema: openSUSE Tumbleweed
Interfaz LAN activa: enp3s0
Firewall activo: firewalld
Zona activa: public
```

Proceso observado:

```text
/usr/bin/synergy
/usr/bin/synergy-client -f --no-tray --debug INFO --name landini-server --enable-crypto --profile-dir /home/alejandro/.config/Synergy --sync-language 192.168.0.118:24800
```

Lectura operativa:

```text
landini-server actúa como cliente Synergy.
MacBook 192.168.0.118 actúa como servidor Synergy.
El cliente del servidor apunta a 192.168.0.118:24800.
```

Firewall en `landini-server`:

```bash
sudo firewall-cmd --zone=public --add-port=24800/tcp --permanent
sudo firewall-cmd --reload
```

Validación:

```bash
sudo firewall-cmd --zone=public --list-ports
```

Salida:

```text
24800/tcp
```

Validación de zona:

```bash
sudo firewall-cmd --zone=public --list-all
```

Salida relevante:

```text
services: dhcpv6-client samba ssh
ports: 24800/tcp
```

Nota técnica:

```text
La prueba desde Mac hacia 192.168.0.210:24800 devolvió Connection refused.
Eso es coherente con que landini-server esté operando como cliente y no como servidor Synergy.
El puerto relevante para el esquema actual es la MacBook 192.168.0.118:24800.
```

Bloqueo de actualización:

```text
Paquete instalado: synergy-1.20.1-1.x86_64
```

`zypper se -si synergy` mostró:

```text
i+ | synergy | paquete | 1.20.1-1 | x86_64 | (Paquetes del sistema)
```

No había locks previos.

Se ejecutó:

```bash
sudo zypper addlock synergy
```

Validación posterior:

```bash
sudo zypper locks
```

Salida:

```text
1 | synergy | package | (cualquiera)
```

`zypper se -si synergy` mostró:

```text
il | synergy | paquete | 1.20.1-1 | x86_64 | (Paquetes del sistema)
```

Lectura:

```text
La l confirma que el paquete quedó bloqueado.
Synergy no debe actualizarse automáticamente con zypper dup.
La actualización de Synergy queda como acción manual, solo cuando se decida explícitamente.
```

---

# Próximos pasos ordenados

## Paso 1 — SSD para servidor web

Pendiente conectar/auditar nuevo SSD 240 GB.

Cuando esté instalado:

```bash
lsblk -o NAME,SIZE,TYPE,FSTYPE,LABEL,UUID,MOUNTPOINTS,MODEL,SERIAL
sudo smartctl -a /dev/sdX
```

No formatear hasta identificar disco exacto.

## Paso 2 — Tailscale

Pendiente configurar para administración privada.

## Paso 3 — Cloudflare Tunnel

Pendiente para publicación web pública.

Requiere decidir dominio/hostname.

## Paso 4 — Monitoreo básico

Pendiente:

```text
SMART periódico
espacio en disco
estado servicios
estado Samba
estado túneles futuros
logs críticos
```

## Paso 5 — Documento operativo

Pendiente armar documento de continuidad de infraestructura Landini.

Nombre sugerido:

```text
infraestructura_landini_server.md
```

Contenido:

```text
hardware
sistema
red
SSH
NAS
Samba
SELinux
firewall
EFI
KDE/theme
backups
offsite OneDrive
servidor web público
acceso remoto seguro
monitoreo
pendientes
```

---

# Estado resumido en una frase

`landini-server` ya quedó como **servidor LAN/NAS operativo, seguro en red local, con Samba funcional desde MacBook, SELinux enforcing, firewall activo, SSH endurecido, backups locales activos para landini-server, ThinkPad y MacBook, offsite backup selectivo semanal en OneDrive y Synergy operativo como cliente hacia MacBook**.
