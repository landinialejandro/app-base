<?php

// FILE: app/Http/Controllers/SelfServiceSalesAccessController.php | V4

namespace App\Http\Controllers;

use App\Support\SelfServiceSales\SelfServiceCustomerCredentialService;
use App\Support\SelfServiceSales\SelfServiceExternalSession;
use App\Support\SelfServiceSales\SelfServiceSalesAccessResolver;
use App\Support\SelfServiceSales\SelfServiceStoreSelectionTokenService;
use Illuminate\Http\Request;

class SelfServiceSalesAccessController extends Controller
{
    public function show(Request $request)
    {
        return view('self-service-sales.access');
    }

    public function store(
        Request $request,
        SelfServiceSalesAccessResolver $resolver,
        SelfServiceCustomerCredentialService $credentials,
        SelfServiceStoreSelectionTokenService $selectionTokens
    ) {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Ingresá tu email.',
            'email.email' => 'Ingresá un email válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        $access = $resolver->resolveByEmail((string) $data['email']);
        $account = $access['account'] ?? null;

        if (! $account) {
            return $this->genericAccessFailure($request);
        }

        if (! $credentials->verifyPassword($account, (string) $data['password'])) {
            return $this->genericAccessFailure($request);
        }

        $status = (string) ($access['status'] ?? '');

        if (! in_array($status, [
            SelfServiceSalesAccessResolver::STATUS_SINGLE_STORE,
            SelfServiceSalesAccessResolver::STATUS_MULTIPLE_STORES,
        ], true)) {
            return $this->genericAccessFailure($request);
        }

        $selectionToken = $selectionTokens->createForAccount($account);

        return redirect()
            ->route('self_service_sales.store_selector', [
                'token' => $selectionToken['token'],
            ]);
    }

    public function logout(SelfServiceExternalSession $externalSession)
    {
        $externalSession->forget();

        return redirect()
            ->route('self_service_sales.access')
            ->with('success', 'Cerraste el acceso externo.');
    }

    protected function genericAccessFailure(Request $request)
    {
        return back()
            ->withInput($request->only('email'))
            ->with('error', 'No pudimos iniciar sesión con esos datos. Podés intentar nuevamente, registrarte en una tienda o recuperar tu acceso.');
    }
}