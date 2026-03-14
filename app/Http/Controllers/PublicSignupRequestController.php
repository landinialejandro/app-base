<?php

// FILE: app/Http/Controllers/PublicSignupRequestController.php

namespace App\Http\Controllers;

use App\Models\SignupRequest;
use Illuminate\Http\Request;

class PublicSignupRequestController extends Controller
{
    public function create()
    {
        return view('public.signup-requests.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'requested_name' => ['required', 'string', 'max:255'],
            'requested_email' => ['required', 'email:rfc,dns', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone_whatsapp' => ['required', 'regex:/^\+[1-9]\d{7,14}$/'],
        ], [
            'requested_name.required' => 'Ingresa tu nombre.',
            'requested_email.required' => 'Ingresa un correo electrónico.',
            'requested_email.email' => 'Ingresa un correo válido.',
            'company_name.required' => 'Ingresa el nombre de tu empresa.',
            'phone_whatsapp.required' => 'Ingresa un teléfono o WhatsApp.',
            'phone_whatsapp.regex' => 'Ingresa el teléfono en formato internacional, por ejemplo +5492991234567.',
        ]);

        SignupRequest::create([
            'requested_name' => $data['requested_name'],
            'requested_email' => $data['requested_email'],
            'company_name' => $data['company_name'],
            'phone_whatsapp' => $data['phone_whatsapp'],
            'status' => 'pending',
        ]);

        return redirect()->route('public.signup-requests.thank-you');
    }
}
