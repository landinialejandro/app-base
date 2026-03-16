<?php

// FILE: app/Http/Controllers/TenantMembershipController.php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;

class TenantMembershipController extends Controller
{
    protected function getOwnerMembership()
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        return $membership;
    }

    public function block(Request $request, Membership $membership)
    {
        $tenant = app('tenant');
        $ownerMembership = $this->getOwnerMembership();

        abort_unless($membership->tenant_id === $tenant->id, 404);

        if ($membership->is_owner) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'users'])
                ->with('error', 'No se puede bloquear un owner desde esta pantalla.');
        }

        if ($membership->user_id === $ownerMembership->user_id) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'users'])
                ->with('error', 'No puedes bloquear tu propia membership.');
        }

        $membership->update([
            'status' => 'blocked',
            'blocked_at' => now(),
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Acceso del usuario inhabilitado para esta empresa.');
    }

    public function unblock(Request $request, Membership $membership)
    {
        $tenant = app('tenant');
        $this->getOwnerMembership();

        abort_unless($membership->tenant_id === $tenant->id, 404);

        $membership->update([
            'status' => 'active',
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Acceso del usuario rehabilitado para esta empresa.');
    }
}
