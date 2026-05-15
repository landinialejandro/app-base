<?php

// FILE: app/Support/Inventory/InventoryMaterialFlowService.php | V1

namespace App\Support\Inventory;

use App\Models\InventoryMaterialFlow;
use App\Models\InventoryOperation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryMaterialFlowService
{
    public const TYPE_FORMAL_DELIVERY = 'formal_delivery';

    public const TYPE_FORMAL_APPLICATION = 'formal_application';

    public const TYPE_FORMAL_RETURN = 'formal_return';

    public static function types(): array
    {
        return [
            self::TYPE_FORMAL_DELIVERY,
            self::TYPE_FORMAL_APPLICATION,
            self::TYPE_FORMAL_RETURN,
        ];
    }

    public function deliverToOrderItem(
        Order $order,
        OrderItem $item,
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $this->validateOrderItemProductContext($order, $item, $product);
        $this->validateOperableOrder($order);
        $this->validateQuantity($normalizedQuantity);

        return DB::transaction(function () use ($order, $item, $product, $normalizedQuantity, $notes, $createdBy, $operation) {
            $movementResult = $this->createPhysicalOrderMovement(
                order: $order,
                item: $item,
                product: $product,
                kind: InventoryMovementService::KIND_ENTREGAR,
                quantity: $normalizedQuantity,
                notes: $notes ?: 'Entrega formal de material.',
                createdBy: $createdBy,
                operation: $operation,
            );

            $flow = $this->createFlow(
                order: $order,
                item: $item,
                product: $product,
                flowType: self::TYPE_FORMAL_DELIVERY,
                quantity: $normalizedQuantity,
                notes: $notes,
                createdBy: $createdBy,
                movementId: $movementResult['movement']?->id,
            );

            return [
                'flow' => $flow,
                'movement_result' => $movementResult,
            ];
        });
    }

    public function applyToOrderItem(
        Order $order,
        OrderItem $item,
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $this->validateOrderItemProductContext($order, $item, $product);
        $this->validateOperableOrder($order);
        $this->validateQuantity($normalizedQuantity);

        $available = $this->availableQuantity($order, $item, $product);

        if ($normalizedQuantity > $available) {
            throw new InvalidArgumentException('La aplicación supera el saldo entregado disponible.');
        }

        $flow = $this->createFlow(
            order: $order,
            item: $item,
            product: $product,
            flowType: self::TYPE_FORMAL_APPLICATION,
            quantity: $normalizedQuantity,
            notes: $notes,
            createdBy: $createdBy,
            movementId: null,
        );

        return [
            'flow' => $flow,
            'movement_result' => null,
        ];
    }

    public function returnFromOrderItem(
        Order $order,
        OrderItem $item,
        Product $product,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        $normalizedQuantity = $this->normalizeQuantity($quantity);
        $this->validateOrderItemProductContext($order, $item, $product);
        $this->validateOperableOrder($order);
        $this->validateQuantity($normalizedQuantity);

        $available = $this->availableQuantity($order, $item, $product);

        if ($normalizedQuantity > $available) {
            throw new InvalidArgumentException('La devolución supera el saldo entregado disponible.');
        }

        return DB::transaction(function () use ($order, $item, $product, $normalizedQuantity, $notes, $createdBy, $operation) {
            $movementResult = $this->createPhysicalOrderMovement(
                order: $order,
                item: $item,
                product: $product,
                kind: InventoryMovementService::KIND_INGRESAR,
                quantity: $normalizedQuantity,
                notes: $notes ?: 'Devolución formal de material entregado.',
                createdBy: $createdBy,
                operation: $operation,
            );

            $flow = $this->createFlow(
                order: $order,
                item: $item,
                product: $product,
                flowType: self::TYPE_FORMAL_RETURN,
                quantity: $normalizedQuantity,
                notes: $notes,
                createdBy: $createdBy,
                movementId: $movementResult['movement']?->id,
            );

            return [
                'flow' => $flow,
                'movement_result' => $movementResult,
            ];
        });
    }

    public function availableQuantity(Order $order, OrderItem $item, Product $product): float
    {
        $this->validateOrderItemProductContext($order, $item, $product);

        $delivered = $this->sumFlow($order, $item, $product, self::TYPE_FORMAL_DELIVERY);
        $applied = $this->sumFlow($order, $item, $product, self::TYPE_FORMAL_APPLICATION);
        $returned = $this->sumFlow($order, $item, $product, self::TYPE_FORMAL_RETURN);

        return max(0.0, $this->normalizeQuantity($delivered - $applied - $returned));
    }

    protected function createPhysicalOrderMovement(
        Order $order,
        OrderItem $item,
        Product $product,
        string $kind,
        float $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
        ?InventoryOperation $operation = null,
    ): array {
        $movementService = app(InventoryMovementService::class);
        $operation ??= $this->resolveOperationForMovement(
            order: $order,
            item: $item,
            kind: $kind,
            notes: $notes,
            createdBy: $createdBy,
        );

        if ((int) $item->product_id === (int) $product->id) {
            return $movementService->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $kind,
                quantity: $quantity,
                notes: $notes,
                createdBy: $createdBy,
                operation: $operation,
            );
        }

        if ($order->group === OrderCatalog::GROUP_PRODUCTION) {
            return $movementService->createForOrderProductionComponent(
                order: $order,
                item: $item,
                componentProduct: $product,
                kind: $kind,
                quantity: $quantity,
                notes: $notes,
                createdBy: $createdBy,
                operation: $operation,
            );
        }

        throw new InvalidArgumentException('El material formal no corresponde a la línea indicada.');
    }

    protected function resolveOperationForMovement(
        Order $order,
        OrderItem $item,
        string $kind,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): InventoryOperation {
        $operationType = match ($kind) {
            InventoryMovementService::KIND_ENTREGAR => InventoryOperationCatalog::TYPE_ORDER_LINE_EXECUTE,
            InventoryMovementService::KIND_INGRESAR => InventoryOperationCatalog::TYPE_ORDER_LINE_RETURN,
            default => throw new InvalidArgumentException('El tipo de movimiento no corresponde a un flujo material formal.'),
        };

        return app(InventoryOpenOperationResolver::class)->resolve(
            tenantId: $order->tenant_id,
            operationType: $operationType,
            originType: InventoryOriginCatalog::TYPE_ORDER,
            originId: $order->id,
            originLineType: InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM,
            originLineId: $item->id,
            notes: $notes,
            createdBy: $createdBy,
        );
    }

    protected function createFlow(
        Order $order,
        OrderItem $item,
        Product $product,
        string $flowType,
        float $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
        int|string|null $movementId = null,
    ): InventoryMaterialFlow {
        if (! in_array($flowType, self::types(), true)) {
            throw new InvalidArgumentException('Tipo de flujo material inválido.');
        }

        return InventoryMaterialFlow::create([
            'tenant_id' => $order->tenant_id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'inventory_movement_id' => $movementId,
            'flow_type' => $flowType,
            'quantity' => $quantity,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }

    protected function sumFlow(Order $order, OrderItem $item, Product $product, string $flowType): float
    {
        return $this->normalizeQuantity(
            InventoryMaterialFlow::query()
                ->where('tenant_id', $order->tenant_id)
                ->where('order_id', $order->id)
                ->where('order_item_id', $item->id)
                ->where('product_id', $product->id)
                ->where('flow_type', $flowType)
                ->sum('quantity')
        );
    }

    protected function validateOrderItemProductContext(Order $order, OrderItem $item, Product $product): void
    {
        if ((int) $item->order_id !== (int) $order->id) {
            throw new InvalidArgumentException('La línea no pertenece a la orden indicada.');
        }

        if ((string) $item->tenant_id !== (string) $order->tenant_id) {
            throw new InvalidArgumentException('La línea pertenece a otro tenant.');
        }

        if ((string) $product->tenant_id !== (string) $order->tenant_id) {
            throw new InvalidArgumentException('El producto pertenece a otro tenant.');
        }

        if ($product->kind !== ProductCatalog::KIND_PRODUCT) {
            throw new InvalidArgumentException('El flujo material solo admite productos físicos stockeables.');
        }

        if ((int) $item->product_id === (int) $product->id) {
            return;
        }

        if ($order->group !== OrderCatalog::GROUP_PRODUCTION) {
            throw new InvalidArgumentException('El material formal no corresponde a la línea indicada.');
        }

        $item->loadMissing([
            'product.components.componentProduct',
        ]);

        $components = $item->product?->components ?? collect();

        $isPhysicalComponent = $components->contains(function ($component) use ($order, $product) {
            return (string) $component->tenant_id === (string) $order->tenant_id
                && (int) $component->component_product_id === (int) $product->id
                && $component->componentProduct?->kind === ProductCatalog::KIND_PRODUCT;
        }) === true;

        if (! $isPhysicalComponent) {
            throw new InvalidArgumentException('El material formal no corresponde a un componente físico de la línea de producción.');
        }
    }

    protected function validateOperableOrder(Order $order): void
    {
        if (! OrderCatalog::isOperableStatus($order->status)) {
            throw new InvalidArgumentException('La orden no está en estado operable para material inventory.');
        }
    }

    protected function validateQuantity(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
