<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    public function create(User $authUser): bool
    {
        return $authUser->isAdmin();
    }

    public function update(User $authUser, User $targetUser): bool
    {
        if ($authUser->isAdmin()) {
            return true;
        }

        return $authUser->id === $targetUser->id;
    }

    public function changeRole(User $authUser, User $targetUser): bool
    {
        // Pengguna tidak boleh mengubah role miliknya sendiri
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        return $authUser->isAdmin();
    }

    public function toggleStatus(User $authUser, User $targetUser): bool
    {
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        return $authUser->isAdmin();
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        if ($authUser->id === $targetUser->id) {
            return false;
        }

        return $authUser->isAdmin();
    }
}
