<?php

// FILE: app/Http/Controllers/SelfServiceSalesCustomerRegistrationController.php | V6

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfServiceCustomerRegistrationRequest;
use App\Models\SelfServiceCustomerRegistration;
use App\Models\SelfServiceStoreCustomer;
use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceCustomerConfirmer;
use App\Support\SelfServiceSales\SelfServiceCustomerRegistrar;
use App\Support\SelfServiceSales\SelfServiceExternalSession;
use App\Support\Shops\ShopPublishedCatalogReader;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SelfServiceSalesCustomerRegistrationController extends Controller
{
    public function shop(
        Request $request,
        Tenant $tenant,
        ShopPublishedCatalogReader $shopCatalogReader
    ) {
        $externalCustomer = null;
        $activeShop = $shopCatalogReader->activeShopForTenant($tenant);
        $shopItems = $activeShop
            ? $shopCatalogReader->visibleItemsForShop($activeShop)
            : collect();
        $shopCatalogStatus = ! $activeShop
            ? 'without_active_shop'
            : ($shopItems->isEmpty() ? 'active_shop_without_items' : 'available');
        $payload = $request->attributes->get('self_service_external_customer');

        if ($payload) {
            $storeCustomer = $payload['store_customer'];
            $party = $payload['party'];
            $account = $payload['account'];

            $identityLabels = [
                SelfServiceStoreCustomer::IDENTITY_STAGE_EMAIL_CONFIRMED => 'Email confirmado',
                SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED => 'Identidad operativa completa',
            ];

            $externalCustomer = [
                'display_name' => $account?->display_name ?: 'Cliente externo',
                'email' => $account?->email,
                'party_label' => $party ? ($party->display_name ?: $party->name ?: 'Cliente') : 'Cliente',
                'identity_stage' => $storeCustomer->identity_stage,
                'identity_label' => $identityLabels[$storeCustomer->identity_stage] ?? $storeCustomer->identity_stage,
                'operation_enabled' => $storeCustomer->operation_enabled === true,
                'can_complete_identity' => $storeCustomer->identity_stage === SelfServiceStoreCustomer::IDENTITY_STAGE_EMAIL_CONFIRMED
                    && $storeCustomer->operation_enabled !== true,
                'can_operate' => $payload['can_operate'] === true,
            ];

        }

        $cartExperienceEnabled = (bool) ($externalCustomer['can_operate'] ?? false);

        return view('self-service-sales.shop', [
            'tenant' => $tenant,
            'externalCustomer' => $externalCustomer,
            'activeShop' => $activeShop,
            'shopItems' => $shopItems,
            'shopCatalogStatus' => $shopCatalogStatus,
            'cartExperienceEnabled' => $cartExperienceEnabled,
        ]);
    }

    public function create(Tenant $tenant)
    {
        return view('self-service-sales.register', [
            'tenant' => $tenant,
        ]);
    }

    public function store(
        StoreSelfServiceCustomerRegistrationRequest $request,
        Tenant $tenant,
        SelfServiceCustomerRegistrar $registrar
    ) {
        $registration = $registrar->createPending($tenant, $request);

        return redirect()
            ->route('self_service_sales.register.thanks', ['tenant' => $tenant])
            ->with('success', 'Recibimos tus datos. Te enviaremos un enlace para confirmar el registro.')
            ->with('registration_email', $registration->email);
    }

    public function thanks(Request $request, Tenant $tenant)
    {
        return view('self-service-sales.thanks', [
            'tenant' => $tenant,
            'email' => session('registration_email'),
        ]);
    }

    public function confirm(
        Request $request,
        Tenant $tenant,
        string $token,
        SelfServiceCustomerConfirmer $confirmer,
        SelfServiceExternalSession $externalSession
    ) {
        try {
            $registration = $confirmer->confirm($tenant, $token, $request);
        } catch (ValidationException $exception) {
            $registration = SelfServiceCustomerRegistration::query()
                ->where('tenant_id', $tenant->id)
                ->where('token', $token)
                ->with(['party', 'account'])
                ->first();

            if (! $registration || ! $registration->isConfirmed()) {
                return redirect()
                    ->route('self_service_sales.shop', ['tenant' => $tenant])
                    ->withErrors($exception->errors())
                    ->with('error', 'No pudimos confirmar el registro.');
            }
        }

        $registration->loadMissing(['party', 'account']);

        $storeCustomer = SelfServiceStoreCustomer::query()
            ->where('tenant_id', $tenant->id)
            ->where('party_id', $registration->party_id)
            ->where('self_service_customer_account_id', $registration->self_service_customer_account_id)
            ->first();

        if ($registration->account && $storeCustomer && $externalSession->start($registration->account, $storeCustomer)) {
            if (
                $storeCustomer->identity_stage !== SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED
                || ! $registration->account->canAccessExternally()
            ) {
                return redirect()
                    ->route('self_service_sales.identity.edit', ['tenant' => $tenant])
                    ->with('success', 'Email confirmado. Ahora finalizá tu registro para poder volver a ingresar a esta tienda.');
            }

            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('success', 'Tu registro ya estaba confirmado.');
        }

        return view('self-service-sales.confirmed', [
            'tenant' => $tenant,
            'registration' => $registration,
            'party' => $registration->party,
        ]);
    }
}
