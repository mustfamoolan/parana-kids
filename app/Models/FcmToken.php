<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'device_type',
        'device_info',
        'app_type',
        'is_active',
    ];

    protected $casts = [
        'device_info' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this FCM token
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific app type
     */
    public function scopeOfAppType($query, $appType)
    {
        return $query->where('app_type', $appType);
    }

    /**
     * Scope for delegate mobile tokens
     */
    public function scopeDelegateMobile($query)
    {
        return $query->where('app_type', 'delegate_mobile')->active();
    }
}
