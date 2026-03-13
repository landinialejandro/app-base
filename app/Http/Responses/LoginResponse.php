<?php

// FILE: app/Http/Responses/LoginResponse.php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();

        $tenantsCount = $user->tenants()->count();

        if ($tenantsCount > 1) {
            return redirect()->route('tenants.select');
        }

        if ($tenantsCount === 1) {
            $tenantId = $user->tenants()->value('tenants.id');

            $request->session()->put('tenant_id', $tenantId);

            return redirect()->route('dashboard');
        }

        return redirect()->route('tenants.select');
    }
}