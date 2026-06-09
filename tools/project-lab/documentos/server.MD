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

Puerto permitido:

```text
24800/tcp
```

HTTP LAN controlado:

```text
Zona activa: public
Interfaz activa: enp3s0
IP LAN servidor: 192.168.0.210/24
Rich rule permanente: rule family="ipv4" source address="192.168.0.0/24" port port="80" protocol="tcp" accept
```

Lectura:

```text
HTTP queda habilitado solo para la red 192.168.0.0/24.
No se agregó http como servicio global.
No se agregó https.
En este cierre LAN inicial no se configuró router, DNS público, certbot ni exposición pública.
Estado vigente de publicación staging: ver [PUBLICACION_PUBLICA_FUTURA].
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

Frente discos cerrado con base en evidencia post-reboot.

Validación post-reboot:

```text
systemctl --failed --no-pager: 0 loaded units listed.
/srv/nas montó automáticamente.
/srv/web montó automáticamente.
/srv/lab-disk no montó automáticamente, según noauto.
```

Alcance del cierre:

```text
Discos, particiones, filesystems, fstab y validación post-reboot.
No configura exposición externa.
No configura Apache, PHP, Laravel, DNS, router, certificados ni firewall externo.
```

### SSD sistema

```text
Dispositivo actual observado: /dev/sda
Modelo: CT240BX500SSD1
Serial: 2044E4C1E043
Uso: sistema operativo openSUSE
```

Particiones:

```text
/dev/sda1 → /boot/efi
/dev/sda2 → btrfs con /, /home, /srv, /var, /opt, /root, /usr/local, /.snapshots
```

No fue tocado en este frente.

### NAS principal

```text
Disco actual observado: /dev/sdd
Partición: /dev/sdd1
Modelo: WDC WD10EARZ-00C5XB0
Serial: WD-WX12DA3KUSAK
Label: LANDINI_NAS
UUID: 92818a4e-1c6b-4ace-a2df-f96485779017
Filesystem: ext4
Montaje: /srv/nas
```

fstab:

```text
UUID=92818a4e-1c6b-4ace-a2df-f96485779017  /srv/nas  ext4  defaults,nofail,x-systemd.device-timeout=10s  0  2
```

Estado post-reboot:

```text
/srv/nas /dev/sdd1 ext4 rw,relatime,seclabel
```

Espacio validado:

```text
916G total
865G libres
1% usado
```

No fue modificado en este frente.

### Volumen web experimental

```text
Disco actual observado: /dev/sdb
Partición: /dev/sdb1
Modelo: R3SL240G
Serial: E201607260021584
by-id: ata-R3SL240G_E201607260021584
Label: LANDINI_WEB
UUID: 116fadde-dd7e-4734-a5c1-a9f7c3d844b0
Filesystem: ext4
Montaje: /srv/web
```

fstab:

```text
UUID=116fadde-dd7e-4734-a5c1-a9f7c3d844b0  /srv/web  ext4  defaults,nofail,x-systemd.device-timeout=10s  0  2
```

Estado post-reboot:

```text
/srv/web /dev/sdb1 ext4 rw,relatime,seclabel
```

Espacio validado:

```text
220G total
208G libres
1% usado
```

Uso definido:

```text
volumen web experimental
app-base inicial
landings HTML livianas
staging o hosting liviano inicial
```

Restricción:

```text
No es SSD ideal definitivo.
Debe migrarse o clonarse cuando se compre un SSD nuevo.
No debe considerarse infraestructura comercial crítica sin backups y monitoreo.
```

SMART previo relevante:

```text
SMART general PASSED.
Vida útil reportada alta.
Señales de atención: sectores/eventos reasignados y errores previos.
```

Decisión:

```text
Uso permitido como web liviano experimental hasta reemplazo futuro.
```

### HDD laboratorio no crítico

```text
Disco actual observado: /dev/sdc
Partición: /dev/sdc1
Modelo: WDC WD20EZRX-00D8PB0
Serial: WD-WMC4M0E18KMY
by-id: ata-WDC_WD20EZRX-00D8PB0_WD-WMC4M0E18KMY
Label: LANDINI_LAB
UUID: bbf58f5c-10b0-4240-a2e1-0022a8e53359
Filesystem: ext4
Punto de montaje: /srv/lab-disk
```

fstab:

```text
UUID=bbf58f5c-10b0-4240-a2e1-0022a8e53359  /srv/lab-disk  ext4  defaults,noauto,nofail,x-systemd.device-timeout=5s  0  2
```

Estado post-reboot:

```text
No montó automáticamente.
```

Lectura correcta:

```text
df -hT /srv/lab-disk puede mostrar /srv cuando el volumen está desmontado, porque el punto existe dentro del filesystem base.
```

Uso definido:

```text
laboratorio
pruebas
descarte
uso no crítico
```

Restricciones:

```text
No usar para datos importantes.
No usar para backups.
No usar para staging importante.
No usar para hosting ni producción.
No montar automáticamente.
```

SMART previo relevante:

```text
Reallocated_Sector_Ct: 1765
Current_Pending_Sector: 180
Offline_Uncorrectable: 133
Power_On_Hours: 47620
```

Decisión:

```text
Disco degradado, solo laboratorio/no crítico.
```

---

## 12. NAS interno

Disco:

```text
/dev/sdd1
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
/srv/nas /dev/sdd1 ext4 rw,relatime,seclabel
```

Espacio:

```text
916G total
865G libres
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
systemctl --failed --no-pager → 0 loaded units listed.
sshd active/enabled
firewalld active/enabled
smb active/enabled
nmb active/enabled
/srv/nas montado
/srv/web montado
/srv/lab-disk no montado automáticamente, según noauto
Samba accesible
SELinux context correcto
EFI random-seed corregido
firewall con ssh + samba
```

Esto cierra la etapa:

```text
Infraestructura LAN/NAS base: CERRADA
Frente discos, particiones, filesystems, fstab y validación post-reboot: CERRADO
```

---

# Decisiones futuras tomadas

## Servidor web público

Decisión conceptual:

```text
público de acceso externo
```

Estado actual del frente discos:

```text
/srv/web existe como volumen web experimental.
Monta automáticamente post-reboot.
No hay en este cierre configuración de exposición externa.
```

Diseño vigente:

```text
SSD sistema actual → /
NAS principal → /srv/nas
Volumen web experimental → /srv/web
HDD laboratorio degradado → /srv/lab-disk noauto
```

Uso de `/srv/web`:

```text
volumen web experimental
app-base inicial
landings HTML livianas
staging o hosting liviano inicial
```

No mezclar:

```text
NAS/backups ≠ runtime web público
```

Restricción:

```text
No afirmar hosting comercial activo.
No afirmar DNS, router, certificados o firewall externo configurados.
La exposición externa queda para un frente posterior.
```

---

## Servidor web local Landini

[WEB_LAN_ESTADO]

```text
host = landini-server
ip_lan = 192.168.0.210/24
gateway_lan = 192.168.0.1
interface = enp3s0
os = openSUSE Tumbleweed
web_volume = /srv/web
web_fs = ext4,seclabel
web_size = 220G aprox.
role_landini_server = infraestructura tipo producción / servidor LAN-NAS-Web
role_thinkpad = desarrollo
public_exposure = false
```

[STACK_WEB]

```text
apache_bin = /usr/sbin/httpd
apache_service = apache2.service
apache_state = active/enabled
apache_listen = *:80
php = 8.5.7
php_fpm_service = php-fpm.service
php_fpm_state = active/enabled
php_fpm_listen = 127.0.0.1:9000
composer = 2.10.0
mariadb = 11.8.8
mariadb_service = mariadb.service
mariadb_state = active/enabled
mariadb_listen = 127.0.0.1:3306
mariadb_bases = information_schema,mysql,performance_schema,sys
mariadb_users = PUBLIC,mariadb.sys@localhost,mysql@localhost,root@localhost
app_base_db = no documentada en este cierre
```

[WEB_FIREWALL]

```text
firewalld_zone = public
firewalld_interface = enp3s0
services_allowed = dhcpv6-client,samba,ssh
ports_allowed = 24800/tcp
http_lan = rich_rule 192.168.0.0/24 tcp/80 accept
http_global_service = false
https_global_service = false
port_443_open = false
router = no_configurado
dns_publico = no_configurado
certbot = no_configurado
certificados_publicos = false
```

[WEB_PATHS]

```text
base_dirs = /srv/web/apps,/srv/web/sites,/srv/web/shared,/srv/web/logs,/srv/web/tmp
service_layout = /srv/web/sites/<service-key>/{current/public,shared/{config,logs,storage,uploads},releases,backups}
local_site = /srv/web/sites/local
panel_site = /srv/web/sites/panel
```

[VHOSTS_VIGENTES]

```text
/etc/apache2/vhosts.d/000-landini-default-deny.conf = ServerName default.landini.invalid -> hosts no declarados -> 403
/etc/apache2/vhosts.d/local-landini.conf = ServerName local.landini -> /srv/web/sites/local/current/public -> health_check_lan
/etc/apache2/vhosts.d/panel-landini.conf = ServerName panel.landini -> /srv/web/sites/panel/current/public -> servicio_real_lan
php_backend = SetHandler "proxy:fcgi://127.0.0.1:9000"
server_name_global = /etc/apache2/conf.d/landini-servername.conf -> ServerName landini-server.local
```

[VALIDACION_WEB_LAN]

```text
local.landini = HTTP_CODE 200
panel.landini = HTTP_CODE 200
no-declarado.landini = HTTP_CODE 403
test-local.landini = HTTP_CODE 403
macbook_hosts = 192.168.0.210 local.landini; 192.168.0.210 panel.landini
validacion_macbook = local.landini OK; panel.landini OK; host_no_declarado 403; test-local.landini 403
```

[LOCAL_LANDINI_CONTRATO]

```text
local.landini = health_check_lan
no_es = desarrollo,staging,sitio_cliente,app_real,servicio_real,landing_productiva,prueba_funcional,app_temporal
confirma = Apache responde,PHP responde,/srv/web montado,servidor responde en LAN
uso_para_servicios_reales = prohibido
```

[SERVICE_PANEL]

```text
service_key = panel
server_name = panel.landini
site_dir = /srv/web/sites/panel
vhost = /etc/apache2/vhosts.d/panel-landini.conf
owner = alejandro:www
dirs = 770
files = 660
status = validated_lan
public_exposure = false
https = false
dns_publico = false
router_port_forwarding = false
```

[SERVICIOS_REALES_REGLA]

```text
service_key = obligatorio
ServerName = propio
site_dir = /srv/web/sites/<service-key>/
vhost = /etc/apache2/vhosts.d/<service-key>-landini.conf
logs = /srv/web/sites/<service-key>/shared/logs/
backup_posterior_validado = obligatorio
local = reservado_health_check
prohibido_nombres_operativos = test,prueba,demo,sample,example,dummy,temporal
prohibido_sin_vhost_explicito = true
exposicion_fuera_lan = prohibida hasta definir router,DNS_publico,HTTPS_ACME,politica_publicacion
```

[HERRAMIENTAS_WEB]

```text
/usr/local/sbin/landini-web-service-create.sh = alta controlada de servicios web reales; estructura; vhost; logs; validación Apache
landini-web-service-create_validado = bash_syntax_OK,--help_OK,--dry-run_OK,--dry-run_no_crea_archivos,ownership_sitio_ajustado
/usr/local/sbin/landini-web-backup.sh = backup web; incluye /srv/web,Apache,PHP,MariaDB config,dump MariaDB,SELinux,reportes,scripts landini-*.sh
landini-web-backup_timer = landini-web-backup.timer active/enabled; OnCalendar=*-*-* 03:00:00; Persistent=true; RandomizedDelaySec=10m
orden_timers = web_backup antes de landini-backup-server.timer
```

[SELINUX_WEB]

```text
selinux = enabled/enforcing
/srv/web(/.*)? = httpd_sys_content_t
/srv/web/logs(/.*)? = httpd_log_t
/srv/web/sites/[^/]+/shared/logs(/.*)? = httpd_log_t
/srv/web/sites/[^/]+/shared/storage(/.*)? = httpd_sys_rw_content_t
/srv/web/sites/[^/]+/shared/uploads(/.*)? = httpd_sys_rw_content_t
restorecon = aplicado
contextos_validados = /srv/web,/srv/web/sites,/srv/web/sites/local/current/public,/srv/web/sites/local/shared/storage,/srv/web/sites/local/shared/uploads,/srv/web/sites/local/shared/logs,/srv/web/logs
```

[MIGRACION_LOCAL_HOST]

```text
from = test-local.landini
to = local.landini
old_site = /srv/web/sites/test-local
new_site = /srv/web/sites/local
old_vhost = /etc/apache2/vhosts.d/test-local-landini.conf
new_vhost = /etc/apache2/vhosts.d/local-landini.conf
rollback_ref = /root/landini-rollback-local-host-20260608_150352
apache_configtest = Syntax OK
old_vhost_removed = true
live_refs_test_local = false
test-local.landini = default_deny_403
backup_after_migration = /srv/nas/landini/backups/landini-server/web/backup_20260608_150436
```

[BACKUP_WEB_VIGENTE]

```text
backup_final = /srv/nas/landini/backups/landini-server/web/backup_20260608_155118
status = validado_base_web_productiva_LAN
incluye = /srv/web,Apache,PHP,MariaDB_config,dump_MariaDB,SELinux,reporte_sistema,scripts_operativos_Landini
incluye_scripts = usr/local/sbin/landini-web-backup.sh,usr/local/sbin/landini-web-service-create.sh
incluye_vhosts = apache2/vhosts.d/000-landini-default-deny.conf,apache2/vhosts.d/local-landini.conf,apache2/vhosts.d/panel-landini.conf
incluye_sites = web/sites/local/current/public/index.php,web/sites/panel/current/public/index.php
backups_validos_previos = backup_20260608_140542,backup_20260608_140709,backup_20260608_141105,backup_20260608_150436
backup_fallido_eliminado = backup_20260608_140306
logs_conservados = landini-web-backup_20260608_140709.log,landini-web-backup_20260608_141105.log
```

[RESTORE_TEST_WEB]

```text
backup_validado = /srv/nas/landini/backups/landini-server/web/backup_20260608_141105
restore_test_valido = /srv/nas/landini/backups/landini-server/web/restore-tests/restore_test_20260608_142648
reporte = /srv/nas/landini/backups/landini-server/web/restore-tests/restore_test_20260608_142648/reporte_restore_test_20260608_142648.txt
resultado = exitoso
tamano = 688K
validado = gzip_t_srv_web,apache2,php8,mariadb_dump; extraccion_srv_web; extraccion_apache; extraccion_php; my.cnf; SELinux_fcontext; SELinux_contexts; archivos_esperados; dump_MariaDB_legible; php_index_restaurado; reporte_generado
salida_index_restaurado = LANDINI WEB OK; Sitio: test-local; PHP: 8.5.7; Fecha: 2026-06-08T18:26:58+00:00
primer_intento_fallido = restore_test_20260608_142509; causa=permisos usuario normal en restore-tests; residuo=false; valido=false
criterio = backup no cierra por generarse; debe extraerse,inspeccionarse,verificarse
```

[POLITICA_INICIAL_BACKUP_WEB]

```text
backups_diarios = conservar 14 iniciales
backups_manuales_validados = conservar en fase armado inicial; luego solo hitos
restore_tests = conservar ultimo valido
reportes_restore_test = conservar
restore_tests_fallidos = eliminar si no aportan diagnostico pendiente
logs_web_backup = conservar 30 dias iniciales
rotacion_logs = no automatizada mientras volumen bajo
backups_fallidos_parciales = no validos; conservar solo diagnostico inmediato; eliminar con limpieza controlada luego de documentar motivo
```

[PUBLICACION_PUBLICA_FUTURA]

```text
estado = publicacion_HTTP_controlada_staging_validada
frente = infraestructura_Landini_publicacion_publica_futura
regla_maxima = NO_SE_ASUME; NO_SE_SUPONE
objetivo = definir criterios seguros para futura publicacion publica de servicios Landini
alcance_validado = publicar_solo_staging.landini.com.ar_por_HTTP_80
no_alcance_vigente = no_HTTPS,no_443,no_certbot,no_app-base,no_Project_Lab,no_local.landini,no_panel.landini,no_SSH,no_Samba,no_MariaDB,no_PHP-FPM
public_exposure = staging.landini.com.ar_HTTP_only

