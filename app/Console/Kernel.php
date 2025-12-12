<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Expire carts every minute
        $schedule->command('carts:expire')->everyMinute();

        // أرشفة السلات المنتهية كل 5 دقائق
        $schedule->command('carts:archive-expired')->everyFiveMinutes();

        // حذف الطلبات المحذوفة القديمة كل يوم الساعة 2 صباحاً
        $schedule->command('orders:delete-old-trashed')->daily()->at('02:00');

        // إيقاف التخفيضات المنتهية كل ساعة
        $schedule->command('promotions:expire')->hourly();

        // حذف روابط المنتجات المنتهية (بعد ساعتين من الإنشاء) كل دقيقة
        $schedule->command('product-links:delete-expired')->everyMinute();

        // مزامنة طلبات الواسط تلقائياً
        $schedule->call(function () {
            $syncEnabled = \App\Models\Setting::getValue('alwaseet_auto_sync_enabled', '0');
            if ($syncEnabled === '1') {
                $syncInterval = (int)\App\Models\Setting::getValue('alwaseet_auto_sync_interval', '60');
                $syncStatusIds = \App\Models\Setting::getValue('alwaseet_auto_sync_status_ids', '');
                $statusIdsArray = !empty($syncStatusIds) ? explode(',', $syncStatusIds) : null;

                // تشغيل المزامنة لكل حالة أو بدون فلترة
                if ($statusIdsArray) {
                    foreach ($statusIdsArray as $statusId) {
                        \App\Jobs\SyncAlWaseetOrdersJob::dispatch(trim($statusId), null, null);
                    }
                } else {
                    \App\Jobs\SyncAlWaseetOrdersJob::dispatch(null, null, null);
                }
            }
        })->everyMinute()->when(function () {
            $syncEnabled = \App\Models\Setting::getValue('alwaseet_auto_sync_enabled', '0');
            $syncInterval = (int)\App\Models\Setting::getValue('alwaseet_auto_sync_interval', '60');

            if ($syncEnabled !== '1') {
                return false;
            }

            // التحقق من أن الوقت مناسب للمزامنة (حسب الفترة المحددة)
            $lastSync = \App\Models\AlWaseetSyncLog::where('type', 'automatic')
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$lastSync || !$lastSync->completed_at) {
                return true; // لم يتم المزامنة من قبل، قم بالمزامنة
            }

            $minutesSinceLastSync = $lastSync->completed_at->diffInMinutes(now());
            return $minutesSinceLastSync >= $syncInterval;
        });

        // تحديث status_id و status للطلبات المرتبطة (كل دقيقة)
        $schedule->job(new \App\Jobs\UpdateAlWaseetShipmentsStatusJob)->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
