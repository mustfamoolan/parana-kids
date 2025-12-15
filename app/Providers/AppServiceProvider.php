<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        // تسجيل Observers
        \App\Models\AlWaseetShipment::observe(\App\Observers\AlWaseetShipmentObserver::class);

        // إنشاء storage link تلقائياً إذا لم يكن موجوداً
        // هذا مهم جداً في Laravel Cloud حيث يتم حذف symlink عند كل deployment
        $link = public_path('storage');
        $target = storage_path('app/public');

        // التحقق من وجود المجلد الهدف وإنشائه باستخدام Storage facade
        // ملاحظة: في Laravel Cloud مع Bucket (S3)، لا نحتاج لإنشاء مجلدات محلية
        try {
            $driver = config('filesystems.disks.public.driver', 'local');

            // فقط إنشاء المجلدات المحلية إذا كان driver هو 'local'
            if ($driver === 'local') {
                // التحقق من وجود المجلد الرئيسي
                $publicPath = storage_path('app/public');
                if (!file_exists($publicPath)) {
                    @mkdir($publicPath, 0755, true);
                    Log::info('Created storage directory: ' . $publicPath);
                }

                // إنشاء المجلدات الفرعية المطلوبة إذا لم تكن موجودة
                $requiredDirectories = ['products', 'messages', 'profiles', 'banners'];
                foreach ($requiredDirectories as $dir) {
                    $dirPath = $publicPath . '/' . $dir;
                    if (!file_exists($dirPath)) {
                        @mkdir($dirPath, 0755, true);
                        Log::info('Created storage subdirectory: ' . $dir);
                    }
                }
            } else {
                // عند استخدام Bucket (S3) في Laravel Cloud
                // المجلدات تُنشأ تلقائياً عند الحاجة عند رفع الملفات
                // لا حاجة لإنشاء المجلدات مسبقاً
                Log::debug('Using cloud storage (Bucket), directories will be created automatically when needed');
            }
        } catch (\Exception $e) {
            // لا نوقف التطبيق، فقط نسجل التحذير
            // في Laravel Cloud مع Bucket، هذا التحذير قد يكون طبيعياً
            Log::debug('Storage directories check: ' . $e->getMessage());
        }

        // التحقق من وجود الرابط
        if (!file_exists($link)) {
            try {
                // حذف أي ملف أو مجلد موجود في نفس المكان (إن وجد)
                if (file_exists($link) || is_dir($link)) {
                    if (is_link($link)) {
                        unlink($link);
                    } elseif (is_dir($link)) {
                        rmdir($link);
                    } else {
                        unlink($link);
                    }
                }

                // استخدام Artisan command لضمان التوافق مع جميع الأنظمة
                Artisan::call('storage:link');

                Log::info('Storage link created successfully');
            } catch (\Exception $e) {
                // تسجيل الخطأ بدون تعطيل التطبيق
                Log::warning('Failed to create storage link automatically: ' . $e->getMessage());

                // محاولة إنشاء symlink مباشرة كحل بديل
                try {
                    if (PHP_OS_FAMILY === 'Windows') {
                        // Windows يحتاج إلى طريقة مختلفة
                        exec('mklink /D "' . $link . '" "' . $target . '"');
                    } else {
                        // Linux/Mac
                        symlink($target, $link);
                    }
                    Log::info('Storage link created using direct symlink method');
                } catch (\Exception $e2) {
                    Log::error('Failed to create storage link using direct method: ' . $e2->getMessage());
                }
            }
        } else {
            // التحقق من أن الرابط يعمل بشكل صحيح
            if (is_link($link)) {
                $linkTarget = readlink($link);
                if ($linkTarget !== $target && realpath($link) !== realpath($target)) {
                    // الرابط يشير إلى مسار خاطئ، إعادة إنشائه
                    try {
                        unlink($link);
                        Artisan::call('storage:link');
                        Log::info('Storage link recreated due to incorrect target');
                    } catch (\Exception $e) {
                        Log::warning('Failed to recreate storage link: ' . $e->getMessage());
                    }
                }
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
