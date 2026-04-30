<?php

// FILE: app/Support/Parties/PartyLinked.php | V2

namespace App\Support\Parties;

use App\Models\Party;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Navigation\NavigationTrail;

class PartyLinked
{
    public static function forParty(
        ?Party $party,
        array $trailQuery = [],
        string $label = 'Contacto',
        ?string $text = null,
    ): array {
        $supported = self::supportsParties();
        $user = auth()->user();
        $normalizedTrailQuery = self::normalizeTrailQuery($trailQuery);

        $exists = $supported && $party instanceof Party;
        $canView = $exists && $user && $user->can('view', $party);

        $state = match (true) {
            ! $supported || ! $exists => 'hidden',
            $canView => 'linked_viewable',
            default => 'linked_readonly',
        };

        return [
            'supported' => $supported,
            'exists' => $exists,
            'hidden' => $state === 'hidden',
            'readonly' => $state === 'linked_readonly',
            'state' => $state,
            'show_url' => $canView
                ? route('parties.show', ['party' => $party] + $normalizedTrailQuery)
                : null,
            'label' => $label,
            'trail_query' => $normalizedTrailQuery,
            'text' => $text ?? ($party?->name ?: $label),
        ];
    }

    protected static function supportsParties(): bool
    {
        return TenantModuleAccess::isEnabled(ModuleCatalog::PARTIES, app('tenant'));
    }

    protected static function normalizeTrailQuery(array $trailQuery): array
    {
        $trail = NavigationTrail::decode($trailQuery['trail'] ?? null);

        return ! empty($trail)
            ? NavigationTrail::toQuery($trail)
            : [];
    }
}