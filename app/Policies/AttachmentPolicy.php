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
            // agent_accueil can download any attachment for a plainte
            if ($user->roles()->where('name', 'agent_accueil')->exists()) {
                return true;
            }

            // enqueteur can download only if assigned to the plainte
            if ($user->roles()->where('name', 'enqueteur')->exists()) {
                $plainte = $attachment->attachable;
                if ($plainte && \App\Models\Enquete::where('plainte_id', $plainte->id)->where('enqueteur_id', $user->id)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }
}
