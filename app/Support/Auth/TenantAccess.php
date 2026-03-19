<?php

namespace App\Support\Auth;

use App\Models\Membership;
use App\Models\User;
use App\Support\Catalogs\RoleCatalog;

class TenantAccess
{
    public static function membership(string $tenantId, ?User $user = null): ?Membership
    {
        $user = $user ?: auth()->user();

        if (! $user) {
            return null;
        }

        return $user->memberships()
            ->with('roles')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();
    }

    public static function belongsToTenant(string $tenantId, int|string $userId): bool
    {
        return Membership::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }

    public static function isOwner(string $tenantId, ?User $user = null): bool
    {
        $membership = static::membership($tenantId, $user);

        return (bool) $membership?->is_owner;
    }

    public static function hasRole(string $tenantId, string $roleSlug, ?User $user = null): bool
    {
        $membership = static::membership($tenantId, $user);

        if (! $membership) {
            return false;
        }

        if ($membership->is_owner) {
            return $roleSlug === RoleCatalog::OWNER;
        }

        return $membership->roles->contains(fn ($role) => $role->slug === $roleSlug);
    }

    public static function isAdmin(string $tenantId, ?User $user = null): bool
    {
        $membership = static::membership($tenantId, $user);

        if (! $membership) {
            return false;
        }

        if ($membership->is_owner) {
            return true;
        }

        return $membership->roles->contains(fn ($role) => $role->slug === RoleCatalog::ADMIN);
    }

    public static function isOwnerOrAdmin(string $tenantId, ?User $user = null): bool
    {
        return static::isAdmin($tenantId, $user);
    }

    public static function roleSlugs(string $tenantId, ?User $user = null): array
    {
        $membership = static::membership($tenantId, $user);

        if (! $membership) {
            return [];
        }

        if ($membership->is_owner) {
            return [RoleCatalog::OWNER];
        }

        return $membership->roles
            ->pluck('slug')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
