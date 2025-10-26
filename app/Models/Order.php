<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cart_id',
        'delegate_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_social_link',
        'notes',
        'status',
        'total_amount',
        'delivery_code',
        'confirmed_at',
        'confirmed_by',
        'cancellation_reason',
        'return_notes',
        'cancelled_at',
        'returned_at',
        'exchanged_at',
        'processed_by',
        'is_partial_return',
        'is_partial_exchange',
        'deleted_by',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'returned_at' => 'datetime',
        'exchanged_at' => 'datetime',
        'is_partial_return' => 'boolean',
        'is_partial_exchange' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad(Order::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        });
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_id');
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function exchangeItems()
    {
        return $this->hasMany(ExchangeItem::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Resolve the route binding to include soft deleted models.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->withTrashed()->where($field ?? 'id', $value)->firstOrFail();
    }

    public function canBeEdited()
    {
        if (!$this->confirmed_at) {
            return true; // غير مقيد بعد
        }

        $fiveHoursAgo = now()->subHours(5);
        return $this->confirmed_at->isAfter($fiveHoursAgo);
    }

    // دالة الإلغاء الكلي
    public function cancel($reason, $userId)
    {
        DB::transaction(function() use ($reason, $userId) {
            // إرجاع جميع المنتجات للمخزن
            foreach ($this->items as $item) {
                if ($item->size) {
                    $item->size->returnStock($item->quantity);
                }
            }

            $this->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_at' => now(),
                'processed_by' => $userId,
            ]);
        });
    }

    // دالة الإرجاع (كلي أو جزئي)
    public function processReturn($returnData, $userId)
    {
        DB::transaction(function() use ($returnData, $userId) {
            $isPartialReturn = count($returnData) < $this->items->count();

            foreach ($returnData as $returnItem) {
                // إرجاع المنتج للمخزن
                $size = ProductSize::find($returnItem['size_id']);
                $size->returnStock($returnItem['quantity']);

                // تسجيل عملية الإرجاع
                ReturnItem::create([
                    'order_id' => $this->id,
                    'order_item_id' => $returnItem['order_item_id'],
                    'product_id' => $returnItem['product_id'],
                    'size_id' => $returnItem['size_id'],
                    'quantity_returned' => $returnItem['quantity'],
                    'return_reason' => $returnItem['reason'],
                ]);
            }

            $this->update([
                'status' => 'returned',
                'is_partial_return' => $isPartialReturn,
                'return_notes' => $returnData[0]['notes'] ?? null,
                'returned_at' => now(),
                'processed_by' => $userId,
            ]);
        });
    }

    // دالة الاستبدال (كلي أو جزئي)
    public function processExchange($exchangeData, $userId)
    {
        DB::transaction(function() use ($exchangeData, $userId) {
            $isPartialExchange = count($exchangeData) < $this->items->count();

            foreach ($exchangeData as $exchange) {
                // إرجاع المنتج القديم
                $oldSize = ProductSize::find($exchange['old_size_id']);
                $oldSize->returnStock($exchange['old_quantity']);

                // خصم المنتج الجديد
                $newSize = ProductSize::find($exchange['new_size_id']);
                $newSize->deductStock($exchange['new_quantity']);

                // تسجيل عملية الاستبدال
                ExchangeItem::create([
                    'order_id' => $this->id,
                    'order_item_id' => $exchange['order_item_id'],
                    'old_product_id' => $exchange['old_product_id'],
                    'old_size_id' => $exchange['old_size_id'],
                    'old_quantity' => $exchange['old_quantity'],
                    'new_product_id' => $exchange['new_product_id'],
                    'new_size_id' => $exchange['new_size_id'],
                    'new_quantity' => $exchange['new_quantity'],
                    'exchange_reason' => $exchange['reason'],
                ]);
            }

            $this->update([
                'status' => 'exchanged',
                'is_partial_exchange' => $isPartialExchange,
                'exchanged_at' => now(),
                'processed_by' => $userId,
            ]);
        });
    }
}
