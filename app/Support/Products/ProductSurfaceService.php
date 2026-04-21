<?php

// FILE: app/Support/Products/ProductSurfaceService.php | V3

namespace App\Support\Products;

use App\Models\Product;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class ProductSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'product.linked',
                label: 'Producto',
                targets: ['inventory.index'],
                slot: 'header_actions',
                priority: 20,
                view: 'products.components.linked-product-action',
                resolver: $this->resolveInventoryCreateAction(...),
                needs: ['trailQuery'],
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        return [];
    }

    private function resolveInventoryCreateAction(array $hostPack): array
    {
        [, , $trailQuery] = $this->unpackHostPack($hostPack);

        $canCreate = auth()->user()?->can('create', Product::class) === true;

        return [
            'count' => 0,
            'data' => [
                'action' => [
                    'supported' => true,
                    'linked' => false,
                    'can_view' => false,
                    'can_create' => $canCreate,
                    'readonly' => false,
                    'hidden' => ! $canCreate,
                    'show_url' => null,
                    'create_url' => $canCreate ? route('products.create', $trailQuery) : null,
                    'label' => 'Artículo',
                    'linked_text' => 'Artículo',
                ],
                'variant' => 'button',
            ],
        ];
    }
}
