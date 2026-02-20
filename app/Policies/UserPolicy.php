<?php
// app/Policies/UserPolicy.php

namespace App\Policies;

use App\Models\User;

class UserPolicy {
    public function approveDeletion(User $authenticated, User $targetUser): bool {
        // Admin de la misma organización puede aprobar bajas
        return $authenticated->organization_id === $targetUser->organization_id
            && ($authenticated->role === 'admin' || $authenticated->is_platform_admin);
    }

    public function viewDeletionRequests(User $authenticated, User $targetUser): bool {
        // Solo admin de la organización o superadmin
        return $authenticated->organization_id === $targetUser->organization_id
            && ($authenticated->role === 'admin' || $authenticated->is_platform_admin);
    }
}
