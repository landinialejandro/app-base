<?php

// FILE: app/Support/Navigation/ProductNavigationTrail.php | V3

namespace App\Support\Navigation;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductNavigationTrail
{
    public static function productsBase(): array
    {
        return NavigationTrail::base([
            NavigationTrail::makeNode('dashboard', null, 'Inicio', route('dashboard')),
            NavigationTrail::makeNode('products.index', null, 'Productos', route('products.index')),
        ]);
    }

    public static function base(Product $product): array
    {
        $trail = self::productsBase();

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'products.show',
                $product->id,
                $product->name ?: 'Producto #'.$product->id,
                route('products.show', ['product' => $product])
            )
        );
    }

    public static function create(Request $request): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::productsBase();
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'products.create',
                'new',
                'Nuevo producto',
                route('products.create')
            )
        );
    }

    public static function show(Request $request, Product $product): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail)) {
            $trail = self::base($product);
        }

        $trail = NavigationTrail::removeNodes($trail, [
            ['key' => 'products.create', 'id' => 'new'],
            ['key' => 'products.edit', 'id' => $product->id],
        ]);

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'products.show',
                $product->id,
                $product->name ?: 'Producto #'.$product->id,
                route('products.show', ['product' => $product])
            )
        );
    }

    public static function edit(Request $request, Product $product): array
    {
        $trail = NavigationTrail::fromRequest($request);

        if (empty($trail) || ! NavigationTrail::hasNode($trail, 'products.show', $product->id)) {
            $trail = self::show($request, $product);
        }

        return NavigationTrail::appendOrCollapse(
            $trail,
            NavigationTrail::makeNode(
                'products.edit',
                $product->id,
                'Editar',
                route('products.edit', ['product' => $product])
            )
        );
    }
}
