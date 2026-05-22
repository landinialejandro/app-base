<?php

// FILE: app/Support/SelfServiceSales/Payments/SelfServicePaymentGateway.php | V1

namespace App\Support\SelfServiceSales\Payments;

interface SelfServicePaymentGateway
{
    public function process(array $paymentRequest): array;
}