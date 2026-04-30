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
        return $user->isAdmin() || $user->isSupplier() || $user->isPrivateSupplier();
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
            if ($order->supplier_id) {
                return $order->supplier_id == $user->id;
            }
            // Fallback: إذا لم يتم اختيار مجهز، نعتمد على صلاحية المخزن
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            return $order->items()->whereHas('product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            })->exists();
        }

        if ($user->isPrivateSupplier()) {
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            return $order->items()->whereHas('product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
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
            if ($order->supplier_id) {
                return $order->supplier_id == $user->id;
            }
            // Fallback
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            return $order->items()->whereHas('product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            })->exists();
        }

        if ($user->isPrivateSupplier()) {
            $accessibleWarehouseIds = $user->warehouses->pluck('id')->toArray();
            return $order->items()->whereHas('product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
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

        // Supplier يمكنه تجهيز طلباته فقط
        if ($user->isSupplier()) {
            if ($order->supplier_id) {
                return $order->supplier_id == $user->id;
            }
            // Fallback
            $warehouseIds = $user->warehouses()->pluck('warehouse_id');
            return $order->items()->whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->exists();
        }

        if ($user->isPrivateSupplier()) {
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
            if ($order->supplier_id) {
                return $order->supplier_id == $user->id;
            }
            // Fallback
            $warehouseIds = $user->warehouses()->pluck('warehouse_id');
            return $order->items()->whereHas('product', function($q) use ($warehouseIds) {
                $q->whereIn('warehouse_id', $warehouseIds);
            })->exists();
        }

        if ($user->isPrivateSupplier()) {
            // المورد يمكنه حذف الطلبات من مخازنه فقط
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
