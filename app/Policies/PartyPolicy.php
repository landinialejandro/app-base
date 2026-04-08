<?php

// FILE: app/Policies/PartyPolicy.php | V5

namespace App\Policies;

use App\Models\Party;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;

class PartyPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, ModuleCatalog::PARTIES.'.viewAny', Party::class);
    }

    public function view(User $user, Party $party): bool
    {
        return $this->security()->allows($user, ModuleCatalog::PARTIES.'.view', $party);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Party $party): bool
    {
        return $this->security()->allows($user, ModuleCatalog::PARTIES.'.update', $party);
    }

    public function delete(User $user, Party $party): bool
    {
        return $this->security()->allows($user, ModuleCatalog::PARTIES.'.delete', $party);
    }
}
