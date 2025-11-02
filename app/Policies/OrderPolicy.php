<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupplier();
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupplier()) {
            // التحقق من أن المجهز له صلاحية الوصول لمخازن المنتجات في هذا الطلب
            return $order->items()->whereHas('product.warehouse.users', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('can_manage', true);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->isDelegate();
    }

    /**
     * Determine whether the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupplier()) {
            // نفس منطق view
            return $order->items()->whereHas('product.warehouse.users', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->where('can_manage', true);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can process the order.
     */
    public function process(User $user, Order $order): bool
    {
        // Admin يمكنه تجهيز أي طلب
        if ($user->isAdmin()) {
            return true;
        }

        // Supplier يمكنه تجهيز الطلبات من مخازنه فقط
        if ($user->isSupplier()) {
            $warehouseIds = $user->warehouses()->pluck('warehouse_id');
            return $order->items()->whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isSupplier()) {
            // المجهز يمكنه حذف الطلبات من مخازنه فقط
            $warehouseIds = $user->warehouses()->pluck('warehouse_id');
            return $order->items()->whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->exists();
        }

        if ($user->isDelegate()) {
            // المندوب يمكنه حذف الطلبات من مخازنه المخصصة فقط
            $warehouseIds = $user->warehouses()->pluck('warehouse_id');
            return $order->items()->whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the order.
     */
    public function restore(User $user, Order $order): bool
    {
        // نفس منطق الحذف
        return $this->delete($user, $order);
    }

    /**
     * Determine whether the user can force delete the order.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        // فقط Admin يمكنه الحذف النهائي
        return $user->isAdmin();
    }
}
