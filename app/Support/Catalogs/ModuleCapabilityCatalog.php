<?php

namespace App\Support\Catalogs;

class ModuleCapabilityCatalog
{
    public static function capabilitiesFor(string $module): array
    {
        return match ($module) {
            ModuleCatalog::DASHBOARD,
            ModuleCatalog::SERVICE_MAINTENANCE,
            ModuleCatalog::PRODUCTION => [
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

    public static function mapFor(array $modules): array
    {
        if (empty($modules)) {
            return [];
        }

        $map = [];

        foreach ($modules as $module) {
            $capabilities = static::capabilitiesFor($module);

            if (! empty($capabilities)) {
                $map[$module] = $capabilities;
            }
        }

        return $map;
    }
}
