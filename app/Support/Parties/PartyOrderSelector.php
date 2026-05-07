<?php

// FILE: app/Support/Parties/PartyOrderSelector.php | V1

namespace App\Support\Parties;

use App\Models\Party;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;
use Illuminate\Support\Collection;

class PartyOrderSelector
{
    public function optionsFor(User $user): Collection
    {
        return app(Security::class)
            ->scope($user, ModuleCatalog::PARTIES.'.viewAny', Party::query())
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(function (Party $party) {
                $roles = $party->roles
                    ->pluck('role')
                    ->filter()
                    ->values()
                    ->all();

                $roleText = empty($roles)
                    ? null
                    : implode(', ', $roles);

                return [
                    'id' => $party->id,
                    'label' => $party->display_name ?: $party->name,
                    'name' => $party->name,
                    'meta' => $roleText,
                ];
            });
    }
}