<?php

// FILE: app/Policies/PartyPolicy.php | V2

namespace App\Policies;

use App\Models\Party;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class PartyPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::PARTIES, app('tenant'), $user);
    }

    public function view(User $user, Party $party): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::PARTIES, app('tenant'), $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::PARTIES, 'create', app('tenant'), $user);
    }

    public function update(User $user, Party $party): bool
    {
        return $this->resolver()->can(ModuleCatalog::PARTIES, 'update', app('tenant'), $user);
    }

    public function delete(User $user, Party $party): bool
    {
        return $this->resolver()->can(ModuleCatalog::PARTIES, 'delete', app('tenant'), $user);
    }
}
