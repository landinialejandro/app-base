<?php

// FILE: app/Http/Controllers/TenantMembershipRoleController.php | V4

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\Role;
use App\Support\Catalogs\RoleCatalog;
use App\Support\Tenants\TenantProfileAccess;
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

        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($data['role_id']);

        if (! RoleCatalog::isAssignable($role->slug)) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'Ese rol no se puede asignar desde esta pantalla.');
        }

        $actorMembership = $request->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->first();

        $tenantProfileAccess = app(TenantProfileAccess::class);

        abort_unless(
            $tenantProfileAccess->canAssignRole($actorMembership, $membership, $role->slug),
            403
        );

        $membership->load('roles');

        $currentRoleSlugs = $membership->roles
            ->pluck('slug')
            ->filter()
            ->values();

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

        $actorMembership = $request->user()
            ->memberships()
            ->where('tenant_id', $tenant->id)
            ->with('roles')
            ->first();

        $tenantProfileAccess = app(TenantProfileAccess::class);

        abort_unless(
            $tenantProfileAccess->canDetachRole($actorMembership, $membership, $role),
            403
        );

        $membership->load('roles');

        $assignedRoleIds = $membership->roles->pluck('id')->all();

        if (! in_array($role->id, $assignedRoleIds, true)) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'Ese rol no está asignado a este usuario.');
        }

        if ($membership->status === 'active' && count($assignedRoleIds) === 1) {
            return redirect()
                ->route('tenant.profile.show', ['tab' => 'accesses'])
                ->with('error', 'La membership activa debe conservar al menos un rol.');
        }

        $membership->roles()
            ->newPivotStatement()
            ->where('membership_id', $membership->id)
            ->where('role_id', $role->id)
            ->whereNull('branch_id')
            ->delete();

        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('success', 'Rol quitado correctamente.');
    }


public function sync(Request $request, Membership $membership)
{
    $tenant = app('tenant');

    $this->authorize('attachRole', $membership);

    $data = $request->validate([
        'roles' => ['nullable', 'array'],
        'roles.*' => ['integer'],
    ]);

    $requestedRoleIds = collect($data['roles'] ?? [])
        ->map(fn ($roleId) => (int) $roleId)
        ->unique()
        ->values();

    $requestedRoles = Role::query()
        ->where('tenant_id', $tenant->id)
        ->whereIn('id', $requestedRoleIds)
        ->get();

    if ($requestedRoles->count() !== $requestedRoleIds->count()) {
        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('error', 'Uno o más roles seleccionados no pertenecen a esta empresa.');
    }

    $invalidRole = $requestedRoles->first(fn ($role) => ! RoleCatalog::isAssignable($role->slug));

    if ($invalidRole) {
        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('error', 'Uno o más roles seleccionados no se pueden asignar desde esta pantalla.');
    }

    $membership->load('roles');

    $currentRoles = $membership->roles;

    $currentRoleIds = $currentRoles
        ->pluck('id')
        ->map(fn ($roleId) => (int) $roleId)
        ->values();

    $actorMembership = $request->user()
        ->memberships()
        ->where('tenant_id', $tenant->id)
        ->with('roles')
        ->first();

    $tenantProfileAccess = app(TenantProfileAccess::class);

    $rolesToAdd = $requestedRoles
        ->filter(fn ($role) => ! $currentRoleIds->contains((int) $role->id))
        ->values();

    foreach ($rolesToAdd as $role) {
        abort_unless(
            $tenantProfileAccess->canAssignRole($actorMembership, $membership, $role->slug),
            403
        );
    }

    $roleIdsToRemove = $currentRoleIds
        ->diff($requestedRoleIds)
        ->values();

    $rolesToRemove = $currentRoles
        ->filter(fn ($role) => $roleIdsToRemove->contains((int) $role->id))
        ->values();

    foreach ($rolesToRemove as $role) {
        abort_unless(
            $tenantProfileAccess->canDetachRole($actorMembership, $membership, $role),
            403
        );
    }

    $requestedSlugs = $requestedRoles
        ->pluck('slug')
        ->filter()
        ->values();

    if ($requestedSlugs->contains(RoleCatalog::ADMIN) && $requestedSlugs->count() > 1) {
        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('error', 'El rol Administrador es exclusivo y no puede combinarse con otros roles.');
    }

    if ($membership->status === 'active' && $requestedRoleIds->isEmpty()) {
        return redirect()
            ->route('tenant.profile.show', ['tab' => 'accesses'])
            ->with('error', 'La membership activa debe conservar al menos un rol.');
    }

    $syncPayload = $requestedRoleIds
        ->mapWithKeys(fn ($roleId) => [
            $roleId => ['branch_id' => null],
        ])
        ->all();

    $membership->roles()->sync($syncPayload);

    return redirect()
        ->route('tenant.profile.show', ['tab' => 'accesses'])
        ->with('success', 'Funciones actualizadas correctamente.');
}
}