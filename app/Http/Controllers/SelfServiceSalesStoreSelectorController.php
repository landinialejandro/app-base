<?php

// FILE: app/Http/Controllers/SelfServiceSalesStoreSelectorController.php | V3

namespace App\Http\Controllers;

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
            'email_confirmed' => 'Email confirmado',
            'operational_identity_completed' => 'Identidad operativa completa',
        ];

        $storeSelectorRows = $storeCustomers
            ->map(function ($storeCustomer) use ($identityLabels) {
                $tenant = $storeCustomer->tenant;
                $party = $storeCustomer->party;

                return [
                    'tenant_label' => $tenant ? $tenant->name : 'Tienda',
                    'party_label' => $party ? ($party->display_name ?: $party->name ?: 'Cliente') : 'Cliente',
                    'identity_label' => $identityLabels[$storeCustomer->identity_stage] ?? $storeCustomer->identity_stage,
                    'operation_enabled' => $storeCustomer->operation_enabled === true,
                ];
            })
            ->values();

        return view('self-service-sales.store-selector', [
            'hasToken' => $plainToken !== '',
            'selectionToken' => $selectionToken,
            'storeSelectorRows' => $storeSelectorRows,
        ]);
    }

    public function store(Request $request)
    {
        return redirect()
            ->route('self_service_sales.access')
            ->with('error', 'No pudimos continuar con la selección de tienda. Iniciá el acceso nuevamente.');
    }
}