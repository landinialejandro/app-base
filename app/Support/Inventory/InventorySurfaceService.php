<?php

// FILE: app/Support/Inventory/InventorySurfaceService.php | V18
namespace App\Support\Inventory;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Modules\Concerns\BuildsSurfaceOffers;
use App\Support\Modules\Contracts\ModuleSurfaceService;
use Illuminate\Support\Collection;

class InventorySurfaceService implements ModuleSurfaceService
{
    use BuildsSurfaceOffers;

    public function offers(): array
    {
        return [
            $this->linkedOffer(
                key: 'inventory.manual-adjustment',
                label: 'Movimiento',
                targets: ['inventory.show'],
                slot: 'header_actions',
                priority: 20,
                view: 'inventory.components.movement-action',
                resolver: $this->resolveManualAdjustmentAction(...),
            ),

            $this->linkedOffer(
                key: 'inventory.product.linked',
                label: 'Inventario',
                targets: ['products.show'],
                slot: 'header_actions',
                priority: 20,
                view: 'inventory.components.linked-inventory',
                resolver: $this->resolveLinkedForProduct(...),
            ),

            $this->embeddedOffer(
                key: 'inventory.product.stock',
                label: 'Stock actual',
                targets: ['products.show'],
                slot: 'summary_items',
                priority: 30,
                view: 'inventory.components.summary-value',
                resolver: $this->resolveStockForProduct(...),
            ),

            $this->embeddedOffer(
                key: 'inventory.product.last_movement_at',
                label: 'Último movimiento',
                targets: ['products.show'],
                slot: 'summary_items',
                priority: 31,
                view: 'inventory.components.summary-value',
                resolver: $this->resolveLastMovementAtForProduct(...),
            ),

            $this->embeddedOffer(
                key: 'inventory.product.last_movement_kind',
                label: 'Tipo de movimiento',
                targets: ['products.show'],
                slot: 'summary_items',
                priority: 32,
                view: 'inventory.components.summary-value',
                resolver: $this->resolveLastMovementKindForProduct(...),
            ),

            $this->embeddedOffer(
                key: 'inventory.product.movements',
                label: 'Movimientos',
                targets: ['products.show'],
                slot: 'tab_panels',
                priority: 40,
                view: 'inventory.partials.embedded-context',
                resolver: $this->resolveEmbeddedForProduct(...),
            ),

            $this->embeddedOffer(
                key: 'inventory.embedded',
                label: 'Operación',
                targets: ['orders.show'],
                slot: 'tab_panels',
                priority: 30,
                view: 'inventory.partials.embedded-context',
                resolver: $this->resolveEmbeddedForOrder(...),
            ),

            $this->linkedOffer(
                key: 'inventory.order_item.execute',
                label: 'Surtir',
                targets: ['orders.items.row'],
                slot: 'row_actions',
                priority: 20,
                view: 'inventory.components.order-item-row-actions',
                resolver: $this->resolveOrderItemExecuteAction(...),
                needs: ['record', 'recordType', 'trailQuery', 'order'],
            ),
        ];
    }

    public function hostPack(string $host, mixed $record = null, array $context = []): array
    {
        if ($host === 'orders.items.row' && $record instanceof OrderItem && ($context['order'] ?? null) instanceof Order) {
            return [
                'host' => $host,
                'record' => $record,
                'recordType' => 'order_item',
                'order' => $context['order'],
                'trailQuery' => $context['trailQuery'] ?? [],
            ];
        }

        return [
            'host' => $host,
            'record' => $record,
            'recordType' => $this->resolveRecordType($record),
            'trailQuery' => $context['trailQuery'] ?? [],
        ];
    }

    private function resolveManualAdjustmentAction(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'product' || ! $record instanceof Product) {
            return [
                'count' => 0,
                'data' => [
                    'action' => [
                        'supported' => false,
                        'hidden' => true,
                        'state' => 'hidden',
                        'create_url' => null,
                        'label' => 'Movimiento',
                        'text' => 'Agregar movimiento',
                    ],
                    'variant' => 'button',
                ],
            ];
        }

        $canCreate = auth()->user()?->can('update', $record) === true;

