<?php

// FILE: app/Support/Auth/RolePermissionResolver.php | V2

namespace App\Support\Auth;

use App\Models\Membership;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\RoleCatalog;

class RolePermissionResolver
{
    public function resolve(string $module, ?Tenant $tenant = null, ?User $user = null): array
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);
        $user = $user ?: auth()->user();

        if (! $tenant || ! $user) {
            return $this->emptyRule();
        }

        if (! in_array($module, ModuleCatalog::all(), true)) {
            return $this->emptyRule();
        }

        if (! TenantModuleAccess::isEnabled($module, $tenant)) {
            return $this->emptyRule();
        }

        $membership = $this->resolveMembership($tenant, $user);

        if (! $membership) {
            return $this->emptyRule();
        }

        $roleSlugs = $this->resolveRoleSlugs($membership);

        if (empty($roleSlugs)) {
            return $this->emptyRule();
        }

        $roles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('slug', $roleSlugs)
            ->with(['permissions' => function ($query) use ($module) {
                $query->where('group', $module);
            }])
            ->get();

        $resolved = $this->emptyRule();

        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $parsed = CapabilityCatalog::parsePermissionSlug((string) $permission->slug);

                if (! $parsed || $parsed['module'] !== $module) {
                    continue;
                }

                $capability = $parsed['capability'];

                $resolved = $this->mergeCapability($resolved, $capability, [
                    'allowed' => true,
                    'scope' => $permission->pivot->scope ?? null,
                    'execution_mode' => $permission->pivot->execution_mode ?? null,
                    'constraints' => $this->normalizeConstraints($permission->pivot->constraints ?? null),
                ]);
            }
        }

        $resolved = $this->applyMembershipOverrides($resolved, $membership, $module);

        $resolved['module_access'] = ! empty($resolved['actions']);
        $resolved['record_visibility'] = $this->resolveRecordVisibility($resolved['actions']);

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

        return array_key_exists($action, $resolved['actions'] ?? []);
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

    public function executionMode(string $module, string $action, ?Tenant $tenant = null, ?User $user = null): ?string
    {
        $resolved = $this->resolve($module, $tenant, $user);

        return $resolved['execution_modes'][$action] ?? null;
    }

    public function constraints(string $module, string $action, ?Tenant $tenant = null, ?User $user = null): array
    {
        $resolved = $this->resolve($module, $tenant, $user);

        return $resolved['constraints'][$action] ?? [];
    }

    protected function resolveMembership(Tenant $tenant, User $user): ?Membership
    {
        return $user->memberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with([
                'roles.permissions',
                'permissionOverrides.permission',
            ])
            ->first();
    }

    protected function resolveRoleSlugs(Membership $membership): array
    {
        $roleSlugs = $membership->roles
            ->pluck('slug')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($membership->is_owner && ! in_array(RoleCatalog::OWNER, $roleSlugs, true)) {
            $roleSlugs[] = RoleCatalog::OWNER;
        }

        return array_values(array_unique($roleSlugs));
    }

    protected function applyMembershipOverrides(array $resolved, Membership $membership, string $module): array
    {
        foreach ($membership->permissionOverrides as $override) {
            $permission = $override->permission;

            if (! $permission) {
                continue;
            }

            $parsed = CapabilityCatalog::parsePermissionSlug((string) $permission->slug);

            if (! $parsed || $parsed['module'] !== $module) {
                continue;
            }

            $capability = $parsed['capability'];

            if ($override->is_allowed === false) {
                unset($resolved['actions'][$capability]);
                unset($resolved['execution_modes'][$capability]);
                unset($resolved['constraints'][$capability]);

                continue;
            }

            $resolved = $this->mergeCapability($resolved, $capability, [
                'allowed' => true,
                'scope' => $override->scope ?? null,
                'execution_mode' => $override->execution_mode ?? null,
                'constraints' => $this->normalizeConstraints($override->constraints ?? null),
                'replace' => true,
            ]);
        }

        return $resolved;
    }

    protected function mergeCapability(array $resolved, string $capability, array $incoming): array
    {
        $replace = (bool) ($incoming['replace'] ?? false);

        $currentScope = $resolved['actions'][$capability] ?? null;
        $incomingScope = $incoming['scope'] ?? null;

        $resolved['actions'][$capability] = $replace
            ? $this->normalizeActionValue($incomingScope)
            : $this->mergeActionValue(
                $this->normalizeActionValue($currentScope),
                $this->normalizeActionValue($incomingScope)
            );

        $currentExecutionMode = $resolved['execution_modes'][$capability] ?? null;
        $incomingExecutionMode = $incoming['execution_mode'] ?? null;

        $resolved['execution_modes'][$capability] = $replace
            ? $incomingExecutionMode
            : $this->mergeExecutionMode($currentExecutionMode, $incomingExecutionMode);

        $currentConstraints = $resolved['constraints'][$capability] ?? [];
        $incomingConstraints = $incoming['constraints'] ?? [];

        $resolved['constraints'][$capability] = $replace
            ? $incomingConstraints
            : $this->mergeConstraints($currentConstraints, $incomingConstraints);

        return $resolved;
    }

    protected function normalizeActionValue(mixed $scope): mixed
    {
        if ($scope === null || $scope === '') {
            return true;
        }

        return $scope;
    }

    protected function resolveRecordVisibility(array $actions): string
    {
        $viewAny = $actions[CapabilityCatalog::VIEW_ANY] ?? null;
        $view = $actions[CapabilityCatalog::VIEW] ?? null;

        $candidate = $viewAny ?? $view;

        if ($candidate === true) {
            return 'tenant_all';
        }

        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }

        return 'none';
    }

    protected function mergeActionValue(mixed $base, mixed $incoming): mixed
    {
        $priority = [
            false => 0,
            'none' => 0,
            'limited' => 1,
            'own_assigned' => 2,
            true => 3,
            'tenant_all' => 3,
            'all' => 4,
        ];

        return ($priority[$incoming] ?? 0) > ($priority[$base] ?? 0)
            ? $incoming
            : $base;
    }

    protected function mergeExecutionMode(?string $base, ?string $incoming): ?string
    {
        if ($incoming === null || $incoming === '') {
            return $base;
        }

        if ($base === null || $base === '') {
            return $incoming;
        }

        if ($base === $incoming) {
            return $base;
        }

        return $incoming;
    }

    protected function mergeConstraints(array $base, array $incoming): array
    {
        return $this->mergeRecursiveDistinct($base, $incoming);
    }

    protected function normalizeConstraints(mixed $constraints): array
    {
        if (is_array($constraints)) {
            return $constraints;
        }

        if (is_string($constraints) && trim($constraints) !== '') {
            $decoded = json_decode($constraints, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function mergeRecursiveDistinct(array $base, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if (
                array_key_exists($key, $base)
                && is_array($base[$key])
                && is_array($value)
            ) {
                $base[$key] = $this->mergeRecursiveDistinct($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    protected function emptyRule(): array
    {
        return [
            'module_access' => false,
            'record_visibility' => 'none',
            'actions' => [],
            'execution_modes' => [],
            'constraints' => [],
        ];
    }
}
