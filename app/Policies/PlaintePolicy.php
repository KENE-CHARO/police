<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plainte;
use App\Models\Enquete;

class PlaintePolicy
{
    public function view(User $user, Plainte $plainte)
    {
        if ($user->id === $plainte->plaignant_id || $user->roles()->where('name', 'admin')->exists()) {
            return true;
        }

        // allow assigned enqueteur to view
        if ($user->roles()->where('name', 'enqueteur')->exists()) {
            return Enquete::where('plainte_id', $plainte->id)->where('enqueteur_id', $user->id)->exists();
        }

        return false;
    }

    public function update(User $user, Plainte $plainte)
    {
        return $user->id === $plainte->plaignant_id || $user->roles()->where('name', 'admin')->exists();
    }

    public function delete(User $user, Plainte $plainte)
    {
        return $user->id === $plainte->plaignant_id || $user->roles()->where('name', 'admin')->exists();
    }

    public function create(User $user)
    {
        return true;
    }
}
