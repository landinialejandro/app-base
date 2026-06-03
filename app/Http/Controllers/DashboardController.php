<?php

// FILE: app/Http/Controllers/DashboardController.php | V15

namespace App\Http\Controllers;

use App\Support\Dashboard\TenantDashboardSectionBuilder;

class DashboardController extends Controller
{
    public function index()
    {
        $tenant = app('tenant');
        $user = auth()->user();

        $dashboardSections = TenantDashboardSectionBuilder::for($tenant, $user);

        return view('dashboard', [
            'tenant' => $tenant,
            'dashboardSections' => $dashboardSections,
        ]);
    }
}
