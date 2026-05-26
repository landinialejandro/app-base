<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCheckoutService.php | V4

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCart;
use App\Models\SelfServiceCartItem;
use App\Models\Shop;
use App\Models\ShopItem;
use App\Models\Tenant;
use App\Support\SelfServiceSales\Payments\SelfServicePaymentGateway;
use App\Support\SelfServiceSales\Payments\SelfServicePaymentRequestFactory;
use App\Support\Shops\ShopItemCommercialPolicyResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SelfServiceCheckoutService
{
    public const MESSAGE_CHECKOUT_DISABLED = 'El checkout de esta tienda no está disponible en este momento.';

    public function __construct(
        protected SelfServiceCartService $carts,
        protected SelfServicePaymentRequestFactory $paymentRequests,
        protected SelfServicePaymentGateway $gateway
    ) {
    }

    public function checkout(Request $request, Tenant $tenant): array
    {
        return DB::transaction(function () use ($request, $tenant): array {
            $cart = $this->carts->checkoutableCart($request, $tenant);
            $this->ensureCheckoutEnabled($cart, $tenant);

            $paymentRequest = $this->paymentRequests->make($tenant, $cart);
            $paymentResponse = $this->gateway->process($paymentRequest);

            $status = (string) ($paymentResponse['status'] ?? '');
            $approved = $status === 'approved';

            $meta = is_array($cart->meta) ? $cart->meta : [];

            $attempt = [
                'processed_at' => now()->toIso8601String(),
                'provider' => $paymentResponse['provider'] ?? null,
                'provider_target' => $paymentResponse['provider_target'] ?? null,
                'status' => $paymentResponse['status'] ?? null,
                'status_detail' => $paymentResponse['status_detail'] ?? null,
                'external_payment_id' => $paymentResponse['external_payment_id'] ?? null,
                'external_reference' => $paymentResponse['external_reference'] ?? null,
                'amount' => $paymentResponse['amount'] ?? null,
                'currency' => $paymentResponse['currency'] ?? null,
            ];

            $meta['payment_attempts'] = array_values([
                ...($meta['payment_attempts'] ?? []),
                $attempt,
            ]);

            $meta['payment_request'] = $paymentRequest;
            $meta['payment_response'] = $paymentResponse;

            if ($approved) {
                $meta['checkout'] = [
                    'status' => 'checked_out',
                    'checked_out_at' => now()->toIso8601String(),
                    'operation' => 'self_service_checkout',
                    'provider' => $paymentResponse['provider'] ?? null,
                    'provider_target' => $paymentResponse['provider_target'] ?? null,
                    'external_payment_id' => $paymentResponse['external_payment_id'] ?? null,
                    'external_reference' => $paymentResponse['external_reference'] ?? null,
                    'amount' => $paymentResponse['amount'] ?? null,
                    'currency' => $paymentResponse['currency'] ?? null,
                ];

                $cart->update([
                    'status' => SelfServiceCart::STATUS_CHECKED_OUT,
                    'meta' => $meta,
                ]);

                return [
                    'ok' => true,
                    'cart' => $cart->fresh(['items']),
                    'payment' => $paymentResponse,
                    'message' => 'Pago aprobado en entorno simulado.',
                ];
            }

            $cart->update([
                'meta' => $meta,
            ]);

            return [
                'ok' => false,
                'cart' => $cart->fresh(['items']),
                'payment' => $paymentResponse,
                'message' => 'El pago no fue aprobado.',
            ];
        });
    }

    private function ensureCheckoutEnabled(SelfServiceCart $cart, Tenant $tenant): void
    {
        $shopItems = ShopItem::query()
            ->with(['shop', 'product'])
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $cart->items->pluck('self_service_shop_item_id')->filter()->values())
            ->where('status', ShopItem::STATUS_PUBLISHED)
            ->where('is_visible', true)
            ->whereHas('shop', function ($query) use ($tenant) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('status', Shop::STATUS_ACTIVE);
            })
            ->whereHas('product', function ($query) use ($tenant) {
                $query
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true);
            })
            ->get()
            ->keyBy('id');

        $resolver = app(ShopItemCommercialPolicyResolver::class);

        foreach ($cart->items as $cartItem) {
            if (! $cartItem instanceof SelfServiceCartItem) {
                continue;
            }

            $shopItem = $shopItems->get($cartItem->self_service_shop_item_id);

            if (! $shopItem instanceof ShopItem) {
                throw new HttpException(422, SelfServiceCartService::MESSAGE_CART_HAS_UNAVAILABLE_ITEMS);
            }

            $commercialPolicy = $resolver->resolve($shopItem);

            if ($commercialPolicy['checkout_enabled'] !== true) {
                throw new HttpException(422, self::MESSAGE_CHECKOUT_DISABLED);
            }

            $maxQuantity = $commercialPolicy['max_quantity_per_checkout'] ?? null;

            if ($maxQuantity !== null && (int) $cartItem->quantity > (int) $maxQuantity) {
                throw new HttpException(422, SelfServiceCartService::MESSAGE_MAX_QUANTITY_EXCEEDED);
            }
        }
    }
}
