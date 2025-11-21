<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // إنشاء storage link تلقائياً إذا لم يكن موجوداً
        $link = public_path('storage');
        $target = storage_path('app/public');

        // التحقق من وجود المجلد الهدف والرابط
        if (is_dir($target) && !file_exists($link)) {
            try {
                // استخدام Artisan command لضمان التوافق مع جميع الأنظمة
                Artisan::call('storage:link');
            } catch (\Exception $e) {
                // تسجيل الخطأ بدون تعطيل التطبيق
                Log::warning('Failed to create storage link automatically: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get back URL from query parameter or use previous URL
     *
     * @param string|null $default
     * @return string
     */
    public static function getBackUrl($default = null)
    {
        $backUrl = request()->query('back_url');
        if ($backUrl) {
            $decoded = urldecode($backUrl);
            // Security check: ensure the URL is from the same domain
            $parsed = parse_url($decoded);
            $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
            if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                return $default ?? url()->previous();
            }
            return $decoded;
        }
        return $default ?? url()->previous();
    }
}
