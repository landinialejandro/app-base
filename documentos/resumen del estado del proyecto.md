PROYECTO: app-base (Laravel SaaS)

ENTORNO
- OS: Linux
- Web: Apache
- PHP: 8.4
- Laravel: 12.53
- DB: MariaDB 11.8
- URL local: http://app-base.local

CONFIGURACIÓN
- DB_DATABASE=app_base
- DB_USERNAME=app_base
- SESSION_DRIVER=database
- sesiones almacenadas en DB

ESTADO DEL SISTEMA
- Laravel instalado y funcionando
- Migraciones base ejecutadas
- Tabla sessions existente
- Apache configurado
- Permisos storage y cache correctos
- Usuario DB dedicado configurado

OBJETIVO DEL PROYECTO
Desarrollar una plataforma SaaS multi-tenant para gestión empresarial.

ARQUITECTURA DEFINIDA
- multi-tenant por empresa (tenant)
- multi-sucursal (branches)
- usuarios globales con memberships
- invitaciones con token y expiración
- RBAC por tenant con alcance opcional por sucursal
- numeración de documentos con prefijo de 3 letras

PRÓXIMO PASO
Diseñar migraciones para:
- tenants
- branches
- memberships
- invitations
- roles / permissions
- document_sequences
