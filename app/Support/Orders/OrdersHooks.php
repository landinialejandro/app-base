<?php

// FILE: app/Support/Orders/OrdersHooks.php | V7

namespace App\Support\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Inventory\InventoryOrderItemHooks;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class OrdersHooks
{
    /**
     * BEFORE HOOK — ORDER ITEM EDIT
     *
     * Estación contractual blanda del módulo orders.
     *
     * Este archivo no debe contener lógica grande ni semántica externa.
     * Solo debe delegar a piezas externas preparadas para intervenir
     * el flujo del módulo sin contaminar el núcleo de orders.
     *
     * Reglas:
     * - si una pieza externa necesita bloquear por regla funcional,
     *   debe lanzar HttpException 422
     * - si la pieza externa falla por error técnico,
     *   se registra warning y el flujo sigue
     */
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

    /**
     * BEFORE HOOK — ORDER ITEM DESTROY
     *
     * Estación contractual blanda del módulo orders.
     * Delegación a pieza externa opcional.
     */
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

    /**
     * BEFORE HOOK — ORDER ITEM UPDATE
     *
     * Recibe dataset de orders, delega a pieza externa y devuelve
     * el dataset resultante.
     *
     * Reglas:
     * - si la pieza externa devuelve array válido, se usa
     * - si devuelve algo inválido, se hace fallback al dataset original
     * - si lanza HttpException 422 funcional, se respeta
     * - si falla por error técnico, se registra warning y se sigue
     *   con el dataset original
     */
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

    /**
     * AFTER HOOK — ORDER ITEM UPDATE
     *
     * Delegación a pieza externa opcional posterior al update.
     * Si falla, no rompe el flujo principal.
     */
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

    /**
     * Punto único de acceso a la pieza externa de inventory.
     *
     * Si mañana inventory cambia de implementación, o se reemplaza
     * por otra pieza, el ajuste debe hacerse aquí y no en el resto
     * del módulo orders.
     */
    protected function inventoryHooks(): InventoryOrderItemHooks
    {
        return app(InventoryOrderItemHooks::class);
    }
}
