<?php

// FILE: app/Http/Controllers/SelfServiceSalesCustomerRegistrationController.php | V2

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfServiceCustomerRegistrationRequest;
use App\Models\Tenant;
use App\Support\SelfServiceSales\SelfServiceCustomerConfirmer;
use App\Support\SelfServiceSales\SelfServiceCustomerRegistrar;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SelfServiceSalesCustomerRegistrationController extends Controller
{
    public function shop(Tenant $tenant)
    {
        return view('self-service-sales.shop', [
            'tenant' => $tenant,
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
        SelfServiceCustomerConfirmer $confirmer
    ) {
        try {
            $registration = $confirmer->confirm($tenant, $token, $request);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('self_service_sales.shop', ['tenant' => $tenant])
                ->withErrors($exception->errors())
                ->with('error', 'No pudimos confirmar el registro.');
        }

        return view('self-service-sales.confirmed', [
            'tenant' => $tenant,
            'registration' => $registration,
            'party' => $registration->party,
        ]);
    }
}