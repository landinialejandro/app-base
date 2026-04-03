<?php

// FILE: app/Support/Auth/RoleModuleAccess.php | V3

namespace App\Support\Auth;

use App\Models\Tenant;
use App\Models\User;

class RoleModuleAccess
{
    public static function canAccess(string $module, ?Tenant $tenant = null, ?User $user = null): bool
    {
        return app(RolePermissionResolver::class)
            ->canUseModule($module, $tenant, $user);
    }
}
