<?php

// FILE: app/Http/Controllers/SuperadminDashboardController.php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Membership;
use App\Models\SignupRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuperadminDashboardController extends Controller
{
    public function index()
    {
        $tenantUserCounts = Membership::query()
            ->select('tenant_id', DB::raw('COUNT(*) as total_users'))
            ->groupBy('tenant_id')
            ->get();

        $ownersPerUser = Membership::query()
            ->select('user_id', DB::raw('COUNT(*) as total_owner_tenants'))
            ->where('is_owner', true)
            ->groupBy('user_id')
            ->get();

        $usersPerTenantCount = $tenantUserCounts->count();

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

            'ownersCount' => Membership::query()
                ->where('is_owner', true)
                ->distinct('user_id')
                ->count('user_id'),

            'multiTenantOwnersCount' => $ownersPerUser
                ->where('total_owner_tenants', '>', 1)
                ->count(),

            'sharedUsersCount' => Membership::query()
                ->select('user_id', DB::raw('COUNT(*) as total_tenants'))
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),

            'singleUserTenantsCount' => $tenantUserCounts
                ->where('total_users', 1)
                ->count(),

            'multiUserTenantsCount' => $tenantUserCounts
                ->where('total_users', '>', 1)
                ->count(),

            'averageUsersPerTenant' => $usersPerTenantCount > 0
                ? round($tenantUserCounts->avg('total_users'), 1)
                : 0,
        ]);
    }
}