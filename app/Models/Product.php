<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'gender_type',
        'purchase_price',
        'selling_price',
        'description',
        'link_1688',
        'created_by',
        'is_hidden',
        'discount_type',
        'discount_value',
        'discount_start_date',
        'discount_end_date',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_hidden' => 'boolean',
        'discount_value' => 'decimal:2',
        'discount_start_date' => 'datetime',
        'discount_end_date' => 'datetime',
    ];

    protected $appends = ['primary_image_url'];

    /**
     * Get the warehouse that owns this product
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the user who created this product
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all images for this product
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    /**
     * Get the primary image for this product
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get all sizes for this product
     */
    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    /**
     * Get total quantity across all sizes
     */
    public function getTotalQuantityAttribute()
    {
        return $this->sizes()->sum('quantity');
    }

    /**
     * Get primary image URL
     */
    public function getPrimaryImageUrlAttribute()
    {
        // استخدام loadMissing لضمان تحميل العلاقة عند الطلب إذا لم تكن محملة مسبقاً
        if (!$this->relationLoaded('primaryImage')) {
            $this->loadMissing('primaryImage');
        }
        return $this->primaryImage ? $this->primaryImage->image_url : null;
    }

    public function profitRecords()
    {
        return $this->hasMany(ProfitRecord::class);
    }

    /**
     * Get all investments for this product
     */
    public function investments()
    {
        return $this->hasMany(Investment::class)->where('investment_type', 'product');
    }

    /**
     * Get the effective price (considering active promotions)
     * الأولوية: تخفيض المنتج الواحد > تخفيض المخزن العام
     */
    public function getEffectivePriceAttribute()
    {
        $originalPrice = $this->selling_price;

        // 1. التحقق من تخفيض المنتج الواحد أولاً (الأولوية الأعلى)
        if ($this->hasActiveDiscount()) {
            $productDiscountPrice = $this->calculateProductDiscountPrice($originalPrice);
            return $productDiscountPrice;
        }

        // 2. التحقق من تخفيض المخزن العام
        $warehouse = $this->warehouse;
        if (!$warehouse) {
            return $originalPrice;
        }

        // استخدام eager loading إذا كان متاحاً
        if ($warehouse->relationLoaded('activePromotion')) {
            $activePromotion = $warehouse->activePromotion;
        } else {
            $activePromotion = WarehousePromotion::active()
                ->forWarehouse($warehouse->id)
                ->first();
        }

        if ($activePromotion && $activePromotion->isActive()) {
            return $activePromotion->calculatePrice($originalPrice);
        }

        return $originalPrice;
    }

    /**
     * Check if product has an active discount
     */
    public function hasActiveDiscount(): bool
    {
        if (!$this->discount_type || $this->discount_type === 'none' || !$this->discount_value) {
            return false;
        }

        $now = now();
        $startDate = $this->discount_start_date;
        $endDate = $this->discount_end_date;

        // إذا لم تكن هناك تواريخ محددة، يعتبر التخفيض دائماً نشطاً
        if (!$startDate && !$endDate) {
            return true;
        }

        // التحقق من التواريخ
        if ($startDate && $now->lt($startDate)) {
            return false;
        }

        if ($endDate && $now->gt($endDate)) {
            return false;
        }

        return true;
    }

    /**
     * Calculate product discount price
     */
    private function calculateProductDiscountPrice($originalPrice): float
    {
        if ($this->discount_type === 'percentage') {
            $discountAmount = ($originalPrice * $this->discount_value) / 100;
            return max(0, $originalPrice - $discountAmount);
        } elseif ($this->discount_type === 'amount') {
            return max(0, $originalPrice - $this->discount_value);
        }

        return $originalPrice;
    }

    /**
     * Get discount information
     */
    public function getDiscountInfo(): ?array
    {
        if (!$this->hasActiveDiscount()) {
            return null;
        }

        $originalPrice = $this->selling_price;
        $discountPrice = $this->calculateProductDiscountPrice($originalPrice);
        $discountAmount = $originalPrice - $discountPrice;

        return [
            'type' => $this->discount_type,
            'value' => $this->discount_value,
            'original_price' => $originalPrice,
            'discount_price' => $discountPrice,
            'discount_amount' => $discountAmount,
            'percentage' => $this->discount_type === 'percentage' ? $this->discount_value : ($discountAmount / $originalPrice * 100),
            'start_date' => $this->discount_start_date,
            'end_date' => $this->discount_end_date,
        ];
    }
}
