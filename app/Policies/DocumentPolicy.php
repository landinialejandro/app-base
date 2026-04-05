<?php

// FILE: app/Policies/DocumentPolicy.php | V6

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use App\Support\Auth\Security;

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
        return $this->security()->allows($user, 'documents.update', $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->security()->allows($user, 'documents.delete', $document);
    }
}
