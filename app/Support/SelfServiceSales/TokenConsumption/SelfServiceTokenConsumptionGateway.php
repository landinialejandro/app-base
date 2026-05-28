<?php

// FILE: app/Support/SelfServiceSales/TokenConsumption/SelfServiceTokenConsumptionGateway.php | V1

namespace App\Support\SelfServiceSales\TokenConsumption;

interface SelfServiceTokenConsumptionGateway
{
    public function process(array $consumptionRequest): array;
}
