<?php

// FILE: app/Http/Controllers/SuperadminDashboardController.php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\SignupRequest;
use App\Models\Tenant;
use App\Models\User;

class SuperadminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'tenantsCount' => Tenant::count(),
            'usersCount' => User::count(),
            'pendingSignupRequestsCount' => SignupRequest::where('status', 'pending')->count(),
            'processedSignupRequestsCount' => SignupRequest::whereIn('status', ['approved', 'rejected'])->count(),
            'ownerInvitationsToSendCount' => Invitation::where('type', 'owner_signup')
                ->whereNull('sent_at')
                ->whereNull('accepted_at')
                ->count(),
            'pendingOwnerInvitationsCount' => Invitation::where('type', 'owner_signup')
                ->whereNotNull('sent_at')
                ->whereNull('accepted_at')
                ->count(),
        ]);
    }
}