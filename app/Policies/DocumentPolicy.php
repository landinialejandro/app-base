<?php

// FILE: app/Policies/DocumentPolicy.php | V4

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\CapabilityCatalog;
use App\Support\Catalogs\ModuleCatalog;

class DocumentPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->actionScope(
            ModuleCatalog::DOCUMENTS,
            CapabilityCatalog::VIEW_ANY,
            app('tenant'),
            $user,
        ) !== false;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->resolver()->actionScope(
            ModuleCatalog::DOCUMENTS,
            CapabilityCatalog::VIEW,
            app('tenant'),
            $user,
        ) !== false;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::DOCUMENTS,
            CapabilityCatalog::CREATE,
            app('tenant'),
            $user,
        );
    }

    public function update(User $user, Document $document): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::DOCUMENTS,
            CapabilityCatalog::UPDATE,
            app('tenant'),
            $user,
        );
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->resolver()->can(
            ModuleCatalog::DOCUMENTS,
            CapabilityCatalog::DELETE,
            app('tenant'),
            $user,
        );
    }
}
