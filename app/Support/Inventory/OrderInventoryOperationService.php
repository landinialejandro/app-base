<?php

// FILE: app/Support/Inventory/OrderInventoryOperationService.php | V2

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

    public function returnLineQuantity(
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
            throw new InvalidArgumentException('La cantidad a devolver debe ser mayor a cero.');
        }

        $executedQuantity = app(OrderItemStatusService::class)->executedQuantity($item);

        if ($executedQuantity <= 0) {
            throw new InvalidArgumentException('La línea no tiene cantidad ejecutada para devolver.');
        }

        if ($normalizedQuantity > $executedQuantity) {
            throw new InvalidArgumentException('La cantidad a devolver supera lo ejecutado neto de la línea.');
        }

        $returnKind = $this->returnKindForOrder($order);

        return DB::transaction(function () use (
            $order,
            $item,
            $product,
            $returnKind,
            $normalizedQuantity,
            $notes,
            $createdBy
        ) {
            $result = app(InventoryMovementService::class)->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $returnKind,
                quantity: $normalizedQuantity,
                notes: $notes ?: 'Devolución registrada sobre la línea.',
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

    protected function returnKindForOrder(Order $order): string
    {
        return match ($order->kind) {
            OrderCatalog::KIND_PURCHASE => InventoryMovementService::KIND_ENTREGAR,
            OrderCatalog::KIND_SALE,
            OrderCatalog::KIND_SERVICE => InventoryMovementService::KIND_INGRESAR,
            default => throw new InvalidArgumentException('Tipo de orden no compatible con devoluciones de inventory.'),
        };
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
