<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# Aplicación Base Multi-Organización

Sistema base reutilizable con autenticación, multi-organización, roles y permisos, desarrollado con Laravel 12 y Filament.

## ✨ Características Principales

### 🔐 Autenticación y Seguridad
- Login/registro con email y contraseña (gestionado por Filament)
- Verificación de email obligatoria
- Email único en toda la base de datos
- Rate limiting en login
- Preparado para 2FA a futuro

### 🏢 Multi-organización
- Registro crea automáticamente una organización
- Usuario pertenece a una sola organización
- Cada organización es independiente
- Superadmin de plataforma con acceso global

### 👥 Roles y Permisos
- **Admin**: Fundador de la organización, gestión completa
- **Supervisor**: Permisos intermedios
- **User**: Usuario básico
- **Superadmin**: Acceso global a todas las organizaciones

### 📧 Invitaciones y Aprobaciones
- Admin invita usuarios por email
- Link único con token de 7 días
- Usuario invitado queda pendiente de aprobación
- Admin aprueba/rechaza nuevos usuarios

### 🔄 Gestión de Bajas
- Usuarios pueden solicitar su baja
- Admin aprueba/rechaza solicitudes
- Soft delete implementado (los datos se conservan)
- Cierre de empresa solo por el creador (baja masiva de usuarios)

### 📊 Paneles Administrativos
- **Panel Usuarios** (`/app`): Dashboard personalizado por rol
- **Panel Superadmin** (`/super`): Gestión global de organizaciones
- Estadísticas en tiempo real
- Filtros avanzados

### 📈 Auditoría
- Registro de logins exitosos/fallidos
- IP y user-agent guardados
- Acciones sensibles de admin registradas
- Logs inmutables con ActivityLog

## 🛠️ Tecnologías Utilizadas

- **Laravel 12** - Framework PHP
- **Filament 3** - Panel administrativo profesional
- **MySQL** - Base de datos
- **Tailwind CSS** - Estilos
- **Spatie Activity Log** - Auditoría

## 📋 Estructura del Proyecto

```
app-base/
├── app/
│   ├── Filament/
│   │   ├── User/          # Panel de usuarios (/app)
│   │   └── Super/         # Panel superadmin (/super)
│   ├── Http/
│   │   ├── Controllers/   # Controladores personalizados
│   │   └── Middleware/    # Middleware de organización
│   ├── Models/
│   │   ├── User.php       # Con roles y organización
│   │   ├── Organization.php
│   │   └── Invitation.php
│   └── Providers/
│       └── Filament/       # Configuración de paneles
├── database/
│   ├── migrations/         # Estructura completa
│   └── seeders/
│       └── DatabaseSeeder.php # Datos de prueba
└── resources/
    └── views/              # Vistas (welcome personalizada)
```

## 🚀 Instalación

```bash
# Clonar repositorio
git clone [tu-repositorio]
cd app-base

# Instalar dependencias PHP
composer install

# Instalar dependencias frontend
npm install && npm run build

# Configurar entorno
cp .env.example .env
# Editar .env con tus datos de base de datos

# Generar clave
php artisan key:generate

# Ejecutar migraciones y seeders
php artisan migrate --seed

# Iniciar servidor
php artisan serve
```

## 🔑 Credenciales de Prueba

### Superadmin
- **URL:** `/super/login`
- **Email:** `super@admin.com`
- **Password:** `admin123`

### Admin Organización
- **URL:** `/app/login`
- **Email:** `admin@demo.com`
- **Password:** `password`

### Usuario Normal
- **URL:** `/app/login`
- **Email:** `user@demo.com`
- **Password:** `password`

## 📱 Accesos Rápidos

| Sección | URL |
|---------|-----|
| Página principal | `/` |
| Login usuarios | `/app/login` |
| Registro usuarios | `/app/register` |
| Dashboard usuarios | `/app` |
| Login superadmin | `/super/login` |
| Panel superadmin | `/super` |
| Gestión organizaciones | `/super/organizations` |

## 🔒 Seguridad

- Middleware de organización que verifica:
  - Usuario pertenece a organización activa
  - Usuario está aprobado
  - Organización no está bloqueada
- Soft deletes en usuarios
- Email único global
- Bloqueo de organizaciones por superadmin

## 📊 Funcionalidades por Rol

### Admin
- ✅ Invitar usuarios
- ✅ Aprobar/rechazar nuevos usuarios
- ✅ Gestionar solicitudes de baja
- ✅ Ver estadísticas de organización

### Supervisor
- ✅ Acceso a reportes
- ✅ Gestión limitada de usuarios

### Usuario
- ✅ Dashboard personal
- ✅ Solicitar baja de cuenta
- ✅ Editar perfil

### Superadmin
- ✅ Ver todas las organizaciones
- ✅ Bloquear/activar organizaciones
- ✅ Estadísticas globales
- ✅ Auditoría completa

## 🗺️ Roadmap

- [x] Autenticación básica
- [x] Multi-organización
- [x] Roles y permisos
- [x] Invitaciones por email
- [x] Aprobación de usuarios
- [x] Solicitud y gestión de bajas
- [x] Paneles Filament unificados
- [x] Superadmin con estadísticas globales
- [ ] Login social (Google, Microsoft)
- [ ] 2FA
- [ ] API REST

## 📄 Licencia

Este es un proyecto base desarrollado para fines educativos y como punto de partida para aplicaciones empresariales. Puedes adaptarlo según tus necesidades.

---

**Desarrollado con** ❤️ **usando Laravel y Filament**