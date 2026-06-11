<<SECTION: METADATOS>>
SECTION_VERSION: 00001
TÍTULO: Deploy y actualización de app-base
DOC_SLUG: deploy_app_base
DOC_VERSION: 00001
TIPO: operativo-técnico
ESTADO: borrador-activo
GESTIÓN: Documento operativo para despliegue y actualización de app-base desde desarrollo hacia landini-server.
<<END SECTION>>

<<SECTION: OBJETIVO>>
SECTION_VERSION: 00001

Definir el proceso operativo para desplegar y actualizar app-base desde el entorno de desarrollo hacia landini-server, preservando runtime, configuración sensible, datos, seguridad, backups y validaciones.

```text
responde = estado_real_runtime_publicado,actualizacion_staging,preservacion_servidor,regeneracion_deploy,validaciones_obligatorias,backups,proteccion_env_storage_datos,validacion_DB_HTTP_HTTPS,rollback_inicial,pendientes_releases
no_define = infraestructura_base,vhosts,DNS,router,certificados,hosting_comercial
fuente_estado_infraestructura = server.MD
funcion_deploy.MD = procedimiento_operativo
```

<<END SECTION>>

<<SECTION: ESTADO REAL DEL RUNTIME STAGING>>
SECTION_VERSION: 00001

```text
servidor = landini-server
servicio = staging-landini
url = https://staging.landini.com.ar
ruta_runtime = /srv/web/sites/staging-landini/current
DocumentRoot = /srv/web/sites/staging-landini/current/public
estado_real = staging_app_base_laravel_publicado_controlado
current = carpeta_real_no_repo_Git
current_public = DocumentRoot_vigente
app_symlink = /srv/web/sites/staging-landini/app -> /srv/web/sites/staging-landini/current
current_como_symlink = no_usar_sin_validacion_explicita_por_incidente_403_AH00037
hosting_comercial_activo = false
releases_formales = no_implementadas
rollback_automatico = no_implementado
```

```text
runtime_laravel_validado = PHP_8.5.7,Composer_2.10.0,Laravel_Framework_13.13.0
APP_DIR = /srv/web/sites/staging-landini/current
APP_NAME = App Base Staging
APP_ENV = staging
APP_DEBUG = false
APP_URL = https://staging.landini.com.ar
```

```text
DB_CONNECTION = mysql
DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_DATABASE = app_base_staging
DB_USERNAME = app_base_staging
DB_PASSWORD = definido_no_vacio_no_documentar_valor
DB_OK = 1
```

```text
MariaDB = escucha_solo_127.0.0.1:3306
usuario_DB = app_base_staging@127.0.0.1
usuarios_no_presentes = app_base_staging@%,app_base_staging@localhost
grants = limitados_a_app_base_staging.*
bases_visibles_usuario_laravel = app_base_staging,information_schema
tables_count = 49
```

<<END SECTION>>

<<SECTION: PRINCIPIOS DEL DEPLOY>>
SECTION_VERSION: 00001

```text
principio_1 = NO_SE_ASUME
principio_2 = NO_SE_SUPONE
principio_3 = current_no_es_repo_Git
principio_4 = .env_no_se_copia_desde_desarrollo
principio_5 = storage_no_se_pisa_con_storage_local
principio_6 = node_modules_no_se_copia
principio_7 = composer_update_no_se_ejecuta_en_servidor
principio_8 = backup_pre_deploy_antes_de_tocar_current
principio_9 = backup_post_deploy_despues_de_validar
principio_10 = restore_test_no_se_declara_si_no_se_ejecuto
```

```text
autoridad_config_sensible = servidor
autoridad_codigo_fuente = Git_HEAD_desarrollo
autoridad_estado_infraestructura = server.MD
autoridad_validacion = consola_runtime_y_Project_Lab
```

<<END SECTION>>

<<SECTION: MÉTODO ACTUAL RECOMENDADO>>
SECTION_VERSION: 00001

```text
metodo_actual = paquete_generado_desde_Git_HEAD_desarrollo
formato_base = git_archive_HEAD
destino_temporal = incoming_temporal_en_servidor
sincronizacion = rsync_hacia_current_con_preservacion_explicita
no_metodo_actual = git_pull_en_current,copia_manual_completa,copiar_node_modules,copiar_.git,copiar_.env_local,copiar_storage_local,composer_update_servidor
automatizacion_deploy = pendiente_no_implementada
```

