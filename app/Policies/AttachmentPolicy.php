<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attachment;

class AttachmentPolicy
{
    public function download(User $user, Attachment $attachment)
    {
        if ($user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        // allow if uploader or owner of the attachable when it's a Plainte
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        if ($attachment->attachable_type === 'App\\Models\\Plainte') {
            return $attachment->attachable->plaignant_id === $user->id;
        }

        return false;
    }
}
