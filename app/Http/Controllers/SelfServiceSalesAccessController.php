<?php

// FILE: app/Http/Controllers/SelfServiceSalesAccessController.php | V1

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SelfServiceSalesAccessController extends Controller
{
    public function show(Request $request)
    {
        return view('self-service-sales.access');
    }

    public function store(Request $request)
    {
        return back()
            ->withInput($request->only('email'))
            ->with('error', 'No pudimos iniciar sesión con esos datos. Podés intentar nuevamente, registrarte en una tienda o recuperar tu acceso.');
    }
}