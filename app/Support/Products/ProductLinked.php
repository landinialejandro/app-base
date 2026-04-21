<?php

// FILE: app/Support/Products/ProductLinked.php | V1

namespace App\Support\Products;

use App\Models\Product;

class ProductLinked
{
    public static function forProduct(?Product $product, array $trailQuery = [], string $label = 'Artículo'): array
    {
        if (! $product) {
            return [
                'supported' => true,
                'exists' => false,
                'hidden' => false,
                'readonly' => false,
                'state' => 'missing',
                'show_url' => null,
                'label' => $label,
                'text' => '—',
            ];
        }

        $user = auth()->user();
        $canView = (bool) ($user && $user->can('view', $product));

        return [
            'supported' => true,
            'exists' => true,
            'hidden' => false,
            'readonly' => ! $canView,
            'state' => $canView ? 'linked_viewable' : 'linked_readonly',
            'show_url' => $canView
                ? route('products.show', ['product' => $product] + $trailQuery)
                : null,
            'label' => $label,
            'text' => $product->name ?: 'Artículo #'.$product->getKey(),
        ];
    }
}
