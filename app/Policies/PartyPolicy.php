<?php

// FILE: app/Policies/PartyPolicy.php | V8

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
        /**
         * parties.create es contextual por kind.
         * Esta policy no debe autorizar create abstracto sin contexto.
         */
        return false;
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
