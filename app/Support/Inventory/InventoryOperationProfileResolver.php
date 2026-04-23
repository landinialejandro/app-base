<?php

// FILE: app/Support/Inventory/InventoryOperationProfileResolver.php | V3

namespace App\Support\Inventory;

use App\Models\Order;
use App\Support\Catalogs\OrderCatalog;
use InvalidArgumentException;

class InventoryOperationProfileResolver
{
    public function forOrder(Order $order): array
    {
        return $this->forOrderKind($order->kind);
    }

    public function forOrderKind(?string $kind): array
    {
        return match ($kind) {
            OrderCatalog::KIND_SERVICE => [
                'source_kind' => $kind,
                'direction' => 'out',
                'execute_kind' => InventoryMovementService::KIND_ENTREGAR,
                'reverse_kind' => InventoryMovementService::KIND_INGRESAR,
                'execute_label' => 'Surtir',
                'reverse_label' => 'Devolver',
                'execute_title' => 'Surtir línea',
                'reverse_title' => 'Devolver línea',
                'execute_icon' => 'truck',
                'reverse_icon' => 'rotate-ccw',
                'execute_action_key' => 'execute',
                'reverse_action_key' => 'return',
            ],

            OrderCatalog::KIND_SALE => [
                'source_kind' => $kind,
                'direction' => 'out',
                'execute_kind' => InventoryMovementService::KIND_ENTREGAR,
                'reverse_kind' => InventoryMovementService::KIND_INGRESAR,
                'execute_label' => 'Entregar',
                'reverse_label' => 'Devolver',
                'execute_title' => 'Entregar línea',
                'reverse_title' => 'Devolver línea',
                'execute_icon' => 'truck',
                'reverse_icon' => 'rotate-ccw',
                'execute_action_key' => 'execute',
                'reverse_action_key' => 'return',
            ],

            OrderCatalog::KIND_PURCHASE => [
                'source_kind' => $kind,
                'direction' => 'in',
                'execute_kind' => InventoryMovementService::KIND_INGRESAR,
                'reverse_kind' => InventoryMovementService::KIND_ENTREGAR,
                'execute_label' => 'Recibir',
                'reverse_label' => 'Retirar',
                'execute_title' => 'Recibir línea',
                'reverse_title' => 'Retirar línea',
                'execute_icon' => 'plus',
                'reverse_icon' => 'rotate-ccw',
                'execute_action_key' => 'execute',
                'reverse_action_key' => 'return',
            ],

            default => throw new InvalidArgumentException('Tipo de orden no compatible con inventory.'),
        };
    }
}