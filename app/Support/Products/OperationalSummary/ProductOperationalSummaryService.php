<?php

// FILE: app/Support/Products/OperationalSummary/ProductOperationalSummaryService.php | V1

namespace App\Support\Products\OperationalSummary;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Catalogs\ProductCatalog;
use App\Support\Inventory\InventoryMovementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class ProductOperationalSummaryService
{
    public function forProduct(Product $product, User $user, array $trailQuery = []): array
    {
        $access = app(ProductOperationalSummaryAccess::class)->resolve($user);

        if (! ($access['can_view'] ?? false)) {
            return [
                'can_view' => false,
                'access' => $access,
            ];
        }

        return [
            'can_view' => true,
            'access' => $access,
            'price' => [
                'current' => $product->price !== null ? (float) $product->price : null,
                'unit_label' => $product->unit_label,
            ],
            'purchases' => ($access['can_view_purchases'] ?? false)
                ? $this->purchaseSummary($product, $user, $trailQuery)
                : ['can_view' => false],
            'sales' => ($access['can_view_sales'] ?? false)
                ? $this->salesSummary($product, $user, $trailQuery)
                : ['can_view' => false],
            'inventory' => ($access['can_view_inventory'] ?? false)
                ? $this->inventorySummary($product, $user, $trailQuery)
                : ['can_view' => false],
        ];
    }

    protected function purchaseSummary(Product $product, User $user, array $trailQuery): array
    {
        $query = $this->orderItemsForGroup($product, $user, OrderCatalog::GROUP_PURCHASE);
        $aggregate = $this->aggregateLineQuery($query);
        $lastLine = $this->lastLine($query);

        return [
            'can_view' => true,
            ...$aggregate,
            'last' => $lastLine
                ? $this->linePayload($lastLine, $user, $trailQuery)
                : null,
            'previous_supplier' => $this->previousSupplierPayload($query, $lastLine, $user, $trailQuery),
        ];
    }

    protected function salesSummary(Product $product, User $user, array $trailQuery): array
    {
        $query = $this->orderItemsForGroup($product, $user, OrderCatalog::GROUP_SALE);
        $aggregate = $this->aggregateLineQuery($query);
        $lastLine = $this->lastLine($query);

        return [
            'can_view' => true,
            ...$aggregate,
            'last' => $lastLine
                ? $this->linePayload($lastLine, $user, $trailQuery)
                : null,
        ];
    }

    protected function inventorySummary(Product $product, User $user, array $trailQuery): array
    {
        if ($product->kind !== ProductCatalog::KIND_PRODUCT) {
            return [
                'can_view' => true,
                'applies' => false,
                'movements_count' => 0,
                'entries_total' => 0.0,
                'exits_total' => 0.0,
                'last_movement' => null,
                'inventory_url' => null,
            ];
        }

        $query = InventoryMovement::query()
            ->where('product_id', $product->id);

        $lastMovement = (clone $query)
            ->with('operation')
            ->latest('created_at')
            ->latest('id')
            ->first();

        return [
            'can_view' => true,
            'applies' => true,
            'movements_count' => (clone $query)->count(),
            'entries_total' => (float) (clone $query)
                ->where('kind', InventoryMovementService::KIND_INGRESAR)
                ->sum('quantity'),
            'exits_total' => (float) (clone $query)
                ->whereIn('kind', [
                    InventoryMovementService::KIND_CONSUMIR,
                    InventoryMovementService::KIND_ENTREGAR,
                ])
                ->sum('quantity'),
            'last_movement' => $lastMovement
                ? $this->movementPayload($lastMovement)
                : null,
            'inventory_url' => $this->inventoryUrl($product, $user, $trailQuery),
        ];
    }

    protected function orderItemsForGroup(Product $product, User $user, string $group): Builder
    {
        $orders = app(Security::class)
            ->scope($user, 'orders.viewAny', Order::query())
            ->where('group', $group)
            ->select('orders.id');

        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereIn('order_id', $orders);
    }

    protected function aggregateLineQuery(Builder $query): array
    {
        $row = (clone $query)
            ->selectRaw('
                COALESCE(SUM(quantity), 0) as quantity_total,
                COALESCE(SUM(quantity * unit_price), 0) as amount_total,
                MIN(unit_price) as unit_price_min,
                MAX(unit_price) as unit_price_max
            ')
            ->first();

        $quantityTotal = (float) ($row->quantity_total ?? 0);
        $amountTotal = (float) ($row->amount_total ?? 0);

        return [
            'quantity_total' => $quantityTotal,
            'amount_total' => $amountTotal,
            'unit_price_avg' => $quantityTotal > 0
                ? round($amountTotal / $quantityTotal, 2)
                : null,
            'unit_price_min' => $row->unit_price_min !== null ? (float) $row->unit_price_min : null,
            'unit_price_max' => $row->unit_price_max !== null ? (float) $row->unit_price_max : null,
        ];
    }

    protected function lastLine(Builder $query): ?OrderItem
    {
        return (clone $query)
            ->with(['order.party'])
            ->orderByDesc(
                Order::query()
                    ->select('ordered_at')
                    ->whereColumn('orders.id', 'order_items.order_id')
                    ->limit(1)
            )
            ->latest('id')
            ->first();
    }

    protected function previousSupplierPayload(
        Builder $query,
        ?OrderItem $lastLine,
        User $user,
        array $trailQuery
    ): ?array {
        if (! $lastLine || ! $lastLine->order?->party_id) {
            return null;
        }

        $lastPartyId = (string) $lastLine->order->party_id;

        $previousLine = (clone $query)
            ->with(['order.party'])
            ->orderByDesc(
                Order::query()
                    ->select('ordered_at')
                    ->whereColumn('orders.id', 'order_items.order_id')
                    ->limit(1)
            )
            ->latest('id')
            ->limit(30)
            ->get()
            ->first(function (OrderItem $item) use ($lastPartyId) {
                return $item->order?->party_id !== null
                    && (string) $item->order->party_id !== $lastPartyId;
            });

        return $previousLine
            ? $this->linePayload($previousLine, $user, $trailQuery)
            : null;
    }

    protected function linePayload(OrderItem $item, User $user, array $trailQuery): array
    {
        $order = $item->order;
        $party = $order?->party;

        return [
            'date' => $order?->ordered_at ?? $order?->created_at,
            'quantity' => (float) $item->quantity,
            'unit_price' => $item->unit_price !== null ? (float) $item->unit_price : null,
            'order_number' => $order?->number,
            'order_url' => $this->orderUrl($order, $user, $trailQuery),
            'party_id' => $party?->id,
            'party_name' => $party?->display_name ?: $party?->name,
        ];
    }

    protected function movementPayload(InventoryMovement $movement): array
    {
        return [
            'date' => $movement->created_at,
            'kind' => $movement->kind,
            'quantity' => (float) $movement->quantity,
            'operation_type' => $movement->operation?->operation_type,
        ];
    }

    protected function orderUrl(?Order $order, User $user, array $trailQuery): ?string
    {
        if (! $order || ! Route::has('orders.show')) {
            return null;
        }

        try {
            if (! $user->can('view', $order)) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return route('orders.show', ['order' => $order] + $trailQuery);
    }

    protected function inventoryUrl(Product $product, User $user, array $trailQuery): ?string
    {
        if (! Route::has('inventory.show')) {
            return null;
        }

        try {
            if (! app(Security::class)->allows($user, 'inventory.view', $product)) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        return route('inventory.show', ['product' => $product] + $trailQuery);
    }
}