<?php

// FILE: app/Support/Tenants/TenantBusinessContext.php | V1

namespace App\Support\Tenants;

use App\Models\Tenant;
use App\Support\Catalogs\BusinessTypeCatalog;

class TenantBusinessContext
{
    public static function type(?Tenant $tenant = null): string
    {
        $tenant = $tenant ?: (app()->bound('tenant') ? app('tenant') : null);

        if (! $tenant) {
            return BusinessTypeCatalog::GENERIC;
        }

        $type = data_get($tenant->settings, 'business_profile.type');

        if (! in_array($type, BusinessTypeCatalog::all(), true)) {
            return BusinessTypeCatalog::GENERIC;
        }

        return $type;
    }
}
