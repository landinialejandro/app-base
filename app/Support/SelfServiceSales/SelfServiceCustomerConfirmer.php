<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCustomerConfirmer.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\Party;
use App\Models\PartyRole;
use App\Models\SelfServiceCustomerRegistration;
use App\Models\Tenant;
use App\Support\Catalogs\PartyCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelfServiceCustomerConfirmer
{
public function confirm(Tenant $tenant, string $token, Request $request): SelfServiceCustomerRegistration
{
    $registration = SelfServiceCustomerRegistration::query()
        ->where('tenant_id', $tenant->id)
        ->where('token', $token)
        ->firstOrFail();

    if (! $registration->isPending()) {
        throw ValidationException::withMessages([
            'token' => 'Este enlace ya fue utilizado o no se encuentra disponible.',
        ]);
    }

    if ($registration->isExpired()) {
        $registration->update([
            'status' => SelfServiceCustomerRegistration::STATUS_EXPIRED,
        ]);

        throw ValidationException::withMessages([
            'token' => 'Este enlace de confirmación ya venció.',
        ]);
    }

    $this->assertNoPartyDuplicates($tenant, $registration);

    return DB::transaction(function () use ($tenant, $registration, $request) {
        $party = Party::create([
            'tenant_id' => $tenant->id,
            'kind' => PartyCatalog::KIND_PERSON,
            'name' => $registration->display_name ?: $registration->name,
            'display_name' => $registration->display_name ?: $registration->name,
            'document_type' => null,
            'document_number' => null,
            'tax_id' => null,
            'email' => $registration->email,
            'phone' => $registration->phone,
            'address' => null,
            'notes' => null,
            'is_active' => true,
        ]);

        PartyRole::create([
            'tenant_id' => $tenant->id,
            'party_id' => $party->id,
            'role' => PartyCatalog::ROLE_CUSTOMER,
        ]);

        $registration->update([
            'party_id' => $party->id,
            'status' => SelfServiceCustomerRegistration::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'accepted_ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => array_merge($registration->meta ?? [], [
                'identity_stage' => 'email_confirmed',
                'operation_enabled' => false,
            ]),
        ]);

        return $registration->fresh(['party']);
    });
}

protected function assertNoPartyDuplicates(
    Tenant $tenant,
    SelfServiceCustomerRegistration $registration
): void {
    $emailExists = Party::query()
        ->where('tenant_id', $tenant->id)
        ->whereRaw('LOWER(email) = ?', [mb_strtolower($registration->email)])
        ->exists();

    if ($emailExists) {
        throw ValidationException::withMessages([
            'email' => 'Ya existe un cliente registrado con ese email en esta tienda.',
        ]);
    }
}
}