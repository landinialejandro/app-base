<?php

// FILE: app/Http/Controllers/TenantProfileController.php | V4

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Role;
use App\Support\Catalogs\BusinessTypeCatalog;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TenantProfileController extends Controller
{
    public function show(Request $request)
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        $memberships = Membership::query()
            ->where('tenant_id', $tenant->id)
            ->with([
                'user',
                'roles' => function ($query) {
                    $query->orderBy('name');
                },
            ])
            ->orderByDesc('is_owner')
            ->orderBy('status')
            ->orderBy('id')
            ->get();

        $availableRoles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('slug', RoleCatalog::assignable())
            ->orderByRaw('
                CASE slug
                    WHEN ? THEN 1
                    WHEN ? THEN 2
                    WHEN ? THEN 3
                    ELSE 99
                END
            ', [
                RoleCatalog::ADMIN,
                RoleCatalog::SALES,
                RoleCatalog::OPERATOR,
            ])
            ->orderBy('name')
            ->get();

        $activeTab = $request->query('tab', 'general');

        if (! in_array($activeTab, ['general', 'users', 'accesses'], true)) {
            $activeTab = 'general';
        }

        $generatedInvitation = null;

        $generatedInvitationId = session('generated_invitation_id');

        if ($generatedInvitationId) {
            $generatedInvitation = Invitation::query()
                ->where('tenant_id', $tenant->id)
                ->where('type', 'member_invite')
                ->where('id', $generatedInvitationId)
                ->first();
        }

        $pendingInvitations = Invitation::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', 'member_invite')
            ->whereNull('accepted_at')
            ->orderByDesc('created_at')
            ->get();

        return view('tenants.profile', [
            'tenant' => $tenant,
            'memberships' => $memberships,
            'availableRoles' => $availableRoles,
            'activeTab' => $activeTab,
            'generatedInvitation' => $generatedInvitation,
            'pendingInvitations' => $pendingInvitations,
            'businessTypeLabels' => BusinessTypeCatalog::labels(),
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

            'settings.business_profile.type' => [
                'nullable',
                'string',
                Rule::in(BusinessTypeCatalog::all()),
            ],
        ]);

        $tenant->update([
            'name' => $data['name'],
            'settings' => array_replace_recursive(
                $tenant->settings ?? [],
                $data['settings'] ?? []
            ),
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'general'])
            ->with('success', 'Perfil de empresa actualizado correctamente.');
    }
}
