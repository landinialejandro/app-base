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
         * parties.create es contextual.
         * Requiere kind y no debe resolverse mediante policy abstracta.
         * El consumo correcto es Security::authorize/allows con contexto explícito.
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
