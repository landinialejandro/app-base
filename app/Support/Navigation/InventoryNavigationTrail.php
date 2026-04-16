<?php

// FILE: app/Support/Navigation/InventoryNavigationTrail.php | V2

namespace App\Support\Navigation;

use App\Models\Product;
use Illuminate\Http\Request;

class InventoryNavigationTrail
{
    public static function index(Request $request): array
    {
        $current = NavigationTrail::decode($request->query('trail'));

        if (! empty($current)) {
            return NavigationTrail::appendOrCollapse(
                $current,
                NavigationTrail::makeNode(
                    'inventory.index',
                    null,
                    'Inventario',
                    route('inventory.index'),
                ),
            );
        }

        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('inventory.index', null, 'Inventario', route('inventory.index')),
        ]);
    }

    public static function show(Request $request, Product $product): array
    {
        $current = NavigationTrail::decode($request->query('trail'));

        if (! empty($current)) {
            return NavigationTrail::appendOrCollapse(
                $current,
                NavigationTrail::makeNode(
                    'inventory.show',
                    $product->id,
                    $product->name ?: 'Producto #'.$product->id,
                    route('inventory.show', ['product' => $product]),
                ),
            );
        }

        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('inventory.index', null, 'Inventario', route('inventory.index')),
            NavigationTrail::makeNode(
                'inventory.show',
                $product->id,
                $product->name ?: 'Producto #'.$product->id,
                route('inventory.show', ['product' => $product]),
            ),
        ]);
    }
}
