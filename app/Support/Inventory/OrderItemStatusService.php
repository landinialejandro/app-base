<?php

// FILE: app/Support/Inventory/OrderItemStatusService.php | V7

namespace App\Support\Inventory;

use App\Models\InventoryMovement;
use App\Models\OrderItem;
use App\Support\Catalogs\OrderItemCatalog;
use App\Support\LineItems\LineItemMath;

class OrderItemStatusService
{
    public function recalculate(OrderItem $item): string
    {
        $math = app(LineItemMath::class);

        $status = $math->statusFor(
            quantity: $item->quantity,
            executedQuantity: $this->executedQuantity($item),
            pendingStatus: OrderItemCatalog::STATUS_PENDING,
            partialStatus: OrderItemCatalog::STATUS_PARTIAL,
            completedStatus: OrderItemCatalog::STATUS_COMPLETED,
            cancelledStatus: OrderItemCatalog::STATUS_CANCELLED,
            cancelled: $this->isCancelled($item),
        );

        return $this->persistStatus($item, $status);
    }

    public function recalculateMany(iterable $items): void
    {
        foreach ($items as $item) {
            if (! $item instanceof OrderItem) {
                continue;
            }

            $this->recalculate($item);
        }
    }

    public function executedQuantity(OrderItem $item): float
    {
        $math = app(LineItemMath::class);
        $profileResolver = app(InventoryOperationProfileResolver::class);

        $item->loadMissing(['order']);

        $profile = $profileResolver->forOrder($item->order);

        $executedNet = $this->movementsForOrderItem($item)
            ->sum(fn ($movement) => $this->executionSignedQuantity($movement, $profile));

        return max(0, $math->normalizeQuantity($executedNet));
    }

    public function pendingQuantity(OrderItem $item): float
    {
        return app(LineItemMath::class)->pendingQuantity(
            $item->quantity,
            $this->executedQuantity($item),
        );
    }

    public function hasMovements(OrderItem $item): bool
    {
        return InventoryMovement::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM)
            ->where('origin_line_id', $item->id)
            ->exists();
    }

    protected function movementsForOrderItem(OrderItem $item)
    {
        return InventoryMovement::query()
            ->where('tenant_id', $item->tenant_id)
            ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM)
            ->where('origin_line_id', $item->id)
            ->get();
    }

    protected function executionSignedQuantity(object $movement, array $profile): float
    {
        $math = app(LineItemMath::class);

        $quantity = $math->normalizeQuantity($movement->quantity ?? 0);
        $kind = (string) ($movement->kind ?? '');

        if ($kind === (string) ($profile['execute_kind'] ?? '')) {
            return $quantity;
        }

        if ($kind === (string) ($profile['reverse_kind'] ?? '')) {
            return -1 * $quantity;
        }

        return 0.0;
    }

    protected function persistStatus(OrderItem $item, string $status): string
    {
        if ($item->status !== $status) {
            $item->forceFill([
                'status' => $status,
            ])->saveQuietly();
        }

        return $status;
    }

    protected function isCancelled(OrderItem $item): bool
    {
        return $item->status === OrderItemCatalog::STATUS_CANCELLED;
    }
}