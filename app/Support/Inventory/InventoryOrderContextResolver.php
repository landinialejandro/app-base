<?php

// FILE: app/Support/Inventory/InventoryOrderContextResolver.php | V4

namespace App\Support\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\OrderItemCatalog;
use App\Support\Catalogs\ProductCatalog;

class InventoryOrderContextResolver
{
    public function forOrder(Order $order): array
    {
        $order->loadMissing([
            'items.product',
        ]);

        $profile = app(InventoryOperationProfileResolver::class)->forOrder($order);
        $isOperable = OrderCatalog::isOperableStatus($order->status);
        $isReadonly = OrderCatalog::isReadonlyStatus($order->status);

        $items = $order->items
            ->sortBy('position')
            ->values()
            ->map(function (OrderItem $item) use ($order, $profile, $isOperable, $isReadonly) {
                return $this->resolveItemContext(
                    order: $order,
                    item: $item,
                    profile: $profile,
                    isOperable: $isOperable,
                    isReadonly: $isReadonly,
                );
            })
            ->values();

        $hasMovements = $items->contains(
            fn (array $row) => ($row['has_movements'] ?? false) === true
        );

        $displayStatus = $this->resolveDisplayStatus($order, $items, $hasMovements);

        $canCancel = ! $isReadonly
            && ! $hasMovements
            && $order->status !== OrderCatalog::STATUS_CANCELLED;

        return [
            'order_id' => $order->id,
            'order_kind' => $order->kind,
            'order_status' => $order->status,
            'direction' => $profile['direction'],
            'operation_profile' => $profile,
            'is_operable' => $isOperable,
            'is_readonly' => $isReadonly,
            'has_movements' => $hasMovements,
            'can_cancel' => $canCancel,
            'items' => $items,
            'display_status' => $displayStatus,
            'display_status_label' => InventoryFulfillmentDisplayCatalog::label($displayStatus),
            'display_status_badge' => InventoryFulfillmentDisplayCatalog::badgeClass($displayStatus),
        ];
    }

    protected function resolveItemContext(
        Order $order,
        OrderItem $item,
        array $profile,
        bool $isOperable,
        bool $isReadonly,
    ): array {
        $statusService = app(OrderItemStatusService::class);
        $stockCalculator = app(ProductStockCalculator::class);

        $product = $item->product;
        $isPhysicalProduct = $product && $product->kind === ProductCatalog::KIND_PRODUCT;

        $orderedQuantity = $this->normalizeQuantity($item->quantity);

        $executedQuantity = $isPhysicalProduct
            ? $statusService->executedQuantity($item)
            : 0.0;

        $pendingQuantity = $isPhysicalProduct
            ? $statusService->pendingQuantity($item)
            : 0.0;

        $lineStatus = $item->status ?: OrderItemCatalog::STATUS_PENDING;

        $currentStock = $isPhysicalProduct
            ? $stockCalculator->forProduct($product)
            : null;

        $hasMovements = $isPhysicalProduct
            ? $statusService->hasMovements($item)
            : false;

        $isLineCompleted = $lineStatus === OrderItemCatalog::STATUS_COMPLETED;
        $isLineCancelled = $lineStatus === OrderItemCatalog::STATUS_CANCELLED;
        $isLineLocked = $isReadonly || $isLineCompleted || $isLineCancelled;

        $canExecute = $isPhysicalProduct
            && $isOperable
            && ! $isReadonly
            && OrderItemCatalog::isOperable($lineStatus)
            && $pendingQuantity > 0;

        $maxReturnQuantity = $isPhysicalProduct
            ? $executedQuantity
            : 0.0;

        $canReturn = $isPhysicalProduct
            && $isOperable
            && ! $isReadonly
            && $executedQuantity > 0;

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
            'direction' => $profile['direction'],
            'is_operable' => $isOperable,
            'is_readonly' => $isReadonly,
            'is_line_locked' => $isLineLocked,
            'has_movements' => $hasMovements,
            'can_execute' => $canExecute,
            'can_return' => $canReturn,
            'max_return_quantity' => $maxReturnQuantity,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'execute_kind' => $profile['execute_kind'],
            'return_kind' => $profile['reverse_kind'],
            'execute_label' => $profile['execute_label'],
            'return_label' => $profile['reverse_label'],
            'execute_title' => $profile['execute_title'],
            'return_title' => $profile['reverse_title'],
            'execute_icon' => $profile['execute_icon'],
            'return_icon' => $profile['reverse_icon'],
            'execute_action_key' => $profile['execute_action_key'],
            'return_action_key' => $profile['reverse_action_key'],
            'order_id' => $order->id,
        ];
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }

    protected function resolveDisplayStatus(Order $order, $items, bool $hasMovements): string
    {
        if ($order->status === OrderCatalog::STATUS_CANCELLED) {
            return InventoryFulfillmentDisplayCatalog::STATUS_CANCELLED;
        }

        if ($order->status === OrderCatalog::STATUS_CLOSED) {
            return InventoryFulfillmentDisplayCatalog::STATUS_CLOSED;
        }

        if ($order->status === OrderCatalog::STATUS_DRAFT) {
            return $hasMovements
                ? InventoryFulfillmentDisplayCatalog::STATUS_PENDING_APPROVAL
                : InventoryFulfillmentDisplayCatalog::STATUS_DRAFT;
        }

        if ($order->status === OrderCatalog::STATUS_PENDING_APPROVAL) {
            return InventoryFulfillmentDisplayCatalog::STATUS_PENDING_APPROVAL;
        }

        if ($order->status !== OrderCatalog::STATUS_APPROVED) {
            return $order->status;
        }

        $physicalItems = collect($items)
            ->filter(fn (array $row) => ($row['is_physical_product'] ?? false) === true)
            ->values();

        if ($physicalItems->isEmpty() || ! $hasMovements) {
            return InventoryFulfillmentDisplayCatalog::STATUS_APPROVED;
        }

        $allFulfilled = $physicalItems->every(
            fn (array $row) => (float) ($row['pending_quantity'] ?? 0) <= 0
        );

        return $allFulfilled
            ? InventoryFulfillmentDisplayCatalog::STATUS_FULFILLED
            : InventoryFulfillmentDisplayCatalog::STATUS_PARTIALLY_FULFILLED;
    }
}
