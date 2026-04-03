<?php

// FILE: app/Http/Controllers/TenantProfilePermissionController.php | V6

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $matrix = $this->normalizePermissionMatrix($request, $moduleCapabilityMap);
        $matrix = $this->autoFixViewDependencies($matrix);

        $this->validateLogicalConsistency($matrix);
        $this->validateScopes($matrix);

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
                        'constraints' => null,
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

    protected function normalizePermissionMatrix(Request $request, array $moduleCapabilityMap): array
    {
        $matrix = [];

        foreach ($moduleCapabilityMap as $module => $capabilities) {
            $matrix[$module] = [];

            foreach ($capabilities as $capability) {
                $scope = $request->input("permissions.$module.$capability.scope");
                $executionMode = $request->input("permissions.$module.$capability.execution_mode");
                $enabled = $request->boolean("permissions.$module.$capability.enabled");
                $normalizedScope = $this->normalizeNullableString($scope);

                if (! $enabled) {
                    $normalizedScope = null;
                }

                if (! PermissionScopeCatalog::supports($module, $capability)) {
                    $normalizedScope = null;
                }

                $matrix[$module][$capability] = [
                    'enabled' => $enabled,
                    'scope' => $normalizedScope,
                    'execution_mode' => $this->normalizeExecutionMode($executionMode),
                ];
            }
        }

        return $matrix;
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
            }

            if ($updateEnabled && ! $viewEnabled && isset($capabilities[CapabilityCatalog::VIEW])) {
                $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
            }

            if ($deleteEnabled && ! $viewEnabled && isset($capabilities[CapabilityCatalog::VIEW])) {
                $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
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
                abort(422, "El módulo [$module] no puede permitir crear sin permitir ver.");
            }

            if ($updateEnabled && ! $viewEnabled) {
                abort(422, "El módulo [$module] no puede permitir editar sin permitir ver.");
            }

            if ($deleteEnabled && ! $viewEnabled) {
                abort(422, "El módulo [$module] no puede permitir eliminar sin permitir ver.");
            }
        }
    }

    protected function validateScopes(array $matrix): void
    {
        foreach ($matrix as $module => $capabilities) {
            foreach ($capabilities as $capability => $meta) {
                if (! $meta['enabled']) {
                    if ($meta['scope'] !== null) {
                        abort(422, "No se puede persistir alcance en [$module][$capability] cuando la capacidad está deshabilitada.");
                    }

                    continue;
                }

                $allowedScopes = PermissionScopeCatalog::optionsFor($module, $capability);

                if (empty($allowedScopes)) {
                    if ($meta['scope'] !== null) {
                        abort(422, "El módulo [$module] no admite alcance para [$capability].");
                    }

                    continue;
                }

                if ($meta['scope'] === null) {
                    abort(422, "El módulo [$module] requiere un alcance explícito para [$capability].");
                }

                if (! array_key_exists($meta['scope'], $allowedScopes)) {
                    abort(422, "El alcance seleccionado no es válido para [$module][$capability].");
                }
            }
        }
    }
}
