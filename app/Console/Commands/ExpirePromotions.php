<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WarehousePromotion;

class ExpirePromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إيقاف التخفيضات المنتهية تلقائياً';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();

        // البحث عن التخفيضات النشطة التي انتهت
        $expiredPromotions = WarehousePromotion::where('is_active', true)
            ->where('end_date', '<', $now)
            ->get();

        $count = 0;
        foreach ($expiredPromotions as $promotion) {
            $promotion->update(['is_active' => false]);
            $count++;
            $this->info("تم إيقاف التخفيض للمخزن ID: {$promotion->warehouse_id} (انتهى في: {$promotion->end_date->format('Y-m-d H:i')})");
        }

        if ($count > 0) {
            $this->info("تم إيقاف {$count} تخفيض منتهي.");
        } else {
            $this->info("لا توجد تخفيضات منتهية.");
        }

        return 0;
    }
}
