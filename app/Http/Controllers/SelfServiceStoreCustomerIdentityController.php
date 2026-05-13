<?php

// FILE: app/Http/Controllers/SelfServiceStoreCustomerIdentityController.php | V1

namespace App\Http\Controllers;

use App\Models\SelfServiceStoreCustomer;
use App\Support\Tenants\TenantProfileAccess;
use Illuminate\Http\Request;

class SelfServiceStoreCustomerIdentityController extends Controller
{
    public function complete(
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

        abort_unless($storeCustomer->tenant_id === $tenant->id, 404);

        if (! $storeCustomer->isActive()) {
            return redirect()
                ->route('tenant.profile.show', [
                    'tab' => 'self_service_customers',
                    'self_service_customer_status' => 'confirmed',
                ])
                ->with('error', 'No se puede completar la identidad de una relación tienda que no está activa.');
        }

        if ($storeCustomer->identity_stage === SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED) {
            return redirect()
                ->route('tenant.profile.show', [
                    'tab' => 'self_service_customers',
                    'self_service_customer_status' => 'confirmed',
                ])
                ->with('success', 'La identidad operativa del cliente ya estaba completa.');
        }

        $meta = $storeCustomer->meta ?? [];

        $storeCustomer->update([
            'identity_stage' => SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED,
            'identity_completed_at' => now(),
            'operation_enabled' => false,
            'meta' => array_replace_recursive($meta, [
                'identity_completed_source' => 'tenant_profile_manual',
                'identity_completed_by_user_id' => auth()->id(),
            ]),
        ]);

        return redirect()
            ->route('tenant.profile.show', [
                'tab' => 'self_service_customers',
                'self_service_customer_status' => 'confirmed',
            ])
            ->with('success', 'Identidad operativa completada. La operación comercial continúa bloqueada.');
    }
}