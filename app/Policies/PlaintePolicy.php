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

        // Agent d'accueil can view all plaintes
        if ($user->roles()->where('name', 'agent_accueil')->exists()) {
            return true;
        }

        // Enqueteur can view only plaintes that are assigned to them via an Enquete
        if ($user->roles()->where('name', 'enqueteur')->exists()) {
            return Enquete::where('plainte_id', $plainte->id)->where('enqueteur_id', $user->id)->exists();
        }

        return false;
    }

    public function update(User $user, Plainte $plainte)
    {
        // Only the plaignant may update a plainte, but allow agent_accueil
        // and an enqueteur assigned to this plainte to update (upload attachments)
        if ($user->id === $plainte->plaignant_id) {
            return true;
        }

        if ($user->roles()->where('name', 'agent_accueil')->exists()) {
            return true;
        }

        if ($user->roles()->where('name', 'enqueteur')->exists()) {
            return \App\Models\Enquete::where('plainte_id', $plainte->id)->where('enqueteur_id', $user->id)->exists();
        }

        return false;
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
