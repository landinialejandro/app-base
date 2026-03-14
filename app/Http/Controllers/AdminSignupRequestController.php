<?php

// FILE: app/Http/Controllers/AdminSignupRequestController.php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\SignupRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminSignupRequestController extends Controller
{
    public function index()
    {
        $signupRequests = SignupRequest::query()
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return view('admin.signup-requests.index', [
            'signupRequests' => $signupRequests,
        ]);
    }

    public function processed()
    {
        $signupRequests = SignupRequest::query()
            ->whereIn('status', ['approved', 'rejected'])
            ->latest()
            ->paginate(20);

        return view('admin.signup-requests.processed', [
            'signupRequests' => $signupRequests,
        ]);
    }

    public function show(SignupRequest $signupRequest)
    {
        $ownerInvitation = Invitation::query()
            ->where('signup_request_id', $signupRequest->id)
            ->where('type', 'owner_signup')
            ->latest()
            ->first();

        return view('admin.signup-requests.show', [
            'signupRequest' => $signupRequest,
            'ownerInvitation' => $ownerInvitation,
        ]);
    }

    public function approve(SignupRequest $signupRequest)
    {
        if ($signupRequest->status !== 'pending') {
            return redirect()
                ->route('admin.signup-requests.show', $signupRequest)
                ->with('error', 'La solicitud ya fue procesada.');
        }

        DB::transaction(function () use ($signupRequest) {
            $signupRequest->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            Invitation::create([
                'tenant_id' => null,
                'type' => 'owner_signup',
                'status' => 'pending',
                'email' => $signupRequest->requested_email,
                'token' => Str::random(64),
                'signup_request_id' => $signupRequest->id,
                'invited_by_user_id' => auth()->id(),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
                'accepted_ip' => null,
                'user_agent' => 'Superadmin',
                'meta' => [
                    'requested_name' => $signupRequest->requested_name,
                    'company_name' => $signupRequest->company_name,
                    'source' => 'signup_request_approval',
                ],
            ]);
        });

        return redirect()
            ->route('admin.signup-requests.show', $signupRequest)
            ->with('success', 'Solicitud aprobada e invitación generada correctamente.');
    }

    public function reject(Request $request, SignupRequest $signupRequest)
    {
        if ($signupRequest->status !== 'pending') {
            return redirect()
                ->route('admin.signup-requests.show', $signupRequest)
                ->with('error', 'La solicitud ya fue procesada.');
        }

        $data = $request->validate([
            'review_notes' => ['nullable', 'string'],
        ]);

        $signupRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'review_notes' => $data['review_notes'] ?? null,
        ]);

        return redirect()
            ->route('admin.signup-requests.show', $signupRequest)
            ->with('success', 'Solicitud rechazada correctamente.');
    }
}
