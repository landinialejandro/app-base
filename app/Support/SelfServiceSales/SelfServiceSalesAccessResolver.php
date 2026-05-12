<?php

// FILE: app/Support/SelfServiceSales/SelfServiceSalesAccessResolver.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCustomerAccount;
use Illuminate\Support\Collection;

class SelfServiceSalesAccessResolver
{
    public const STATUS_ACCOUNT_NOT_FOUND = 'account_not_found';
    public const STATUS_ACCOUNT_BLOCKED = 'account_blocked';
    public const STATUS_WITHOUT_STORES = 'without_stores';
    public const STATUS_SINGLE_STORE = 'single_store';
    public const STATUS_MULTIPLE_STORES = 'multiple_stores';

    public function resolveByEmail(string $email): array
    {
        $email = mb_strtolower(trim($email));

        $account = SelfServiceCustomerAccount::query()
            ->where('email', $email)
            ->with([
                'storeCustomers' => function ($query) {
                    $query
                        ->where('status', 'active')
                        ->with(['tenant', 'party'])
                        ->orderBy('id');
                },
            ])
            ->first();

        if (! $account) {
            return $this->result(
                status: self::STATUS_ACCOUNT_NOT_FOUND,
            );
        }

        if (! $account->isActive()) {
            return $this->result(
                status: self::STATUS_ACCOUNT_BLOCKED,
                account: $account,
            );
        }

        $storeCustomers = $account->storeCustomers ?? collect();

        if ($storeCustomers->isEmpty()) {
            return $this->result(
                status: self::STATUS_WITHOUT_STORES,
                account: $account,
                storeCustomers: $storeCustomers,
            );
        }

        if ($storeCustomers->count() === 1) {
            return $this->result(
                status: self::STATUS_SINGLE_STORE,
                account: $account,
                storeCustomers: $storeCustomers,
            );
        }

        return $this->result(
            status: self::STATUS_MULTIPLE_STORES,
            account: $account,
            storeCustomers: $storeCustomers,
        );
    }

    protected function result(
        string $status,
        ?SelfServiceCustomerAccount $account = null,
        ?Collection $storeCustomers = null
    ): array {
        return [
            'status' => $status,
            'account' => $account,
            'store_customers' => $storeCustomers ?? collect(),
        ];
    }
}