<?php

// FILE: app/Policies/AppointmentPolicy.php | V3

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Support\Auth\RecordScopeResolver;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;
use App\Support\Catalogs\PermissionScopeCatalog;

class AppointmentPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    protected function recordScopeResolver(): RecordScopeResolver
    {
        return app(RecordScopeResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::APPOINTMENTS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user
        );
    }

    public function view(User $user, Appointment $appointment): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::APPOINTMENTS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user
        );

        if (! in_array($scope, [PermissionScopeCatalog::ALL, PermissionScopeCatalog::OWN_ASSIGNED], true)) {
            return false;
        }

        return $this->recordScopeResolver()->allowsAssignedUserScope($scope, $appointment, $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::APPOINTMENTS,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user
        );
    }

    public function update(User $user, Appointment $appointment): bool
    {
        $scope = $this->resolver()->actionScope(
            ModuleCatalog::APPOINTMENTS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user
        );

        if (! in_array($scope, [PermissionScopeCatalog::ALL, PermissionScopeCatalog::OWN_ASSIGNED], true)) {
            return false;
        }

        return $this->recordScopeResolver()->allowsAssignedUserScope($scope, $appointment, $user);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::APPOINTMENTS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user
        );
    }
}
