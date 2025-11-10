<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'invoice_product_id',
        'size',
        'quantity',
        'price_yuan',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price_yuan' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceProduct()
    {
        return $this->belongsTo(InvoiceProduct::class);
    }
}
