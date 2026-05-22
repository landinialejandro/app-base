<?php

// FILE: app/Support/SelfServiceSales/Payments/SelfServicePaymentRequestFactory.php | V1

namespace App\Support\SelfServiceSales\Payments;

use App\Models\SelfServiceCart;
use App\Models\Tenant;

class SelfServicePaymentRequestFactory
{
    public function make(Tenant $tenant, SelfServiceCart $cart): array
    {
        $cart->loadMissing(['items', 'account', 'storeCustomer']);

        $items = $cart->items
            ->map(function ($item): array {
                $unitPrice = (float) $item->unit_price_snapshot;
                $quantity = (int) $item->quantity;

                return [
                    'id' => (string) $item->id,
                    'shop_item_id' => (int) $item->self_service_shop_item_id,
                    'product_id' => (int) $item->product_id,
                    'title' => (string) $item->display_name_snapshot,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'currency' => 'ARS',
                ];
            })
            ->values()
            ->all();

        $amount = round(collect($items)->sum(
            fn (array $item): float => ((float) $item['unit_price']) * ((int) $item['quantity'])
        ), 2);

        $account = $cart->account;
        $storeCustomer = $cart->storeCustomer;

        return [
            'provider' => 'simulated',
            'provider_target' => 'mercado_pago',
            'operation' => 'self_service_checkout',
            'internal_reference' => 'SSCART-'.$cart->id,
            'external_reference' => 'tenant:'.$tenant->id.':cart:'.$cart->id,
            'idempotency_key' => $this->idempotencyKey($tenant, $cart, $amount, $items),
            'currency' => 'ARS',
            'amount' => $amount,
            'payer' => [
                'account_id' => $account?->id,
                'store_customer_id' => $storeCustomer?->id,
                'email' => $account?->email,
                'name' => $account?->display_name,
                'document_type' => null,
                'document_number' => null,
                'phone' => $account?->phone,
            ],
            'items' => $items,
            'callbacks' => [
                'success_url' => null,
                'failure_url' => null,
                'pending_url' => null,
                'webhook_url' => null,
            ],
            'security' => [
                'mode' => 'server_side',
                'source' => 'self_service_sales',
                'tenant_id' => $tenant->id,
                'cart_id' => $cart->id,
            ],
        ];
    }

    private function idempotencyKey(Tenant $tenant, SelfServiceCart $cart, float $amount, array $items): string
    {
        return hash('sha256', json_encode([
            'tenant_id' => $tenant->id,
            'cart_id' => $cart->id,
            'status' => $cart->status,
            'amount' => $amount,
            'items' => $items,
        ], JSON_THROW_ON_ERROR));
    }
}