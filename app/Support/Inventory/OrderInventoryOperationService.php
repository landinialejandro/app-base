<?php

// FILE: app/Support/Inventory/OrderInventoryOperationService.php | V4
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
        $statusService = app(OrderItemStatusService::class);
        $profileResolver = app(InventoryOperationProfileResolver::class);
        $movementService = app(InventoryMovementService::class);

        $this->validateOrderItemRelation($order, $item);
        $this->validateOrderOperable($order);

        $item->loadMissing(['product', 'inventoryMovements']);

        $product = $this->resolvePhysicalProduct($item);
        $normalizedQuantity = $this->normalizeQuantity($quantity);

        if ($normalizedQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $pendingQuantity = $statusService->pendingQuantity($item);

        if ($pendingQuantity <= 0) {
            throw new InvalidArgumentException('La línea ya no tiene cantidad pendiente.');
        }

        if ($normalizedQuantity > $pendingQuantity) {
            throw new InvalidArgumentException('La cantidad supera el pendiente de la línea.');
        }

        $profile = $profileResolver->forOrder($order);

        return DB::transaction(function () use (
            $order,
            $item,
            $product,
            $profile,
            $normalizedQuantity,
            $notes,
            $createdBy,
            $movementService,
            $statusService
        ) {
            $result = $movementService->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $profile['execute_kind'],
                quantity: $normalizedQuantity,
                notes: $notes,
                createdBy: $createdBy,
            );

            $item->refresh();
            $statusService->recalculate($item);

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
        $statusService = app(OrderItemStatusService::class);
        $profileResolver = app(InventoryOperationProfileResolver::class);
        $movementService = app(InventoryMovementService::class);

        $this->validateOrderItemRelation($order, $item);
        $this->validateOrderOperable($order);

        $item->loadMissing(['product', 'inventoryMovements']);

        $product = $this->resolvePhysicalProduct($item);
        $normalizedQuantity = $this->normalizeQuantity($quantity);

        if ($normalizedQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad a devolver debe ser mayor a cero.');
        }

        $executedQuantity = $statusService->executedQuantity($item);

        if ($executedQuantity <= 0) {
            throw new InvalidArgumentException('La línea no tiene cantidad ejecutada para devolver.');
        }

        if ($normalizedQuantity > $executedQuantity) {
            throw new InvalidArgumentException('La cantidad a devolver supera lo ejecutado neto de la línea.');
        }

        $profile = $profileResolver->forOrder($order);

        return DB::transaction(function () use (
            $order,
            $item,
            $product,
            $profile,
            $normalizedQuantity,
            $notes,
            $createdBy,
            $movementService,
            $statusService
        ) {
            $result = $movementService->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $profile['reverse_kind'],
                quantity: $normalizedQuantity,
                notes: $notes ?: 'Contramovimiento registrado sobre la línea.',
                createdBy: $createdBy,
            );

            $item->refresh();
            $statusService->recalculate($item);

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
        if (! OrderCatalog::isOperableStatus($order->status)) {
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

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}