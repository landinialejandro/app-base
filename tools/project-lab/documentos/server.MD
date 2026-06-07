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
```

Al momento de la configuración, el NAS estaba disponible con amplio espacio libre.

Regla:

```text
NAS local no reemplaza copia offsite.
No registrar contraseñas ni secretos.
Las password files existen y quedan fuera de este documento.
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
f5778f6c  tags: landini-server, manual, system-config
79be230d  tags: landini-server, scheduled, system-config
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
c3ff03b7  tags: thinkpad, manual, home  tamaño procesado observado: 2.727 GiB  check OK
09ae650c  tags: thinkpad, scheduled, home  check OK
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
```

Timers activos validados:

```text
landini-backup-server.timer
landini-backup-thinkpad-home.timer
landini-restic-maintenance.timer
landini-restic-maintenance-thinkpad.timer
```

Pendientes:

```text
Definir backup de MacBook.
Definir backup específico de app-base runtime/data si corresponde:
- base de datos
- storage privado
- archivos tenant
- dumps
Definir backup offsite:
- disco externo rotativo
- nube futura
- otro servidor
Guardar contraseñas Restic fuera del servidor.
Evaluar permisos de logs si se quieren leer cómodamente por Samba.
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
servidor web público
acceso remoto seguro
monitoreo
pendientes
```

---

# Estado resumido en una frase

`landini-server` ya quedó como **servidor LAN/NAS operativo, seguro en red local, con Samba funcional desde MacBook, SELinux enforcing, firewall activo, SSH endurecido y backups locales validados para landini-server y ThinkPad**.
