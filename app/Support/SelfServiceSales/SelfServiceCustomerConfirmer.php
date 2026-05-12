<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCustomerConfirmer.php | V2

namespace App\Support\SelfServiceSales;

use App\Models\Party;
use App\Models\PartyRole;
use App\Models\SelfServiceCustomerAccount;
use App\Models\SelfServiceCustomerRegistration;
use App\Models\SelfServiceStoreCustomer;
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
            $account = $this->resolveAccount($registration);

            $this->assertNoStoreCustomerDuplicate($tenant, $account);

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

            SelfServiceStoreCustomer::create([
                'self_service_customer_account_id' => $account->id,
                'tenant_id' => $tenant->id,
                'party_id' => $party->id,
                'status' => SelfServiceStoreCustomer::STATUS_ACTIVE,
                'identity_stage' => SelfServiceStoreCustomer::IDENTITY_STAGE_EMAIL_CONFIRMED,
                'operation_enabled' => false,
                'identity_completed_at' => null,
                'terms_accepted_at' => null,
                'meta' => [
                    'source' => 'shop_public_registration',
                    'registration_id' => $registration->id,
                ],
            ]);

            $registration->update([
                'party_id' => $party->id,
                'self_service_customer_account_id' => $account->id,
                'status' => SelfServiceCustomerRegistration::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'accepted_ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'meta' => array_merge($registration->meta ?? [], [
                    'identity_stage' => 'email_confirmed',
                    'operation_enabled' => false,
                    'account_created' => true,
                    'store_customer_created' => true,
                ]),
            ]);

            return $registration->fresh(['party', 'account']);
        });
    }

    protected function resolveAccount(
        SelfServiceCustomerRegistration $registration
    ): SelfServiceCustomerAccount {
        $email = mb_strtolower(trim($registration->email));

        $account = SelfServiceCustomerAccount::query()
            ->where('email', $email)
            ->first();

        if ($account) {
            $updates = [];

            if ($account->email_confirmed_at === null) {
                $updates['email_confirmed_at'] = now();
            }

            if ($account->display_name === null) {
                $updates['display_name'] = $registration->display_name ?: $registration->name;
            }

            if ($account->phone === null) {
                $updates['phone'] = $registration->phone;
            }

            if ($updates !== []) {
                $account->update($updates);
            }

            return $account;
        }

        return SelfServiceCustomerAccount::create([
            'email' => $email,
            'display_name' => $registration->display_name ?: $registration->name,
            'phone' => $registration->phone,
            'status' => SelfServiceCustomerAccount::STATUS_ACTIVE,
            'email_confirmed_at' => now(),
            'last_access_at' => null,
            'meta' => [
                'source' => 'shop_public_registration',
            ],
        ]);
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

        $phoneExists = Party::query()
            ->where('tenant_id', $tenant->id)
            ->where('phone', $registration->phone)
            ->exists();

        if ($phoneExists) {
            throw ValidationException::withMessages([
                'phone' => 'Ya existe un cliente registrado con ese teléfono en esta tienda.',
            ]);
        }
    }

    protected function assertNoStoreCustomerDuplicate(
        Tenant $tenant,
        SelfServiceCustomerAccount $account
    ): void {
        $exists = SelfServiceStoreCustomer::query()
            ->where('tenant_id', $tenant->id)
            ->where('self_service_customer_account_id', $account->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta ya está vinculada a esta tienda.',
            ]);
        }
    }
}