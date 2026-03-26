<?php

// FILE: app/Policies/AttachmentPolicy.php | V3

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

        if (! $user->can('view', $attachable)) {
            return false;
        }

        if ($user->can('delete', $attachable)) {
            return true;
        }

        return (string) $attachment->uploaded_by_user_id === (string) $user->id;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if (! $attachable) {
            return false;
        }

        if (! $user->can('view', $attachable)) {
            return false;
        }

        if ($user->can('delete', $attachable)) {
            return true;
        }

        return (string) $attachment->uploaded_by_user_id === (string) $user->id;
    }
}
