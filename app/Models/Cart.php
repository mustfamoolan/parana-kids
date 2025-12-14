<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegate_id',
        'created_by',
        'session_id',
        'cart_name',
        'status',
        'expires_at',
        'customer_name',
        'customer_phone',
        'customer_phone2',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Get or create active cart for guest (shop customer)
     */
    public static function getOrCreateGuestCart($sessionId)
    {
        return self::firstOrCreate(
            [
                'session_id' => $sessionId,
                'status' => 'active',
            ],
            [
                'cart_name' => 'طلب زبون - ' . now()->format('Y-m-d H:i'),
                'expires_at' => now()->addHours(24),
            ]
        );
    }

    /**
     * Scope for guest carts (shop customers)
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('delegate_id')->whereNotNull('session_id');
    }

    /**
     * Scope for delegate carts
     */
    public function scopeDelegate($query)
    {
        return $query->whereNotNull('delegate_id');
    }
}
