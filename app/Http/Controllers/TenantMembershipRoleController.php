<?php

// FILE: app/Http/Controllers/TenantMembershipRoleController.php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Role;
use Illuminate\Http\Request;

class TenantMembershipRoleController extends Controller
{
    protected function getOwnerMembership(): Membership
    {
        $tenant = app('tenant');

        $membership = auth()->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->first();

        abort_unless($membership?->is_owner, 403);

        return $membership;
    }

    public function attach(Request $request, Membership $membership)
    {
        $tenant = app('tenant');
        $ownerMembership = $this->getOwnerMembership();

        abort_unless($membership->tenant_id === $tenant->id, 404);

        $data = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['role_id']);

        if ($membership->user_id === $ownerMembership->user_id && $membership->is_owner) {
            // Permitido asignar roles al owner si querés; si no, podés bloquearlo.
            // De momento lo dejamos permitido.
        }

        $alreadyAssigned = $membership->roles()
            ->where('roles.id', $role->id)
            ->wherePivot('branch_id', null)
            ->exists();

        if ($alreadyAssigned) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'users'])
                ->with('error', 'Ese rol ya está asignado a este usuario.');
        }

        $membership->roles()->attach($role->id, [
            'branch_id' => null,
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Rol asignado correctamente.');
    }

    public function detach(Request $request, Membership $membership, Role $role)
    {
        $tenant = app('tenant');
        $ownerMembership = $this->getOwnerMembership();

        abort_unless($membership->tenant_id === $tenant->id, 404);
        abort_unless($role->tenant_id === $tenant->id, 404);

        if ($membership->user_id === $ownerMembership->user_id && $membership->is_owner) {
            // Permitido quitar roles extra al owner; no afecta is_owner.
            // Si después querés endurecerlo, se ajusta.
        }

        $membership->roles()
            ->newPivotStatement()
            ->where('membership_id', $membership->id)
            ->where('role_id', $role->id)
            ->whereNull('branch_id')
            ->delete();

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'users'])
            ->with('success', 'Rol quitado correctamente.');
    }
}
