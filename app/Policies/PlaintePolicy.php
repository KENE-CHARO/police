<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plainte;
use App\Models\Enquete;

class PlaintePolicy
{
    public function view(User $user, Plainte $plainte)
    {
        // Plaignant always can view their own plainte
        if ($user->id === $plainte->plaignant_id) {
            return true;
        }

        // Personnel (agent_accueil, enqueteur) can view all plaintes, admin should not see details
        if ($user->roles()->whereIn('name', ['agent_accueil', 'enqueteur'])->exists()) {
            return true;
        }

        return false;
    }

    public function update(User $user, Plainte $plainte)
    {
        // Only the plaignant may update a plainte
        return $user->id === $plainte->plaignant_id;
    }

    public function delete(User $user, Plainte $plainte)
    {
        // Only the plaignant may delete their plainte
        return $user->id === $plainte->plaignant_id;
    }

    public function create(User $user)
    {
        // Only citizens may create plaintes
        return $user->roles()->where('name', 'citoyen')->exists();
    }
}