[DOMINIOS]
dominio_publico_base = landini.com.ar
registrador = NIC Argentina
proveedor_actual = DonWeb
subdominio_temporal_controlado = staging.landini.com.ar
staging_actual = A 152.171.194.12
staging_anterior = CNAME landini.com.ar
www_staging_actual = sin_registro_visible
www_staging_anterior = CNAME landini.com.ar
staging_validado = HTTP_CODE_200_REMOTE_IP_152.171.194.12
landini_server_expuesto = solo_staging_HTTP_80
no_tocado_DNS = landini.com.ar,www.landini.com.ar,MX,TXT_SPF_DKIM_DMARC,mail,app,spv2,ftp,AAAA_raiz

[LAN_ONLY]
local.landini = health_check_lan
panel.landini = servicio_real_lan
Project_Lab = LAN/local_only
SSH = no_publicar
Samba = no_publicar
MariaDB = no_publicar
PHP-FPM = no_publicar

[PUBLICABLES]
primer_publicable_futuro = staging-landini
ServerName = staging.landini.com.ar
tipo = landing_html_minima
estado = publicado_HTTP_controlado
exposicion_publica = true_HTTP_80_staging_only
site_dir = /srv/web/sites/staging-landini/
document_root = /srv/web/sites/staging-landini/current/public/
shared_dir = /srv/web/sites/staging-landini/shared/
logs_dir = /srv/web/sites/staging-landini/shared/logs/
vhost_file = /etc/apache2/vhosts.d/staging-landini.conf
access_log = /srv/web/sites/staging-landini/shared/logs/access.log
error_log = /srv/web/sites/staging-landini/shared/logs/error.log
landing_file = /srv/web/sites/staging-landini/current/public/index.html
patron_real = /srv/web/sites/<service-key>/current/public + shared/logs + shared/config + shared/storage + shared/uploads + releases + backups
document_root_no_usado = /srv/web/sites/staging-landini/public/

