<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size_id',
        'warehouse_id',
        'order_id',
        'user_id',
        'movement_type',
        'quantity',
        'balance_after',
        'order_status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقات
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function size(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class, 'size_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes للفلترة
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBySize($query, $sizeId)
    {
        return $query->where('size_id', $sizeId);
    }

    public function scopeByMovementType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByOrderStatus($query, $status)
    {
        return $query->where('order_status', $status);
    }

    // Helper method لتسجيل الحركات
    public static function record(array $data)
    {
        return self::create(array_merge([
            'user_id' => auth()->id(),
        ], $data));
    }

    // Accessors للحصول على أسماء الحركات بالعربية
    public function getMovementTypeNameAttribute(): string
    {
        return match($this->movement_type) {
            'add' => 'إضافة',
            'sale' => 'بيع',
            'confirm' => 'تقييد',
            'cancel' => 'إلغاء',
            'return' => 'استرجاع',
            'delete' => 'حذف',
            'restore' => 'استرجاع من الحذف',
            'transfer_out' => 'نقل - خروج',
            'transfer_in' => 'نقل - دخول',
            default => $this->movement_type,
        };
    }

    // Accessor للحصول على لون الحركة
    public function getMovementColorAttribute(): string
    {
        return match($this->movement_type) {
            'add' => 'success',
            'sale' => 'primary',
            'confirm' => 'warning',
            'cancel' => 'danger',
            'return' => 'info',
            'delete' => 'danger',
            'restore' => 'success',
            'transfer_out' => 'purple',
            'transfer_in' => 'indigo',
            default => 'secondary',
        };
    }

    // Accessor للتمييز بين مصادر الحركات
    public function getMovementSourceAttribute(): string
    {
        return $this->order_id ? 'طلب' : 'إدارة المخزن';
    }
}
