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
