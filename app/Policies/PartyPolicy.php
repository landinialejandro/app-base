<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;
use App\Support\Auth\RoleModuleAccess;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\ModuleCatalog;

class PartyPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::PARTIES, $tenant, $user);
    }

    public function view(User $user, Party $party): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::PARTIES, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        $tenant = app('tenant');

        return RoleModuleAccess::canUse(ModuleCatalog::PARTIES, $tenant, $user);
    }

    public function update(User $user, Party $party): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::PARTIES, $tenant, $user)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Party $party): bool
    {
        $tenant = app('tenant');

        if (! RoleModuleAccess::canUse(ModuleCatalog::PARTIES, $tenant, $user)) {
            return false;
        }

        return TenantAccess::isOwnerOrAdmin($tenant->id, $user);
    }
}
