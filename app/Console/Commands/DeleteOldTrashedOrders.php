<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeleteOldTrashedOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:delete-old-trashed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'حذف الطلبات المحذوفة التي مر عليها أكثر من 7 أيام';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // جلب الطلبات المحذوفة منذ أكثر من 7 أيام
        $oldTrashedOrders = Order::onlyTrashed()
            ->where('deleted_at', '<', $sevenDaysAgo)
            ->get();

        $count = 0;

        foreach ($oldTrashedOrders as $order) {
            try {
                DB::transaction(function () use ($order, &$count) {
                    // حذف عناصر الطلب نهائياً
                    $order->items()->forceDelete();

                    // حذف الطلب نهائياً
                    $order->forceDelete();

                    $count++;
                });

                $this->info("تم حذف الطلب #{$order->order_number} نهائياً");
            } catch (\Exception $e) {
                $this->error("فشل في حذف الطلب #{$order->order_number}: " . $e->getMessage());
            }
        }

        if ($count > 0) {
            $this->info("تم حذف {$count} طلب محذوف قديم بشكل نهائي.");
        } else {
            $this->info("لا توجد طلبات محذوفة قديمة للحذف.");
        }

        return 0;
    }
}