Comandos de referencia del flujo, no declarados como script implementado:

```text
generar_paquete = git archive HEAD
dependencias_php = composer install --no-dev --optimize-autoloader
dependencias_js = npm ci
assets = npm run build
migraciones = php artisan migrate --force
caches = php artisan optimize:clear; php artisan config:cache; php artisan route:cache; php artisan view:cache
backup = landini-web-backup.service
```

<<END SECTION>>

<<SECTION: ELEMENTOS QUE DEBEN PRESERVARSE>>
SECTION_VERSION: 00001

```text
preservar = .env,storage/
.env = /srv/web/sites/staging-landini/current/.env
storage = /srv/web/sites/staging-landini/current/storage
secreto = no_imprimir_no_copiar_desde_desarrollo_no_documentar_valores
datos = no_pisar_DB_no_pisar_uploads_no_pisar_storage_runtime
bootstrap_cache = preservar_directorio_y_permisos; contenido_controlado_por_artisan
permisos_runtime = storage_y_bootstrap/cache_alejandro:www_drwxrwsr-x
SELinux_runtime = storage_y_bootstrap/cache_httpd_sys_rw_content_t
```

<<END SECTION>>

<<SECTION: ELEMENTOS QUE SE REGENERAN>>
SECTION_VERSION: 00001

```text
regenerar_o_controlar = bootstrap/cache/,public/build/,vendor/,caches_Laravel
vendor = composer_install_no_dev_optimize_autoloader
public_build = npm_ci_y_npm_run_build
bootstrap_cache = artisan_optimize_clear_config_cache_route_cache_view_cache
no_regenerar_desde_local = .env,storage/,node_modules,.git
```

<<END SECTION>>

<<SECTION: PROCESO OPERATIVO DE ACTUALIZACIÓN>>
SECTION_VERSION: 00001

```text
paso_01 = validar_desarrollo_local
paso_02 = commit_local_del_cambio
paso_03 = generar_paquete_con_git_archive_HEAD
paso_04 = copiar_paquete_al_servidor
paso_05 = ejecutar_backup_web_pre_deploy
paso_06 = extraer_paquete_en_incoming_temporal
paso_07 = rsync_hacia_current_preservando_.env_storage_bootstrap/cache
paso_08 = composer_install_no_dev_optimize_autoloader
paso_09 = npm_ci
paso_10 = npm_run_build
paso_11 = php_artisan_migrate_force
paso_12 = artisan_optimize_clear_config_cache_route_cache_view_cache
paso_13 = revisar_permisos_storage_y_bootstrap/cache
paso_14 = aplicar_restorecon_si_corresponde
paso_15 = validar_Laravel_DB_OK
paso_16 = validar_HTTPS_LOCAL_FORCED_CODE_200
paso_17 = validar_HTTP_LOCAL_FORCED_CODE_301
paso_18 = ejecutar_backup_web_post_deploy
paso_19 = documentar_solo_si_cambia_infraestructura_contrato_operativo_o_evidencia_relevante
```

```text
rsync_preserva = .env,storage/,bootstrap/cache/
rsync_excluye = .git,node_modules,.env,storage/,bootstrap/cache/
current_destino = /srv/web/sites/staging-landini/current
incoming = temporal_no_runtime_publico
```

<<END SECTION>>

<<SECTION: VALIDACIONES POST-DEPLOY>>
SECTION_VERSION: 00001

```text
validar_artisan_version = php artisan --version
validar_migraciones = php artisan migrate:status
validar_entorno = php artisan tinker con APP_ENV,APP_DEBUG,APP_URL,DB_DATABASE,DB_OK
validar_https = curl HTTPS local forzado con --resolve staging.landini.com.ar:443:127.0.0.1
validar_http = curl HTTP local forzado con --resolve staging.landini.com.ar:80:127.0.0.1
si_falla = revisar_tail_logs_apache_php_laravel
```

