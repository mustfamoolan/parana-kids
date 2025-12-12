<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlWaseetShipment extends Model
{
    protected $table = 'alwaseet_shipments';

    protected $fillable = [
        'alwaseet_order_id',
        'client_name',
        'client_mobile',
        'client_mobile2',
        'city_id',
        'city_name',
        'region_id',
        'region_name',
        'location',
        'price',
        'delivery_price',
        'package_size',
        'type_name',
        'status_id',
        'status',
        'items_number',
        'merchant_notes',
        'issue_notes',
        'replacement',
        'qr_id',
        'qr_link',
        'pickup_id',
        'merchant_invoice_id',
        'api_data',
        'alwaseet_created_at',
        'alwaseet_updated_at',
        'synced_at',
        'order_id',
        'printed_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'delivery_price' => 'decimal:2',
        'replacement' => 'boolean',
        'api_data' => 'array',
        'alwaseet_created_at' => 'datetime',
        'alwaseet_updated_at' => 'datetime',
        'synced_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    /**
     * Get the order this shipment is linked to
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get notifications for this shipment
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\AlWaseetNotification::class, 'alwaseet_shipment_id');
    }

    /**
     * Check if shipment is linked to an order
     */
    public function isLinked(): bool
    {
        return !is_null($this->order_id);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status_id) {
            '1' => 'badge-outline-info',
            '2' => 'badge-outline-primary',
            '3' => 'badge-outline-warning',
            '4' => 'badge-outline-success',
            '5' => 'badge-outline-danger',
            default => 'badge-outline-secondary',
        };
    }

    /**
     * Check if order can be edited
     * Orders can only be edited before being picked up by the delivery driver
     */
    public function canBeEdited(): bool
    {
        // يمكن التعديل إذا كانت الحالة هي "جديد" أو "قيد المعالجة"
        // عادة الحالة 1 أو 2 تعني أن الطلب لم يتم استلامه بعد
        return in_array($this->status_id, ['1', '2']);
    }

    /**
     * Check if order is delivered
     */
    public function isDelivered(): bool
    {
        // الحالة 4 عادة تعني "تم التسليم"
        return $this->status_id === '4';
    }

    /**
     * Get invoice ID if order is included in an invoice
     */
    public function getInvoiceId(): ?string
    {
        // من بيانات الطلب، merchant_invoice_id يعطي معرف الفاتورة
        // إذا كان "-1" يعني أن الطلب غير مرتبط بفاتورة
        return ($this->merchant_invoice_id && $this->merchant_invoice_id !== '-1')
            ? $this->merchant_invoice_id
            : null;
    }
}
