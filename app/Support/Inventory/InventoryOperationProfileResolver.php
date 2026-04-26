<?php

// FILE: app/Support/Inventory/InventoryOperationProfileResolver.php | V4

namespace App\Support\Inventory;

use App\Models\Order;
use App\Support\Catalogs\OrderCatalog;
use InvalidArgumentException;

class InventoryOperationProfileResolver
{
    public function forOrder(Order $order): array
    {
        return $this->forOrderType($order->group, $order->kind);
    }

    public function forOrderType(?string $group, ?string $kind): array
    {
        return match ($group) {
            OrderCatalog::GROUP_SERVICE => [
                'source_group' => $group,
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

            OrderCatalog::GROUP_SALE => [
                'source_group' => $group,
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

            OrderCatalog::GROUP_PURCHASE => [
                'source_group' => $group,
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

    public function forOrderKind(?string $kind): array
    {
        return $this->forOrderType($kind, OrderCatalog::KIND_STANDARD);
    }
}