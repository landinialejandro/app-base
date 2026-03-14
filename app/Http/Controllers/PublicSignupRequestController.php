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
            'requested_email' => ['required', 'email', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'phone_whatsapp' => ['required', 'string', 'max:50'],
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
