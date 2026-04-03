<?php

// FILE: app/Http/Controllers/TenantProfilePermissionController.php | V5

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

                    DB::table('role_permission')->updateOrInsert(
                        [
                            'role_id' => $role->id,
                            'permission_id' => $permission->id,
                        ],
                        [
                            'scope' => $meta['scope'],
                            'execution_mode' => $meta['execution_mode'],
                            'constraints' => null,
                            'updated_at' => now(),
                            'created_at' => now(),
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

                $matrix[$module][$capability] = [
                    'enabled' => $request->boolean("permissions.$module.$capability.enabled"),
                    'scope' => $this->normalizeNullableString($scope),
                    'execution_mode' => $this->normalizeNullableString($executionMode) ?? 'manual',
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

    protected function autoFixViewDependencies(array $matrix): array
    {
        foreach ($matrix as $module => &$capabilities) {

            $viewAny = $capabilities[CapabilityCatalog::VIEW_ANY]['enabled'] ?? false;
            $view = $capabilities[CapabilityCatalog::VIEW]['enabled'] ?? false;
            $create = $capabilities[CapabilityCatalog::CREATE]['enabled'] ?? false;
            $update = $capabilities[CapabilityCatalog::UPDATE]['enabled'] ?? false;
            $delete = $capabilities[CapabilityCatalog::DELETE]['enabled'] ?? false;

            if ($create && ! $view && ! $viewAny) {
                if (isset($capabilities[CapabilityCatalog::VIEW])) {
                    $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
                }
            }

            if ($update && ! $view) {
                if (isset($capabilities[CapabilityCatalog::VIEW])) {
                    $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
                }
            }

            if ($delete && ! $view) {
                if (isset($capabilities[CapabilityCatalog::VIEW])) {
                    $capabilities[CapabilityCatalog::VIEW]['enabled'] = true;
                }
            }
        }

        return $matrix;
    }

    protected function validateLogicalConsistency(array $matrix): void
    {
        foreach ($matrix as $module => $capabilities) {
            $viewAny = (bool) ($capabilities[CapabilityCatalog::VIEW_ANY]['enabled'] ?? false);
            $view = (bool) ($capabilities[CapabilityCatalog::VIEW]['enabled'] ?? false);
            $create = (bool) ($capabilities[CapabilityCatalog::CREATE]['enabled'] ?? false);
            $update = (bool) ($capabilities[CapabilityCatalog::UPDATE]['enabled'] ?? false);
            $delete = (bool) ($capabilities[CapabilityCatalog::DELETE]['enabled'] ?? false);

            if ($create && ! $viewAny && ! $view) {
                abort(422, "El módulo [$module] no puede permitir crear sin permitir ver.");
            }

            if ($update && ! $view) {
                abort(422, "El módulo [$module] no puede permitir editar sin permitir ver.");
            }

            if ($delete && ! $view) {
                abort(422, "El módulo [$module] no puede permitir eliminar sin permitir ver.");
            }
        }
    }

    protected function validateScopes(array $matrix): void
    {
        foreach ($matrix as $module => $capabilities) {
            foreach ($capabilities as $capability => $meta) {
                if (! $meta['enabled']) {
                    continue;
                }

                $allowedScopes = PermissionScopeCatalog::optionsForCapability($capability);

                if (empty($allowedScopes)) {
                    if ($meta['scope'] !== null) {
                        abort(422, "El módulo [$module] no admite alcance para [$capability].");
                    }

                    continue;
                }

                if ($meta['scope'] !== null && ! array_key_exists($meta['scope'], $allowedScopes)) {
                    abort(422, "El alcance seleccionado no es válido para [$module][$capability].");
                }
            }
        }
    }
}
