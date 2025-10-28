<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivedOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'delegate_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_social_link',
        'notes',
        'items',
        'total_amount',
        'archived_at',
    ];

    protected $casts = [
        'items' => 'array',
        'archived_at' => 'datetime',
    ];

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }
}

