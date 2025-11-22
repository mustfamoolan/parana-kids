<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CleanSessionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * تنظيف session من البيانات غير الضرورية لتجنب مشاكل الكوكيز الكبيرة
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // تنظيف البيانات القديمة من session إذا كانت كبيرة جداً
        $sessionData = Session::all();
        $sessionSize = strlen(serialize($sessionData));

        // إذا كان حجم session أكبر من 2KB، قم بتنظيف البيانات غير الضرورية
        // (الحد الأقصى للكوكي عادة 4KB، لكن نترك هامش أمان)
        if ($sessionSize > 2048) {
            // الحفاظ على البيانات المهمة فقط
            $importantKeys = [
                '_token',
                '_flash',
                '_previous',
                'login_web',
            ];

            // لا نحتفظ ب current_cart_id و customer_data في session
            // لأنها الآن مخزنة في localStorage لتجنب مشاكل الكوكيز الكبيرة

            // حذف جميع المفاتيح غير المهمة
            foreach ($sessionData as $key => $value) {
                if (!in_array($key, $importantKeys) && !str_starts_with($key, '_')) {
                    Session::forget($key);
                }
            }

            \Log::warning('CleanSessionMiddleware: Session was too large (' . $sessionSize . ' bytes), cleaned unnecessary data');
        }

        return $next($request);
    }
}

