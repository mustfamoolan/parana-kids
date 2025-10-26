<?php

namespace App\Policies;

use App\Models\User;

class TransferPolicy
{
    public function viewAny(User $user)
    {
        return $user->isAdmin();
    }

    public function create(User $user)
    {
        return $user->isAdmin();
    }
}