        return [
            'count' => 0,
            'data' => [
                'action' => [
                    'supported' => true,
                    'hidden' => ! $canCreate,
                    'state' => $canCreate ? 'creatable' : 'hidden',
                    'create_url' => $canCreate
                        ? route('inventory.movements.create', ['product' => $record] + $trailQuery)
                        : null,
                    'label' => 'Movimiento',
                    'text' => 'Agregar movimiento',
                ],
                'variant' => 'button',
            ],
        ];
    }

    private function resolveLinkedForProduct(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'product' || ! $record instanceof Product || ! $this->supportsInventoryForProduct($record)) {
            return [
                'count' => 0,
                'data' => [
                    'linked' => [
                        'state' => 'hidden',
                        'show_url' => null,
                        'label' => 'Inventario',
                        'text' => 'Inventario',
                    ],
                    'variant' => 'button',
                ],
            ];
        }

        $canView = auth()->user()?->can('view', $record) === true;

        return [
            'count' => 0,
            'data' => [
                'linked' => [
                    'state' => $canView ? 'linked_viewable' : 'linked_readonly',
                    'show_url' => $canView
                        ? route('inventory.show', ['product' => $record] + $trailQuery)
                        : null,
                    'label' => 'Inventario',
                    'text' => 'Inventario',
                ],
                'variant' => 'button',
            ],
        ];
    }

    private function resolveStockForProduct(array $hostPack): array
    {
        $product = $this->productFromHostPack($hostPack);

        return [
            'count' => 0,
            'data' => [
                'value' => $product && $this->supportsInventoryForProduct($product)
                    ? number_format(app(ProductStockCalculator::class)->forProduct($product), 2, ',', '.')
                    : '—',
            ],
        ];
    }

    private function resolveLastMovementAtForProduct(array $hostPack): array
    {
        $product = $this->productFromHostPack($hostPack);
        $lastMovement = $product ? $this->lastMovementForProduct($product) : null;

        return [
            'count' => 0,
            'data' => [
                'value' => $lastMovement?->created_at?->format('d/m/Y H:i') ?: '—',
            ],
        ];
    }

    private function resolveLastMovementKindForProduct(array $hostPack): array
    {
        $product = $this->productFromHostPack($hostPack);
        $lastMovement = $product ? $this->lastMovementForProduct($product) : null;

        return [
            'count' => 0,
            'data' => [
                'value' => $lastMovement
                    ? ($this->kindLabels()[$lastMovement->kind] ?? ucfirst($lastMovement->kind))
                    : '—',
            ],
        ];
    }

    private function resolveEmbeddedForProduct(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'product' || ! $record instanceof Product || ! $this->supportsInventoryForProduct($record)) {
            return [
                'count' => 0,
                'data' => [
                    'contextType' => 'product',
                    'product' => null,
                    'movementRows' => collect(),
                    'movementKind' => '',
                    'kindTabs' => [],
                    'emptyMessage' => 'No hay movimientos registrados para este artículo.',
                    'trailQuery' => $trailQuery,
                ],
            ];
        }

        $movementKind = (string) request()->query('kind', '');
        $movementRows = $this->movementRowsForProduct($record, $movementKind);

        return [
            'count' => $movementRows->count(),
            'data' => [
                'contextType' => 'product',
                'product' => $record,
                'movementRows' => $movementRows,
                'movementKind' => $movementKind,
                'kindTabs' => $this->kindTabsForHost('products.show', $record, $trailQuery),
                'emptyMessage' => 'No hay movimientos registrados para este artículo.',
                'trailQuery' => $trailQuery,
            ],
        ];
    }

    private function resolveEmbeddedForOrder(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'order' || ! $record instanceof Order) {
            return [
                'count' => 0,
                'data' => [
                    'contextType' => 'order',
                    'order' => null,
                    'inventoryContext' => [
                        'items' => [],
                    ],
                    'trailQuery' => $trailQuery,
                ],
            ];
        }

        $inventoryContext = app(OrderInventoryContextResolver::class)->forOrder($record);

        return [
            'count' => collect($inventoryContext['items'] ?? [])->count(),
            'data' => [
                'contextType' => 'order',
                'order' => $record,
                'inventoryContext' => $inventoryContext,
                'trailQuery' => $trailQuery,
            ],
        ];
    }

    private function resolveOrderItemExecuteAction(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];
        $order = $hostPack['order'] ?? null;

        if ($recordType !== 'order_item' || ! $record instanceof OrderItem || ! $order instanceof Order) {
            return [
                'count' => 0,
                'data' => [
                    'actions' => [],
                ],
            ];
        }

        $order->loadMissing([
            'items.product',
            'items.inventoryMovements',
        ]);

        $inventoryContext = app(OrderInventoryContextResolver::class)->forOrder($order);

        $row = collect($inventoryContext['items'] ?? [])
            ->first(fn (array $candidate) => (int) ($candidate['order_item_id'] ?? 0) === (int) $record->id);

        if (! is_array($row) || (($row['can_execute'] ?? false) !== true)) {
            return [
                'count' => 0,
                'data' => [
                    'actions' => [],
                ],
            ];
        }

        return [
            'count' => 1,
            'data' => [
                'actions' => [[
                    'type' => 'modal',
                    'label' => 'Surtir línea',
                    'title' => 'Surtir línea',
                    'button_class' => 'btn btn-success btn-icon',
                    'icon' => 'truck',
                    'modal_view' => 'inventory.partials.order-line-surtir-modal',
                    'modal_id' => 'inventory-row-surtir-line-'.$record->id,
                    'row' => $row,
                    'order' => $order,
                    'trailQuery' => $trailQuery,
                ]],
            ],
        ];
    }

    private function productFromHostPack(array $hostPack): ?Product
    {
        [$record, $recordType] = $this->unpackHostPack($hostPack);

        return $recordType === 'product' && $record instanceof Product
            ? $record
            : null;
    }

    private function supportsInventoryForProduct(Product $product): bool
    {
        return $product->kind === ProductCatalog::KIND_PRODUCT;
    }

    private function lastMovementForProduct(Product $product): ?InventoryMovement
    {
        return $product->inventoryMovements()
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

    private function movementRowsForProduct(Product $product, string $movementKind = ''): Collection
    {
        $movements = $product->inventoryMovements()
            ->with(['order', 'document'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $runningBalances = [];
        $runningBalance = 0.0;

        foreach ($movements as $movement) {
            $runningBalance += $this->signedQuantity($movement->kind, (float) $movement->quantity);
            $runningBalances[$movement->id] = $runningBalance;
        }

        return $movements
            ->when($movementKind !== '', fn (Collection $items) => $items->where('kind', $movementKind))
            ->sortByDesc(fn (InventoryMovement $movement) => sprintf(
                '%s-%010d',
                $movement->created_at?->format('YmdHis') ?? '00000000000000',
                $movement->id
            ))
            ->values()
            ->map(function (InventoryMovement $movement) use ($runningBalances) {
                return [
                    'movement' => $movement,
                    'signed_quantity' => $this->signedQuantity($movement->kind, (float) $movement->quantity),
                    'running_balance' => (float) ($runningBalances[$movement->id] ?? 0),
                ];
            })
            ->values();
    }

    private function signedQuantity(string $kind, float $quantity): float
    {
        return match ($kind) {
            InventoryMovementService::KIND_INGRESAR => $quantity,
            InventoryMovementService::KIND_CONSUMIR,
            InventoryMovementService::KIND_ENTREGAR => -1 * $quantity,
            default => 0.0,
        };
    }

    private function kindTabsForHost(string $host, Product $product, array $trailQuery): array
    {
        $routeName = $host === 'products.show' ? 'products.show' : 'inventory.show';

        return collect([
            '' => 'Todos',
            InventoryMovementService::KIND_INGRESAR => 'Ingresos',
            InventoryMovementService::KIND_CONSUMIR => 'Consumos',
            InventoryMovementService::KIND_ENTREGAR => 'Entregas',
        ])->map(function (string $label, string $kind) use ($routeName, $product, $trailQuery) {
            return [
                'label' => $label,
                'url' => route($routeName, ['product' => $product] + $trailQuery + ($kind !== '' ? ['kind' => $kind] : [])),
                'is_active' => (string) request()->query('kind', '') === $kind,
            ];
        })->values()->all();
    }

    private function kindLabels(): array
    {
        return [
            InventoryMovementService::KIND_INGRESAR => 'Ingresar',
            InventoryMovementService::KIND_CONSUMIR => 'Consumir',
            InventoryMovementService::KIND_ENTREGAR => 'Entregar',
        ];
    }

    private function resolveRecordType(mixed $record): ?string
    {
        return match (true) {
            $record instanceof Order => 'order',
            $record instanceof OrderItem => 'order_item',
            $record instanceof Product => 'product',
            default => null,
        };
    }
}