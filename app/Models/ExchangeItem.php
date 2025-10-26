<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'old_product_id',
        'old_size_id',
        'old_quantity',
        'new_product_id',
        'new_size_id',
        'new_quantity',
        'exchange_reason',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function oldProduct()
    {
        return $this->belongsTo(Product::class, 'old_product_id');
    }

    public function oldSize()
    {
        return $this->belongsTo(ProductSize::class, 'old_size_id');
    }

    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }

    public function newSize()
    {
        return $this->belongsTo(ProductSize::class, 'new_size_id');
    }
}
