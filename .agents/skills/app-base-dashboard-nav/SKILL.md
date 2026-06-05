---
name: app-base-dashboard-nav
description: "Use this skill for app-base tasks involving Dashboard, Navbar, ModuleCatalog navigation metadata, RoleModuleAccess, dashboard requirements, service/production dashboard entries, or decisions about NavMatrix / TenantModuleRequirementResolver. Do not use for unrelated CRUD work, database schema changes, seeders, CSS-only visual work, or documentation-only tasks."
---

# app-base Dashboard/Nav

Use this skill for app-base tasks involving Dashboard, Navbar, ModuleCatalog navigation metadata, RoleModuleAccess, dashboard requirements, service/production dashboard entries, or decisions about NavMatrix / TenantModuleRequirementResolver.

Do not use this skill for unrelated CRUD work, database schema changes, seeders, visual CSS-only changes, or documentation-only tasks.

## Core rule

NO SE ASUME.
NO SE SUPONE.

Work from real code and runtime evidence. Do not invent routes, classes, permissions, modules, views, or contracts.

## Scope

This skill applies to:

- `app/Support/Catalogs/ModuleCatalog.php`
- `app/View/Components/Layout/Navbar.php`
- `app/Support/Navigation/NavbarContext.php`
- `resources/views/components/layout/navbar.blade.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Support/Dashboard/TenantDashboardSectionBuilder.php`
- `app/Support/Dashboard/TenantDashboardResolver.php`
- `resources/views/dashboard.blade.php`
- `resources/views/dashboard/partials/*`
- service / production dashboard access where directly related to dashboard/nav coherence

## Doctrine

`ModuleCatalog` is the primary descriptive source for modules/fronts.

It may define stable descriptive information such as:

- module key
- base label
- icon
- nav definition
- nav route
- nav group
- active patterns
- nav order
- surface services / activity services when already part of the project contract

`ModuleCatalog` must not:

- authorize
- query the database
- resolve effective scopes
- decide role assignments
- replace Security
- replace policies
- replace permission resolvers

## Navbar

Navbar is global stable navigation.

Navbar should consume:

- `ModuleCatalog` for descriptive/navigation metadata
- `RoleModuleAccess` for module visibility/access

Navbar should not:

- consume `RolePermissionResolver` directly if `RoleModuleAccess` covers the question
- resolve permissions in Blade
- query tenant membership/access in Blade
- duplicate dashboard requirements
- become a dashboard matrix

`resources/views/components/layout/navbar.blade.php` should represent prepared payload.

## Dashboard

Dashboard is a contextual operational surface.

Dashboard may have its own UX matrix:

- sections
- cards
- partials
- metrics
- texts
- contextual actions
- requirements

Dashboard should consume:

- `ModuleCatalog` for module identity/icon/base descriptive information
- `TenantDashboardResolver` for filtering/resolving sections/items
- `Security` and `TenantModuleAccess` through resolver logic, not Blade

Dashboard Blade should represent prepared `dashboardSections`.

## Dashboard matrix

`TenantDashboardSectionBuilder::matrix()` is the public contract.

The preferred internal structure is:

- `sections()` defines dashboard sections
- `items()` defines dashboard items/cards by functional intention
- `matrix()` composes final resolver-compatible output

Item keys should identify functional intention, not section location.

Good examples:

- `appointments.calendar`
- `parties.index`
- `assets.index`
- `orders.index`
- `service.orders.index`
- `production.orders.create`

Avoid keys that bind items to placement if the item may be reused:

- `daily.appointments`
- `management.orders`

`enabled` means only global declarative inclusion in the dashboard matrix.

`enabled` does not authorize and does not replace:

- `requires`
- `Security`
- `TenantModuleAccess`
- permissions
- policies
- tenant checks

## Requirements

Dashboard `requires` are contextual runtime conditions.

They may include:

- module enabled
- ability
- analytics
- subject/context for ability checks

Do not create `TenantModuleRequirementResolver` unless evidence shows repeated requirement evaluation outside dashboard or real duplication.

## NavMatrix

Do not create `NavMatrix` unless code evidence shows `ModuleCatalog` getters and existing navbar contracts are insufficient.

Current preferred direction:

- `ModuleCatalog` remains primary descriptive source
- Navbar uses `ModuleCatalog` + `RoleModuleAccess`
- Dashboard uses contextual matrix + `TenantDashboardResolver`

Convergence should be doctrinal, not mechanical.

## Service / Production

`/service` and `/production` are semantic satellite fronts over Orders.

They are not ordinary CRUD modules.

Orders keeps ownership.

Service/Production dashboard entries must distinguish:

- area/front access
- operational Orders permissions
- contextual Orders create/view with group/kind

Do not convert service or production into standalone CRUD ownership.

## Validation checklist

For Dashboard/Nav changes, usually run:

```bash
git status --short
php -l app/Support/Dashboard/TenantDashboardSectionBuilder.php
php -l app/Support/Dashboard/TenantDashboardResolver.php
php -l app/View/Components/Layout/Navbar.php
php -l app/Support/Navigation/NavbarContext.php
php artisan view:clear
CACHE_STORE=array php artisan view:cache
CACHE_STORE=array php artisan route:list > /tmp/app-base-route-list.txt
git diff --check
```
