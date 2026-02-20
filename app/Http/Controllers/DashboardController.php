<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $data = [
            'user' => $user,
        ];
        
        // Datos específicos por rol
        if ($user->is_platform_admin) {
            $data['total_organizations'] = \App\Models\Organization::count();
            $data['total_users'] = \App\Models\User::count();
        } elseif ($user->role === 'admin') {
            $data['organization_users'] = $user->organization->users()->count();
            $data['pending_approvals'] = $user->organization->users()
                ->whereNull('approved_at')
                ->count();
            $data['pending_deletions'] = $user->organization->users()
                ->whereNotNull('deletion_requested_at')
                ->where('deletion_approved', false)
                ->count();
        }
        
        return view('dashboard', $data);
    }
}