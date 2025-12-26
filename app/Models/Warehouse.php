<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'created_by',
    ];

    /**
     * Get the user who created this warehouse
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all products in this warehouse
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all users who have access to this warehouse
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'warehouse_user')
                    ->withPivot('can_manage')
                    ->withTimestamps();
    }

    /**
     * Check if a user can access this warehouse
     */
    public function canUserAccess($user)
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Check if a user can manage this warehouse
     */
    public function canUserManage($user)
    {
        return $this->users()
                    ->where('user_id', $user->id)
                    ->where('can_manage', true)
                    ->exists();
    }

    public function profitRecords()
    {
        return $this->hasMany(ProfitRecord::class);
    }

    /**
     * Get all investments for this warehouse
     */
    public function investments()
    {
        return $this->hasMany(Investment::class)->where('investment_type', 'warehouse');
    }

    /**
     * Get all promotions for this warehouse
     */
    public function promotions()
    {
        return $this->hasMany(WarehousePromotion::class);
    }

    /**
     * Get the active promotion for this warehouse
     */
    public function activePromotion()
    {
        return $this->hasOne(WarehousePromotion::class)
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Get the current active promotion (helper method)
     */
    public function getCurrentActivePromotion()
    {
        return WarehousePromotion::active()
            ->forWarehouse($this->id)
            ->first();
    }
}
