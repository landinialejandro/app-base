# Repository Guidelines

## App-base Project Precedence

This repository uses Laravel Boost as a local development assistance tool for Codex/MCP.

Laravel Boost guidelines are useful for Laravel-specific implementation support, but they do not override app-base project doctrine.

Precedence order:

1. `contexto_fijo_proyecto_app_base`
2. `seguridad_acceso_proyecto_app_base`
3. `plantilla_inicio_chats_proyecto_app_base`
4. specialized active project document
5. real code, runtime, Project Lab evidence and current Git state
6. Laravel Boost guidelines and MCP assistance

Rules:

- NO SE ASUME.
- NO SE SUPONE.
- Project Lab remains the local evidence, audit and validation station.
- Boost is a development assistant, not a product feature.
- Boost MCP tools must not bypass authorization, tenant isolation, backend validation, Project Lab evidence or user approval.
- Database, log and schema tools are diagnostic/read tools unless explicitly authorized otherwise.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
