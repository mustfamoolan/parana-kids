<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'size_id',
        'product_name',
        'product_code',
        'size_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size()
    {
        return $this->belongsTo(ProductSize::class, 'size_id');
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class, 'order_item_id');
    }

    /**
     * حساب الكمية الأصلية (الكمية الحالية + جميع الإرجاعات)
     */
    public function getOriginalQuantityAttribute()
    {
        $returnedQuantity = $this->returnItems()->sum('quantity_returned');
        return $this->quantity + $returnedQuantity;
    }

    /**
     * حساب الكمية المتبقية بعد الإرجاعات
     */
    public function getRemainingQuantityAttribute()
    {
        // الكمية المتبقية = الكمية الحالية (التي تم تقليلها بعد الإرجاعات)
        // أو يمكن حسابها: الكمية الأصلية - مجموع الإرجاعات
        return max(0, $this->quantity);
    }
}
