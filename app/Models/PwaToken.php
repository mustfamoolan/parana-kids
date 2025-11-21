<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PwaToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new token for a user
     */
    public static function generateToken($userId, $expiresInDays = 30)
    {
        // Delete existing tokens for this user
        self::where('user_id', $userId)->delete();

        // Generate new token
        $token = Str::random(64);
        $expiresAt = Carbon::now()->addDays($expiresInDays);

        return self::create([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Check if token is valid
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Expire the token
     */
    public function expire(): void
    {
        $this->update(['expires_at' => Carbon::now()]);
    }

    /**
     * Find token by token string
     */
    public static function findByToken($token)
    {
        return self::where('token', $token)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Clean expired tokens
     */
    public static function cleanExpired()
    {
        return self::where('expires_at', '<=', Carbon::now())->delete();
    }
}