```text
validacion_HTTP_HTTPS_vigente = 2026-06-11T14:15:20-03:00
apache_configtest = Syntax_OK
HTTPS_LOCAL_FORCED = CODE_200_REMOTE_IP_127.0.0.1_SSL_VERIFY_20_REDIRECT_vacio_ERROR_vacio
HTTP_LOCAL_FORCED = CODE_301_REMOTE_IP_127.0.0.1_REDIRECT_https://staging.landini.com.ar/_ERROR_vacio
HTTPS_NO_DECLARADO = CODE_403_REMOTE_IP_127.0.0.1_ERROR_vacio
HTTP_NO_DECLARADO = CODE_403_REMOTE_IP_127.0.0.1_ERROR_vacio
default_deny = validado
errores_historicos = permisos_previos,PCRE_JIT,symlink_403_AH00037
```

<<END SECTION>>

<<SECTION: BACKUPS PRE Y POST DEPLOY>>
SECTION_VERSION: 00001

```text
backup_pre_deploy = landini-web-backup.service_antes_de_tocar_current
backup_post_deploy = landini-web-backup.service_despues_de_validar
backup_web_vigente_post_deploy = /srv/nas/landini/backups/landini-server/web/backup_20260611_141637
backup_post_deploy_estado = generado_y_validado_con_integridad_tar_gzip
backup_contiene_runtime_app_base = true
restore_test_post_deploy = pendiente_no_declarado
```

```text
backup_20260611_141637_incluye = files/srv_web_20260611_141637.tar.gz,files/apache2_20260611_141637.tar.gz,files/php8_20260611_141637.tar.gz,files/letsencrypt_20260611_141637.tar.gz,files/my.cnf.d_20260611_141637.tar.gz,files/my.cnf_20260611_141637,db/mariadb_all_20260611_141637.sql.gz,reports/estado_stack_web_20260611_141637.txt,selinux/contexts_srv_web_20260611_141637.txt,selinux/fcontext_srv_web_20260611_141637.txt,scripts/landini_sbin_20260611_141637.tar.gz
srv_web_tar_markers = web/sites/staging-landini/current/composer.json,web/sites/staging-landini/current/vendor/autoload.php,web/sites/staging-landini/current/artisan,web/sites/staging-landini/current/public/index.php,web/sites/staging-landini/current/.env
srv_web_tar_dirs = web/sites/staging-landini/current/app/,web/sites/staging-landini/current/routes/,web/sites/staging-landini/current/vendor/,web/sites/staging-landini/current/public/
tar_prefix = web/
```

<<END SECTION>>

<<SECTION: ROLLBACK INICIAL>>
SECTION_VERSION: 00001

```text
rollback_actual = manual
fuente_rollback = backup_web_o_copia_previa
rollback_automatico = no_implementado
releases_versionados = no_implementados
switch_current = no_declarado
restore_test_post_deploy = pendiente
regla = no_declarar_rollback_exitoso_sin_ejecucion_y_validacion
```

<<END SECTION>>

<<SECTION: NO HACER>>
SECTION_VERSION: 00001

```text
no_hacer = git_pull_en_current
no_hacer = copiar_carpeta_manual_completa
no_hacer = copiar_node_modules
no_hacer = copiar_.git
no_hacer = copiar_.env_local
no_hacer = copiar_storage_local
no_hacer = composer_update_en_servidor
no_hacer = imprimir_secretos
no_hacer = publicar_desde_/srv/web/apps
no_hacer = usar_current_como_symlink_sin_validacion_explicita
no_hacer = declarar_restore_test_no_ejecutado
no_hacer = declarar_releases_formales_no_implementados
no_hacer = declarar_rollback_automatico_no_implementado
no_hacer = declarar_hosting_comercial_activo
```

<<END SECTION>>

<<SECTION: PENDIENTES PARA MADURAR EL DEPLOY>>
SECTION_VERSION: 00001

```text
pendiente = evaluar_releases_con_rollback_formal
pendiente = evaluar_current_sin_symlink_o_mecanismo_switch_seguro
pendiente = script_landini-app-base-staging-deploy.sh
pendiente = restore_test_especifico_post_deploy
pendiente = deploy_produccion_futura_separado_de_staging
pendiente = politica_migraciones_entornos_criticos
pendiente = separar_usuario_runtime_DB_y_usuario_migraciones_si_corresponde
pendiente = monitoreo_de_deploy
pendiente = documentacion_incidentes_y_rollback
```

<<END SECTION>>
