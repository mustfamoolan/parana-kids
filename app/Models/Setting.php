<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * الحصول على قيمة إعداد معين
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * تعيين قيمة إعداد معين
     */
    public static function setValue(string $key, $value, $description = null)
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );
    }

    /**
     * الحصول على سعر التوصيل
     */
    public static function getDeliveryFee(): float
    {
        return (float) self::getValue('delivery_fee', 5000);
    }

    /**
     * الحصول على ربح الفروقات
     */
    public static function getProfitMargin(): float
    {
        return (float) self::getValue('profit_margin', 0);
    }
}
