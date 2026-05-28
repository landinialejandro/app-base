<?php

// FILE: app/Http/Controllers/SelfServiceSalesConsumptionPointController.php | V1

namespace App\Http\Controllers;

use App\Models\SelfServiceStoreCustomer;
use App\Models\Shop;
use App\Models\ShopConsumptionPoint;
use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceTokenPocketService;
use App\Support\Shops\ShopPublishedCatalogReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SelfServiceSalesConsumptionPointController extends Controller
{
    public function show(
        Request $request,
        Tenant $tenant,
        string $publicToken,
        ShopPublishedCatalogReader $shopCatalogReader,
        SelfServiceTokenPocketService $tokenPockets
    ): View|RedirectResponse {
        $activeShop = $shopCatalogReader->activeShopForTenant($tenant);
        $point = ShopConsumptionPoint::query()
            ->where('public_token', $publicToken)
            ->where('tenant_id', $tenant->id)
            ->with('shop')
            ->first();

        if (
            ! $point instanceof ShopConsumptionPoint
            || ! $point->isActive()
            || ! $point->shop instanceof Shop
            || (string) $point->shop->tenant_id !== (string) $tenant->id
            || ! $point->shop->isActive()
            || ! $activeShop instanceof Shop
            || (int) $activeShop->id !== (int) $point->self_service_shop_id
        ) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('error', 'El punto de consumo no está disponible.');
        }

        $externalCustomer = null;
        $tokenPocketSummary = [];
        $shopItems = $shopCatalogReader->visibleItemsForShop($activeShop);
        $shopCatalogStatus = $shopItems->isEmpty() ? 'active_shop_without_items' : 'available';
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

            if ($storeCustomer->isActive() && $account?->isActive()) {
                $tokenPocketSummary = $tokenPockets->summaryForExternalCustomer(
                    tenantId: (string) $tenant->id,
                    accountId: (int) $account->id,
                    storeCustomerId: (int) $storeCustomer->id,
                );
            }
        }

        return view('self-service-sales.shop', [
            'tenant' => $tenant,
            'externalCustomer' => $externalCustomer,
            'activeShop' => $activeShop,
            'shopItems' => $shopItems,
            'shopCatalogStatus' => $shopCatalogStatus,
            'cartExperienceEnabled' => (bool) ($externalCustomer['can_operate'] ?? false),
            'tokenPockets' => $tokenPocketSummary,
            'consumptionPointContext' => [
                'id' => $point->id,
                'name' => $point->displayName(),
                'code' => $point->code,
                'attempt_url' => route('self_service_sales.token_consumption_attempts.store', [
                    'tenant' => $tenant,
                ]),
            ],
        ]);
    }
}
