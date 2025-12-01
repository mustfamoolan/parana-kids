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
}