[STAGING_LANDINI_FASE_1]
estado = cerrada
alcance = preparacion_local_no_publica
implementacion = LAN_Host_header
publicacion_publica = false
pre_auditoria = Apache_configtest_Syntax_OK,Apache_*:80,PHP-FPM_127.0.0.1:9000,MariaDB_127.0.0.1:3306,firewalld_sin_http_global_sin_https_global,443_no_escucha,local.landini_200,panel.landini_200,staging.landini.com.ar_pre_implementacion_403,hosts_no_declarados_403
precheck_detiene = [ERROR] Ya existe /srv/web/sites/staging-landini. Detener para revisar.
estructura_parcial_detectada = /srv/web/sites/staging-landini,/srv/web/sites/staging-landini/backups,/srv/web/sites/staging-landini/backups/prechange_20260609_085902
estructura_parcial_sin = index.html,current/public,shared/logs,vhost_staging
backup_parcial_preexistente = conservado
validacion_servidor = Apache_configtest_Syntax_OK,staging.landini.com.ar_200,local.landini_200,panel.landini_200,no-declarado.landini_403
contenido_validado = STAGING LANDINI OK; Servicio staging-landini preparado localmente.
validacion_macbook = curl --resolve staging.landini.com.ar:80:192.168.0.210 -> HTTP_CODE=200
seguridad_conservada = router_no_tocado,DNS_no_tocado,DonWeb_no_tocado,firewalld_publico_no_ampliado,http_global_firewalld_false,https_global_firewalld_false,443_no_abierto,HTTPS_no_configurado,certbot_no_instalado,app_base_no_publicada,Project_Lab_no_publicado,Samba_no_publicado_publicamente,SSH_no_expuesto_publicamente_por_esta_fase,exposicion_publica_false

