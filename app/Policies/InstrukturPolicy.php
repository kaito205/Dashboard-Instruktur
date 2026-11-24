<?php

namespace App\Policies;

use App\Models\Instruktur;
use App\Models\User;

class InstrukturPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin', 'supervisor');
    }

    public function view(User $user, Instruktur $instruktur): bool
    {
        if ($user->hasRole('admin', 'supervisor')) {
            return true;
        }

        return $user->isInstruktur() && $instruktur->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Instruktur $instruktur): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Instruktur hanya diperbolehkan mengubah profil miliknya sendiri
        return $user->isInstruktur() && $instruktur->user_id === $user->id;
    }

    public function toggleStatus(User $user, Instruktur $instruktur): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Instruktur $instruktur): bool
    {
        return $user->isAdmin();
    }
}
