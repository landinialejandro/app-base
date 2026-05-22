<?php

// FILE: app/Support/SelfServiceSales/Payments/SimulatedExternalPaymentGateway.php | V1

namespace App\Support\SelfServiceSales\Payments;

use Illuminate\Support\Str;

class SimulatedExternalPaymentGateway implements SelfServicePaymentGateway
{
    public function process(array $paymentRequest): array
    {
        $target = (string) ($paymentRequest['provider_target'] ?? 'mercado_pago');
        $amount = (float) ($paymentRequest['amount'] ?? 0);

        return [
            'provider' => 'simulated',
            'provider_target' => $target,
            'status' => 'approved',
            'status_label' => 'Aprobado',
            'status_detail' => 'accredited',
            'external_payment_id' => 'SIM-MP-'.Str::upper(Str::random(10)),
            'external_preference_id' => 'SIM-PREF-'.Str::upper(Str::random(10)),
            'external_reference' => (string) ($paymentRequest['external_reference'] ?? ''),
            'amount' => $amount,
            'currency' => (string) ($paymentRequest['currency'] ?? 'ARS'),
            'raw' => [
                'simulated' => true,
                'provider_shape' => $target,
            ],
        ];
    }
}