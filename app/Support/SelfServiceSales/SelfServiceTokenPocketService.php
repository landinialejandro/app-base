<?php

// FILE: app/Support/SelfServiceSales/SelfServiceTokenPocketService.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\SelfServiceStoreCustomer;
use App\Models\SelfServiceCart;
use App\Models\SelfServiceCartItem;
use App\Models\SelfServiceTokenPocket;
use App\Models\SelfServiceTokenPocketMovement;
use App\Support\Catalogs\ProductCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SelfServiceTokenPocketService
{
    public function summaryForExternalCustomer(string $tenantId, int $accountId, int $storeCustomerId): array
    {
        return SelfServiceTokenPocket::query()
            ->with('product')
            ->where('tenant_id', $tenantId)
            ->where('self_service_customer_account_id', $accountId)
            ->where('self_service_store_customer_id', $storeCustomerId)
            ->where('status', SelfServiceTokenPocket::STATUS_ACTIVE)
            ->orderBy('product_id')
            ->get()
            ->map(function (SelfServiceTokenPocket $pocket): array {
                $quantity = $this->normalizeNumber((float) $pocket->quantity_available);
                $unitSeconds = $pocket->unit_seconds_snapshot;
                $totalSeconds = $unitSeconds !== null
                    ? $this->normalizeNumber((float) $pocket->quantity_available * (int) $unitSeconds)
                    : null;
                $totalMinutes = $totalSeconds !== null
                    ? $this->normalizeNumber((float) $totalSeconds / 60)
                    : null;

                return [
                    'pocket_id' => $pocket->id,
                    'product_id' => $pocket->product_id,
                    'sku' => $pocket->product?->sku,
                    'name' => $pocket->product?->name ?: 'Ficha',
                    'quantity_available' => $quantity,
                    'unit_label' => $pocket->unit_label_snapshot,
                    'unit_seconds' => $unitSeconds,
                    'total_seconds' => $totalSeconds,
                    'total_minutes' => $totalMinutes,
                    'summary_label' => $this->summaryLabel(
                        quantity: $quantity,
                        unitLabel: $pocket->unit_label_snapshot,
                        totalMinutes: $totalMinutes,
                    ),
                ];
            })
            ->values()
            ->all();
    }

    public function creditFromCheckout(SelfServiceCart $cart, Order $order): array
    {
        return DB::transaction(function () use ($cart, $order): array {
            $cart = SelfServiceCart::query()
                ->whereKey($cart->id)
                ->with([
                    'items.product.components.componentProduct',
                    'storeCustomer.party',
                    'account',
                ])
                ->lockForUpdate()
                ->firstOrFail();

            $order->loadMissing('items');

            $this->assertCreditable($cart, $order);

            $movements = [];
            $skipped = [];

            foreach ($cart->items as $cartItem) {
                if (! $cartItem instanceof SelfServiceCartItem || ! $cartItem->product instanceof Product) {
                    continue;
                }

                $tokenDefinition = $this->tokenDefinitionForProduct($cartItem->product);

                if ($tokenDefinition === null) {
                    $skipped[] = [
                        'cart_item_id' => $cartItem->id,
                        'product_id' => $cartItem->product_id,
                    ];

                    continue;
                }

                $movements[] = $this->creditCartItem(
                    cart: $cart,
                    order: $order,
                    cartItem: $cartItem,
                    tokenDefinition: $tokenDefinition,
                );
            }

            return [
                'credited_count' => count($movements),
                'skipped_count' => count($skipped),
                'movement_ids' => collect($movements)->pluck('id')->values()->all(),
                'skipped' => $skipped,
            ];
        });
    }

    public function creditFromInventoryOrderItem(
        Order $order,
        OrderItem $item,
        float|int|string|null $quantity = null,
        ?int $createdBy = null
    ): ?SelfServiceTokenPocketMovement
    {
        return DB::transaction(function () use ($order, $item, $quantity, $createdBy): ?SelfServiceTokenPocketMovement {
            $order->loadMissing('party');
            $item->loadMissing('product');

            $this->assertInventoryOrderItemCreditable($order, $item);

            $product = $item->product;

            if (! $product instanceof Product || $product->kind !== ProductCatalog::KIND_INTANGIBLE) {
                return null;
            }

            $tokenDefinition = $this->tokenDefinitionForProduct($product);

            if ($tokenDefinition === null) {
                return null;
            }

            $quantity = round((float) ($quantity ?? $item->quantity), 2);

            if ($quantity <= 0) {
                throw new InvalidArgumentException('La cantidad de la línea debe ser mayor a cero para acreditar fichas.');
            }

            $existingMovement = SelfServiceTokenPocketMovement::query()
                ->where('movement_type', SelfServiceTokenPocketMovement::TYPE_PURCHASE_CREDIT)
                ->where('order_item_id', $item->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existingMovement) {
                return $existingMovement;
            }

            $idempotencyKey = $this->inventoryOrderItemIdempotencyKey($item, $product);
            $existingMovement = SelfServiceTokenPocketMovement::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingMovement) {
                return $existingMovement;
            }

            $storeCustomer = SelfServiceStoreCustomer::query()
                ->where('tenant_id', $order->tenant_id)
                ->where('party_id', $order->party_id)
                ->first();

            $pocket = SelfServiceTokenPocket::query()->firstOrCreate(
                [
                    'tenant_id' => $order->tenant_id,
                    'party_id' => $order->party_id,
                    'product_id' => $product->id,
                ],
                [
                    'self_service_customer_account_id' => $storeCustomer?->self_service_customer_account_id,
                    'self_service_store_customer_id' => $storeCustomer?->id,
                    'quantity_available' => 0,
                    'unit_label_snapshot' => $tokenDefinition['unit_label_snapshot'],
                    'unit_seconds_snapshot' => $tokenDefinition['unit_seconds_snapshot'],
                    'status' => SelfServiceTokenPocket::STATUS_ACTIVE,
                ],
            );

            $pocket = SelfServiceTokenPocket::query()
                ->whereKey($pocket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingMovement = SelfServiceTokenPocketMovement::query()
                ->where('movement_type', SelfServiceTokenPocketMovement::TYPE_PURCHASE_CREDIT)
                ->where('order_item_id', $item->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existingMovement) {
                return $existingMovement;
            }

            $existingMovement = SelfServiceTokenPocketMovement::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existingMovement) {
                return $existingMovement;
            }

            $balanceAfter = (float) $pocket->quantity_available + $quantity;

            $pocket->update([
                'self_service_customer_account_id' => $pocket->self_service_customer_account_id ?: $storeCustomer?->self_service_customer_account_id,
                'self_service_store_customer_id' => $pocket->self_service_store_customer_id ?: $storeCustomer?->id,
                'quantity_available' => $balanceAfter,
                'unit_label_snapshot' => $tokenDefinition['unit_label_snapshot'],
                'unit_seconds_snapshot' => $tokenDefinition['unit_seconds_snapshot'],
                'status' => SelfServiceTokenPocket::STATUS_ACTIVE,
            ]);

            return SelfServiceTokenPocketMovement::query()->create([
                'tenant_id' => $order->tenant_id,
                'self_service_token_pocket_id' => $pocket->id,
                'self_service_cart_id' => null,
                'self_service_cart_item_id' => null,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'product_id' => $product->id,
                'movement_type' => SelfServiceTokenPocketMovement::TYPE_PURCHASE_CREDIT,
                'quantity' => $quantity,
                'balance_after' => $balanceAfter,
                'unit_label_snapshot' => $tokenDefinition['unit_label_snapshot'],
                'unit_seconds_snapshot' => $tokenDefinition['unit_seconds_snapshot'],
                'source_type' => 'inventory.order_item_execution',
                'source_id' => $item->id,
                'idempotency_key' => $idempotencyKey,
                'notes' => 'Acreditación por ejecución de línea intangible desde Inventory.',
                'meta' => [
                    'order_number' => $order->number,
                    'created_by' => $createdBy,
                ],
            ]);
        });
    }

    private function assertCreditable(SelfServiceCart $cart, Order $order): void
    {
        if ($cart->status !== SelfServiceCart::STATUS_CHECKED_OUT) {
            throw new InvalidArgumentException('El carrito debe estar checked_out para acreditar fichas.');
        }

        if ((string) $cart->tenant_id !== (string) $order->tenant_id) {
            throw new InvalidArgumentException('La orden debe pertenecer al mismo tenant del carrito.');
        }

        if (! $cart->self_service_customer_account_id || ! $cart->self_service_store_customer_id) {
            throw new InvalidArgumentException('El carrito debe tener customer externo para acreditar fichas.');
        }

        if (! $cart->storeCustomer || ! $cart->storeCustomer->party_id) {
            throw new InvalidArgumentException('El customer externo debe tener Party para acreditar fichas.');
        }
    }

    private function assertInventoryOrderItemCreditable(Order $order, OrderItem $item): void
    {
        if ((int) $item->order_id !== (int) $order->id) {
            throw new InvalidArgumentException('La línea no pertenece a la orden indicada.');
        }

        if ((string) $item->tenant_id !== (string) $order->tenant_id) {
            throw new InvalidArgumentException('La línea pertenece a otro tenant.');
        }

        if (! $order->party_id) {
            throw new InvalidArgumentException('La orden debe tener Party para acreditar fichas.');
        }

        if (! $item->product instanceof Product) {
            throw new InvalidArgumentException('La línea debe tener producto para acreditar fichas.');
        }

        if (! $item->product->isStockable()) {
            throw new InvalidArgumentException('El producto intangible debe ser stockeable para acreditar fichas.');
        }
    }

    private function creditCartItem(
        SelfServiceCart $cart,
        Order $order,
        SelfServiceCartItem $cartItem,
        array $tokenDefinition,
    ): SelfServiceTokenPocketMovement {
        $idempotencyKey = $this->idempotencyKey($cartItem);
        $existingMovement = SelfServiceTokenPocketMovement::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingMovement) {
            return $existingMovement;
        }

        $pocket = SelfServiceTokenPocket::query()->firstOrCreate(
            [
                'tenant_id' => $cart->tenant_id,
                'party_id' => $cart->storeCustomer->party_id,
                'product_id' => $cartItem->product_id,
            ],
            [
                'self_service_customer_account_id' => $cart->self_service_customer_account_id,
                'self_service_store_customer_id' => $cart->self_service_store_customer_id,
                'quantity_available' => 0,
                'unit_label_snapshot' => $tokenDefinition['unit_label_snapshot'],
                'unit_seconds_snapshot' => $tokenDefinition['unit_seconds_snapshot'],
                'status' => SelfServiceTokenPocket::STATUS_ACTIVE,
            ],
        );

        $pocket = SelfServiceTokenPocket::query()
            ->whereKey($pocket->id)
            ->lockForUpdate()
            ->firstOrFail();

        $existingMovement = SelfServiceTokenPocketMovement::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existingMovement) {
            return $existingMovement;
        }

        $quantity = (float) $cartItem->quantity;
        $balanceAfter = (float) $pocket->quantity_available + $quantity;
        $orderItem = $this->matchingOrderItem($order->items, $cartItem);

        $pocket->update([
            'quantity_available' => $balanceAfter,
            'unit_label_snapshot' => $tokenDefinition['unit_label_snapshot'],
            'unit_seconds_snapshot' => $tokenDefinition['unit_seconds_snapshot'],
            'status' => SelfServiceTokenPocket::STATUS_ACTIVE,
        ]);

        return SelfServiceTokenPocketMovement::query()->create([
            'tenant_id' => $cart->tenant_id,
            'self_service_token_pocket_id' => $pocket->id,
            'self_service_cart_id' => $cart->id,
            'self_service_cart_item_id' => $cartItem->id,
            'order_id' => $order->id,
            'order_item_id' => $orderItem?->id,
            'product_id' => $cartItem->product_id,
            'movement_type' => SelfServiceTokenPocketMovement::TYPE_PURCHASE_CREDIT,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'unit_label_snapshot' => $tokenDefinition['unit_label_snapshot'],
            'unit_seconds_snapshot' => $tokenDefinition['unit_seconds_snapshot'],
            'source_type' => 'self_service_sales.checkout',
            'source_id' => $cart->id,
            'idempotency_key' => $idempotencyKey,
            'notes' => 'Acreditación por checkout aprobado de Shopping Autoservicio.',
            'meta' => [
                'order_number' => $order->number,
            ],
        ]);
    }

    private function tokenDefinitionForProduct(Product $product): ?array
    {
        if ((string) $product->unit_label !== 'ficha') {
            return null;
        }

        $unitSeconds = $product->components
            ->filter(function (ProductComponent $component): bool {
                $componentProduct = $component->componentProduct;

                return $componentProduct instanceof Product
                    && $componentProduct->kind === ProductCatalog::KIND_SERVICE
                    && $componentProduct->unit_label === 'segundo';
            })
            ->sum(fn (ProductComponent $component): float => (float) $component->quantity);

        if ($unitSeconds <= 0) {
            return null;
        }

        return [
            'unit_label_snapshot' => 'ficha',
            'unit_seconds_snapshot' => (int) round($unitSeconds),
        ];
    }

    private function matchingOrderItem(Collection $orderItems, SelfServiceCartItem $cartItem): ?OrderItem
    {
        return $orderItems->first(function (OrderItem $item) use ($cartItem): bool {
            return (int) $item->product_id === (int) $cartItem->product_id
                && trim((string) $item->description) === trim((string) $cartItem->display_name_snapshot);
        });
    }

    private function idempotencyKey(SelfServiceCartItem $cartItem): string
    {
        return sprintf(
            'self_service_token_pocket:purchase_credit:cart:%s:item:%s:product:%s',
            $cartItem->self_service_cart_id,
            $cartItem->id,
            $cartItem->product_id,
        );
    }

    private function inventoryOrderItemIdempotencyKey(OrderItem $item, Product $product): string
    {
        return sprintf(
            'self_service_token_pocket:purchase_credit:order_item:%s:product:%s',
            $item->id,
            $product->id,
        );
    }

    private function summaryLabel(int|float $quantity, ?string $unitLabel, int|float|null $totalMinutes): string
    {
        $unit = $unitLabel ?: 'ficha';
        $availableLabel = $quantity === 1 ? 'disponible' : 'disponibles';
        $label = sprintf('%s %s %s', $this->formatNumber($quantity), $this->pluralizeUnit($unit, $quantity), $availableLabel);

        if ($totalMinutes !== null) {
            $label .= sprintf(' · %s min', $this->formatNumber($totalMinutes));
        }

        return $label;
    }

    private function normalizeNumber(float $value): int|float
    {
        return floor($value) === $value ? (int) $value : $value;
    }

    private function formatNumber(int|float $value): string
    {
        if (is_int($value) || floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');
    }

    private function pluralizeUnit(string $unitLabel, int|float $quantity): string
    {
        if ($quantity === 1) {
            return $unitLabel;
        }

        return str_ends_with($unitLabel, 's') ? $unitLabel : $unitLabel.'s';
    }
}
