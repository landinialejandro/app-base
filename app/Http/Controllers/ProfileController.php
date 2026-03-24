<?php

// FILE: app/Http/Controllers/ProfileController.php | V1

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 403);

        if ($user->is_superadmin) {
            return view('profile.show');
        }

        return redirect()->route('profile.tenant.show');
    }

    public function showTenant(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 403);

        if ($user->is_superadmin) {
            return redirect()->route('profile.show');
        }

        abort_unless(app()->bound('tenant'), 403);

        return view('profile.show');
    }
}
