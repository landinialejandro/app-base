<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCheckoutOrderBridge.php | V1

namespace App\Support\SelfServiceSales;

use App\Events\OperationalRecordCreated;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\SelfServiceCart;
use App\Models\SelfServiceCartItem;
use App\Support\Catalogs\OrderCatalog;
use App\Support\Numbering\RecordNumberGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SelfServiceCheckoutOrderBridge
{
    public function formalize(SelfServiceCart $cart): Order
    {
        return DB::transaction(function () use ($cart): Order {
            $cart = SelfServiceCart::query()
                ->whereKey($cart->id)
                ->lockForUpdate()
                ->firstOrFail();

            $meta = is_array($cart->meta) ? $cart->meta : [];
            $existingOrderId = data_get($meta, 'formalization.order_id');

            if ($existingOrderId) {
                return Order::query()
                    ->where('tenant_id', $cart->tenant_id)
                    ->whereKey($existingOrderId)
                    ->firstOrFail();
            }

            $cart->loadMissing(['items.product', 'items.shopItem', 'storeCustomer.party', 'account']);

            $this->assertFormalizable($cart);

            $party = $cart->storeCustomer->party;
            $shopId = $this->resolveShopId($cart);
            $sequenceDefinition = OrderCatalog::sequenceDefinitionForGroup(OrderCatalog::GROUP_SALE);
            $sequence = RecordNumberGenerator::generate(
                tenantId: (string) $cart->tenant_id,
                kind: $sequenceDefinition['kind'],
                defaultPrefix: $sequenceDefinition['prefix'],
                pointOfSale: '0001',
            );

            $order = Order::query()->create([
                'tenant_id' => $cart->tenant_id,
                'party_id' => $party->id,
                'counterparty_reference' => $party->name,
                'group' => OrderCatalog::GROUP_SALE,
                'kind' => OrderCatalog::KIND_STANDARD,
                'number' => $sequence['number'],
                'sequence_prefix' => $sequence['prefix'],
                'point_of_sale' => $sequence['point_of_sale'],
                'sequence_number' => $sequence['sequence_number'],
                'status' => OrderCatalog::STATUS_PENDING_APPROVAL,
                'ordered_at' => Carbon::now()->toDateString(),
                'record_metadata' => $this->orderMetadata($cart, $shopId),
                'created_by' => null,
                'updated_by' => null,
            ]);

            event(new OperationalRecordCreated(
                record: $order,
                actorUserId: null,
            ));

            foreach ($cart->items->values() as $index => $cartItem) {
                $this->createOrderItem($order, $cartItem, $index + 1);
            }

            $meta['formalization'] = [
                'status' => 'order_created',
                'order_id' => $order->id,
                'order_number' => $order->number,
                'shop_id' => $shopId,
                'created_at' => Carbon::now()->toIso8601String(),
            ];

            $cart->update([
                'meta' => $meta,
            ]);

            return $order->fresh(['items']);
        });
    }

    private function assertFormalizable(SelfServiceCart $cart): void
    {
        if ($cart->status !== SelfServiceCart::STATUS_CHECKED_OUT) {
            throw new InvalidArgumentException('El carrito debe estar checked_out para formalizarse como orden.');
        }

        if (! $cart->storeCustomer) {
            throw new InvalidArgumentException('El carrito debe tener cliente de tienda asociado.');
        }

        if (! $cart->storeCustomer->party_id) {
            throw new InvalidArgumentException('El cliente de tienda debe tener party_id para formalizar la orden.');
        }

        if (! $cart->storeCustomer->party instanceof Party) {
            throw new InvalidArgumentException('No se pudo resolver la contraparte del cliente de tienda.');
        }

        if ((string) $cart->storeCustomer->party->tenant_id !== (string) $cart->tenant_id) {
            throw new InvalidArgumentException('La contraparte debe pertenecer al mismo tenant del carrito.');
        }

        if ($cart->items->isEmpty()) {
            throw new InvalidArgumentException('El carrito debe tener al menos un ítem para formalizar la orden.');
        }

        foreach ($cart->items as $item) {
            if (! $item instanceof SelfServiceCartItem) {
                throw new InvalidArgumentException('Cada ítem del carrito debe ser una línea válida para formalizar la orden.');
            }

            if ((string) $item->tenant_id !== (string) $cart->tenant_id) {
                throw new InvalidArgumentException('Cada ítem del carrito debe pertenecer al mismo tenant.');
            }

            if (! $item->shopItem) {
                throw new InvalidArgumentException('Cada ítem del carrito debe tener artículo de tienda asociado.');
            }

            if ((string) $item->shopItem->tenant_id !== (string) $cart->tenant_id) {
                throw new InvalidArgumentException('Cada artículo de tienda debe pertenecer al mismo tenant.');
            }

            if (! $item->shopItem->self_service_shop_id) {
                throw new InvalidArgumentException('Cada artículo de tienda debe pertenecer a una tienda.');
            }

            if (trim((string) $item->display_name_snapshot) === '') {
                throw new InvalidArgumentException('Cada ítem del carrito debe tener descripción para formalizar la orden.');
            }
        }
    }

    private function resolveShopId(SelfServiceCart $cart): int
    {
        $shopIds = $cart->items
            ->map(fn (SelfServiceCartItem $item) => $item->shopItem?->self_service_shop_id)
            ->filter()
            ->unique()
            ->values();

        if ($shopIds->count() !== 1) {
            throw new InvalidArgumentException('El carrito debe pertenecer a una única tienda para formalizar la orden.');
        }

        return (int) $shopIds->first();
    }

    private function createOrderItem(Order $order, SelfServiceCartItem $cartItem, int $position): OrderItem
    {
        return OrderItem::query()->create([
            'tenant_id' => $order->tenant_id,
            'order_id' => $order->id,
            'product_id' => $cartItem->product_id,
            'position' => $position,
            'kind' => $cartItem->product?->kind ?: 'product',
            'description' => trim((string) $cartItem->display_name_snapshot),
            'quantity' => $cartItem->quantity,
            'status' => 'pending',
            'unit_price' => $cartItem->unit_price_snapshot,
        ]);
    }

    private function orderMetadata(SelfServiceCart $cart, int $shopId): array
    {
        return [
            'origin' => 'self_service_sales',
            'created_by' => [
                'type' => 'system',
                'source' => 'self_service_sales.checkout',
                'label' => 'Shopping Autoservicio',
            ],
            'self_service_sales' => [
                'cart_id' => $cart->id,
                'customer_account_id' => $cart->self_service_customer_account_id,
                'store_customer_id' => $cart->self_service_store_customer_id,
                'shop_id' => $shopId,
            ],
            'formalization' => [
                'status' => 'order_created',
                'stage' => 'logical_order',
            ],
        ];
    }
}
