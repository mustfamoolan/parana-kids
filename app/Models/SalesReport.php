<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'date_from',
        'date_to',
        'filters',
        'total_amount_with_delivery',
        'total_amount_without_delivery',
        'total_profit_without_margin',
        'total_profit_with_margin',
        'total_margin_amount',
        'orders_count',
        'items_count',
        'most_sold_product_id',
        'least_sold_product_id',
        'chart_data',
    ];

    protected $casts = [
        'report_date' => 'date',
        'date_from' => 'date',
        'date_to' => 'date',
        'filters' => 'array',
        'chart_data' => 'array',
        'total_amount_with_delivery' => 'decimal:2',
        'total_amount_without_delivery' => 'decimal:2',
        'total_profit_without_margin' => 'decimal:2',
        'total_profit_with_margin' => 'decimal:2',
        'total_margin_amount' => 'decimal:2',
    ];

    // العلاقات
    public function mostSoldProduct()
    {
        return $this->belongsTo(Product::class, 'most_sold_product_id');
    }

    public function leastSoldProduct()
    {
        return $this->belongsTo(Product::class, 'least_sold_product_id');
    }

    // Scopes
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('report_date', [$from, $to]);
    }
}