[STAGING_LANDINI_FASE_2_HTTP_PUBLICA]
FASE_2_PUBLICACION_HTTP_CONTROLADA_STAGING = OK
FASE_2D_DNS_CONTROLADO_STAGING = OK
estado = publicado_HTTP_80_desde_internet
HTTPS = pendiente
443 = no_abierto
certbot = no_instalado
router = Technicolor DPC3848VE DOCSIS 3.0 Gateway; Gateway_Mode=Router
router_port_forwarding = WAN_TCP_80 -> 192.168.0.210_TCP_80
router_DHCP_reserva = MAC_50:46:5d:a0:32:15 -> 192.168.0.210
tcpdump_externo = 181.238.73.148.* > 192.168.0.210.80 GET / HTTP/1.1; 192.168.0.210.80 > 181.238.73.148.* HTTP/1.1 200 OK
validacion_externa_host_header = no-declarado.landini_403,local.landini_403,panel.landini_403,staging.landini.com.ar_200
validacion_DNS = staging.landini.com.ar_A_152.171.194.12; staging_CNAME_vacio; staging_AAAA_vacio; www.staging.landini.com.ar_vacio
validacion_HTTP_DNS = staging DNS HTTP_CODE=200 REMOTE_IP=152.171.194.12 ERROR=
publicado = staging.landini.com.ar_HTTP_80
no_publicado = app-base,Project_Lab,local.landini,panel.landini,Samba,SSH,MariaDB,PHP-FPM
default_deny_publico = hosts_no_declarados_403

