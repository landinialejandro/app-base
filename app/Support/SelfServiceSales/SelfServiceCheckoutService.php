<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCheckoutService.php | V2

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCart;
use App\Models\Tenant;
use App\Support\SelfServiceSales\Payments\SelfServicePaymentGateway;
use App\Support\SelfServiceSales\Payments\SelfServicePaymentRequestFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelfServiceCheckoutService
{
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
}