<?php

// FILE: app/Policies/AttachmentPolicy.php | V7

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

        return $user->can('update', $attachable);
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if (! $attachable) {
            return false;
        }

        return $user->can('update', $attachable);
    }
}
