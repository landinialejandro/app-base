# App Base SaaS (Laravel Multi-Tenant)

Base architecture for building SaaS applications using **Laravel 12**, implementing **multi-tenant support**, authentication with **Laravel Fortify**, and a tenant selection flow.

This project provides a clean foundation for building business systems such as ERP, CRM, or internal SaaS platforms where multiple organizations (tenants) operate within the same application.

---

# Features

- Laravel 12
- Multi-tenant architecture
- Tenant resolution middleware
- User ↔ Tenant memberships
- Authentication with Laravel Fortify
- Tenant selection workflow
- Invitation system
- Role and permission structure
- Branch (location) support
- UUID tenants
- Session-based tenant context

---

# Architecture Overview

The system supports multiple organizations sharing the same application.


User
↓
Membership
↓
Tenant
↓
Application context


Each user can belong to **multiple tenants**, and each tenant can contain:

- branches
- roles
- permissions
- users

---

# Tenant Resolution

The tenant context is resolved using:

1. **Session tenant_id** (primary method for web usage)
2. **X-Tenant header** (fallback for API or testing)

Middleware:


app/Http/Middleware/ResolveTenant.php


Once resolved, the tenant instance is registered in the container:

```php
app()->instance('tenant', $tenant);

```
---

# Authentication

Authentication is implemented using Laravel Fortify.

Routes include:

```
/login
/register
/logout
```

After login, users are redirected to:

```
/tenants/select
```

---

# Tenant Selection Flow

The SaaS flow is:

```
login
↓
tenants/select
↓
choose tenant
↓
store tenant_id in session
↓
tenant middleware resolves tenant
↓
dashboard
```

Tenant selection route:

```
GET  /tenants/select
POST /tenants/select/{tenant}
```

---

# Database Structure

Key tables:

tenants

Organizations using the system.

users

Application users.

memberships

Relationship between users and tenants.

```
users ↔ memberships ↔ tenants
```

### branches

Physical or logical locations belonging to a tenant.

roles

Tenant-specific roles.

permissions

Permission definitions.

invitations

Invitation system for adding users to tenants.

Example Membership

```
tenant_id: UUID
user_id: integer
status: active
is_owner: true
joined_at: timestamp
```
---
## Development Setup

Requirements

- PHP 8.4+
- Composer
- MariaDB / MySQL
- Node (optional for frontend tooling)

### Installation

Clone the repository:
```
git clone https://github.com/your-user/app-base.git
cd app-base
```
Install dependencies:
```
composer install
```
Copy environment:
```
cp .env.example .env
```
Generate application key:
```
php artisan key:generate
```
Configure database in .env.

Run migrations:
```
php artisan migrate
```
Start server:
```
php artisan serve
```
---
## Testing Tenant Header

You can resolve tenants via header:
```
curl -H "X-Tenant: demo" http://app-base.local/whoami
```

Example response:

```
{
"tenant_id": "...",
"tenant_slug": "demo"
}
```

Current Status

Implemented:

- authentication
- memberships
- tenant resolver
- invitation acceptance
- tenant selector
- multi-tenant database structure

Pending (future improvements):

- dashboard UI
- tenant scoped models
- global tenant query scopes
- API authentication
- permission middleware
- admin panel

---

## Project Goal

Provide a clean Laravel SaaS starting point with a clear and extensible multi-tenant architecture.

This repository is designed to serve as a base for building:

- ERP systems
- CRM platforms
- internal company tools
- subscription SaaS applications

License

MIT License