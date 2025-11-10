<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class InvoiceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'image_url',
        'product_link',
        'price_yuan',
        'available_sizes',
        'created_by',
        'private_warehouse_id',
    ];

    protected $casts = [
        'price_yuan' => 'decimal:2',
        'available_sizes' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the private warehouse this product belongs to
     */
    public function privateWarehouse()
    {
        return $this->belongsTo(PrivateWarehouse::class, 'private_warehouse_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->code)) {
                $product->code = static::generateUniqueCode();
            }
        });
    }

    /**
     * Generate a unique product code
     */
    protected static function generateUniqueCode(): string
    {
        // التحقق من وجود الحقل في قاعدة البيانات
        if (!\Schema::hasColumn('invoice_products', 'code')) {
            // إذا لم يكن الحقل موجوداً، إرجاع كود بسيط بدون التحقق من التكرار
            return 'PRD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        }

        do {
            $code = 'PRD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
