<?php

// FILE: app/Policies/AttachmentPolicy.php | V2

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if (! $attachable) {
            return false;
        }

        return $user->can('view', $attachable);
    }

    public function update(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if (! $attachable) {
            return false;
        }

        if ($user->can('update', $attachable)) {
            return true;
        }

        return (string) $attachment->uploaded_by_user_id === (string) $user->id
            && $user->can('view', $attachable);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if (! $attachable) {
            return false;
        }

        if ($user->can('delete', $attachable)) {
            return true;
        }

        return (string) $attachment->uploaded_by_user_id === (string) $user->id
            && $user->can('view', $attachable);
    }
}