[PROHIBIDO]
no_modificar = landini.com.ar_raiz
no_apuntar = otros_hosts_a_landini-server_sin_decision_explicita
no_abrir = DMZ,443,http_global_firewalld,https_global_firewalld
no_instalar = certbot
no_exponer = SSH,Samba,local.landini,panel.landini,app-base,Project_Lab,backups,dumps,.env,credenciales

[AUDITORIA_RED_VALIDADA]
ip_publica_externa = 152.171.194.12
router_modelo = Technicolor DPC3848VE DOCSIS 3.0 Gateway
router_gateway_mode = Router
router_wan_ip = 152.171.194.12
CGNAT = no_detectado
doble_NAT = no_detectado_con_datos_actuales
landini_server_lan_ip = 192.168.0.210
landini_server_lan_ip_tipo = DHCP_reservada_en_router
landini_server_dhcp_reserva = MAC_50:46:5d:a0:32:15 -> 192.168.0.210
gateway_lan = 192.168.0.1
riesgo_pendiente = HTTPS_443_certbot_pendiente

[FASES]
FASE_0_POLITICA = definida_no_implementada
FASE_1_PREPARACION_LOCAL_NO_PUBLICA = cerrada_implementada_local_no_publica
FASE_2_PUBLICACION_HTTP_CONTROLADA_STAGING = OK
FASE_2D_DNS_CONTROLADO_STAGING = OK
FASE_3_HTTPS_443_CERTBOT = pendiente_decision_usuario
FASE_4_VALIDACION_POST_HTTPS = pendiente_decision_usuario
FASE_5_BACKUP_POST_PUBLICACION_HTTPS = pendiente_decision_usuario

