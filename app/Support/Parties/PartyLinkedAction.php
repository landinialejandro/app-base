<?php

// FILE: app/Support/Parties/PartyLinkedAction.php | V1

namespace App\Support\Parties;

use App\Models\Party;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;

class PartyLinkedAction
{
    public static function forParty(
        ?Party $party,
        array $trailQuery = [],
        string $label = 'Contacto',
        ?string $linkedText = null,
    ): array {
        $supported = self::supportsParties();
        $user = auth()->user();

        $linked = $supported && $party instanceof Party;
        $canView = $linked && $user && $user->can('view', $party);

        return [
            'supported' => $supported,
            'linked' => $linked,
            'can_view' => $canView,
            'show_url' => $linked
                ? route('parties.show', ['party' => $party] + $trailQuery)
                : null,
            'label' => $label,
            'linked_text' => $linkedText ?? ($party?->name ?: $label),
        ];
    }

    protected static function supportsParties(): bool
    {
        return TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, app('tenant'));
    }
}
