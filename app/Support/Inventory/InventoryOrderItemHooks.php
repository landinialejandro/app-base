<?php

// FILE: app/Support/Inventory/InventoryOrderItemHooks.php | V3

namespace App\Support\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Catalogs\OrderItemCatalog;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InventoryOrderItemHooks
{
    /**
     * Regla funcional previa a editar línea.
     *
     * Puede bloquear la edición si el estado operativo de la línea ya no lo permite.
     */
    public function beforeOrderItemEdit(Order $order, OrderItem $item): void
    {
        if (in_array($item->status, [
            OrderItemCatalog::STATUS_COMPLETED,
            OrderItemCatalog::STATUS_CANCELLED,
        ], true)) {
            throw new HttpException(
                422,
                'La línea ya no puede editarse en su estado actual.'
            );
        }
    }

    /**
     * Regla funcional previa a eliminar línea.
     *
     * Por ahora, una línea con movimientos registrados no puede eliminarse.
     */
    public function beforeOrderItemDestroy(Order $order, OrderItem $item): void
    {
        if (app(OrderItemStatusService::class)->hasMovements($item)) {
            throw new HttpException(
                422,
                'La línea ya tiene movimientos registrados y no puede eliminarse.'
            );
        }
    }

    /**
     * Regla funcional previa al update de línea.
     *
     * Puede validar y eventualmente devolver un dataset ajustado.
     * Si necesita bloquear el flujo, debe lanzar HttpException 422.
     */
    public function beforeOrderItemUpdate(Order $order, OrderItem $item, array $data): array
    {
        $statusService = app(OrderItemStatusService::class);

        $hasInventoryMovements = $statusService->hasMovements($item);
        $executedQuantity = $statusService->executedQuantity($item);
        $newQuantity = $this->normalizeQuantity($data['quantity'] ?? null);

        if ($newQuantity < $executedQuantity) {
            throw new HttpException(
                422,
                'La cantidad no puede ser menor a lo ya ejecutado neto en la línea.'
            );
        }

        if ($hasInventoryMovements) {
            $currentProductId = $item->product_id ? (int) $item->product_id : null;
            $newProductId = ! empty($data['product_id']) ? (int) $data['product_id'] : null;

            if ($newProductId !== $currentProductId) {
                throw new HttpException(
                    422,
                    'No se puede cambiar el producto de una línea que ya tiene movimientos.'
                );
            }

            if ((string) ($data['kind'] ?? '') !== (string) $item->kind) {
                throw new HttpException(
                    422,
                    'No se puede cambiar el tipo de una línea que ya tiene movimientos.'
                );
            }

            if (trim((string) ($data['description'] ?? '')) !== trim((string) $item->description)) {
                throw new HttpException(
                    422,
                    'No se puede cambiar la descripción de una línea que ya tiene movimientos.'
                );
            }
        }

        return $data;
    }

    /**
     * Ajuste posterior al update de línea.
     *
     * Recalcula el estado operativo persistido del item según movimientos vigentes.
     */
    public function afterOrderItemUpdate(Order $order, OrderItem $item): void
    {
        app(OrderItemStatusService::class)->recalculate($item);
    }

    /**
     * Regla funcional de cierre de orden.
     *
     * Inventory informa si existen líneas físicas pendientes o parciales que impiden cerrar.
     */
    public function hasCloseBlockers(Order $order): bool
    {
        $order->loadMissing('items.product');

        return $order->items->contains(function ($item) {
            $product = $item->product;

            if (! $product || $product->kind !== 'product') {
                return false;
            }

            return ! in_array($item->status, [
                OrderItemCatalog::STATUS_COMPLETED,
                OrderItemCatalog::STATUS_CANCELLED,
            ], true);
        });
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}