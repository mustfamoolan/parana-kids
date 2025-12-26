<?php

if (!function_exists('formatPhoneForWhatsApp')) {
    /**
     * تنسيق رقم الهاتف لرابط واتساب مع مفتاح العراق
     *
     * @param string|null $phone
     * @return string
     */
    function formatPhoneForWhatsApp($phone)
    {
        if (empty($phone)) {
            return '';
        }

        // إزالة جميع الأحرف غير الرقمية
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

        // إذا كان الرقم يبدأ بـ 0، استبدله بـ 964
        if (strpos($cleanPhone, '0') === 0) {
            $cleanPhone = '964' . substr($cleanPhone, 1);
        }
        // إذا كان يبدأ بـ 964، نتركه كما هو
        elseif (strpos($cleanPhone, '964') !== 0) {
            // إذا لم يبدأ بـ 0 أو 964، نضيف 964
            $cleanPhone = '964' . $cleanPhone;
        }

        return $cleanPhone;
    }
}

if (!function_exists('roundToNearestCurrency')) {
    /**
     * تقريب المبلغ لأقرب عملة صحيحة عراقية (مضاعفات 250 دينار)
     * 
     * @param float $amount المبلغ المراد تقريبه
     * @return float المبلغ المقرب
     */
    function roundToNearestCurrency(float $amount): float
    {
        return round($amount / 250) * 250;
    }
}

if (!function_exists('formatCurrency')) {
    /**
     * تنسيق المبلغ مع التقريب لأقرب عملة صحيحة عراقية
     * 
     * @param float $amount المبلغ المراد تنسيقه
     * @param int $decimals عدد الأرقام العشرية (افتراضي 0)
     * @return string المبلغ المنسق
     */
    function formatCurrency(float $amount, int $decimals = 0): string
    {
        $rounded = roundToNearestCurrency($amount);
        return number_format($rounded, $decimals) . ' IQD';
    }
}
