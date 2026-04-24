<?php

// FILE: app/Support/Navigation/InventoryNavigationTrail.php | V5

namespace App\Support\Navigation;

use App\Models\InventoryMovement;
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

        $label = 'Movimientos: '.($product->name ?: 'Producto #'.$product->id);

        if (! empty($current)) {
            return NavigationTrail::appendOrCollapse(
                $current,
                NavigationTrail::makeNode(
                    'inventory.show',
                    $product->id,
                    $label,
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
                $label,
                route('inventory.show', ['product' => $product]),
            ),
        ]);
    }

    public static function movementShow(Request $request, InventoryMovement $movement): array
    {
        $current = NavigationTrail::decode($request->query('trail'));

        $product = $movement->product;
        $productLabel = $product
            ? 'Movimientos: '.($product->name ?: 'Producto #'.$product->id)
            : 'Movimientos';

        $label = 'Movimiento #'.$movement->id;

        if (! empty($current)) {
            return NavigationTrail::appendOrCollapse(
                $current,
                NavigationTrail::makeNode(
                    'inventory.movements.show',
                    $movement->id,
                    $label,
                    route('inventory.movements.show', ['movement' => $movement]),
                ),
            );
        }

        $nodes = [
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('inventory.index', null, 'Inventario', route('inventory.index')),
        ];

        if ($product) {
            $nodes[] = NavigationTrail::makeNode(
                'inventory.show',
                $product->id,
                $productLabel,
                route('inventory.show', ['product' => $product]),
            );
        }

        $nodes[] = NavigationTrail::makeNode(
            'inventory.movements.show',
            $movement->id,
            $label,
            route('inventory.movements.show', ['movement' => $movement]),
        );

        return NavigationTrail::base($nodes);
    }

    public static function productShow(Request $request, Product $product): array
    {
        $current = NavigationTrail::decode($request->query('trail'));

        if (! empty($current)) {
            return NavigationTrail::appendOrCollapse(
                $current,
                NavigationTrail::makeNode(
                    'products.show',
                    $product->id,
                    $product->name ?: 'Producto #'.$product->id,
                    route('products.show', ['product' => $product]),
                ),
            );
        }

        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('products.index', null, 'Productos', route('products.index')),
            NavigationTrail::makeNode(
                'products.show',
                $product->id,
                $product->name ?: 'Producto #'.$product->id,
                route('products.show', ['product' => $product]),
            ),
        ]);
    }
}