<?php

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
        return $this->resolver()->canUseModule(ModuleCatalog::DOCUMENTS, app('tenant'), $user);
    }

    public function create(User $user): bool
    {
        return $this->resolver()->can(ModuleCatalog::DOCUMENTS, 'create', app('tenant'), $user);
    }

    public function update(User $user, Document $document): bool
    {
        return $this->resolver()->can(ModuleCatalog::DOCUMENTS, 'update', app('tenant'), $user);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->resolver()->can(ModuleCatalog::DOCUMENTS, 'delete', app('tenant'), $user);
    }
}
