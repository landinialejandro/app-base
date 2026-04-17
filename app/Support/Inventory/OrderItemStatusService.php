<?php

// FILE: app/Support/Inventory/OrderItemStatusService.php | V3

namespace App\Support\Inventory;

use App\Models\OrderItem;
use App\Support\Catalogs\OrderItemCatalog;

class OrderItemStatusService
{
    public function recalculate(OrderItem $item): string
    {
        if ($this->isCancelled($item)) {
            return $this->persistStatus($item, OrderItemCatalog::STATUS_CANCELLED);
        }

        $orderedQuantity = $this->normalizeQuantity($item->quantity);
        $executedQuantity = $this->executedQuantity($item);

        if ($executedQuantity <= 0) {
            return $this->persistStatus($item, OrderItemCatalog::STATUS_PENDING);
        }

        if ($executedQuantity < $orderedQuantity) {
            return $this->persistStatus($item, OrderItemCatalog::STATUS_PARTIAL);
        }

        return $this->persistStatus($item, OrderItemCatalog::STATUS_COMPLETED);
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
        $item->loadMissing('inventoryMovements');

        $executedNet = $item->inventoryMovements
            ->filter(fn ($movement) => $movement->trashed() === false)
            ->sum(function ($movement) {
                return $this->executionSignedQuantity($movement);
            });

        return max(0, $this->normalizeQuantity($executedNet));
    }

    public function pendingQuantity(OrderItem $item): float
    {
        $orderedQuantity = $this->normalizeQuantity($item->quantity);
        $executedQuantity = $this->executedQuantity($item);

        return max(0, $this->normalizeQuantity($orderedQuantity - $executedQuantity));
    }

    protected function executionSignedQuantity(object $movement): float
    {
        $quantity = $this->normalizeQuantity($movement->quantity ?? 0);
        $kind = (string) ($movement->kind ?? '');

        return match ($kind) {
            InventoryMovementService::KIND_ENTREGAR,
            InventoryMovementService::KIND_CONSUMIR => $quantity,

            InventoryMovementService::KIND_INGRESAR => -1 * $quantity,

            default => 0.0,
        };
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

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
