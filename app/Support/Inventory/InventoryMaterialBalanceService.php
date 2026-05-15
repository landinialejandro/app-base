<?php

// FILE: app/Support/Inventory/InventoryMaterialBalanceService.php | V1

namespace App\Support\Inventory;

use App\Models\InventoryMaterialFlow;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class InventoryMaterialBalanceService
{
    public const CONSISTENCY_NO_MOVEMENTS = 'no_movements';

    public const CONSISTENCY_AMBIGUOUS = 'ambiguous';

    public const CONSISTENCY_NOT_FORMALIZABLE = 'not_formalizable';

    public const CONSISTENCY_FORMAL = 'formal';

    public function forOrder(Order $order): array
    {
        $order->loadMissing(['items.product.components.componentProduct']);

        $items = $order->items
            ->sortBy('position')
            ->values()
            ->map(fn (OrderItem $item) => $this->forOrderItem($order, $item));

        return [
            'order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'is_reliable' => $items->every(fn (array $item) => ($item['is_reliable'] ?? false) === true),
            'has_ambiguous_balances' => $items->contains(fn (array $item) => ($item['has_ambiguous_balances'] ?? false) === true),
            'has_double_consumption_risk' => $items->contains(fn (array $item) => ($item['has_double_consumption_risk'] ?? false) === true),
            'items' => $items->all(),
        ];
    }

    public function forOrderItem(Order $order, OrderItem $item): array
    {
        $this->validateOrderItemContext($order, $item);

        $item->loadMissing(['product.components.componentProduct']);

        $rows = $this->materialRowsForItem($order, $item);

        return [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'tenant_id' => $order->tenant_id,
            'is_reliable' => $rows->every(fn (array $row) => ($row['is_reliable'] ?? false) === true),
            'has_ambiguous_balances' => $rows->contains(fn (array $row) => ($row['is_ambiguous'] ?? false) === true),
            'has_double_consumption_risk' => $rows->contains(fn (array $row) => ($row['has_double_consumption_risk'] ?? false) === true),
            'materials' => $rows->all(),
        ];
    }

    public function productionComponentBalances(Order $order, OrderItem $item, Product $producedProduct, ?float $quantity = null): array
    {
        $this->validateOrderItemContext($order, $item);

        $producedProduct->loadMissing(['components.componentProduct']);

        $rows = $this->physicalProductionComponents($producedProduct)
            ->map(function ($component) use ($order, $item, $quantity) {
                $componentProduct = $component->componentProduct;
                $requiredQuantity = $this->normalizeQuantity(
                    ($quantity ?? (float) $item->quantity) * (float) $component->quantity
                );

                return $this->materialBalanceRow(
                    order: $order,
                    item: $item,
                    product: $componentProduct,
                    requiredQuantity: $requiredQuantity,
                    materialRole: 'production_component',
                );
            })
            ->values();

        return [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $producedProduct->id,
            'quantity' => $quantity,
            'is_reliable' => $rows->every(fn (array $row) => ($row['is_reliable'] ?? false) === true),
            'has_ambiguous_balances' => $rows->contains(fn (array $row) => ($row['is_ambiguous'] ?? false) === true),
            'has_double_consumption_risk' => $rows->contains(fn (array $row) => ($row['has_double_consumption_risk'] ?? false) === true),
            'materials' => $rows->all(),
        ];
    }

    public function doubleConsumptionRiskForProduction(Order $order, OrderItem $item, Product $producedProduct, float $quantity): array
    {
        $balances = $this->productionComponentBalances($order, $item, $producedProduct, $quantity);

        $riskyRows = collect($balances['materials'] ?? [])
            ->filter(fn (array $row) => ($row['has_double_consumption_risk'] ?? false) === true)
            ->values();

        return [
            'has_risk' => $riskyRows->isNotEmpty(),
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $producedProduct->id,
            'materials' => $riskyRows->all(),
            'warnings' => $riskyRows
                ->flatMap(fn (array $row) => $row['warnings'] ?? [])
                ->unique()
                ->values()
                ->all(),
        ];
    }

    protected function materialRowsForItem(Order $order, OrderItem $item): Collection
    {
        $product = $item->product;

        if (! $product || $product->kind !== ProductCatalog::KIND_PRODUCT) {
            return collect();
        }

        if ($order->group === OrderCatalog::GROUP_PRODUCTION) {
            return $this->physicalProductionComponents($product)
                ->map(function ($component) use ($order, $item) {
                    return $this->materialBalanceRow(
                        order: $order,
                        item: $item,
                        product: $component->componentProduct,
                        requiredQuantity: $this->normalizeQuantity((float) $item->quantity * (float) $component->quantity),
                        materialRole: 'production_component',
                    );
                })
                ->values();
        }

        return collect([
            $this->materialBalanceRow(
                order: $order,
                item: $item,
                product: $product,
                requiredQuantity: $this->normalizeQuantity((float) $item->quantity),
                materialRole: 'order_line_product',
            ),
        ]);
    }

    protected function validateOrderItemContext(Order $order, OrderItem $item): void
    {
        if ((int) $item->order_id !== (int) $order->id) {
            throw new InvalidArgumentException('La línea no pertenece a la orden indicada para el balance material.');
        }

        if ((string) $item->tenant_id !== (string) $order->tenant_id) {
            throw new InvalidArgumentException('La línea pertenece a otro tenant para el balance material.');
        }
    }

    protected function materialBalanceRow(
        Order $order,
        OrderItem $item,
        Product $product,
        float $requiredQuantity,
        string $materialRole,
    ): array {
        $movements = $this->movementsForOrderItemProduct($order, $item, $product);
        $flows = $this->flowsForOrderItemProduct($order, $item, $product);

        if ($flows->isNotEmpty()) {
            return $this->formalMaterialBalanceRow(
                order: $order,
                item: $item,
                product: $product,
                requiredQuantity: $requiredQuantity,
                materialRole: $materialRole,
                flows: $flows,
                movements: $movements,
            );
        }

        $delivered = $this->sumKind($movements, InventoryMovementService::KIND_ENTREGAR);
        $applied = $this->sumKind($movements, InventoryMovementService::KIND_CONSUMIR);
        $returned = $this->sumKind($movements, InventoryMovementService::KIND_INGRESAR);
        $available = $this->normalizeQuantity($delivered - $applied - $returned);
        $missing = max(0.0, $this->normalizeQuantity($requiredQuantity - $delivered));

        $warnings = [];

        if ($delivered > 0) {
            $warnings[] = 'entrega_no_clasificada';
        }

        if ($applied > 0 && $delivered <= 0) {
            $warnings[] = 'aplicacion_no_trazable';
        }

        if ($returned > 0) {
            $warnings[] = 'devolucion_no_trazable';
        }

        if ($available < 0) {
            $warnings[] = 'saldo_no_formalizable';
        }

        $hasMovements = $movements->isNotEmpty();
        $hasDoubleConsumptionRisk = $delivered > 0;
        $isAmbiguous = $hasMovements;

        if ($hasDoubleConsumptionRisk) {
            $warnings[] = 'riesgo_doble_consumo';
        }

        return [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'material_role' => $materialRole,
            'required' => $requiredQuantity,
            'delivered' => $delivered,
            'applied' => $applied,
            'returned' => $returned,
            'available' => $available,
            'missing' => $missing,
            'movement_count' => $movements->count(),
            'movement_ids' => $movements->pluck('id')->all(),
            'is_reliable' => ! $isAmbiguous,
            'is_ambiguous' => $isAmbiguous,
            'has_double_consumption_risk' => $hasDoubleConsumptionRisk,
            'consistency_status' => $hasMovements
                ? ($available < 0 ? self::CONSISTENCY_NOT_FORMALIZABLE : self::CONSISTENCY_AMBIGUOUS)
                : self::CONSISTENCY_NO_MOVEMENTS,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    protected function formalMaterialBalanceRow(
        Order $order,
        OrderItem $item,
        Product $product,
        float $requiredQuantity,
        string $materialRole,
        Collection $flows,
        Collection $movements,
    ): array {
        $delivered = $this->sumFlow($flows, InventoryMaterialFlowService::TYPE_FORMAL_DELIVERY);
        $applied = $this->sumFlow($flows, InventoryMaterialFlowService::TYPE_FORMAL_APPLICATION);
        $returned = $this->sumFlow($flows, InventoryMaterialFlowService::TYPE_FORMAL_RETURN);
        $available = $this->normalizeQuantity($delivered - $applied - $returned);
        $missing = max(0.0, $this->normalizeQuantity($requiredQuantity - $delivered));

        $classifiedMovementIds = $flows
            ->pluck('inventory_movement_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $unclassifiedMovements = $movements
            ->reject(fn (InventoryMovement $movement) => $classifiedMovementIds->contains((int) $movement->id))
            ->values();

        $warnings = [];

        if ($available < 0) {
            $warnings[] = 'saldo_no_formalizable';
        }

        if ($unclassifiedMovements->isNotEmpty()) {
            $warnings[] = 'movimiento_no_clasificado';
        }

        if ($available > 0) {
            $warnings[] = 'saldo_entregado_pendiente';
        }

        return [
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'material_role' => $materialRole,
            'required' => $requiredQuantity,
            'delivered' => $delivered,
            'applied' => $applied,
            'returned' => $returned,
            'available' => $available,
            'missing' => $missing,
            'movement_count' => $movements->count(),
            'movement_ids' => $movements->pluck('id')->all(),
            'flow_count' => $flows->count(),
            'flow_ids' => $flows->pluck('id')->all(),
            'is_reliable' => $unclassifiedMovements->isEmpty() && $available >= 0,
            'is_ambiguous' => $unclassifiedMovements->isNotEmpty(),
            'has_double_consumption_risk' => $available > 0,
            'consistency_status' => $available < 0
                ? self::CONSISTENCY_NOT_FORMALIZABLE
                : self::CONSISTENCY_FORMAL,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    protected function movementsForOrderItemProduct(Order $order, OrderItem $item, Product $product): Collection
    {
        return InventoryMovement::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('origin_type', InventoryOriginCatalog::TYPE_ORDER)
            ->where('origin_id', $order->id)
            ->where('origin_line_type', InventoryOriginCatalog::LINE_TYPE_ORDER_ITEM)
            ->where('origin_line_id', $item->id)
            ->where('product_id', $product->id)
            ->with('operation')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    protected function flowsForOrderItemProduct(Order $order, OrderItem $item, Product $product): Collection
    {
        return InventoryMaterialFlow::query()
            ->where('tenant_id', $order->tenant_id)
            ->where('order_id', $order->id)
            ->where('order_item_id', $item->id)
            ->where('product_id', $product->id)
            ->with('movement')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    protected function physicalProductionComponents(Product $product): Collection
    {
        $product->loadMissing(['components.componentProduct']);

        return $product->components
            ->filter(fn ($component) => $component->componentProduct !== null)
            ->filter(fn ($component) => $component->componentProduct->kind === ProductCatalog::KIND_PRODUCT)
            ->values();
    }

    protected function sumKind(Collection $movements, string $kind): float
    {
        return $this->normalizeQuantity(
            $movements
                ->where('kind', $kind)
                ->sum(fn (InventoryMovement $movement) => (float) $movement->quantity)
        );
    }

    protected function sumFlow(Collection $flows, string $flowType): float
    {
        return $this->normalizeQuantity(
            $flows
                ->where('flow_type', $flowType)
                ->sum(fn (InventoryMaterialFlow $flow) => (float) $flow->quantity)
        );
    }

    protected function normalizeQuantity(float|int|string|null $value): float
    {
        return round((float) ($value ?? 0), 2);
    }
}
