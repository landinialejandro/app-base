<?php

// FILE: app/Support/SelfServiceSales/TokenConsumption/SelfServiceTokenConsumptionRequestFactory.php | V1

namespace App\Support\SelfServiceSales\TokenConsumption;

use App\Models\SelfServiceTokenConsumptionAttempt;
use App\Models\SelfServiceTokenPocket;
use App\Models\ShopConsumptionPoint;

class SelfServiceTokenConsumptionRequestFactory
{
    public function make(
        SelfServiceTokenConsumptionAttempt $attempt,
        SelfServiceTokenPocket $pocket,
        ?ShopConsumptionPoint $point = null
    ): array {
        $quantity = (float) $attempt->quantity;
        $requestPayload = is_array($attempt->request_payload) ? $attempt->request_payload : [];

        $consumptionRequest = [
            'provider' => 'simulated',
            'provider_target' => 'token_controller',
            'operation' => 'self_service_token_consumption',
            'internal_reference' => 'SSTCA-'.$attempt->id,
            'external_reference' => 'tenant:'.$attempt->tenant_id.':attempt:'.$attempt->id,
            'idempotency_key' => $this->idempotencyKey($attempt, $pocket, $point),
            'tenant_id' => $attempt->tenant_id,
            'attempt_id' => $attempt->id,
            'pocket_id' => $pocket->id,
            'product_id' => $attempt->product_id,
            'quantity' => $quantity,
            'unit_label' => $attempt->unit_label_snapshot,
            'unit_seconds' => $attempt->unit_seconds_snapshot,
            'total_seconds' => $attempt->total_seconds,
            'consumption_point' => [
                'id' => $point?->id,
                'name' => $point?->displayName(),
                'code' => $point?->code,
            ],
            'security' => [
                'mode' => 'server_side',
                'source' => 'self_service_sales',
                'tenant_id' => $attempt->tenant_id,
                'attempt_id' => $attempt->id,
                'pocket_id' => $pocket->id,
                'consumption_point_id' => $point?->id,
            ],
        ];

        if (($requestPayload['simulated_gateway_status'] ?? null) === 'rejected') {
            $consumptionRequest['simulation'] = [
                'status' => 'rejected',
            ];
        }

        return $consumptionRequest;
    }

    private function idempotencyKey(
        SelfServiceTokenConsumptionAttempt $attempt,
        SelfServiceTokenPocket $pocket,
        ?ShopConsumptionPoint $point
    ): string {
        return hash('sha256', json_encode([
            'tenant_id' => $attempt->tenant_id,
            'attempt_id' => $attempt->id,
            'pocket_id' => $pocket->id,
            'product_id' => $attempt->product_id,
            'quantity' => (float) $attempt->quantity,
            'total_seconds' => $attempt->total_seconds,
            'consumption_point_id' => $point?->id,
        ], JSON_THROW_ON_ERROR));
    }
}
