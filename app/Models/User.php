<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'code',
        'phone',
        'page_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Check if user is admin
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is supplier
     */
    public function isSupplier()
    {
        return $this->role === 'supplier';
    }

    /**
     * Check if user is delegate
     */
    public function isDelegate()
    {
        return $this->role === 'delegate';
    }

    /**
     * Check if user is admin or supplier
     */
    public function isAdminOrSupplier()
    {
        return $this->isAdmin() || $this->isSupplier();
    }

    /**
     * Get all warehouses this user has access to
     */
    public function warehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'warehouse_user')
                    ->withPivot('can_manage')
                    ->withTimestamps();
    }

    /**
     * Get warehouses this user can manage
     */
    public function manageableWarehouses()
    {
        return $this->warehouses()->wherePivot('can_manage', true);
    }

    /**
     * Get all products created by this user
     */
    public function createdProducts()
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    /**
     * Check if user can access a specific warehouse
     */
    public function canAccessWarehouse($warehouseId)
    {
        return $this->warehouses()->where('warehouse_id', $warehouseId)->exists();
    }

    /**
     * Check if user can manage a specific warehouse
     */
    public function canManageWarehouse($warehouseId)
    {
        return $this->warehouses()
                    ->where('warehouse_id', $warehouseId)
                    ->wherePivot('can_manage', true)
                    ->exists();
    }

    /**
     * Get all carts for this delegate
     */
    public function carts()
    {
        return $this->hasMany(Cart::class, 'delegate_id');
    }

    /**
     * Get all orders for this delegate
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'delegate_id');
    }
}
