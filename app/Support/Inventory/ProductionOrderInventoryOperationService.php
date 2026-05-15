<?php

// FILE: app/Support/Inventory/ProductionOrderInventoryOperationService.php | V1

namespace App\Support\Inventory;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductionOrderInventoryOperationService
{
    public function executeLine(
        Order $order,
        OrderItem $item,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $this->validateProductionOrder($order);
        $this->validateOrderItemRelation($order, $item);
        $this->validateOrderOperable($order);

        $item->loadMissing(['product']);
        $product = $this->resolvePhysicalProduct($item);
        $normalizedQuantity = $this->normalizeQuantity($quantity);

        if ($normalizedQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }

        $statusService = app(OrderItemStatusService::class);
        $pendingQuantity = $statusService->pendingQuantity($item);

        if ($pendingQuantity <= 0) {
            $statusService->recalculate($item);

            throw new InvalidArgumentException('La línea ya no tiene cantidad pendiente.');
        }

        if ($normalizedQuantity > $pendingQuantity) {
            throw new InvalidArgumentException('La cantidad supera el pendiente de la línea.');
        }

        $this->assertNoDoubleConsumptionRisk(
            order: $order,
            item: $item,
            product: $product,
            quantity: $normalizedQuantity,
        );

        return $this->runProductionMovement(
            order: $order,
            item: $item,
            product: $product,
            quantity: $normalizedQuantity,
            outputKind: InventoryMovementService::KIND_INGRESAR,
            componentKind: InventoryMovementService::KIND_CONSUMIR,
            operationType: InventoryOperationCatalog::TYPE_ORDER_LINE_EXECUTE,
            notes: $notes ?: 'Ejecución de producción.',
            componentNotesPrefix: 'Consumo de componente por producción.',
            createdBy: $createdBy,
        );
    }

    public function returnLineQuantity(
        Order $order,
        OrderItem $item,
        float|int|string $quantity,
        ?string $notes = null,
        int|string|null $createdBy = null,
    ): array {
        $this->validateProductionOrder($order);
        $this->validateOrderItemRelation($order, $item);
        $this->validateOrderOperable($order);

        $item->loadMissing(['product']);
        $product = $this->resolvePhysicalProduct($item);
        $normalizedQuantity = $this->normalizeQuantity($quantity);

        if ($normalizedQuantity <= 0) {
            throw new InvalidArgumentException('La cantidad a retirar debe ser mayor a cero.');
        }

        $statusService = app(OrderItemStatusService::class);
        $executedQuantity = $statusService->executedQuantity($item);

        if ($executedQuantity <= 0) {
            throw new InvalidArgumentException('La línea no tiene producción ingresada para retirar.');
        }

        if ($normalizedQuantity > $executedQuantity) {
            throw new InvalidArgumentException('La cantidad a retirar supera la producción ingresada neta de la línea.');
        }

        return $this->runProductionMovement(
            order: $order,
            item: $item,
            product: $product,
            quantity: $normalizedQuantity,
            outputKind: InventoryMovementService::KIND_ENTREGAR,
            componentKind: InventoryMovementService::KIND_INGRESAR,
            operationType: InventoryOperationCatalog::TYPE_ORDER_LINE_RETURN,
            notes: $notes ?: 'Retiro de producción ingresada.',
            componentNotesPrefix: 'Reversión de consumo de componente por retiro de producción.',
            createdBy: $createdBy,
        );
    }

