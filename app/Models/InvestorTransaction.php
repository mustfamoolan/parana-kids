<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'transaction_type',
        'amount',
        'balance_before',
        'balance_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Get the investor
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * Get the user who created this transaction
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope for deposits
     */
    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', 'deposit');
    }

    /**
     * Scope for withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}
