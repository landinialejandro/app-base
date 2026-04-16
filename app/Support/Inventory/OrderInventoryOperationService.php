<?php

// FILE: app/Support/Inventory/OrderInventoryOperationService.php | V1

namespace App\Support\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderInventoryOperationService
{
    public function executeLine(
        Order $order,
        OrderItem $item,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $this->validateOrderItemRelation($order, $item);
        $this->validateOrderOperable($order);

        $item->loadMissing(['product', 'inventoryMovements']);

        $product = $this->resolvePhysicalProduct($item);
        $normalizedQuantity = $this->normalizeQuantity($quantity);

        if ($normalizedQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $pendingQuantity = app(OrderItemStatusService::class)->pendingQuantity($item);

        if ($pendingQuantity <= 0) {
            throw new InvalidArgumentException('La línea ya no tiene cantidad pendiente.');
        }

        if ($normalizedQuantity > $pendingQuantity) {
            throw new InvalidArgumentException('La cantidad supera el pendiente de la línea.');
        }

        $movementKind = $this->movementKindForOrder($order);

        return DB::transaction(function () use (
            $order,
            $item,
            $product,
            $movementKind,
            $normalizedQuantity,
            $notes,
            $createdBy
        ) {
            $result = app(InventoryMovementService::class)->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $movementKind,
                quantity: $normalizedQuantity,
                notes: $notes,
                createdBy: $createdBy,
            );

            $item->refresh();
            app(OrderItemStatusService::class)->recalculate($item);

            return $result;
        });
    }

    public function reverseLineMovement(
        Order $order,
        OrderItem $item,
        int $movementId,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $this->validateOrderItemRelation($order, $item);

        $item->loadMissing(['product', 'inventoryMovements']);
        $product = $this->resolvePhysicalProduct($item);

        $movement = $item->inventoryMovements
            ->first(fn ($candidate) => (int) $candidate->id === $movementId);

        if (! $movement) {
            throw new InvalidArgumentException('El movimiento indicado no pertenece a la línea.');
        }

        if ($movement->trashed()) {
            throw new InvalidArgumentException('No se puede revertir un movimiento eliminado.');
        }

        $reverseKind = $this->reverseKind($movement->kind);

        return DB::transaction(function () use (
            $order,
            $item,
            $product,
            $movement,
            $reverseKind,
            $notes,
            $createdBy
        ) {
            $result = app(InventoryMovementService::class)->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $reverseKind,
                quantity: (float) $movement->quantity,
                notes: $notes ?: 'Contramovimiento automático por reversión.',
                createdBy: $createdBy,
            );

            $item->refresh();
            app(OrderItemStatusService::class)->recalculate($item);

            return $result;
        });
    }

    protected function validateOrderItemRelation(Order $order, OrderItem $item): void
    {
        if ((int) $item->order_id !== (int) $order->id) {
            throw new InvalidArgumentException('La línea no pertenece a la orden indicada.');
        }

        if ($item->tenant_id !== $order->tenant_id) {
            throw new InvalidArgumentException('La línea pertenece a otro tenant.');
        }
    }

    protected function validateOrderOperable(Order $order): void
    {
        if ($order->status !== OrderCatalog::STATUS_APPROVED) {
            throw new InvalidArgumentException('La orden no está en estado operable para inventory.');
        }
    }

    protected function resolvePhysicalProduct(OrderItem $item): Product
    {
        $product = $item->product;

        if (! $product) {
            throw new InvalidArgumentException('La línea no tiene producto asociado.');
        }

        if ($product->kind !== ProductCatalog::KIND_PRODUCT) {
            throw new InvalidArgumentException('La línea no corresponde a un producto físico stockeable.');
        }

        return $product;
    }

    protected function movementKindForOrder(Order $order): string
    {
        return match ($order->kind) {
            OrderCatalog::KIND_PURCHASE => InventoryMovementService::KIND_INGRESAR,
            OrderCatalog::KIND_SALE,
            OrderCatalog::KIND_SERVICE => InventoryMovementService::KIND_ENTREGAR,
            default => throw new InvalidArgumentException('Tipo de orden no compatible con inventory.'),
        };
    }

    protected function reverseKind(string $kind): string
    {
        return match ($kind) {
            InventoryMovementService::KIND_INGRESAR => InventoryMovementService::KIND_ENTREGAR,
            InventoryMovementService::KIND_ENTREGAR,
            InventoryMovementService::KIND_CONSUMIR => InventoryMovementService::KIND_INGRESAR,
            default => throw new InvalidArgumentException('Tipo de movimiento no reversible.'),
        };
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
