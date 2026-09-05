<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attachment;

class AttachmentPolicy
{
    public function download(User $user, Attachment $attachment)
    {
        // allow if uploader
        if ($attachment->uploaded_by === $user->id) {
            return true;
        }

        // If attachment belongs to a Plainte, allow the plaignant and personnel to download
        if ($attachment->attachable_type === 'App\\Models\\Plainte') {
            // plaignant
            if ($attachment->attachable->plaignant_id === $user->id) {
                return true;
            }

            // personnel (agent_accueil, enqueteur)
            if ($user->roles()->whereIn('name', ['agent_accueil', 'enqueteur'])->exists()) {
                return true;
            }
        }

        return false;
    }
}
