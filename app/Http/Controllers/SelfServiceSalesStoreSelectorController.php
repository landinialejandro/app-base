<?php

// FILE: app/Http/Controllers/SelfServiceSalesStoreSelectorController.php | V6

namespace App\Http\Controllers;

use App\Models\SelfServiceStoreCustomer;
use App\Support\SelfServiceSales\SelfServiceExternalSession;
use App\Support\SelfServiceSales\SelfServiceStoreSelectionTokenService;
use Illuminate\Http\Request;

class SelfServiceSalesStoreSelectorController extends Controller
{
    public function show(Request $request, SelfServiceStoreSelectionTokenService $tokens)
    {
        $plainToken = (string) $request->query('token', '');

        $selectionToken = $plainToken !== ''
            ? $tokens->resolve($plainToken)
            : null;

        $storeCustomers = $selectionToken?->account?->storeCustomers ?? collect();

        $identityLabels = [
            SelfServiceStoreCustomer::IDENTITY_STAGE_EMAIL_CONFIRMED => 'Email confirmado',
            SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED => 'Identidad operativa completa',
        ];

        $storeSelectorRows = $storeCustomers
            ->map(function ($storeCustomer) use ($identityLabels) {
                $tenant = $storeCustomer->tenant;
                $party = $storeCustomer->party;

                return [
                    'store_customer_id' => $storeCustomer->id,
                    'tenant_label' => $tenant ? $tenant->name : 'Tienda',
                    'party_label' => $party ? ($party->display_name ?: $party->name ?: 'Cliente') : 'Cliente',
                    'identity_label' => $identityLabels[$storeCustomer->identity_stage] ?? $storeCustomer->identity_stage,
                    'operation_enabled' => $storeCustomer->operation_enabled === true,
                ];
            })
            ->values();

        return view('self-service-sales.store-selector', [
            'plainToken' => $plainToken,
            'hasToken' => $plainToken !== '',
            'selectionToken' => $selectionToken,
            'storeSelectorRows' => $storeSelectorRows,
        ]);
    }

    public function store(
        Request $request,
        SelfServiceStoreSelectionTokenService $tokens,
        SelfServiceExternalSession $externalSession
    ) {
        $selectionErrorMessage = 'No pudimos continuar con la selección de tienda. Iniciá el acceso nuevamente.';

        $plainToken = (string) $request->input('token', '');
        $storeCustomerId = $request->input('store_customer_id');

        if ($plainToken === '' || ! is_numeric($storeCustomerId)) {
            return $this->selectionFailure($selectionErrorMessage);
        }

        $selectionToken = $tokens->resolve($plainToken);

        if (! $selectionToken || ! $selectionToken->account) {
            return $this->selectionFailure($selectionErrorMessage);
        }

        $storeCustomer = $selectionToken->account->storeCustomers
            ->firstWhere('id', (int) $storeCustomerId);

        if (! $storeCustomer || ! $storeCustomer->isActive() || ! $storeCustomer->tenant) {
            return $this->selectionFailure($selectionErrorMessage);
        }

        if (! $externalSession->start($selectionToken->account, $storeCustomer)) {
            return $this->selectionFailure($selectionErrorMessage);
        }

        $selectionToken->forceFill([
            'used_at' => now(),
        ])->save();

        $redirect = redirect()->route('self_service_sales.shop', [
            'tenant' => $storeCustomer->tenant->slug,
        ]);

        if (! $storeCustomer->canOperate()) {
            $redirect->with(
                'self_service_sales_operation_notice',
                'Tu acceso a esta tienda está confirmado, pero la operación comercial todavía está pendiente de habilitación.'
            );
        }

        return $redirect;
    }

    protected function selectionFailure(string $message)
    {
        return redirect()
            ->route('self_service_sales.access')
            ->with('error', $message);
    }
}