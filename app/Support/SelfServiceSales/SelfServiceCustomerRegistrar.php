<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCustomerRegistrar.php | V1

namespace App\Support\SelfServiceSales;

use App\Http\Requests\StoreSelfServiceCustomerRegistrationRequest;
use App\Models\SelfServiceCustomerRegistration;
use App\Models\Tenant;
use Illuminate\Support\Str;

class SelfServiceCustomerRegistrar
{
public function createPending(
    Tenant $tenant,
    StoreSelfServiceCustomerRegistrationRequest $request
): SelfServiceCustomerRegistration {
    $data = $request->validated();

    return SelfServiceCustomerRegistration::create([
        'tenant_id' => $tenant->id,
        'status' => SelfServiceCustomerRegistration::STATUS_PENDING,
        'token' => $this->makeUniqueToken(),
        'name' => $data['display_name'],
        'display_name' => $data['display_name'],
        'document_type' => null,
        'document_number' => null,
        'email' => $data['email'],
        'phone' => $data['phone'],
        'expires_at' => now()->addDay(),
        'accepted_ip' => null,
        'user_agent' => null,
        'meta' => [
            'source' => 'shop_public_registration',
            'identity_stage' => 'initial_contact',
            'operation_enabled' => false,
        ],
    ]);
}

    protected function makeUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (
            SelfServiceCustomerRegistration::query()
                ->where('token', $token)
                ->exists()
        );

        return $token;
    }
}