<?php

// FILE: app/Http/Controllers/TenantMembershipController.php | V2

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Role;
use App\Support\Catalogs\RoleCatalog;
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

        $this->ensureOperationalRole($membership);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Acceso del usuario rehabilitado para esta empresa.');
    }

    protected function ensureOperationalRole(Membership $membership): void
    {
        $membership->loadMissing('roles');

        if ($membership->is_owner || $membership->roles->isNotEmpty()) {
            return;
        }

        $defaultRole = Role::query()
            ->where('tenant_id', $membership->tenant_id)
            ->where('slug', RoleCatalog::defaultOperational())
            ->first();

        if (! $defaultRole) {
            return;
        }

        $membership->roles()->syncWithoutDetaching([
            $defaultRole->id => [
                'branch_id' => null,
            ],
        ]);
    }
}
