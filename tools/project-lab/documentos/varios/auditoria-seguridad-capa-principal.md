# Auditoría de seguridad - capa principal

Fecha: 12 de mayo de 2026

## Visión general

Proyecto: Laravel App-Base

Esta auditoría revisa la capa de seguridad principal del proyecto, enfocándose en autenticación, sesiones, autorización y configuración de seguridad.

## Vulnerabilidades críticas

### 1. Rate limiting ausente en login externo

Ubicación: `app/Http/Controllers/SelfServiceSalesAccessController.php`

- No existe throttling o rate limiting en el acceso externo (`store()`).
- Esto permite ataques de fuerza bruta ilimitados.
- Impacto: compromiso de cuentas externas y posible denegación de servicio.

### 2. Sesiones externas sin expiración forzada

Ubicación: `app/Support/SelfServiceSales/SelfServiceExternalSession.php`

- La sesión externa se mantiene mientras las claves existen en session.
- No hay expiración automática ni cierre forzado.
- Impacto: sesiones persistentes y riesgo de robo de sesión.

### 3. Validación insuficiente de tenant por ruta

Ubicación: `app/Http/Middleware/ResolveTenant.php`

- El middleware valida tenant desde sesión o encabezado X-Tenant.
- No se valida correctamente la consistencia con la ruta actual.
- Impacto: posible acceso cruzado entre tenants.

## Vulnerabilidades medias

### 4. CORS no configurado

- No se ha encontrado configuración CORS activa en el proyecto.
- Riesgo: peticiones desde orígenes no autorizados.

### 5. Validación de contraseñas débil

Ubicación: `app/Support/SelfServiceSales/SelfServiceCustomerCredentialService.php`

- Requisito actual: mínimo 8 caracteres.
- No se exige complejidad adicional.
- Riesgo: contraseñas débiles aceptadas.

### 6. Sesiones sin encriptar

Ubicación: `config/session.php`

- La opción `encrypt` está configurada mediante `env('SESSION_ENCRYPT', false)`.
- Riesgo: datos sensibles de sesión almacenados sin cifrar.

## Fortalezas detectadas

- Uso de Laravel Fortify para autenticación.
- Políticas de autorización implementadas.
- Middleware específico para superadmin y tenant.
- Hashing de contraseñas correcto con `Hash::make()` y `Hash::check()`.
- Validación de input bien aplicada en controladores.

## Recomendaciones inmediatas

1. Implementar rate limiting en `SelfServiceSalesAccessController::store()`.
2. Añadir expiración forzada a sesiones externas en `SelfServiceExternalSession`.
3. Mejorar validación tenant-ruta en `ResolveTenant`.

## Recomendaciones de mejora

4. Configurar CORS restrictivo.
5. Fortalecer políticas de contraseña (longitud y complejidad).
6. Habilitar cifrado de sesiones (`encrypt => true`).

## Plan de acción sugerido

- Fase 1: corregir las 3 vulnerabilidades críticas.
- Fase 2: aplicar mejoras de configuración y políticas.
- Fase 3: añadir MFA y CSP para protección avanzada.

## Conclusión

La aplicación cuenta con buenas prácticas generales, pero tiene debilidades críticas en la capa de acceso externo y gestión de sesiones que deben repararse de inmediato.
