<?php

// FILE: app/Policies/AppointmentPolicy.php | V2

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
        return $this->resolver()->can(
            ModuleCatalog::APPOINTMENTS,
            'view_any',
            app('tenant'),
            $user
        );
    }

    public function view(User $user, Appointment $appointment): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::APPOINTMENTS,
            'view',
            app('tenant'),
            $user
        );

        return $this->allowsAppointmentScope($scope, $appointment, $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::APPOINTMENTS,
            'create',
            app('tenant'),
            $user
        );
    }

    public function update(User $user, Appointment $appointment): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::APPOINTMENTS,
            'update',
            app('tenant'),
            $user
        );

        return $this->allowsAppointmentScope($scope, $appointment, $user);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::APPOINTMENTS,
            'delete',
            app('tenant'),
            $user
        );

        return $this->allowsAppointmentScope($scope, $appointment, $user);
    }

    protected function allowsAppointmentScope(mixed $scope, Appointment $appointment, User $user): bool
    {
        if (in_array($scope, [true, 'tenant_all', 'all'], true)) {
            return true;
        }

        if (in_array($scope, ['own_assigned', 'limited'], true)) {
            return (int) $appointment->assigned_user_id === (int) $user->id;
        }

        return false;
    }
}
