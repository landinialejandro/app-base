PROYECTO: app-base (Laravel SaaS)
ENTORNO

OS: Linux
Web: Apache
PHP: 8.4
Laravel: 12.53
DB: MariaDB 11.8

URL local:

http://app-base.local
CONFIGURACIÓN
DB_DATABASE=app_base
DB_USERNAME=app_base
SESSION_DRIVER=database

sesiones almacenadas en DB

ESTADO DEL SISTEMA
Infraestructura básica

(chat 01)

Laravel instalado y funcionando
Migraciones base ejecutadas
Tabla sessions existente
Apache configurado
Permisos storage y cache correctos
Usuario DB dedicado configurado

OBJETIVO DEL PROYECTO

Desarrollar una plataforma SaaS multi-tenant para gestión empresarial.

El sistema permitirá que múltiples empresas utilicen la misma aplicación manteniendo aislamiento lógico de datos.

ARQUITECTURA DEFINIDA

(chat 01)

multi-tenant por empresa (tenant)

multi-sucursal (branches)

usuarios globales con memberships

invitaciones con token y expiración

RBAC por tenant con alcance opcional por sucursal

numeración de documentos con prefijo de 3 letras

MODELO DE DATOS IMPLEMENTADO
tenants

(chat 02)

Representa una empresa dentro del sistema.

Características:

id UUID
name
slug único
settings JSON opcional
branches

(chat 02)

Sucursales de cada tenant.

Campos principales:

id
tenant_id
name
code
address
city

Restricción importante:

UNIQUE (tenant_id, name)

Evita duplicar sucursales dentro de la misma empresa.

users

(chat 01)

Usuarios globales del sistema.

Un usuario puede pertenecer a múltiples tenants.

memberships

(chat 02)

Relación entre usuarios y tenants.

users ↔ memberships ↔ tenants

Campos:

tenant_id
user_id
status
is_owner
joined_at
blocked_at
blocked_reason

Permite:

usuarios en múltiples empresas
roles por empresa
control de acceso

roles

(chat 02)

Roles específicos por tenant.

Ejemplo:

admin
operator
supervisor
permissions

(chat 02)

Permisos del sistema agrupados por módulos.

Ejemplo:

invoices.view
invoices.create
users.manage
role_permission

(chat 02)

Relación entre roles y permisos.

membership_role

(chat 02)

Relación entre membership y roles.

Esto permite que el rol sea por empresa, no global.

invitations

(chat 02)

Sistema de invitaciones para agregar usuarios a una empresa.

Campos:

tenant_id
email
token
expires_at
accepted_at
accepted_ip
user_agent

Flujo:

1 usuario crea invitación
2 sistema genera token
3 invitado accede con enlace
4 se crea usuario si no existe
5 se crea membership

document_sequences

(chat 02)

Tabla para numeración de documentos.

Diseñada para soportar:

prefijo de 3 letras
contador incremental
numeración por tenant

Ejemplo:

INV-000001
ORD-000034
AUTENTICACIÓN

(chat 02)

Se implementó Laravel Fortify.

Rutas activas:

/login
/register
/logout

Características:

autenticación basada en Fortify
rate limiter configurado
sesiones almacenadas en DB

SISTEMA MULTI-TENANT

(chat 02)

Se implementó un middleware para resolver el tenant activo.

Archivo:

app/Http/Middleware/ResolveTenant.php

El tenant se resuelve en este orden:

1️⃣ session('tenant_id')
2️⃣ header X-Tenant

(fallback para API o debugging)

Cuando se resuelve:

app()->instance('tenant', $tenant);

Esto permite acceder al tenant desde cualquier parte de la aplicación.

FLUJO SAAS IMPLEMENTADO

(chat 02)

login
   ↓
/tenants/select
   ↓
selección de empresa
   ↓
POST /tenants/select/{tenant}
   ↓
session('tenant_id')
   ↓
ResolveTenant middleware
   ↓
dashboard
RUTAS PRINCIPALES

(chat 02)

GET  /
GET  /login
POST /login

GET  /tenants/select
POST /tenants/select/{tenant}

GET  /whoami
GET  /accept-invitation/{token}
DEBUG TENANT

(chat 02)

Ruta implementada:

GET /whoami

Permite verificar qué tenant está resolviendo el middleware.

Ejemplo de respuesta:

{
  "tenant_id": "...",
  "tenant_slug": "demo"
}
DESARROLLO FUNCIONAL
Dashboard inicial

(chat 03)

