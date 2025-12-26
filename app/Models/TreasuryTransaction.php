<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'treasury_id',
        'transaction_type',
        'amount',
        'reference_type',
        'reference_id',
        'description',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the treasury
     */
    public function treasury()
    {
        return $this->belongsTo(Treasury::class);
    }

    /**
     * Get the user who created this transaction
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the reference model (morphTo)
     */
    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Get the order if reference_type is 'order'
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'reference_id')->where('reference_type', 'order');
    }
}
