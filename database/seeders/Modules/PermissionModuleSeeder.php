<?php

// FILE: database/seeders/Modules/PermissionModuleSeeder.php | V3

namespace Database\Seeders\Modules;

use App\Models\Permission;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;

class PermissionModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        $this->context['permissions'] = [];

        foreach ($this->getPermissionDefinitions() as $definition) {
            $permission = Permission::updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'group' => $definition['group'],
                    'description' => $definition['description'] ?? null,
                ]
            );

            $this->context['permissions'][$definition['slug']] = $permission;
        }
    }

    protected function getPermissionDefinitions(): array
    {
        $definitions = [];

        foreach (ModuleCatalog::all() as $module) {
            if ($module === ModuleCatalog::DASHBOARD) {
                continue;
            }

            foreach ($this->capabilitiesForModule($module) as $capability) {
                $definitions[] = [
                    'slug' => CapabilityCatalog::permissionSlug($module, $capability),
                    'name' => $this->buildPermissionName($module, $capability),
                    'group' => $module,
                    'description' => $this->buildPermissionDescription($module, $capability),
                ];
            }
        }

        return $definitions;
    }

    protected function capabilitiesForModule(string $module): array
    {
        return [
            CapabilityCatalog::VIEW_ANY,
            CapabilityCatalog::VIEW,
            CapabilityCatalog::CREATE,
            CapabilityCatalog::UPDATE,
            CapabilityCatalog::DELETE,
        ];
    }

    protected function buildPermissionName(string $module, string $capability): string
    {
        $moduleLabel = mb_strtolower((string) ModuleCatalog::label($module, $module));
        $capabilityLabel = CapabilityCatalog::label($capability, $capability);

        return sprintf('%s %s', $capabilityLabel, $moduleLabel);
    }

    protected function buildPermissionDescription(string $module, string $capability): string
    {
        $moduleLabel = mb_strtolower((string) ModuleCatalog::label($module, $module));
        $capabilityLabel = CapabilityCatalog::label($capability, $capability);

        return sprintf('%s sobre %s.', $capabilityLabel, $moduleLabel);
    }
}
