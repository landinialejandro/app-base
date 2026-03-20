<?php

// FILE: app/Support/Auth/RolePermissionResolver.php

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\ModuleCatalog;

class RolePermissionResolver
{
    public function resolve(string $module, ?Tenant $tenant = null, ?User $user = null): array
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);
        $user = $user ?: auth()->user();

        if (! $tenant || ! $user) {
            return RolePermissionMatrix::emptyRule();
        }

        if (! in_array($module, ModuleCatalog::all(), true)) {
            return RolePermissionMatrix::emptyRule();
        }

        if (! TenantModuleAccess::isEnabled($module, $tenant)) {
            return RolePermissionMatrix::emptyRule();
        }

        $roleSlugs = TenantAccess::roleSlugs($tenant->id, $user);

        if (empty($roleSlugs)) {
            return RolePermissionMatrix::emptyRule();
        }

        $resolved = RolePermissionMatrix::emptyRule();

        foreach ($roleSlugs as $roleSlug) {
            $rule = RolePermissionMatrix::for($module, $roleSlug);
            $resolved = $this->merge($resolved, $rule);
        }

        return $resolved;
    }

    public function canUseModule(string $module, ?Tenant $tenant = null, ?User $user = null): bool
    {
        $resolved = $this->resolve($module, $tenant, $user);

        return (bool) ($resolved['module_access'] ?? false);
    }

    public function can(string $module, string $action, ?Tenant $tenant = null, ?User $user = null): bool
    {
        $resolved = $this->resolve($module, $tenant, $user);
        $value = $resolved['actions'][$action] ?? false;

        return $value === true || $value === 'all';
    }

    public function actionScope(string $module, string $action, ?Tenant $tenant = null, ?User $user = null): mixed
    {
        $resolved = $this->resolve($module, $tenant, $user);

        return $resolved['actions'][$action] ?? false;
    }

    public function visibility(string $module, ?Tenant $tenant = null, ?User $user = null): string
    {
        $resolved = $this->resolve($module, $tenant, $user);

        return $resolved['record_visibility'] ?? 'none';
    }

    protected function merge(array $base, array $incoming): array
    {
        return [
            'module_access' => ($base['module_access'] ?? false) || ($incoming['module_access'] ?? false),
            'record_visibility' => $this->mergeVisibility(
                $base['record_visibility'] ?? 'none',
                $incoming['record_visibility'] ?? 'none'
            ),
            'actions' => $this->mergeActions(
                $base['actions'] ?? [],
                $incoming['actions'] ?? []
            ),
            'type_restrictions' => $this->mergeTypes(
                $base['type_restrictions'] ?? [],
                $incoming['type_restrictions'] ?? []
            ),
        ];
    }

    protected function mergeVisibility(string $base, string $incoming): string
    {
        $priority = [
            'none' => 0,
            'limited' => 1,
            'own_assigned' => 2,
            'tenant_all' => 3,
            'all' => 4,
        ];

        return ($priority[$incoming] ?? 0) > ($priority[$base] ?? 0)
            ? $incoming
            : $base;
    }

    protected function mergeActions(array $base, array $incoming): array
    {
        $result = $base;

        foreach ($incoming as $action => $value) {
            if (! array_key_exists($action, $result)) {
                $result[$action] = $value;

                continue;
            }

            $result[$action] = $this->mergeActionValue($result[$action], $value);
        }

        return $result;
    }

    protected function mergeActionValue(mixed $base, mixed $incoming): mixed
    {
        $priority = [
            false => 0,
            'own_assigned' => 1,
            true => 2,
            'all' => 3,
        ];

        return ($priority[$incoming] ?? 0) > ($priority[$base] ?? 0)
            ? $incoming
            : $base;
    }

    protected function mergeTypes(array $base, array $incoming): array
    {
        if (in_array('*', $base, true) || in_array('*', $incoming, true)) {
            return ['*'];
        }

        return array_values(array_unique(array_merge($base, $incoming)));
    }
}
