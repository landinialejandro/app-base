<?php

// FILE: app/Http/Controllers/AdminTenantModuleAccessController.php | V1

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Http\Request;

class AdminTenantModuleAccessController extends Controller
{
    public function edit(Tenant $tenant)
    {
        $moduleLabels = ModuleCatalog::labels();
        $editableOverrides = $this->editableOverrides($tenant);
        $effectiveModules = TenantModuleAccess::enabledModules($tenant);

        return view('admin.tenants.modules-edit', [
            'tenant' => $tenant,
            'moduleLabels' => $moduleLabels,
            'editableOverrides' => $editableOverrides,
            'effectiveModules' => $effectiveModules,
            'hasEditableOverride' => ! empty($editableOverrides),
            'enabledModulesCount' => collect($effectiveModules)->filter()->count(),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $submittedModules = $request->input('modules', []);

        if (! is_array($submittedModules)) {
            $submittedModules = [];
        }

        $normalizedModules = [];

        foreach (ModuleCatalog::all() as $module) {
            $normalizedModules[$module] = $request->boolean('modules.'.$module);
        }

        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        unset($settings['enabled_modules']);

        $moduleAccess = $settings['module_access'] ?? [];

        if (! is_array($moduleAccess)) {
            $moduleAccess = [];
        }

        $moduleAccess['enabled_modules'] = $normalizedModules;
        $settings['module_access'] = $moduleAccess;

        $tenant->update([
            'settings' => $settings,
        ]);

        $enabledCount = collect($normalizedModules)->filter()->count();

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with(
                'success',
                $enabledCount > 0
                    ? 'Configuración de módulos actualizada correctamente.'
                    : 'Configuración guardada. El tenant quedó sin módulos habilitados.'
            );
    }

    public function reset(Tenant $tenant)
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        unset($settings['enabled_modules']);

        if (isset($settings['module_access']) && is_array($settings['module_access'])) {
            unset($settings['module_access']['enabled_modules']);

            if (empty($settings['module_access'])) {
                unset($settings['module_access']);
            }
        }

        $tenant->update([
            'settings' => $settings,
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('success', 'Se restauró la configuración heredada de módulos para este tenant.');
    }

    protected function editableOverrides(Tenant $tenant): array
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        $nestedOverrides = data_get($settings, 'module_access.enabled_modules', []);
        $legacyOverrides = $settings['enabled_modules'] ?? [];

        if (is_array($nestedOverrides) && ! empty($nestedOverrides)) {
            return $this->normalizeModuleMap($nestedOverrides);
        }

        if (is_array($legacyOverrides) && ! empty($legacyOverrides)) {
            return $this->normalizeModuleMap($legacyOverrides);
        }

        return [];
    }

    protected function normalizeModuleMap(array $modules): array
    {
        $normalized = [];

        foreach (ModuleCatalog::all() as $module) {
            if (! array_key_exists($module, $modules)) {
                continue;
            }

            $normalized[$module] = (bool) $modules[$module];
        }

        return $normalized;
    }
}
