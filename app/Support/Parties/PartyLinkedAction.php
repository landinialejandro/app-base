<?php

// FILE: app/Support/Parties/PartyLinkedAction.php | V2

namespace App\Support\Parties;

use App\Models\Party;
use App\Support\Auth\Security;
use App\Support\Auth\TenantModuleAccess;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Navigation\NavigationTrail;

class PartyLinkedAction
{
    public static function forParty(
        ?Party $party,
        array $trailQuery = [],
        string $label = 'Contacto',
        ?string $linkedText = null,
        ?string $createKind = null,
        array $createParams = [],
        bool $allowCreate = false,
    ): array {
        $supported = self::supportsParties();
        $user = auth()->user();
        $normalizedTrailQuery = self::normalizeTrailQuery($trailQuery);

        $linked = $supported && $party instanceof Party;
        $canView = $linked && $user && $user->can('view', $party);

        $canCreate = false;
        $createUrl = null;

        if (
            $supported
            && $allowCreate
            && is_string($createKind)
            && $createKind !== ''
            && $user
            && app(Security::class)->allows(
                $user,
                ModuleCatalog::PARTIES.'.create',
                Party::class,
                ['kind' => $createKind]
            )
        ) {
            $canCreate = true;
            $createUrl = route(
                'parties.create',
                array_merge($createParams, $normalizedTrailQuery, ['kind' => $createKind])
            );
        }

        $readonly = $linked && ! $canView;
        $hidden = ! $supported || (! $linked && ! $canCreate);

        return [
            'supported' => $supported,
            'linked' => $linked,
            'can_view' => (bool) $canView,
            'can_create' => $canCreate,
            'readonly' => $readonly,
            'hidden' => $hidden,
            'show_url' => ($linked && $canView)
                ? route('parties.show', ['party' => $party] + $normalizedTrailQuery)
                : null,
            'create_url' => $createUrl,
            'label' => $label,
            'trail_query' => $normalizedTrailQuery,
            'linked_text' => $linkedText ?? ($party?->name ?: $label),
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
