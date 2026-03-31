<?php

// FILE: app/Support/Navigation/PartyNavigationTrail.php | V3

namespace App\Support\Navigation;

use App\Models\Party;
use Illuminate\Http\Request;

class PartyNavigationTrail
{
    public static function partiesBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('parties.index', null, 'Contactos', route('parties.index')),
        ]);
    }

    public static function base(Party $party): array
    {
        $trail = self::partiesBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'parties.show',
                $party->id,
                $party->name ?: 'Contacto #'.$party->id,
                route('parties.show', ['party' => $party])
            )
        );
    }

    public static function create(Request $request): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::partiesBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'parties.create',
                'new',
                'Nuevo contacto',
                route('parties.create')
            )
        );
    }

    public static function show(Request $request, Party $party): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::base($party);
        }

        $trail = NavigationTrail::removeNodes($trail, [
            ['key' => 'parties.create', 'id' => 'new'],
            ['key' => 'parties.edit', 'id' => $party->id],
        ]);

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'parties.show',
                $party->id,
                $party->name ?: 'Contacto #'.$party->id,
                route('parties.show', ['party' => $party])
            )
        );
    }

    public static function edit(Request $request, Party $party): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'parties.show', $party->id)) {
            $trail = self::show($request, $party);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'parties.edit',
                $party->id,
                'Editar',
                route('parties.edit', ['party' => $party])
            )
        );
    }
}
