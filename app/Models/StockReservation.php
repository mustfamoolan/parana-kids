<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_size_id',
        'cart_item_id',
        'quantity_reserved',
    ];

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id');
    }

    public function cartItem()
    {
        return $this->belongsTo(CartItem::class);
    }
}
