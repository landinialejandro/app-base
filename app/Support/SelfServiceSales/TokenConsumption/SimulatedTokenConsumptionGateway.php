<?php

// FILE: app/Support/SelfServiceSales/TokenConsumption/SimulatedTokenConsumptionGateway.php | V1

namespace App\Support\SelfServiceSales\TokenConsumption;

use Illuminate\Support\Str;

class SimulatedTokenConsumptionGateway implements SelfServiceTokenConsumptionGateway
{
    public function process(array $consumptionRequest): array
    {
        return [
            'provider' => 'simulated',
            'provider_target' => 'token_controller',
            'status' => 'approved',
            'status_label' => 'Aprobado',
            'status_detail' => 'simulated_controller_accepted',
            'external_consumption_id' => 'SIM-TOKEN-'.Str::upper(Str::random(10)),
            'external_reference' => (string) ($consumptionRequest['external_reference'] ?? ''),
            'attempt_id' => $consumptionRequest['attempt_id'] ?? null,
            'consumption_point_id' => $consumptionRequest['consumption_point']['id'] ?? null,
            'quantity' => $consumptionRequest['quantity'] ?? null,
            'total_seconds' => $consumptionRequest['total_seconds'] ?? null,
            'raw' => [
                'simulated' => true,
                'controller_shape' => 'token_controller',
            ],
        ];
    }
}
