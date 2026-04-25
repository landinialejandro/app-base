<?php

// FILE: app/Support/Inventory/InventorySurfaceService.php | V23

namespace App\Support\Inventory;

use App\Models\Document;
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

            $this->embeddedOffer(
                key: 'inventory.document.movements',
                label: 'Inventario',
                targets: ['documents.show'],
                slot: 'tab_panels',
                priority: 30,
                view: 'inventory.partials.embedded-context',
                resolver: $this->resolveEmbeddedForDocument(...),
            ),

            $this->linkedOffer(
                key: 'inventory.order_item.actions',
                label: 'Operación por línea',
                targets: ['orders.items.row'],
                slot: 'row_actions',
                priority: 20,
                view: 'inventory.components.order-item-row-actions',
                resolver: $this->resolveOrderItemRowActions(...),
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
                'modal_namespace' => (string) ($context['modal_namespace'] ?? ''),
            ];
        }

        return [
            'host' => $host,
            'record' => $record,
            'recordType' => $this->resolveRecordType($record),
            'trailQuery' => $context['trailQuery'] ?? [],
            'modal_namespace' => (string) ($context['modal_namespace'] ?? ''),
        ];
    }

    private function resolveEmbeddedForDocument(array $hostPack): array
    {
        [$record, $recordType, $trailQuery] = $this->unpackHostPack($hostPack);

        if ($recordType !== 'document' || ! $record instanceof Document) {
            return [
                'count' => 0,
                'data' => [
                    'contextType' => 'document',
                    'document' => null,
                    'movementRows' => collect(),
                    'emptyMessage' => 'No hay movimientos de inventario asociados a este documento.',
                    'trailQuery' => $trailQuery,
                ],
            ];
        }

        $movementRows = $this->movementRowsForOrigin(
            originType: InventoryOriginCatalog::TYPE_DOCUMENT,
            originId: $record->id,
            tenantId: $record->tenant_id,
        );

        return [
            'count' => $movementRows->count(),
            'data' => [
                'contextType' => 'document',
                'document' => $record,
                'movementRows' => $movementRows,
                'emptyMessage' => 'No hay movimientos de inventario asociados a este documento.',
                'trailQuery' => $trailQuery,
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

        $inventoryContext = app(InventoryOrderContextResolver::class)->forOrder($record);

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

    private function resolveOrderItemRowActions(array $hostPack): array
    {
        $record = $hostPack['record'] ?? null;
        $recordType = $hostPack['recordType'] ?? null;
        $trailQuery = is_array($hostPack['trailQuery'] ?? null) ? $hostPack['trailQuery'] : [];
        $order = $hostPack['order'] ?? null;
        $modalNamespace = trim((string) ($hostPack['modal_namespace'] ?? ''));

        if ($recordType !== 'order_item' || ! $record instanceof OrderItem || ! $order instanceof Order) {
            return [
                'count' => 0,
                'data' => [
                    'actions' => [],
                ],
            ];
        }

        $contextResolver = app(InventoryOrderContextResolver::class);

        $order->loadMissing([
            'items.product',
        ]);

        $inventoryContext = $contextResolver->forOrder($order);

        $row = collect($inventoryContext['items'] ?? [])
            ->first(fn (array $candidate) => (int) ($candidate['order_item_id'] ?? 0) === (int) $record->id);

        if (! is_array($row)) {
            return [
                'count' => 0,
                'data' => [
                    'actions' => [],
                ],
            ];
        }

        $actions = [];
        $productId = $row['product_id'] ?? null;
        $orderItemId = $row['order_item_id'] ?? null;
        $modalPrefix = $modalNamespace !== '' ? $modalNamespace.'-' : '';

        if (($row['can_execute'] ?? false) === true) {
            $actions[] = [
                'type' => 'modal',
                'action_key' => $row['execute_action_key'] ?? 'execute',
                'label' => $row['execute_label'] ?? 'Operar línea',
                'title' => $row['execute_title'] ?? 'Operar línea',
                'icon' => $row['execute_icon'] ?? 'truck',
                'modal_view' => 'inventory.partials.order-line-execute-modal',
                'modal_id' => $modalPrefix.'inventory-row-execute-line-'.$record->id,
                'row' => $row,
                'order' => $order,
                'trailQuery' => $trailQuery,
            ];
        }

        if (($row['can_return'] ?? false) === true && (float) ($row['max_return_quantity'] ?? 0) > 0) {
            $actions[] = [
                'type' => 'modal',
                'action_key' => $row['return_action_key'] ?? 'return',
                'label' => $row['return_label'] ?? 'Devolver línea',
                'title' => $row['return_title'] ?? 'Devolver línea',
                'icon' => $row['return_icon'] ?? 'rotate-ccw',
                'modal_view' => 'inventory.partials.order-line-return-modal',
                'modal_id' => $modalPrefix.'inventory-row-return-line-'.$record->id,
                'row' => $row,
                'order' => $order,
                'trailQuery' => $trailQuery,
            ];
        }

        if ($productId && $orderItemId) {
            $actions[] = [
                'type' => 'link',
                'action_key' => 'view_movements',
                'label' => 'Ver movimientos',
                'title' => 'Ver movimientos de la línea',
                'icon' => 'eye',
                'href' => route('inventory.show', [
                    'product' => $productId,
                ] + $trailQuery + [
                    'origin_line_type' => InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM,
                    'origin_line_id' => $orderItemId,
                ]),
            ];
        }

        return [
            'count' => count($actions),
            'data' => [
                'actions' => $actions,
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
        return InventoryMovement::query()
            ->where('tenant_id', $product->tenant_id)
            ->where('product_id', $product->id)
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

private function movementRowsForOrigin(string $originType, int|string $originId, string $tenantId): Collection
{
    $movements = InventoryMovement::query()
        ->where('tenant_id', $tenantId)
        ->where('origin_type', $originType)
        ->where('origin_id', $originId)
        ->with([
            'product',
            'operation',
        ])
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

    return $this->buildMovementRows($movements);
}

private function movementRowsForProduct(Product $product, string $movementKind = ''): Collection
{
    $movements = InventoryMovement::query()
        ->where('tenant_id', $product->tenant_id)
        ->where('product_id', $product->id)
        ->with([
            'product',
            'operation',
        ])
        ->orderBy('created_at')
        ->orderBy('id')
        ->get();

    return $this->buildMovementRows($movements, $movementKind);
}

    private function buildMovementRows(Collection $movements, string $movementKind = ''): Collection
    {
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
            $record instanceof Document => 'document',
            $record instanceof Order => 'order',
            $record instanceof OrderItem => 'order_item',
            $record instanceof Product => 'product',
            default => null,
        };
    }
}