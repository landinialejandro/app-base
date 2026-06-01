# Repository Guidelines

## Project Structure & Module Organization

This is a Laravel multi-tenant SaaS application. Core PHP code lives in `app/`: controllers and requests in `app/Http`, models in `app/Models`, authorization in `app/Policies`, services/support code in `app/Services` and `app/Support`.

Routes are in `routes/`. Blade views are module-scoped under `resources/views` (for example `orders`, `products`, `tenants`). Frontend entrypoints are in `resources/js` and `resources/css`; public assets are in `public/`. Migrations, factories, and modular seeders are in `database/`. Tests are split between `tests/Feature` and `tests/Unit`. Reference documentation is in `tools/project-lab/documentos`.

## Build, Test, and Development Commands

- `composer setup`: install dependencies, create `.env`, generate the app key, migrate, and build assets.
- `composer dev`: run Laravel, queue listener, logs, and Vite together.
- `php artisan serve`: start only the Laravel development server.
- `npm run dev`: start Vite.
- `npm run build`: build production frontend assets.
- `composer test` or `php artisan test`: run the PHPUnit suite.
- `vendor/bin/pint`: format PHP code with Laravel Pint.

## Coding Style & Naming Conventions

Follow Laravel conventions and PSR-4 namespaces. Use 4-space indentation for PHP and name classes after their responsibility, such as `OrderPolicy` or `AttachmentStorageService`. Keep controllers focused on module flow; delegate business rules, integrations, and cross-module behavior to policies, services, support classes, or explicit boundaries.

Blade files should stay under `resources/views/{module}` and use partials/components for repeated UI. Do not enforce authorization in Blade or JavaScript; backend policies and services decide.

## Testing Guidelines

Use PHPUnit through Laravel's test runner. Put request, workflow, tenant-context, and authorization behavior in `tests/Feature`; put isolated service/helper behavior in `tests/Unit`. Name files after the subject or workflow, ending in `Test.php`.

Run `composer test` before submitting changes. Add focused tests when changing policies, middleware, tenant resolution, checkout/token flows, or shared support services.

## Commit & Pull Request Guidelines

Recent history uses concise Spanish summaries in imperative/present tense, for example `Agrega rechazo simulado controlado para consumo de fichas` or `Revalida punto de consumo activo al confirmar fichas`. Keep commits scoped to one logical change.

Pull requests should include a short description, linked issue/task when available, test commands run, migration or seeder impact, and screenshots for UI changes. Call out tenant access, permissions, payment/token, or public self-service changes.

## Security & Configuration Tips

Keep secrets in `.env`; never commit local credentials or generated deploy archives. Tenant/profile access rules are centralized in `app/Support/Tenants/TenantProfileAccess.php`. Validate critical behavior through backend requests, policies, services, middleware, or official contracts.
