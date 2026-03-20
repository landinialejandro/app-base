<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class AppointmentPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::APPOINTMENTS, app('tenant'), $user);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if (! $this->resolver()->canUseModule(ModuleCatalog::APPOINTMENTS, app('tenant'), $user)) {
            return false;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::APPOINTMENTS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        $scope = $this->resolver()->actionScope(ModuleCatalog::APPOINTMENTS, 'update', app('tenant'), $user);

        if ($scope === 'all') {
            return true;
        }

        if ($scope === 'own_assigned') {
            return (int) $appointment->assigned_user_id === (int) $user->id;
        }

        return false;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->resolver()->can(ModuleCatalog::APPOINTMENTS, 'delete', app('tenant'), $user);
    }
}
