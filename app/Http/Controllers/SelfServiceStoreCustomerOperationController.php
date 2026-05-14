<?php

// FILE: app/Http/Controllers/SelfServiceStoreCustomerOperationController.php | V1

namespace App\Http\Controllers;

use App\Models\SelfServiceStoreCustomer;
use App\Support\Tenants\TenantProfileAccess;
use Illuminate\Http\Request;

class SelfServiceStoreCustomerOperationController extends Controller
{
    public function enable(
        Request $request,
        SelfServiceStoreCustomer $storeCustomer,
        TenantProfileAccess $tenantProfileAccess
    ) {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->first();

        abort_unless($tenantProfileAccess->canManageSelfServiceCustomers($membership), 403);

        abort_unless((int) $storeCustomer->tenant_id === (int) $tenant->id, 404);

        if (! $storeCustomer->isActive()) {
            return redirect()
                ->route('tenant.profile.show', [
                    'tab' => 'self_service_customers',
                    'self_service_customer_status' => 'all',
                ])
                ->with('error', 'No se puede habilitar la operación de una relación tienda que no está activa.');
        }

        if ($storeCustomer->identity_stage !== SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED) {
            return redirect()
                ->route('tenant.profile.show', [
                    'tab' => 'self_service_customers',
                    'self_service_customer_status' => 'email_confirmed',
                ])
                ->with('error', 'Para habilitar la operación, primero debe estar completa la identidad operativa del cliente.');
        }

        if ($storeCustomer->operation_enabled === true) {
            return redirect()
                ->route('tenant.profile.show', [
                    'tab' => 'self_service_customers',
                    'self_service_customer_status' => 'operation_enabled',
                ])
                ->with('success', 'La operación comercial del cliente ya estaba habilitada.');
        }

        $storeCustomer->update([
            'operation_enabled' => true,
            'meta' => array_replace_recursive($storeCustomer->meta ?? [], [
                'operation_enabled_source' => 'tenant_profile_manual',
                'operation_enabled_by_user_id' => auth()->id(),
                'operation_enabled_at' => now()->toDateTimeString(),
            ]),
        ]);

        return redirect()
            ->route('tenant.profile.show', [
                'tab' => 'self_service_customers',
                'self_service_customer_status' => 'operation_enabled',
            ])
            ->with('success', 'Operación comercial habilitada para el cliente.');
    }
}