<?php

// FILE: app/Policies/DocumentPolicy.php | V3

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\Auth\RolePermissionResolver;
use App\Support\Catalogs\ModuleCatalog;

class DocumentPolicy
{
    protected function resolver(): RolePermissionResolver
    {
        return app(RolePermissionResolver::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->resolver()->canUseModule(ModuleCatalog::DOCUMENTS, app('tenant'), $user);
    }

    public function view(User $user, Document $document): bool
    {
        if (! $this->resolver()->canUseModule(ModuleCatalog::DOCUMENTS, app('tenant'), $user)) {
            return false;
        }

        if ($document->order) {
            return $user->can('view', $document->order);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::DOCUMENTS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Document $document): bool
    {
        if (! $this->resolver()->can(ModuleCatalog::DOCUMENTS, 'update', app('tenant'), $user)) {
            return false;
        }

        if ($document->order) {
            return $user->can('update', $document->order);
        }

        return true;
    }

    public function delete(User $user, Document $document): bool
    {
        if (! $this->resolver()->can(ModuleCatalog::DOCUMENTS, 'delete', app('tenant'), $user)) {
            return false;
        }

        if ($document->order) {
            return $user->can('delete', $document->order);
        }

        return true;
    }
}
