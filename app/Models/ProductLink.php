<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProductLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'token',
        'warehouse_id',
        'gender_type',
        'size_name',
        'has_discount',
        'created_by',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
        'has_discount' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($productLink) {
            if (empty($productLink->token)) {
                do {
                    $token = Str::random(32);
                } while (self::where('token', $token)->exists());

                $productLink->token = $token;
            }
        });
    }

    /**
     * Get the warehouse that owns this link
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this link
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the full URL for this link
     */
    public function getFullUrlAttribute()
    {
        return url('/p/' . $this->token);
    }

    /**
     * Get the expiration time for this link
     */
    public function getExpiresAtAttribute()
    {
        $duration = (int) Setting::getValue('app_product_link_duration', 2);
        return $this->created_at->addHours($duration);
    }

    /**
     * Check if the link is expired
     */
    public function isExpired()
    {
        return now()->gt($this->expires_at);
    }
}
