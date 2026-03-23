<?php

// FILE: app/Http/Controllers/AdminTenantController.php | V1

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $tenants = Tenant::query()
            ->withCount([
                'memberships as users_count',
                'memberships as owners_count' => function ($query) {
                    $query->where('is_owner', true);
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%')
                        ->orWhere('id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'search' => $search,
        ]);
    }

    public function show(Tenant $tenant)
    {
        $tenant->loadCount([
            'memberships as users_count',
            'memberships as owners_count' => function ($query) {
                $query->where('is_owner', true);
            },
        ]);

        $moduleLabels = ModuleCatalog::labels();
        $effectiveModules = TenantModuleAccess::enabledModules($tenant);
        $editableOverrides = $this->editableOverrides($tenant);

        return view('admin.tenants.show', [
            'tenant' => $tenant,
            'moduleLabels' => $moduleLabels,
            'effectiveModules' => $effectiveModules,
            'editableOverrides' => $editableOverrides,
            'hasEditableOverride' => ! empty($editableOverrides),
            'enabledModulesCount' => collect($effectiveModules)->filter()->count(),
            'disabledModulesCount' => collect($effectiveModules)->filter(fn ($enabled) => ! $enabled)->count(),
        ]);
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