Se implementó un dashboard básico protegido por tenant.

Ruta:

GET /dashboard

Middleware:

auth
tenant

El dashboard muestra información del tenant activo y enlaces a módulos del sistema.

SISTEMA DE AISLAMIENTO DE DATOS

(chat 03)

Se implementó un sistema automático de aislamiento de datos por tenant.

Se crearon traits reutilizables para todos los modelos del sistema.

TenantScoped

Archivo:

app/Models/Concerns/TenantScoped.php

Funcionalidad:

Aplica automáticamente el filtro:

tenant_id = tenant_actual

en todas las consultas Eloquent.

Además asigna automáticamente el tenant al crear registros:

$model->tenant_id = app('tenant')->id;

Esto asegura que ningún registro pueda crearse sin pertenecer a una empresa.

ResolvesTenantRouteBinding

(chat 03)

Trait utilizado para proteger el acceso a registros mediante URL.

Archivo:

app/Models/Concerns/ResolvesTenantRouteBinding.php

Función:

Cuando Laravel resuelve rutas como:

/projects/{project}

la consulta se convierte automáticamente en:

id = X
AND tenant_id = tenant_actual
AND deleted_at IS NULL

Esto evita que un usuario pueda acceder por URL a datos de otra empresa.

SOPORTE PARA SOFT DELETE

(chat 03)

Los modelos empresariales utilizan SoftDeletes.

Esto permite:

borrado lógico de registros
evitar pérdida de datos
bloquear acceso por URL a registros eliminados

PRIMER MÓDULO EMPRESARIAL
Projects

(chat 03)

Se implementó el primer módulo empresarial del sistema.

Modelo:

Project

Tabla:

projects

Campos principales:

id
tenant_id
name
description
created_at
updated_at
deleted_at

El modelo utiliza:

TenantScoped
ResolvesTenantRouteBinding
SoftDeletes
ProjectController

(chat 03)

Se creó un controller completo para gestión de proyectos.

Archivo:

app/Http/Controllers/ProjectController.php

Operaciones implementadas:

index
create
store
show
edit
update
destroy
Rutas de proyectos

(chat 03)

GET     /projects
GET     /projects/create
POST    /projects
GET     /projects/{project}
GET     /projects/{project}/edit
PUT     /projects/{project}
DELETE  /projects/{project}

Todas protegidas por:

auth
tenant
SEGUNDO MÓDULO EMPRESARIAL
Clients

(chat 03)

Se implementó el segundo módulo del sistema.

Modelo:

Client

Tabla:

clients

Campos:

id
tenant_id
name
email
phone
notes
created_at
updated_at
deleted_at

El modelo utiliza:

TenantScoped
ResolvesTenantRouteBinding
SoftDeletes

Esto confirma que el patrón multi-tenant es reutilizable para nuevos módulos.

ClientController

(chat 03)

Controller CRUD completo.

Operaciones:

index
create
store
show
edit
update
destroy
Rutas de clientes

(chat 03)

GET     /clients
GET     /clients/create
POST    /clients
GET     /clients/{client}
GET     /clients/{client}/edit
PUT     /clients/{client}
DELETE  /clients/{client}
PRUEBAS DE AISLAMIENTO MULTI-TENANT

(chat 03)

Se verificó el correcto aislamiento entre empresas.

Pruebas realizadas:

crear registros en un tenant
cambiar de tenant
verificar que los registros no aparecen en el otro tenant
intentar acceder por URL directa

Resultados:

404 Not Found

Esto confirma que el sistema protege correctamente el acceso entre tenants.

ESTADO FUNCIONAL ACTUAL

(chat 03)

El sistema SaaS ya posee:

infraestructura multi-tenant completa
middleware de resolución de tenant
autenticación con Fortify
sistema de memberships
sistema de invitaciones
selector de tenant
aislamiento automático de datos
protección de rutas por tenant
soft delete en módulos empresariales

Módulos implementados:

Projects
Clients
ESTADO DEL PROYECTO

La arquitectura SaaS ya está funcional y validada.

El sistema soporta:

múltiples empresas
usuarios en múltiples empresas
aislamiento automático de datos
módulos empresariales reutilizables

La base del sistema está lista para escalar a nuevos módulos.

PRÓXIMOS PASOS

Implementar layout visual del sistema

mejorar dashboard con métricas

crear middleware de permisos RBAC

agregar relaciones entre módulos (ej: projects ↔ tasks)

iniciar desarrollo de módulos empresariales adicionales

ejemplos:

tasks
invoices
products
orders