[VALIDACION_PUBLICACION_HTTP]
estado = FASE_2_HTTP_y_FASE_2D_DNS_validadas
DNS = staging.landini.com.ar_A_152.171.194.12,CNAME_vacio,AAAA_vacio,www.staging_vacio
HTTP = http://staging.landini.com.ar -> HTTP_CODE_200_REMOTE_IP_152.171.194.12
vhosts_publicos = staging.landini.com.ar_200,no-declarado.landini_403,local.landini_403,panel.landini_403
no_tocado = landini.com.ar,www.landini.com.ar,MX,TXT_SPF_DKIM_DMARC,mail,app,spv2,ftp,AAAA_raiz
pendiente = HTTPS,443,certbot,validacion_post_HTTPS

[ROLLBACK]
rollback_DNS = volver_staging.landini.com.ar_a_DonWeb/public_html/staging
rollback_router = eliminar_port_forwarding_creado,confirmar_DMZ_false,confirmar_SSH_Samba_sin_forward
rollback_firewall = volver_estado_seguro,http_global_false,https_global_false,conservar_rich_rule_HTTP_LAN
rollback_apache = deshabilitar_staging-landini.conf,recargar_Apache,validar_default_deny_local_panel
rollback_archivos = no_tocar_local.landini,no_tocar_panel.landini,no_mezclar_servicios

