<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCartPresenter.php | V3

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCart;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Tenant;

class SelfServiceCartPresenter
{
    public const ITEM_UNAVAILABLE_MESSAGE = 'Este producto ya no está disponible. Eliminalo del carrito para continuar.';

    public function present(SelfServiceCart $cart, Tenant $tenant, string $message = 'Carrito actualizado.'): array
    {
        $items = $cart->items->map(function ($item) use ($tenant) {
            $unitPrice = (float) $item->unit_price_snapshot;
            $subtotal = $unitPrice * (int) $item->quantity;
            $isAvailable = $this->itemStillAvailable($item, $tenant);

            return [
                'id' => $item->id,
                'shop_item_id' => $item->self_service_shop_item_id,
                'name' => $item->display_name_snapshot,
                'unit' => $item->unit_label_snapshot,
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'unit_price_label' => $this->money($unitPrice),
                'subtotal' => $subtotal,
                'subtotal_label' => $this->money($subtotal),
                'is_available' => $isAvailable,
                'availability_message' => $isAvailable ? null : self::ITEM_UNAVAILABLE_MESSAGE,
                'actions' => [
                    'update_url' => route('self_service_sales.cart.items.update', [
                        'tenant' => $tenant,
                        'cartItem' => $item,
                    ]),
                    'delete_url' => route('self_service_sales.cart.items.destroy', [
                        'tenant' => $tenant,
                        'cartItem' => $item,
                    ]),
                ],
            ];
        })->values();

        $total = $items->sum('subtotal');

        return [
            'ok' => true,
            'message' => $message,
            'cart' => [
                'id' => $cart->id,
                'status' => $cart->status,
                'items' => $items,
                'total' => $total,
                'total_label' => $this->money($total),
            ],
        ];
    }

    public function presentCheckout(
        SelfServiceCart $cart,
        Tenant $tenant,
        array $payment,
        string $message = 'Pago procesado.'
    ): array {
        $payload = $this->present($cart, $tenant, $message);

        $payload['payment'] = [
            'provider' => $payment['provider'] ?? null,
            'provider_target' => $payment['provider_target'] ?? null,
            'status' => $payment['status'] ?? null,
            'status_label' => $payment['status_label'] ?? null,
            'status_detail' => $payment['status_detail'] ?? null,
            'external_payment_id' => $payment['external_payment_id'] ?? null,
            'external_preference_id' => $payment['external_preference_id'] ?? null,
            'external_reference' => $payment['external_reference'] ?? null,
            'amount' => $payment['amount'] ?? null,
            'amount_label' => $this->money((float) ($payment['amount'] ?? 0)),
            'currency' => $payment['currency'] ?? 'ARS',
            'simulated' => (bool) ($payment['raw']['simulated'] ?? false),
        ];

        return $payload;
    }

    public function empty(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'cart' => [
                'id' => null,
                'status' => null,
                'items' => [],
                'total' => 0,
                'total_label' => $this->money(0),
            ],
        ];
    }

    public function error(string $message, ?SelfServiceCart $cart = null, ?Tenant $tenant = null): array
    {
        if ($cart && $tenant) {
            $payload = $this->present($cart, $tenant, $message);
            $payload['ok'] = false;

            return $payload;
        }

        return $this->empty($message);
    }

    private function itemStillAvailable($item, Tenant $tenant): bool
    {
        return ShopItem::query()
            ->where('tenant_id', $tenant->id)
            ->whereKey($item->self_service_shop_item_id)
            ->where('status', ShopItem::STATUS_PUBLISHED)
            ->where('is_visible', true)
            ->whereHas('shop', function ($query) use ($tenant) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('status', Shop::STATUS_ACTIVE);
            })
            ->whereHas('product', function ($query) use ($tenant, $item) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->whereKey($item->product_id);
            })
            ->exists();
    }

    private function money(float|int $value): string
    {
        return '$ '.number_format((float) $value, 2, ',', '.');
    }
}