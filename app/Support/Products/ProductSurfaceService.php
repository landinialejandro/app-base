<?php

// FILE: app/Support/Products/ProductSurfaceService.php | V7

namespace App\Support\Products;

use App\Models\Product;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use App\Support\Products\OperationalSummary\ProductOperationalSummaryService;

class ProductSurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    protected array $operationalSummaryCache = [];

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

            $this->embeddedOffer(
                key: 'product.operational_summary.purchases',
                label: 'Compras',
                targets: ['products.show'],
                slot: 'summary_items',
                priority: 70,
                view: 'products.operational-summary.purchases-summary',
                resolver: $this->resolveOperationalPurchasesSummary(...),
            ),

            $this->embeddedOffer(
                key: 'product.operational_summary.sales',
                label: 'Ventas',
                targets: ['products.show'],
                slot: 'summary_items',
                priority: 71,
                view: 'products.operational-summary.sales-summary',
                resolver: $this->resolveOperationalSalesSummary(...),
            ),

            $this->embeddedOffer(
                key: 'product.operational_summary.flow',
                label: 'Entradas / salidas',
                targets: ['products.show'],
                slot: 'summary_items',
                priority: 72,
                view: 'products.operational-summary.flow-summary',
                resolver: $this->resolveOperationalFlowSummary(...),
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

        if ($host === 'products.show' && $record instanceof Product) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'product',
                'trailQuery' => is_array($context['trailQuery'] ?? null)
                    ? $context['trailQuery']
                    : [],
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

    private function resolveOperationalPurchasesSummary(array $hostPack): array
    {
        $summary = $this->operationalSummary($hostPack);
        $purchases = $summary['purchases'] ?? ['can_view' => false];

        return [
            'visible' => ($purchases['can_view'] ?? false) === true,
            'count' => null,
            'data' => [
                'purchases' => $purchases,
                'unitLabel' => $summary['price']['unit_label'] ?? null,
            ],
        ];
    }

    private function resolveOperationalSalesSummary(array $hostPack): array
    {
        $summary = $this->operationalSummary($hostPack);
        $sales = $summary['sales'] ?? ['can_view' => false];

        return [
            'visible' => ($sales['can_view'] ?? false) === true,
            'count' => null,
            'data' => [
                'sales' => $sales,
                'unitLabel' => $summary['price']['unit_label'] ?? null,
            ],
        ];
    }

    private function resolveOperationalFlowSummary(array $hostPack): array
    {
        $summary = $this->operationalSummary($hostPack);
        $inventory = $summary['inventory'] ?? ['can_view' => false];

        return [
            'visible' => ($inventory['can_view'] ?? false) === true,
            'count' => null,
            'data' => [
                'inventory' => $inventory,
                'unitLabel' => $summary['price']['unit_label'] ?? null,
            ],
        ];
    }

    private function operationalSummary(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        $user = auth()->user();

        if ($recordType !== 'product' || ! $record instanceof Product || ! $user instanceof User) {
            return [
                'can_view' => false,
            ];
        }

        $cacheKey = implode(':', [
            (string) $record->tenant_id,
            (string) $record->id,
            (string) $user->id,
            md5(json_encode($trailQuery, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
        ]);

        if (! array_key_exists($cacheKey, $this->operationalSummaryCache)) {
            $this->operationalSummaryCache[$cacheKey] = app(ProductOperationalSummaryService::class)
                ->forProduct($record, $user, $trailQuery);
        }

        return $this->operationalSummaryCache[$cacheKey];
    }
}