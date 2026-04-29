<?php

// FILE: app/Http/Responses/LogoutResponse.php | V2

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $request->session()->forget([
            'tenant_id',
            'invitation_accept_url',
        ]);

        return redirect('/');
    }
}