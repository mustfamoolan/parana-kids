<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'public_key',
        'auth_token',
        'device_type',
        'device_info',
    ];

    protected $casts = [
        'device_info' => 'array',
    ];

    /**
     * Get the user that owns this push subscription
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
