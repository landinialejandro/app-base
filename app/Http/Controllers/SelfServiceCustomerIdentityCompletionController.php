<?php

// FILE: app/Http/Controllers/SelfServiceCustomerIdentityCompletionController.php | V2

namespace App\Http\Controllers;

use App\Http\Requests\CompleteSelfServiceCustomerIdentityRequest;
use App\Models\Party;
use App\Models\SelfServiceCustomerAccount;
use App\Models\SelfServiceStoreCustomer;
use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceCustomerCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SelfServiceCustomerIdentityCompletionController extends Controller
{
    public function edit(Request $request, Tenant $tenant)
    {
        $context = $this->externalContext($request, $tenant);

        if (! $context) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('error', 'Para completar tus datos, primero tenés que confirmar el registro desde el enlace recibido.');
        }

        $storeCustomer = $context['store_customer'];
        $party = $context['party'];
        $account = $context['account'] ?? null;

        if (! $storeCustomer->isActive() || ! $party || ! $account) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('error', 'No pudimos encontrar una relación activa con esta tienda.');
        }

        if (
            $storeCustomer->identity_stage === SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED
            && $account->canAccessExternally()
        ) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('success', 'Tu registro ya está completo. La operación comercial sigue pendiente de habilitación.');
        }

        return view('self-service-sales.complete-identity', [
            'tenant' => $tenant,
            'storeCustomer' => $storeCustomer,
            'party' => $party,
            'account' => $account,
        ]);
    }

    public function update(
        CompleteSelfServiceCustomerIdentityRequest $request,
        Tenant $tenant,
        SelfServiceCustomerCredentialService $credentials
    ) {
        $context = $this->externalContext($request, $tenant);

        if (! $context) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('error', 'Para completar tus datos, primero tenés que confirmar el registro desde el enlace recibido.');
        }

        /** @var SelfServiceStoreCustomer $storeCustomer */
        $storeCustomer = $context['store_customer'];

        /** @var Party|null $party */
        $party = $context['party'];

        /** @var SelfServiceCustomerAccount|null $account */
        $account = $context['account'] ?? null;

        if (! $storeCustomer->isActive() || ! $party || ! $account) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('error', 'No pudimos encontrar una relación activa con esta tienda.');
        }

        if (
            $storeCustomer->identity_stage === SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED
            && $account->canAccessExternally()
        ) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->with('success', 'Tu registro ya estaba completo. La operación comercial sigue pendiente de habilitación.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($party, $storeCustomer, $account, $credentials, $data) {
            $party->update([
                'name' => $data['name'],
                'display_name' => $data['name'],
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'],
                'phone' => $data['phone'],
            ]);

            $storeCustomer->update([
                'identity_stage' => SelfServiceStoreCustomer::IDENTITY_STAGE_OPERATIONAL_IDENTITY_COMPLETED,
                'identity_completed_at' => now(),
                'terms_accepted_at' => now(),
                'operation_enabled' => false,
                'meta' => array_replace_recursive($storeCustomer->meta ?? [], [
                    'identity_completed_source' => 'self_service_external_customer',
                    'identity_completed_at' => now()->toDateTimeString(),
                    'external_access_enabled' => true,
                ]),
            ]);

            $credentials->setPassword(
                $account,
                (string) $data['password'],
                true
            );
        });

        return redirect()
            ->route('self_service_sales.shop', ['tenant' => $tenant])
            ->with('success', 'Tu registro quedó completo. La operación comercial sigue pendiente de habilitación por la tienda.');
    }

    protected function externalContext(Request $request, Tenant $tenant): ?array
    {
        $payload = $request->attributes->get('self_service_external_customer');

        if (! is_array($payload)) {
            return null;
        }

        $storeCustomer = $payload['store_customer'] ?? null;

        if (! $storeCustomer instanceof SelfServiceStoreCustomer) {
            return null;
        }

        if ((int) $storeCustomer->tenant_id !== (int) $tenant->id) {
            return null;
        }

        return $payload;
    }
}
