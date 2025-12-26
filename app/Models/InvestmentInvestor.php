<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentInvestor extends Model
{
    use HasFactory;

    protected $fillable = [
        'investment_id',
        'investor_id',
        'profit_percentage',
        'cost_percentage',
        'investment_amount',
        'notes',
    ];

    protected $casts = [
        'profit_percentage' => 'decimal:2',
        'cost_percentage' => 'decimal:2',
        'investment_amount' => 'decimal:2',
    ];

    /**
     * Get the investment
     */
    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    /**
     * Get the investor
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }
}
