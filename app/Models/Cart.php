<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegate_id',
        'cart_name',
        'status',
        'expires_at',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_social_link',
        'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function getTotalAmountAttribute()
    {
        return $this->items->sum(function($item) {
            return $item->quantity * $item->price;
        });
    }

    public function getTotalItemsAttribute()
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if cart is expired
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Extend cart expiration by 1 hour
     */
    public function extendExpiration()
    {
        $this->update([
            'expires_at' => now()->addHour()
        ]);
    }

    /**
     * Scope for active carts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for expired carts
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    /**
     * Get or create active cart for delegate
     */
    public static function getOrCreateActiveCart($delegateId)
    {
        return self::firstOrCreate(
            [
                'delegate_id' => $delegateId,
                'status' => 'active',
            ],
            [
                'cart_name' => 'طلب مؤقت - ' . now()->format('Y-m-d H:i'),
                'expires_at' => now()->addHours(24),
            ]
        );
    }
}
