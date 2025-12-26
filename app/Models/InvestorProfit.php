<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestorProfit extends Model
{
    use HasFactory;

    protected $fillable = [
        'investor_id',
        'investment_id',
        'profit_record_id',
        'order_id',
        'product_id',
        'warehouse_id',
        'private_warehouse_id',
        'profit_amount',
        'base_profit',
        'profit_percentage',
        'profit_date',
        'status',
        'payment_date',
        'payment_notes',
    ];

    protected $casts = [
        'profit_amount' => 'decimal:2',
        'base_profit' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'profit_date' => 'date',
        'payment_date' => 'date',
    ];

    /**
     * Get the investor
     */
    public function investor()
    {
        return $this->belongsTo(Investor::class);
    }

    /**
     * Get the investment
     */
    public function investment()
    {
        return $this->belongsTo(Investment::class);
    }

    /**
     * Get the profit record
     */
    public function profitRecord()
    {
        return $this->belongsTo(ProfitRecord::class);
    }

    /**
     * Get the order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the private warehouse
     */
    public function privateWarehouse()
    {
        return $this->belongsTo(PrivateWarehouse::class, 'private_warehouse_id');
    }

    /**
     * Scope for pending profits
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for paid profits
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope by date range
     */
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('profit_date', [$from, $to]);
    }
}
