<?php

// FILE: app/Support/SelfServiceSales/SelfServiceCustomerCredentialService.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCustomerAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SelfServiceCustomerCredentialService
{
    public function setPassword(
        SelfServiceCustomerAccount $account,
        string $password,
        bool $enableAccess = false
    ): SelfServiceCustomerAccount {
        $this->assertValidPassword($password);

        $account->update([
            'password_hash' => Hash::make($password),
            'password_set_at' => now(),
            'password_needs_reset' => false,
            'access_enabled' => $enableAccess,
            'meta' => array_merge($account->meta ?? [], [
                'credential_stage' => $enableAccess
                    ? 'external_access_enabled'
                    : 'external_password_set',
            ]),
        ]);

        return $account->fresh();
    }

    public function enableAccess(SelfServiceCustomerAccount $account): SelfServiceCustomerAccount
    {
        if (! $account->hasExternalCredential()) {
            throw ValidationException::withMessages([
                'password' => 'La cuenta externa todavía no tiene credencial configurada.',
            ]);
        }

        $account->update([
            'access_enabled' => true,
            'password_needs_reset' => false,
            'meta' => array_merge($account->meta ?? [], [
                'credential_stage' => 'external_access_enabled',
            ]),
        ]);

        return $account->fresh();
    }

    public function disableAccess(SelfServiceCustomerAccount $account): SelfServiceCustomerAccount
    {
        $account->update([
            'access_enabled' => false,
            'meta' => array_merge($account->meta ?? [], [
                'credential_stage' => 'external_access_disabled',
            ]),
        ]);

        return $account->fresh();
    }

    public function verifyPassword(SelfServiceCustomerAccount $account, string $password): bool
    {
        if (! $account->canAccessExternally()) {
            return false;
        }

        if (! filled($account->password_hash)) {
            return false;
        }

        return Hash::check($password, $account->password_hash);
    }

    protected function assertValidPassword(string $password): void
    {
        if (mb_strlen($password) < 8) {
            throw ValidationException::withMessages([
                'password' => 'La contraseña externa debe tener al menos 8 caracteres.',
            ]);
        }
    }
}