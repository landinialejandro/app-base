<?php

// FILE: app/Support/Navigation/AssetNavigationTrail.php | V4

namespace App\Support\Navigation;

use App\Models\Asset;
use App\Models\Party;
use Illuminate\Http\Request;

class AssetNavigationTrail
{
    public static function assetsBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('assets.index', null, 'Activos', route('assets.index')),
        ]);
    }

    public static function base(Asset $asset): array
    {
        $trail = self::assetsBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'assets.show',
                $asset->id,
                $asset->name ?: 'Activo #'.$asset->id,
                route('assets.show', ['asset' => $asset])
            )
        );
    }

    public static function create(Request $request, ?Party $party = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = $party
                ? PartyNavigationTrail::base($party)
                : self::assetsBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'assets.create',
                'new',
                'Nuevo activo',
                route('assets.create')
            )
        );
    }

    public static function show(Request $request, Asset $asset): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::base($asset);
        }

        $trail = NavigationTrail::removeNodes($trail, [
            ['key' => 'assets.create', 'id' => 'new'],
            ['key' => 'assets.edit', 'id' => $asset->id],
        ]);

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'assets.show',
                $asset->id,
                $asset->name ?: 'Activo #'.$asset->id,
                route('assets.show', ['asset' => $asset])
            )
        );
    }

    public static function edit(Request $request, Asset $asset): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'assets.show', $asset->id)) {
            $trail = self::show($request, $asset);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'assets.edit',
                $asset->id,
                'Editar',
                route('assets.edit', ['asset' => $asset])
            )
        );
    }
}
