<?php

// FILE: app/Support/SelfServiceSales/SelfServiceExternalSession.php | V1

namespace App\Support\SelfServiceSales;

use App\Models\SelfServiceCustomerAccount;
use App\Models\SelfServiceStoreCustomer;
use App\Models\Tenant;

class SelfServiceExternalSession
{
    public const KEY_ACCOUNT_ID = 'self_service_sales.account_id';
    public const KEY_STORE_CUSTOMER_ID = 'self_service_sales.store_customer_id';
    public const KEY_TENANT_ID = 'self_service_sales.tenant_id';

    public function start(
        SelfServiceCustomerAccount $account,
        SelfServiceStoreCustomer $storeCustomer
    ): bool {
        if (! $this->isStartable($account, $storeCustomer)) {
            return false;
        }

        session([
            self::KEY_ACCOUNT_ID => $account->id,
            self::KEY_STORE_CUSTOMER_ID => $storeCustomer->id,
            self::KEY_TENANT_ID => $storeCustomer->tenant_id,
        ]);

        return true;
    }

    public function forget(): void
    {
        session()->forget([
            self::KEY_ACCOUNT_ID,
            self::KEY_STORE_CUSTOMER_ID,
            self::KEY_TENANT_ID,
        ]);
    }

    public function has(): bool
    {
        return filled(session(self::KEY_ACCOUNT_ID))
            && filled(session(self::KEY_STORE_CUSTOMER_ID))
            && filled(session(self::KEY_TENANT_ID));
    }

    public function account(): ?SelfServiceCustomerAccount
    {
        $accountId = session(self::KEY_ACCOUNT_ID);

        if (! $accountId) {
            return null;
        }

        $account = SelfServiceCustomerAccount::query()->find($accountId);

        if (! $account || ! $account->isActive()) {
            $this->forget();

            return null;
        }

        return $account;
    }

    public function storeCustomer(): ?SelfServiceStoreCustomer
    {
        $account = $this->account();
        $storeCustomerId = session(self::KEY_STORE_CUSTOMER_ID);
        $tenantId = session(self::KEY_TENANT_ID);

        if (! $account || ! $storeCustomerId || ! $tenantId) {
            $this->forget();

            return null;
        }

        $storeCustomer = SelfServiceStoreCustomer::query()
            ->with(['tenant', 'party', 'account'])
            ->where('id', $storeCustomerId)
            ->where('self_service_customer_account_id', $account->id)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $storeCustomer || ! $storeCustomer->isActive()) {
            $this->forget();

            return null;
        }

        return $storeCustomer;
    }

    public function tenant(): ?Tenant
    {
        $storeCustomer = $this->storeCustomer();

        return $storeCustomer?->tenant;
    }

    public function isSelectedForTenant(Tenant $tenant): bool
    {
        $storeCustomer = $this->storeCustomer();

        return $storeCustomer !== null
            && $storeCustomer->tenant_id === $tenant->id;
    }

    public function canOperate(): bool
    {
        $storeCustomer = $this->storeCustomer();

        return $storeCustomer !== null && $storeCustomer->canOperate();
    }

    public function payload(): ?array
    {
        $storeCustomer = $this->storeCustomer();

        if (! $storeCustomer) {
            return null;
        }

        return [
            'account' => $storeCustomer->account,
            'store_customer' => $storeCustomer,
            'tenant' => $storeCustomer->tenant,
            'party' => $storeCustomer->party,
            'can_operate' => $storeCustomer->canOperate(),
        ];
    }

    protected function isStartable(
        SelfServiceCustomerAccount $account,
        SelfServiceStoreCustomer $storeCustomer
    ): bool {
        return $account->isActive()
            && $storeCustomer->isActive()
            && (int) $storeCustomer->self_service_customer_account_id === (int) $account->id
            && filled($storeCustomer->tenant_id)
            && $storeCustomer->tenant !== null;
    }
}