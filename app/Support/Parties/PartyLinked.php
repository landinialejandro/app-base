<?php

// FILE: app/Support/Parties/PartyLinked.php | V1

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
        $readonly = $exists && ! $canView;
        $hidden = ! $supported || ! $exists;

        $state = 'hidden';

        if (! $hidden) {
            $state = $canView ? 'linked_viewable' : 'linked_readonly';
        }

        return [
            'supported' => $supported,
            'exists' => $exists,
            'hidden' => $hidden,
            'readonly' => $readonly,
            'state' => $state,
            'show_url' => ($exists && $canView)
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
