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
