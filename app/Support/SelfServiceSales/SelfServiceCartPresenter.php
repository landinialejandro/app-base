<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCartPresenter.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCart;
use App\Models\Tenant;

class SelfServiceCartPresenter
{
    public function present(SelfServiceCart $cart, Tenant $tenant, string $message = 'Carrito actualizado.'): array
    {
        $items = $cart->items->map(function ($item) use ($tenant) {
            $unitPrice = (float) $item->unit_price_snapshot;
            $subtotal = $unitPrice * (int) $item->quantity;

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
                'items' => $items,
                'total' => $total,
                'total_label' => $this->money($total),
            ],
        ];
    }

    public function empty(string $message): array
    {
        return [
            'ok' => false,
            'message' => $message,
            'cart' => [
                'id' => null,
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

    private function money(float|int $value): string
    {
        return '$ '.number_format((float) $value, 2, ',', '.');
    }
}
