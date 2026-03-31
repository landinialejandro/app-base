<?php
// FILE: app/Http/Controllers/TenantMembershipRoleController.php | V3

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Role;
use App\Support\Catalogs\RoleCatalog;
use Illuminate\Http\Request;

class TenantMembershipRoleController extends Controller
{
    public function attach(Request $request, Membership $membership)
    {
        $tenant = app('tenant');

        $this->authorize('attachRole', $membership);

        $data = $request->validate([
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::query()->where('tenant_id', $tenant->id)->findOrFail($data['role_id']);

        if (!RoleCatalog::isAssignable($role->slug)) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'Ese rol no se puede asignar desde esta pantalla.');
        }

        $membership->load('roles');

        $currentRoleSlugs = $membership->roles->pluck('slug')->filter()->values();

        if ($currentRoleSlugs->contains($role->slug)) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'Ese rol ya está asignado a este usuario.');
        }

        if (RoleCatalog::isExclusive($role->slug)) {
            $membership->roles()->sync([
                $role->id => ['branch_id' => null],
            ]);

            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('success', 'Rol exclusivo asignado correctamente.');
        }

        if ($currentRoleSlugs->contains(RoleCatalog::ADMIN)) {
            $membership->roles()->sync([
                $role->id => ['branch_id' => null],
            ]);

            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('success', 'Rol reemplazado correctamente.');
        }

        $membership->roles()->syncWithoutDetaching([
            $role->id => ['branch_id' => null],
        ]);

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('success', 'Rol asignado correctamente.');
    }

    public function detach(Request $request, Membership $membership, Role $role)
    {
        $tenant = app('tenant');

        $this->authorize('detachRole', $membership);

        abort_unless($role->tenant_id === $tenant->id, 404);

        $membership->load('roles');

        $assignedRoleIds = $membership->roles->pluck('id')->all();

        if (!in_array($role->id, $assignedRoleIds, true)) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'Ese rol no está asignado a este usuario.');
        }

        if ($membership->status === 'active' && count($assignedRoleIds) === 1) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'La membership activa debe conservar al menos un rol.');
        }

        $membership->roles()->newPivotStatement()->where('membership_id', $membership->id)->where('role_id', $role->id)->whereNull('branch_id')->delete();

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('success', 'Rol quitado correctamente.');
    }
}
