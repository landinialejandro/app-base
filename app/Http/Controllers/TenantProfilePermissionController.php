<?php

// FILE: app/Http/Controllers/TenantProfilePermissionController.php | V13

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\PartyCatalog;
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
            ->with('permissions')
            ->firstOrFail();

        $enabledModules = collect(TenantModuleAccess::enabledModules($tenant))
            ->filter(fn ($enabled) => $enabled === true)
            ->keys()
            ->values()
            ->all();

        $moduleCapabilityMap = $this->buildModuleCapabilityMap($enabledModules);
        $existingMatrix = $this->buildExistingPermissionMatrix($role, $moduleCapabilityMap);

        $matrix = $this->normalizePermissionMatrix($request, $moduleCapabilityMap, $existingMatrix);

        $this->validateLogicalConsistency($matrix);
        $this->validateScopes($matrix);
        $this->validateConstraints($matrix);

        DB::transaction(function () use ($role, $matrix) {
            $role->permissions()->detach();

            foreach ($matrix as $module => $capabilities) {
                foreach ($capabilities as $capability => $meta) {
                    if (! $meta['enabled']) {
                        continue;
                    }

                    $role->permissions()->attach(
                        $this->permissionIdFor($module, $capability),
                        [
                            'scope' => $meta['scope'],
                            'execution_mode' => $meta['execution_mode'],
                            'constraints' => empty($meta['constraints'])
                                ? null
                                : json_encode($meta['constraints'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
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

        $map = [];

        foreach ($enabledModules as $module) {
            $capabilities = $this->capabilitiesForModule($module);

            if (! empty($capabilities)) {
                $map[$module] = $capabilities;
            }
        }

        return $map;
    }

    protected function capabilitiesForModule(string $module): array
    {
        return match ($module) {
            ModuleCatalog::DASHBOARD => [
                CapabilityCatalog::VIEW_ANY,
            ],

            ModuleCatalog::APPOINTMENTS,
            ModuleCatalog::ASSETS,
            ModuleCatalog::PRODUCTS,
            ModuleCatalog::INVENTORY,
            ModuleCatalog::DOCUMENTS,
            ModuleCatalog::PROJECTS,
            ModuleCatalog::TASKS,
            ModuleCatalog::ORDERS,
            ModuleCatalog::PARTIES => [
                CapabilityCatalog::VIEW_ANY,
                CapabilityCatalog::VIEW,
                CapabilityCatalog::CREATE,
                CapabilityCatalog::UPDATE,
                CapabilityCatalog::DELETE,
            ],

            default => [],
        };
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

        $permissionsBySlug = $role->permissions->keyBy('slug');

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            foreach ($capabilities as $capability) {
                $slug = CapabilityCatalog::permissionSlug($module, $capability);
                $assignedPermission = $permissionsBySlug->get($slug);

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

                    $validKinds = $this->validKindsForModule($module);

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
        if (! in_array($capability, [
            CapabilityCatalog::VIEW_ANY,
            CapabilityCatalog::VIEW,
            CapabilityCatalog::CREATE,
            CapabilityCatalog::UPDATE,
            CapabilityCatalog::DELETE,
        ], true)) {
            return false;
        }

        return in_array($module, [
            ModuleCatalog::ORDERS,
            ModuleCatalog::PARTIES,
        ], true);
    }

protected function validKindsForModule(string $module): array
{
    return match ($module) {
        ModuleCatalog::ORDERS => array_keys(OrderCatalog::groups()),
        ModuleCatalog::PARTIES => array_keys(PartyCatalog::kindLabels()),
        default => [],
    };
}

    protected function permissionIdFor(string $module, string $capability): int
    {
        return (int) DB::table('permissions')
            ->where('slug', CapabilityCatalog::permissionSlug($module, $capability))
            ->value('id');
    }

    protected function failValidation(string $key, string $message): never
    {
        throw ValidationException::withMessages([
            $key => $message,
        ]);
    }
}
