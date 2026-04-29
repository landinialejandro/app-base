<?php

// FILE: app/Http/Responses/LoginResponse.php | V2

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $invitationUrl = $request->session()->pull('invitation_accept_url');

        if ($invitationUrl) {
            return redirect()->to($invitationUrl);
        }

        if ($request->session()->has('url.intended')) {
            return redirect()->intended();
        }

        $user = Auth::user();

        if ($user->is_superadmin) {
            return redirect()->route('admin.dashboard');
        }

        $activeTenants = $user->tenants()
            ->wherePivot('status', 'active')
            ->select('tenants.id')
            ->get();

        if ($activeTenants->count() > 1) {
            return redirect()->route('tenants.select');
        }

        if ($activeTenants->count() === 1) {
            $request->session()->put('tenant_id', $activeTenants->first()->id);

            return redirect()->route('dashboard');
        }

        return redirect()->route('tenants.select');
    }
}