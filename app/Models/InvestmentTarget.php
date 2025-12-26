<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_id',
        'target_type',
        'target_id',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    /**
     * Get the investment
     */
    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    /**
     * Get the target model (morphTo)
     */
    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }
}
