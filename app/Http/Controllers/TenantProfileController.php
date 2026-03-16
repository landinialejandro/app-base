<?php

// FILE: app/Http/Controllers/TenantProfileController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    public function show()
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        return view('tenants.profile', [
            'tenant' => $tenant,
        ]);
    }

    public function update(Request $request)
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],

            'settings.legal_name' => ['nullable', 'string', 'max:255'],
            'settings.tax_id' => ['nullable', 'string', 'max:50'],
            'settings.email' => ['nullable', 'email', 'max:255'],
            'settings.phone' => ['nullable', 'string', 'max:100'],

            'settings.address' => ['nullable', 'string', 'max:255'],
            'settings.city' => ['nullable', 'string', 'max:150'],
            'settings.state' => ['nullable', 'string', 'max:150'],
            'settings.country' => ['nullable', 'string', 'max:150'],
        ]);

        $tenant->update([
            'name' => $data['name'],
            'settings' => array_merge(
                $tenant->settings ?? [],
                $data['settings'] ?? []
            ),
        ]);

        return redirect()
            ->route('tenant.profile.show')
            ->with('success', 'Perfil de empresa actualizado correctamente.');
    }
}
