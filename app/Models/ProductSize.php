<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_name',
        'quantity',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($productSize) {
            $user = auth()->user();
            if ($user && ($user->isAdmin() || $user->isSupplier() || $user->isPrivateSupplier())) {
                app(\App\Services\AdminNotificationService::class)->notifyProductSizeAction($productSize, 'created', $user);
            }
        });

        static::updated(function ($productSize) {
            $user = auth()->user();
            // Only notify if triggered by admin/supplier (not automatic order sync)
            if ($user && ($user->isAdmin() || $user->isSupplier() || $user->isPrivateSupplier())) {
                app(\App\Services\AdminNotificationService::class)->notifyProductSizeAction($productSize, 'updated', $user);
            }
        });

        static::deleted(function ($productSize) {
            $user = auth()->user();
            if ($user && ($user->isAdmin() || $user->isSupplier() || $user->isPrivateSupplier())) {
                app(\App\Services\AdminNotificationService::class)->notifyProductSizeAction($productSize, 'deleted', $user);
            }
        });
    }

    /**
     * Get the product that owns this size
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all stock reservations for this size
     */
    public function reservations()
    {
        return $this->hasMany(StockReservation::class, 'product_size_id');
    }

    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQuantityAttribute()
    {
        return $this->quantity - $this->reservations()->sum('quantity_reserved');
    }

    /**
     * Get reserved quantity
     */
    public function getReservedQuantityAttribute()
    {
        return $this->reservations()->sum('quantity_reserved');
    }

    public function returnStock($quantity)
    {
        $this->increment('quantity', $quantity);
    }

    public function deductStock($quantity)
    {
        if ($this->quantity < $quantity) {
            throw new \Exception('الكمية المتاحة غير كافية');
        }
        $this->decrement('quantity', $quantity);
    }
}
