<?php

namespace App\Policies;

use App\Models\ProductMovement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductMovementPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupplier();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductMovement $productMovement): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupplier()) {
            return $user->warehouses->contains($productMovement->warehouse_id);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isSupplier() || $user->isDelegate();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductMovement $productMovement): bool
    {
        return false; // لا يمكن تعديل الحركات بعد إنشائها
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductMovement $productMovement): bool
    {
        return false; // لا يمكن حذف الحركات
    }
}
