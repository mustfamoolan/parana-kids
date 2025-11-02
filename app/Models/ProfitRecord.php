<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'order_id',
        'delegate_id',
        'return_item_id',
        'record_date',
        'warehouse_value',
        'product_value',
        'expected_profit',
        'actual_profit',
        'return_amount',
        'total_amount',
        'record_type',
        'status',
    ];

    protected $casts = [
        'record_date' => 'date',
        'warehouse_value' => 'decimal:2',
        'product_value' => 'decimal:2',
        'expected_profit' => 'decimal:2',
        'actual_profit' => 'decimal:2',
        'return_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // العلاقات
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function returnItem()
    {
        return $this->belongsTo(ReturnItem::class);
    }

    // Scopes للكشوفات المستقبلية
    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('record_date', [$from, $to]);
    }

    public function scopeByDelegate($query, $delegateId)
    {
        return $query->where('delegate_id', $delegateId);
    }

    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByRecordType($query, $type)
    {
        return $query->where('record_type', $type);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
