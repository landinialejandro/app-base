<?php

// FILE: database/seeders/Modules/RoleModuleSeeder.php | V1

namespace Database\Seeders\Modules;

use App\Models\Role;
use App\Support\Catalogs\RoleCatalog;

class RoleModuleSeeder extends BaseModuleSeeder
{
    public function run(): void
    {
        if (! $this->hasDependency('tenants')) {
            throw new \RuntimeException('RoleModuleSeeder requires tenants');
        }

        $tenants = $this->getDependency('tenants');
        $rolesByTenant = [];

        foreach ($tenants as $tenantKey => $tenant) {
            $rolesByTenant[$tenantKey] = [];

            foreach (RoleCatalog::all() as $roleSlug) {
                $role = Role::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'slug' => $roleSlug,
                    ],
                    [
                        'name' => RoleCatalog::label($roleSlug, ucfirst($roleSlug)),
                        'description' => null,
                    ]
                );

                $rolesByTenant[$tenantKey][$roleSlug] = $role;
            }
        }

        $this->context['roles'] = $rolesByTenant;
    }
}
