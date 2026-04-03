<?php

// FILE: database/migrations/2026_04_03_180000_normalize_role_permission_scopes_v2.php | V1

use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->join('roles', 'roles.id', '=', 'role_permission.role_id')
            ->select([
                'role_permission.role_id',
                'role_permission.permission_id',
                'role_permission.scope',
                'role_permission.execution_mode',
                'permissions.slug as permission_slug',
                'roles.slug as role_slug',
            ])
            ->get();

        $now = now();

        foreach ($rows as $row) {
            $parsed = CapabilityCatalog::parsePermissionSlug((string) $row->permission_slug);

            if (! $parsed) {
                continue;
            }

            $module = $parsed['module'];
            $capability = $parsed['capability'];
            $allowedScopes = PermissionScopeCatalog::optionsFor($module, $capability);

            $normalizedScope = $this->normalizeScope(
                module: $module,
                capability: $capability,
                roleSlug: (string) $row->role_slug,
                currentScope: $row->scope,
                allowedScopes: $allowedScopes,
            );

            $normalizedExecutionMode = $this->normalizeExecutionMode($row->execution_mode);

            if (
                $normalizedScope === $row->scope
                && $normalizedExecutionMode === $row->execution_mode
            ) {
                continue;
            }

            DB::table('role_permission')
                ->where('role_id', $row->role_id)
                ->where('permission_id', $row->permission_id)
                ->update([
                    'scope' => $normalizedScope,
                    'execution_mode' => $normalizedExecutionMode,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        //
    }

    protected function normalizeScope(
        string $module,
        string $capability,
        string $roleSlug,
        mixed $currentScope,
        array $allowedScopes
    ): ?string {
        if (empty($allowedScopes)) {
            return null;
        }

        if (is_string($currentScope) && array_key_exists($currentScope, $allowedScopes)) {
            return $currentScope;
        }

        return $this->fallbackScope($module, $capability, $roleSlug);
    }

    protected function normalizeExecutionMode(mixed $currentExecutionMode): string
    {
        if (! is_string($currentExecutionMode)) {
            return 'manual';
        }

        $currentExecutionMode = trim($currentExecutionMode);

        return $currentExecutionMode === '' ? 'manual' : $currentExecutionMode;
    }

    protected function fallbackScope(string $module, string $capability, string $roleSlug): ?string
    {
        if (in_array($module, [
            ModuleCatalog::PARTIES,
            ModuleCatalog::ASSETS,
            ModuleCatalog::PRODUCTS,
            ModuleCatalog::DOCUMENTS,
            ModuleCatalog::ORDERS,
        ], true)) {
            return match ($capability) {
                CapabilityCatalog::VIEW_ANY,
                CapabilityCatalog::VIEW,
                CapabilityCatalog::UPDATE,
                CapabilityCatalog::DELETE => PermissionScopeCatalog::TENANT_ALL,
                default => null,
            };
        }

        if ($module === ModuleCatalog::TASKS) {
            return match ($capability) {
                CapabilityCatalog::VIEW_ANY,
                CapabilityCatalog::VIEW => PermissionScopeCatalog::TENANT_ALL,
                CapabilityCatalog::UPDATE => $this->isPrivilegedRole($roleSlug)
                    ? PermissionScopeCatalog::ALL
                    : PermissionScopeCatalog::OWN_ASSIGNED,
                default => null,
            };
        }

        if ($module === ModuleCatalog::APPOINTMENTS) {
            return match ($capability) {
                CapabilityCatalog::VIEW,
                CapabilityCatalog::UPDATE => $this->isPrivilegedRole($roleSlug)
                    ? PermissionScopeCatalog::ALL
                    : PermissionScopeCatalog::OWN_ASSIGNED,
                default => null,
            };
        }

        if ($module === ModuleCatalog::PROJECTS) {
            return match ($capability) {
                CapabilityCatalog::VIEW,
                CapabilityCatalog::UPDATE,
                CapabilityCatalog::DELETE => $this->isPrivilegedRole($roleSlug)
                    ? PermissionScopeCatalog::TENANT_ALL
                    : PermissionScopeCatalog::LIMITED,
                default => null,
            };
        }

        return null;
    }

    protected function isPrivilegedRole(string $roleSlug): bool
    {
        return in_array($roleSlug, [
            RoleCatalog::OWNER,
            RoleCatalog::ADMIN,
        ], true);
    }
};
