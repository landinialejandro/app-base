<?php

// FILE: app/Policies/DocumentPolicy.php | V7

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\Auth\Security;
use App\Support\Auth\TenantAccess;
use App\Support\Catalogs\DocumentCatalog;

class DocumentPolicy
{
    protected function security(): Security
    {
        return app(Security::class);
    }

    public function viewAny(User $user): bool
    {
        return $this->security()->allows($user, 'documents.viewAny');
    }

    public function view(User $user, Document $document): bool
    {
        return $this->security()->allows($user, 'documents.view', $document);
    }

    public function create(User $user): bool
    {
        return $this->security()->allows($user, 'documents.create', Document::class);
    }

    public function update(User $user, Document $document): bool
    {
        if (DocumentCatalog::isReadonlyStatus($document->status)) {
            return false;
        }

        return $this->security()->allows($user, 'documents.update', $document);
    }

    public function delete(User $user, Document $document): bool
    {
        if (DocumentCatalog::isReadonlyStatus($document->status)) {
            return false;
        }

        return $this->security()->allows($user, 'documents.delete', $document);
    }

    public function changeStatus(User $user, Document $document): bool
    {
        if ($document->status === DocumentCatalog::STATUS_CLOSED) {
            return TenantAccess::isOwner($document->tenant_id, $user);
        }

        if ($document->status === DocumentCatalog::STATUS_CANCELLED) {
            return false;
        }

        return $this->security()->allows($user, 'documents.update', $document);
    }
}