    protected function runProductionMovement(
        Order $order,
        OrderItem $item,
        Product $product,
        float $quantity,
        string $outputKind,
        string $componentKind,
        string $operationType,
        string $notes,
        string $componentNotesPrefix,
        int|string|null $createdBy = null,
    ): array {
        $movementService = app(InventoryMovementService::class);
        $openOperationResolver = app(InventoryOpenOperationResolver::class);
        $statusService = app(OrderItemStatusService::class);

        return DB::transaction(function () use (
            $order,
            $item,
            $product,
            $quantity,
            $outputKind,
            $componentKind,
            $operationType,
            $notes,
            $componentNotesPrefix,
            $createdBy,
            $movementService,
            $openOperationResolver,
            $statusService,
        ) {
            $operation = $openOperationResolver->resolve(
                tenantId: $order->tenant_id,
                operationType: $operationType,
                originType: InventoryOriginCatalog::TYPE_ORDER,
                originId: $order->id,
                originLineType: InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM,
                originLineId: $item->id,
                notes: $notes,
                createdBy: $createdBy,
            );

            $outputResult = $movementService->createForOrderItem(
                order: $order,
                item: $item,
                product: $product,
                kind: $outputKind,
                quantity: $quantity,
                notes: $notes,
                createdBy: $createdBy,
                operation: $operation,
            );

            $componentResults = [];

            foreach ($this->physicalComponents($product) as $component) {
                $componentProduct = $component->componentProduct;

                if (! $componentProduct) {
                    continue;
                }

                $componentQuantity = $this->normalizeQuantity(
                    $quantity * (float) $component->quantity
                );

                if ($componentQuantity <= 0) {
                    continue;
                }

                $componentResults[] = $movementService->createForOrderProductionComponent(
                    order: $order,
                    item: $item,
                    componentProduct: $componentProduct,
                    kind: $componentKind,
                    quantity: $componentQuantity,
                    notes: $componentNotesPrefix.' Producto producido: '.$product->name,
                    createdBy: $createdBy,
                    operation: $operation,
                );
            }

            $item->refresh();
            $statusService->recalculate($item);

            $negativeStock = ($outputResult['negative_stock'] ?? false) === true;

            foreach ($componentResults as $componentResult) {
                if (($componentResult['negative_stock'] ?? false) === true) {
                    $negativeStock = true;
                    break;
                }
            }

            return [
                'operation' => $operation,
                'movement' => $outputResult['movement'] ?? null,
                'output_result' => $outputResult,
                'component_results' => $componentResults,
                'stock_after' => $outputResult['stock_after'] ?? null,
                'negative_stock' => $negativeStock,
                'owner_alert_task' => $outputResult['owner_alert_task'] ?? null,
            ];
        });
    }

    protected function physicalComponents(Product $product)
    {
        $product->loadMissing(['components.componentProduct']);

        return $product->components
            ->filter(fn ($component) => $component->componentProduct !== null)
            ->filter(fn ($component) => $component->componentProduct->kind === ProductCatalog::KIND_PRODUCT)
            ->values();
    }

    protected function assertNoDoubleConsumptionRisk(
        Order $order,
        OrderItem $item,
        Product $product,
        float $quantity,
    ): void {
        $risk = app(InventoryMaterialBalanceService::class)->doubleConsumptionRiskForProduction(
            order: $order,
            item: $item,
            producedProduct: $product,
            quantity: $quantity,
        );

        if (($risk['has_risk'] ?? false) !== true) {
            return;
        }

        $materials = collect($risk['materials'] ?? [])
            ->map(fn (array $row) => $row['product_name'] ?? ('Producto #'.($row['product_id'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->implode(', ');

        throw new InvalidArgumentException(
            'No se puede ejecutar producción instantánea porque existen entregas previas o saldos entregados pendientes de componentes'
            .($materials !== '' ? ': '.$materials : '.')
            .'. La orden requiere flujo formal por etapas o regularización material antes de volver a consumir desde depósito.'
        );
    }

    protected function validateProductionOrder(Order $order): void
    {
        if ($order->group !== OrderCatalog::GROUP_PRODUCTION) {
            throw new InvalidArgumentException('La orden no corresponde a producción.');
        }
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
            throw new InvalidArgumentException('La orden no está en estado operable para production inventory.');
        }
    }

    protected function resolvePhysicalProduct(OrderItem $item): Product
    {
        $product = $item->product;

        if (! $product) {
            throw new InvalidArgumentException('La línea no tiene producto asociado.');
        }

        if ($product->kind !== ProductCatalog::KIND_PRODUCT) {
            throw new InvalidArgumentException('La línea no corresponde a un producto físico producido.');
        }

        return $product;
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
