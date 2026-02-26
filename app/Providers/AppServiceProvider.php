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
