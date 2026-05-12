<?php

// FILE: app/Support/SelfServiceSales/SelfServiceStoreSelectionTokenService.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCustomerAccount;
use App\Models\SelfServiceStoreSelectionToken;
use Illuminate\Support\Str;

class SelfServiceStoreSelectionTokenService
{
    public function createForAccount(SelfServiceCustomerAccount $account): array
    {
        $plainToken = Str::random(80);

        $selectionToken = SelfServiceStoreSelectionToken::create([
            'token_hash' => $this->hash($plainToken),
            'self_service_customer_account_id' => $account->id,
            'expires_at' => now()->addMinutes(10),
            'used_at' => null,
            'meta' => [
                'source' => 'self_service_store_selector',
                'purpose' => 'external_store_selection',
            ],
        ]);

        return [
            'token' => $plainToken,
            'selection_token' => $selectionToken,
        ];
    }

    public function resolve(string $plainToken): ?SelfServiceStoreSelectionToken
    {
        $selectionToken = SelfServiceStoreSelectionToken::query()
            ->where('token_hash', $this->hash($plainToken))
            ->with([
                'account.storeCustomers' => function ($query) {
                    $query
                        ->where('status', 'active')
                        ->with(['tenant', 'party'])
                        ->orderBy('id');
                },
            ])
            ->first();

        if (! $selectionToken || ! $selectionToken->isAvailable()) {
            return null;
        }

        if (! $selectionToken->account || ! $selectionToken->account->isActive()) {
            return null;
        }

        return $selectionToken;
    }

    protected function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}