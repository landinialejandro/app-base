<?php

// FILE: app/Support/Orders/OrdersHooks.php | V8

namespace App\Support\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Inventory\InventoryOrderItemHooks;
use App\Support\Inventory\InventoryOriginCatalog;
use App\Support\Inventory\InventoryTraceabilityService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class OrdersHooks
{
    public function beforeOrderItemEdit(Order $order, OrderItem $item): void
    {
        try {
            $this->inventoryHooks()->beforeOrderItemEdit($order, $item);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('OrdersHooks.beforeOrderItemEdit falló y no interrumpió el flujo principal.', [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function beforeOrderItemDestroy(Order $order, OrderItem $item): void
    {
        try {
            $this->inventoryHooks()->beforeOrderItemDestroy($order, $item);
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('OrdersHooks.beforeOrderItemDestroy falló y no interrumpió el flujo principal.', [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function beforeOrderItemUpdate(Order $order, OrderItem $item, array $data): array
    {
        try {
            $result = $this->inventoryHooks()->beforeOrderItemUpdate($order, $item, $data);

            return is_array($result) ? $result : $data;
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('OrdersHooks.beforeOrderItemUpdate falló y se aplicó fallback al dataset original.', [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);

            return $data;
        }
    }

    public function afterOrderItemUpdate(Order $order, OrderItem $item): void
    {
        try {
            $this->inventoryHooks()->afterOrderItemUpdate($order, $item);
        } catch (Throwable $e) {
            Log::warning('OrdersHooks.afterOrderItemUpdate falló y no interrumpió el flujo principal.', [
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function hasExternalMovements(Order $order): bool
    {
        try {
            return app(InventoryTraceabilityService::class)
                ->hasMovementsForOrigin(
                    tenantId: (string) $order->tenant_id,
                    originType: InventoryOriginCatalog::TYPE_ORDER,
                    originId: $order->id,
                );
        } catch (Throwable $e) {
            Log::warning('OrdersHooks.hasExternalMovements falló y se asumió que existen movimientos.', [
                'order_id' => $order->id,
                'message' => $e->getMessage(),
            ]);

            return true;
        }
    }

    protected function inventoryHooks(): InventoryOrderItemHooks
    {
        return app(InventoryOrderItemHooks::class);
    }
}