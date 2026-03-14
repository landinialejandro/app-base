<?php

// FILE: app/Http/Controllers/AdminMetricsController.php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AdminMetricsController extends Controller
{
    public function owners()
    {
        $owners = User::query()
            ->whereHas('memberships', function (Builder $query) {
                $query->where('is_owner', true);
            })
            ->with([
                'memberships' => function ($query) {
                    $query->where('is_owner', true)
                        ->with('tenant')
                        ->orderBy('tenant_id');
                },
            ])
            ->withCount([
                'memberships as owner_tenants_count' => function ($query) {
                    $query->where('is_owner', true);
                },
            ])
            ->orderByDesc('owner_tenants_count')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.metrics.owners', [
            'owners' => $owners,
        ]);
    }

    public function tenants()
    {
        $tenants = Tenant::query()
            ->withCount([
                'memberships as users_count',
                'memberships as owners_count' => function ($query) {
                    $query->where('is_owner', true);
                },
            ])
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.metrics.tenants', [
            'tenants' => $tenants,
        ]);
    }
}