[PENDIENTE_DECISION_USUARIO]
politica_HTTPS_ACME = pendiente_decision_usuario
subdominios_reservados_adicionales = pendiente_decision_usuario
rol_futuro_panel_publico = pendiente_decision_usuario
politica_staging_final = pendiente_decision_usuario
politica_hosting_clientes = pendiente_decision_usuario
```

[HOSTING_CRITERIO]

```text
idea = vender servicios de hosting de internet
criterio = no vender hosting generico masivo
orientacion = hosting gestionado / presencia web / sistemas alojados Landini
posible_nombre = Landini Web Gestionada; Landini Hosting Gestionado; app-base + hosting administrado + mantenimiento + backups
no_vender_inicialmente = cPanel_libre,correo_propio,FTP_abierto,SSH_clientes,hosting_compartido_codigo_desconocido,SLA_datacenter
```

[ACCESO_REMOTO_PUBLICACION]

```text
administracion_privada = Tailscale
publicacion_web_publica = Cloudflare Tunnel
router_puertos_primera_opcion = no
exponer_SSH = no
exponer_Samba = no
DMZ = no
```

[LIMITES_WEB_ACTUALES]

```text
no_abrir_https_firewalld = true
no_abrir_http_global = true
no_router = true
no_DNS_publico = true
no_certbot = true
no_exposicion_publica = true
no_hosting_comercial_activo = true
pendiente_antes_exposicion = dominio,politica_vhosts,politica_certificados,estrategia_logs,nombres_LAN_comodos,estructura_sitios_apps,postfix,DB_por_servicio,alta_sitio_app,deploy_app_base_como_servicio_alojado_no_raiz_especial
criterio_documental = no documentar microacciones; documentar cierres,cambios_estructurales,exposicion,seguridad,backup_restore,servicios_reales,decisiones_preventivas
```

---

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
landini-web-backup_20260608_140709.log
landini-web-backup_20260608_141105.log
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
Size: 916G
Avail: 865G
Use%: 1%
Mount: /srv/nas
```

Espacio web validado:

```text
Comando: df -hT /srv/web
Size: 220G
Avail: 208G
Use%: 1%
Mount: /srv/web
```

Medición visible sin sudo:

```text
/srv/nas/landini/backups: 4,8G
/srv/nas/landini/backups/landini-server: 212K visible, incompleto por permission denied en repo/reportes root
/srv/nas/landini/backups/landini-server/web: 2,8M antes de limpieza
/srv/nas/landini/backups/_logs: 80K
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
web local: activo
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
web local: realizado y validado de forma no destructiva desde backup_20260608_141105 hacia restore_test_20260608_142648
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

Pendiente comprar SSD definitivo para reemplazar el volumen web experimental.

Cuando exista el SSD nuevo:

```bash
lsblk -o NAME,SIZE,TYPE,FSTYPE,LABEL,UUID,MOUNTPOINTS,MODEL,SERIAL
sudo smartctl -a /dev/sdX
```

No formatear, migrar ni clonar hasta identificar disco exacto.

Decisión actual:

```text
/srv/web puede usarse solo como web liviano experimental hasta reemplazo futuro.
No considerar infraestructura comercial crítica sin backups y monitoreo.
```

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
estado apache2/php-fpm/mariadb
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
discos
NAS
Samba
SELinux
firewall
EFI
KDE/theme
backups
offsite OneDrive
servidor web público
servidor web local
backups web
acceso remoto seguro
monitoreo
pendientes
```

---

# Estado consolidado

```text
landini-server = LAN/NAS/Web operativo
SELinux = enforcing
firewalld = active
SSH = hardened
Samba = LAN
NAS = mounted
WEB = mounted
HTTP = LAN_only
default_deny = active
local.landini = health_check
panel.landini = service_lan
backup = active_local + web_restore_validated + offsite_selective
Synergy = client_to_MacBook
public_exposure = false
```
