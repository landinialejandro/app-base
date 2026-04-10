<?php

// FILE: app/Policies/PartyPolicy.php | V7

namespace App\Policies;

use App\Models\Party;
use App\Models\User;
use App\Support\Auth\Security;

class PartyPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'parties.viewAny');
    }

    public function view(User $user, Party $party): bool
    {
        return $this->security()->allows($user, 'parties.view', $party);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'parties.create', Party::class);
    }

    public function update(User $user, Party $party): bool
    {
        return $this->security()->allows($user, 'parties.update', $party);
    }

    public function delete(User $user, Party $party): bool
    {
        return $this->security()->allows($user, 'parties.delete', $party);
    }
}
