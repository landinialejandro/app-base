<?php

// FILE: app/Support/Products/ProductSurfaceService.php | V6
namespace App\Support\Products;

use App\Models\Product;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;

class ProductSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'product.inventory.linked',
                label: 'Artículo',
                targets: ['inventory.show'],
                slot: 'summary_items',
                priority: 10,
                view: 'products.components.linked-product',
                resolver: $this->resolveInventoryLinked(...),
            ),

            $this->linkedOffer(
                key: 'product.inventory.create',
                label: 'Nuevo artículo',
                targets: ['inventory.index'],
                slot: 'header_actions',
                priority: 20,
                view: 'products.components.linked-product',
                resolver: $this->resolveInventoryCreateAction(...),
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host === 'inventory.show' && $record instanceof Product) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'product',
                'trailQuery' => is_array($context['trailQuery'] ?? null)
                    ? $context['trailQuery']
                    : [],
            ];
        }

        if ($host === 'inventory.index') {
            return [
                'host' => $host,
                'record' => null,
                'recordType' => 'inventory_index',
            ];
        }

        return [];
    }

    private function resolveInventoryLinked(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'product' || ! $record instanceof Product) {
            return [
                'data' => [
                    'linked' => [
                        'supported' => true,
                        'exists' => false,
                        'hidden' => true,
                        'readonly' => false,
                        'state' => 'hidden',
                        'show_url' => null,
                        'create_url' => null,
                        'label' => 'Artículo',
                        'text' => 'Artículo',
                    ],
                    'variant' => 'summary',
                ],
            ];
        }

        return [
            'data' => [
                'linked' => ProductLinked::forProduct(
                    $record,
                    $trailQuery,
                    'Artículo',
                ),
                'variant' => 'summary',
            ],
        ];
    }

    private function resolveInventoryCreateAction(array $hostPack): array
    {
        [, $recordType] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'inventory_index') {
            return [
                'data' => [
                    'linked' => [
                        'supported' => false,
                        'exists' => false,
                        'hidden' => true,
                        'readonly' => false,
                        'state' => 'hidden',
                        'show_url' => null,
                        'create_url' => null,
                        'label' => 'Artículo',
                        'text' => 'Nuevo artículo',
                    ],
                    'variant' => 'button',
                ],
            ];
        }

        $user = auth()->user();

        $canCreate = $user
            && app(Security::class)->allows(
                $user,
                ModuleCatalog::PRODUCTS . '.create',
                Product::class
            );

        return [
            'data' => [
                'linked' => [
                    'supported' => true,
                    'exists' => false,
                    'hidden' => ! $canCreate,
                    'readonly' => ! $canCreate,
                    'state' => $canCreate ? 'creatable' : 'hidden',
                    'show_url' => null,
                    'create_url' => $canCreate ? route('products.create') : null,
                    'label' => 'Artículo',
                    'text' => 'Nuevo artículo',
                ],
                'variant' => 'button',
            ],
        ];
    }
}