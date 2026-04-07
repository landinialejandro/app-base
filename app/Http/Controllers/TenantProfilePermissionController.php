<?php

// FILE: app/Http/Controllers/TenantProfilePermissionController.php | V7

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantProfilePermissionController extends Controller
{
    public function update(Request $request)
    {
        $tenant = app('tenant');

        $membership = $request->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        $data = $request->validate([
            'role' => ['required', 'string', Rule::in(RoleCatalog::assignable())],
            'permissions' => ['nullable', 'array'],
        ]);

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $data['role'])
            ->firstOrFail();

        $enabledModules = collect(TenantModuleAccess::enabledModules($tenant))
            ->filter(fn ($enabled) => $enabled === true)
            ->keys()
            ->values()
            ->all();

        $moduleCapabilityMap = $this->buildModuleCapabilityMap($enabledModules);
        $existingMatrix = $this->buildExistingPermissionMatrix($role, $moduleCapabilityMap);

        $matrix = $this->normalizePermissionMatrix($request, $moduleCapabilityMap, $existingMatrix);
        $matrix = $this->autoFixViewDependencies($matrix);

        $this->validateLogicalConsistency($matrix);
        $this->validateScopes($matrix);
        $this->validateConstraints($matrix);

        $permissionSlugs = collect($moduleCapabilityMap)
            ->flatMap(function ($capabilities, $module) {
                return collect($capabilities)
                    ->map(fn ($capability) => CapabilityCatalog::permissionSlug($module, $capability));
            })
            ->values()
            ->all();

        $permissionsBySlug = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->get()
            ->keyBy('slug');

        DB::transaction(function () use ($role, $matrix, $permissionsBySlug) {
            foreach ($matrix as $module => $capabilities) {
                foreach ($capabilities as $capability => $meta) {
                    $permissionSlug = CapabilityCatalog::permissionSlug($module, $capability);
                    $permission = $permissionsBySlug->get($permissionSlug);

                    if (! $permission) {
                        continue;
                    }

                    if (! $meta['enabled']) {
                        DB::table('role_permission')
                            ->where('role_id', $role->id)
                            ->where('permission_id', $permission->id)
                            ->delete();

                        continue;
                    }

                    $payload = [
                        'scope' => $meta['scope'],
                        'execution_mode' => $meta['execution_mode'],
                        'constraints' => empty($meta['constraints'])
                            ? null
                            : json_encode($meta['constraints'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ];

                    DB::table('role_permission')->updateOrInsert(
                        [
                            'role_id' => $role->id,
                            'permission_id' => $permission->id,
                        ],
                        $payload + ['created_at' => now()]
                    );
                }
            }
        });

        return redirect()
            ->route('tenant.profile.show', [
                'tab' => 'permissions',
                'role' => $data['role'],
            ])
            ->with('success', 'Permisos actualizados correctamente.');
    }

    protected function buildModuleCapabilityMap(array $enabledModules): array
    {
        if (empty($enabledModules)) {
            return [];
        }

        $permissions = Permission::query()
            ->whereIn('group', $enabledModules)
            ->get();

        $map = [];

        foreach ($enabledModules as $module) {
            $map[$module] = [];
        }

        foreach ($permissions as $permission) {
            $parsed = CapabilityCatalog::parsePermissionSlug((string) $permission->slug);

            if (! $parsed) {
                continue;
            }

            $module = $parsed['module'];
            $capability = $parsed['capability'];

            if (! in_array($module, $enabledModules, true)) {
                continue;
            }

            $map[$module][] = $capability;
        }

        foreach ($map as $module => $capabilities) {
            $map[$module] = collect($capabilities)
                ->filter(fn ($capability) => in_array($capability, CapabilityCatalog::all(), true))
                ->unique()
                ->sortBy(fn ($capability) => array_search($capability, CapabilityCatalog::all(), true))
                ->values()
                ->all();
        }

        return array_filter($map, fn ($capabilities) => ! empty($capabilities));
    }

    protected function buildExistingPermissionMatrix(Role $role, array $moduleCapabilityMap): array
    {
        $matrix = [];

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            $matrix[$module] = [];

            foreach ($capabilities as $capability) {
                $matrix[$module][$capability] = [
                    'enabled' => false,
                    'scope' => null,
                    'execution_mode' => 'manual',
                    'constraints' => [],
                ];
            }
        }

        if (empty($moduleCapabilityMap)) {
            return $matrix;
        }

        $role->loadMissing('permissions');

        $permissionSlugs = collect($moduleCapabilityMap)
            ->flatMap(function ($capabilities, $module) {
                return collect($capabilities)
                    ->map(fn ($capability) => CapabilityCatalog::permissionSlug($module, $capability));
            })
            ->values()
            ->all();

        $permissionsBySlug = Permission::query()
            ->whereIn('slug', $permissionSlugs)
            ->get()
            ->keyBy('slug');

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            foreach ($capabilities as $capability) {
                $slug = CapabilityCatalog::permissionSlug($module, $capability);
                $permission = $permissionsBySlug->get($slug);

                if (! $permission) {
                    continue;
                }

                $assignedPermission = $role->permissions->firstWhere('id', $permission->id);

                if (! $assignedPermission) {
                    continue;
                }

                $matrix[$module][$capability] = [
                    'enabled' => true,
                    'scope' => $assignedPermission->pivot->scope,
                    'execution_mode' => $assignedPermission->pivot->execution_mode ?: 'manual',
                    'constraints' => $this->normalizeConstraints($assignedPermission->pivot->constraints ?? null),
                ];
            }
        }

        return $matrix;
    }

    protected function normalizePermissionMatrix(Request $request, array $moduleCapabilityMap, array $existingMatrix): array
    {
        $matrix = [];

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            $matrix[$module] = [];

            foreach ($capabilities as $capability) {
                $scope = $request->input("permissions.$module.$capability.scope");
                $executionMode = $request->input("permissions.$module.$capability.execution_mode");
                $enabled = $request->boolean("permissions.$module.$capability.enabled");
                $normalizedScope = $this->normalizeNullableString($scope);
                $allowedScopes = PermissionScopeCatalog::optionsFor($module, $capability);

                if (! $enabled) {
                    $normalizedScope = null;
                } elseif (! PermissionScopeCatalog::supports($module, $capability)) {
                    $normalizedScope = null;
                } elseif ($normalizedScope === null && count($allowedScopes) === 1) {
                    $normalizedScope = array_key_first($allowedScopes);
                }

                $existingConstraints = $existingMatrix[$module][$capability]['constraints'] ?? [];

                $matrix[$module][$capability] = [
                    'enabled' => $enabled,
                    'scope' => $normalizedScope,
                    'execution_mode' => $this->normalizeExecutionMode($executionMode),
                    'constraints' => $enabled
                        ? $this->normalizeConstraintsInput($request, $module, $capability, $existingConstraints)
                        : [],
                ];
            }
        }

        return $matrix;
    }

    protected function normalizeConstraintsInput(
        Request $request,
        string $module,
        string $capability,
        array $existingConstraints = []
    ): array {
        $constraints = $existingConstraints;

        if ($this->supportsAllowedKindsConstraint($module, $capability)) {
            $allowedKinds = $request->input("permissions.$module.$capability.constraints.allowed_kinds", []);

            if (! is_array($allowedKinds)) {
                $allowedKinds = [];
            }

            $constraints['allowed_kinds'] = array_values(array_unique(array_filter(
                $allowedKinds,
                fn ($value) => is_string($value) && trim($value) !== ''
            )));
        }

        return $constraints;
    }

    protected function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function normalizeExecutionMode(mixed $value): string
    {
        $value = $this->normalizeNullableString($value);

        return $value ?? 'manual';
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

    protected function autoFixViewDependencies(array $matrix): array
    {
        foreach ($matrix as $module => &$capabilities) {
            $viewAnyEnabled = (bool) ($capabilities[CapabilityCatalog::VIEW_ANY]['enabled'] ?? false);
            $viewEnabled = (bool) ($capabilities[CapabilityCatalog::VIEW]['enabled'] ?? false);
            $createEnabled = (bool) ($capabilities[CapabilityCatalog::CREATE]['enabled'] ?? false);
            $updateEnabled = (bool) ($capabilities[CapabilityCatalog::UPDATE]['enabled'] ?? false);
            $deleteEnabled = (bool) ($capabilities[CapabilityCatalog::DELETE]['enabled'] ?? false);

            if ($createEnabled && ! $viewEnabled && ! $viewAnyEnabled && isset($capabilities[CapabilityCatalog::VIEW])) {
                $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
                $capabilities[CapabilityCatalog::VIEW]['scope'] ??= $this->singleScopeOrNull($module, CapabilityCatalog::VIEW);
            }

            if ($updateEnabled && ! $viewEnabled && isset($capabilities[CapabilityCatalog::VIEW])) {
                $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
                $capabilities[CapabilityCatalog::VIEW]['scope'] ??= $this->singleScopeOrNull($module, CapabilityCatalog::VIEW);
            }

            if ($deleteEnabled && ! $viewEnabled && isset($capabilities[CapabilityCatalog::VIEW])) {
                $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
                $capabilities[CapabilityCatalog::VIEW]['scope'] ??= $this->singleScopeOrNull($module, CapabilityCatalog::VIEW);
            }
        }

        unset($capabilities);

        return $matrix;
    }

    protected function validateLogicalConsistency(array $matrix): void
    {
        foreach ($matrix as $module => $capabilities) {
            $viewAnyEnabled = (bool) ($capabilities[CapabilityCatalog::VIEW_ANY]['enabled'] ?? false);
            $viewEnabled = (bool) ($capabilities[CapabilityCatalog::VIEW]['enabled'] ?? false);
            $createEnabled = (bool) ($capabilities[CapabilityCatalog::CREATE]['enabled'] ?? false);
            $updateEnabled = (bool) ($capabilities[CapabilityCatalog::UPDATE]['enabled'] ?? false);
            $deleteEnabled = (bool) ($capabilities[CapabilityCatalog::DELETE]['enabled'] ?? false);

            if ($createEnabled && ! $viewAnyEnabled && ! $viewEnabled) {
                $this->failValidation(
                    "permissions.$module.".CapabilityCatalog::CREATE.'.enabled',
                    "El módulo [$module] no puede permitir crear sin permitir ver."
                );
            }

            if ($updateEnabled && ! $viewEnabled) {
                $this->failValidation(
                    "permissions.$module.".CapabilityCatalog::UPDATE.'.enabled',
                    "El módulo [$module] no puede permitir editar sin permitir ver."
                );
            }

            if ($deleteEnabled && ! $viewEnabled) {
                $this->failValidation(
                    "permissions.$module.".CapabilityCatalog::DELETE.'.enabled',
                    "El módulo [$module] no puede permitir eliminar sin permitir ver."
                );
            }
        }
    }

    protected function validateScopes(array $matrix): void
    {
        foreach ($matrix as $module => $capabilities) {
            foreach ($capabilities as $capability => $meta) {
                if (! $meta['enabled']) {
                    if ($meta['scope'] !== null) {
                        $this->failValidation(
                            "permissions.$module.$capability.scope",
                            "No se puede persistir alcance en [$module][$capability] cuando la capacidad está deshabilitada."
                        );
                    }

                    continue;
                }

                $allowedScopes = PermissionScopeCatalog::optionsFor($module, $capability);

                if (empty($allowedScopes)) {
                    if ($meta['scope'] !== null) {
                        $this->failValidation(
                            "permissions.$module.$capability.scope",
                            "El módulo [$module] no admite alcance para [$capability]."
                        );
                    }

                    continue;
                }

                if ($meta['scope'] === null) {
                    $this->failValidation(
                        "permissions.$module.$capability.scope",
                        "El módulo [$module] requiere un alcance explícito para [$capability]."
                    );
                }

                if (! array_key_exists($meta['scope'], $allowedScopes)) {
                    $this->failValidation(
                        "permissions.$module.$capability.scope",
                        "El alcance seleccionado no es válido para [$module][$capability]."
                    );
                }
            }
        }
    }

    protected function validateConstraints(array $matrix): void
    {
        foreach ($matrix as $module => $capabilities) {
            foreach ($capabilities as $capability => $meta) {
                if (! $meta['enabled']) {
                    continue;
                }

                if ($this->supportsAllowedKindsConstraint($module, $capability)) {
                    $allowedKinds = $meta['constraints']['allowed_kinds'] ?? [];

                    if (empty($allowedKinds)) {
                        $this->failValidation(
                            "permissions.$module.$capability.constraints.allowed_kinds",
                            "Debes seleccionar al menos un tipo permitido para [$module][$capability]."
                        );
                    }

                    $validKinds = array_keys(OrderCatalog::kindLabels());

                    foreach ($allowedKinds as $kind) {
                        if (! in_array($kind, $validKinds, true)) {
                            $this->failValidation(
                                "permissions.$module.$capability.constraints.allowed_kinds",
                                "Se detectó un tipo inválido en [$module][$capability]."
                            );
                        }
                    }
                }
            }
        }
    }

    protected function supportsAllowedKindsConstraint(string $module, string $capability): bool
    {
        return $module === ModuleCatalog::ORDERS
            && in_array($capability, [
                CapabilityCatalog::VIEW_ANY,
                CapabilityCatalog::VIEW,
                CapabilityCatalog::CREATE,
                CapabilityCatalog::UPDATE,
                CapabilityCatalog::DELETE,
            ], true);
    }

    protected function singleScopeOrNull(string $module, string $capability): ?string
    {
        $options = PermissionScopeCatalog::optionsFor($module, $capability);

        return count($options) === 1
            ? array_key_first($options)
            : null;
    }

    protected function failValidation(string $key, string $message): never
    {
        throw ValidationException::withMessages([
            $key => $message,
        ]);
    }
}
