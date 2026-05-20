<?php

// FILE: app/Support/Navigation/ShopNavigationTrail.php | V1

namespace App\Support\Navigation;

use App\Models\Shop;
use App\Models\ShopItem;
use Illuminate\Http\Request;

class ShopNavigationTrail
{
    public static function shopsBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('shops.index', null, 'Tiendas', route('shops.index')),
        ]);
    }

    public static function show(Request $request, Shop $shop, ?string $returnTab = null): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::shopsBase();
        }

        $trail = NavigationTrail::removeNodes($trail, [
            ['key' => 'shops.create', 'id' => 'new'],
            ['key' => 'shops.edit', 'id' => $shop->id],
            ['key' => 'shops.preview', 'id' => $shop->id],
            ['key' => 'shops.items.create', 'id' => $shop->id],
            ['key' => 'shops.items.edit'],
        ]);

        $query = [];

        if ($returnTab !== null && $returnTab !== '') {
            $query['return_tab'] = $returnTab;
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'shops.show',
                $shop->id,
                $shop->name ?: 'Tienda #'.$shop->id,
                route('shops.show', ['shop' => $shop] + $query)
            )
        );
    }

    public static function create(Request $request): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::shopsBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'shops.create',
                'new',
                'Nueva tienda',
                route('shops.create')
            )
        );
    }

    public static function edit(Request $request, Shop $shop): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'shops.show', $shop->id)) {
            $trail = self::show($request, $shop);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'shops.edit',
                $shop->id,
                'Editar',
                route('shops.edit', ['shop' => $shop])
            )
        );
    }

    public static function preview(Request $request, Shop $shop): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'shops.show', $shop->id)) {
            $trail = self::show($request, $shop, 'items');
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'shops.preview',
                $shop->id,
                'Vista previa',
                route('shops.preview', ['shop' => $shop])
            )
        );
    }

    public static function itemCreate(Request $request, Shop $shop): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'shops.show', $shop->id)) {
            $trail = self::show($request, $shop, 'items');
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'shops.items.create',
                $shop->id,
                'Agregar artículo',
                route('shops.items.create', ['shop' => $shop])
            )
        );
    }

    public static function itemEdit(Request $request, Shop $shop, ShopItem $item): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'shops.show', $shop->id)) {
            $trail = self::show($request, $shop, 'items');
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'shops.items.edit',
                $item->id,
                'Editar artículo',
                route('shops.items.edit', [
                    'shop' => $shop,
                    'item' => $item,
                ])
            )
        );
    }
}