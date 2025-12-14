<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Models\AlWaseetShipment;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cart_id',
        'delegate_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'customer_phone2',
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
        'deletion_reason',
        'size_reviewed',
        'message_confirmed',
        'delivery_fee_at_confirmation',
        'profit_margin_at_confirmation',
        'alwaseet_address',
        'alwaseet_city_id',
        'alwaseet_region_id',
        'alwaseet_delivery_time_note',
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
            // البحث عن آخر رقم طلب في نفس اليوم (بما فيه المحذوف)
            $lastOrder = Order::withTrashed()
                ->whereDate('created_at', today())
                ->orderBy('order_number', 'desc')
                ->first();

            if ($lastOrder) {
                // استخراج الرقم الأخير وزيادته
                preg_match('/ORD-\d{8}-(\d{4})/', $lastOrder->order_number, $matches);
                $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
                $newNumber = $lastNumber + 1;
            } else {
                // أول طلب في هذا اليوم
                $newNumber = 1;
            }

            $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

            // التأكد من عدم وجود تكرار (safety check)
            while (Order::withTrashed()->where('order_number', $order->order_number)->exists()) {
                $newNumber++;
                $order->order_number = 'ORD-' . date('Ymd') . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }

            // تعيين القيم الافتراضية
            if (empty($order->size_reviewed)) {
                $order->size_reviewed = 'not_reviewed';
            }
            if (empty($order->message_confirmed)) {
                $order->message_confirmed = 'not_sent';
            }
        });

        // إرسال إشعار عند حذف الطلب
        static::deleting(function ($order) {
            // التأكد من أن الحذف soft delete وليس force delete
            if (!$order->isForceDeleting()) {
                app(\App\Services\SweetAlertService::class)->notifyOrderDeleted($order);
            }
        });

        // إرسال إشعار عند تقييد الطلب
        static::updating(function ($order) {
            // التحقق من أن confirmed_at تم تعيينه لأول مرة
            if ($order->isDirty('confirmed_at') && $order->confirmed_at && !$order->getOriginal('confirmed_at')) {
                app(\App\Services\SweetAlertService::class)->notifyOrderConfirmed($order);
            }
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

    public function deletedByUser()
    {
        return $this->belongsTo(User::class, 'deleted_by');
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

    public function profitRecords()
    {
        return $this->hasMany(ProfitRecord::class);
    }

    public function alwaseetShipment()
    {
        return $this->hasOne(AlWaseetShipment::class, 'order_id');
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

    public function getSizeReviewStatusTextAttribute()
    {
        return [
            'not_reviewed' => 'لم يتم التدقيق',
            'reviewed' => 'تم تدقيق القياس',
        ][$this->size_reviewed] ?? 'غير محدد';
    }

    public function getSizeReviewStatusBadgeClassAttribute()
    {
        return [
            'not_reviewed' => 'badge-outline-warning',
            'reviewed' => 'badge-outline-success',
        ][$this->size_reviewed] ?? 'badge-outline-secondary';
    }

    public function getMessageConfirmationStatusTextAttribute()
    {
        return [
            'not_sent' => 'لم يرسل الرسالة',
            'waiting_response' => 'تم الارسال رسالة وبالانتضار الرد',
            'not_confirmed' => 'لم يتم التاكيد الرسالة',
            'confirmed' => 'تم تاكيد الرسالة',
        ][$this->message_confirmed] ?? 'غير محدد';
    }

    public function getMessageConfirmationStatusBadgeClassAttribute()
    {
        return [
            'not_sent' => 'badge-outline-warning',
            'waiting_response' => 'badge-outline-info',
            'not_confirmed' => 'badge-outline-danger',
            'confirmed' => 'badge-outline-success',
        ][$this->message_confirmed] ?? 'badge-outline-secondary';
    }
}
