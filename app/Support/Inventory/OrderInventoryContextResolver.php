<?php

// FILE: app/Support/Inventory/OrderInventoryContextResolver.php | V2

namespace App\Support\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\OrderItemCatalog;
use App\Support\Catalogs\ProductCatalog;

class OrderInventoryContextResolver
{
    public function forOrder(Order $order): array
    {
        $order->loadMissing([
            'items.product',
            'items.inventoryMovements',
        ]);

        $direction = $this->directionForOrder($order);
        $isOperable = $this->isOrderOperable($order);
        $isReadonly = $this->isOrderReadonly($order);

        $items = $order->items
            ->sortBy('position')
            ->values()
            ->map(function (OrderItem $item) use ($order, $direction, $isOperable, $isReadonly) {
                return $this->resolveItemContext(
                    order: $order,
                    item: $item,
                    direction: $direction,
                    isOperable: $isOperable,
                    isReadonly: $isReadonly,
                );
            })
            ->values();

        $hasMovements = $items->contains(fn (array $row) => ($row['has_movements'] ?? false) === true);
        $canCancel = ! $isReadonly && ! $hasMovements && $order->status !== OrderCatalog::STATUS_CANCELLED;

        return [
            'order_id' => $order->id,
            'order_kind' => $order->kind,
            'order_status' => $order->status,
            'direction' => $direction,
            'is_operable' => $isOperable,
            'is_readonly' => $isReadonly,
            'has_movements' => $hasMovements,
            'can_cancel' => $canCancel,
            'items' => $items,
        ];
    }

    protected function resolveItemContext(
        Order $order,
        OrderItem $item,
        string $direction,
        bool $isOperable,
        bool $isReadonly,
    ): array {
        $product = $item->product;
        $isPhysicalProduct = $product && $product->kind === ProductCatalog::KIND_PRODUCT;

        $orderedQuantity = $this->normalizeQuantity($item->quantity);

        $executedQuantity = $isPhysicalProduct
            ? app(OrderItemStatusService::class)->executedQuantity($item)
            : 0.0;

        $pendingQuantity = $isPhysicalProduct
            ? max(0, $this->normalizeQuantity($orderedQuantity - $executedQuantity))
            : 0.0;

        $lineStatus = $item->status ?: OrderItemCatalog::STATUS_PENDING;
        $currentStock = $isPhysicalProduct
            ? app(ProductStockCalculator::class)->forProduct($product)
            : null;

        $hasMovements = $item->inventoryMovements
            ->filter(fn ($movement) => $movement->trashed() === false)
            ->isNotEmpty();

        $isLineCompleted = $lineStatus === OrderItemCatalog::STATUS_COMPLETED;
        $isLineCancelled = $lineStatus === OrderItemCatalog::STATUS_CANCELLED;
        $isLineLocked = $isReadonly || $isLineCompleted || $isLineCancelled;

        $canExecute = $isPhysicalProduct
            && $isOperable
            && ! $isReadonly
            && OrderItemCatalog::isOperable($lineStatus)
            && $pendingQuantity > 0;

        $canEdit = ! $isLineLocked;
        $canDelete = ! $isLineLocked;

        return [
            'order_item_id' => $item->id,
            'position' => $item->position,
            'description' => $item->description,
            'kind' => $item->kind,
            'product_id' => $product?->id,
            'product_name' => $product?->name,
            'is_physical_product' => $isPhysicalProduct,
            'ordered_quantity' => $orderedQuantity,
            'executed_quantity' => $executedQuantity,
            'pending_quantity' => $pendingQuantity,
            'current_stock' => $currentStock,
            'line_status' => $lineStatus,
            'line_status_label' => OrderItemCatalog::statusLabel($lineStatus),
            'line_status_badge' => OrderItemCatalog::badgeClass($lineStatus),
            'direction' => $direction,
            'is_operable' => $isOperable,
            'is_readonly' => $isReadonly,
            'is_line_locked' => $isLineLocked,
            'has_movements' => $hasMovements,
            'can_execute' => $canExecute,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'execute_kind' => $this->movementKindForDirection($direction),
            'order_id' => $order->id,
        ];
    }

    protected function directionForOrder(Order $order): string
    {
        return match ($order->kind) {
            OrderCatalog::KIND_PURCHASE => 'in',
            OrderCatalog::KIND_SALE,
            OrderCatalog::KIND_SERVICE => 'out',
            default => 'out',
        };
    }

    protected function movementKindForDirection(string $direction): string
    {
        return $direction === 'in'
            ? InventoryMovementService::KIND_INGRESAR
            : InventoryMovementService::KIND_ENTREGAR;
    }

    protected function isOrderOperable(Order $order): bool
    {
        return $order->status === OrderCatalog::STATUS_APPROVED;
    }

    protected function isOrderReadonly(Order $order): bool
    {
        return in_array($order->status, [
            OrderCatalog::STATUS_CLOSED,
            OrderCatalog::STATUS_CANCELLED,
        ], true);
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
