<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\ArchivedOrder;
use App\Models\ProductMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ArchiveExpiredCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:archive-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'أرشفة السلات النشطة التي مر عليها أكثر من ساعة بدون إتمام';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('جاري البحث عن السلات المنتهية...');

        // جلب السلات النشطة التي مر عليها أكثر من ساعة
        $expiredCarts = Cart::where('status', 'active')
            ->where('created_at', '<=', Carbon::now()->subHour())
            ->with(['items.product', 'items.size', 'delegate'])
            ->get();

        if ($expiredCarts->isEmpty()) {
            $this->info('لا توجد سلات منتهية.');
            return 0;
        }

        $archivedCount = 0;

        foreach ($expiredCarts as $cart) {
            try {
                DB::transaction(function () use ($cart) {
                    // إنشاء سجل في الأرشيف
                    $archivedOrder = ArchivedOrder::create([
                        'delegate_id' => $cart->delegate_id,
                        'cart_name' => $cart->cart_name,
                        'customer_name' => session("customer_data.customer_name_{$cart->id}") ?? 'غير محدد',
                        'customer_phone' => session("customer_data.customer_phone_{$cart->id}") ?? 'غير محدد',
                        'customer_address' => session("customer_data.customer_address_{$cart->id}") ?? 'غير محدد',
                        'customer_social_link' => session("customer_data.customer_social_link_{$cart->id}") ?? 'غير محدد',
                        'notes' => 'تم الأرشفة تلقائياً بعد مرور ساعة',
                        'total_amount' => $cart->total_amount,
                        'archived_at' => now(),
                        'archived_reason' => 'انتهاء المدة المحددة (ساعة واحدة)',
                    ]);

                    // نسخ المنتجات إلى الأرشيف
                    foreach ($cart->items as $item) {
                        $archivedOrder->items()->create([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'product_code' => $item->product->code,
                            'product_name' => $item->product->name,
                            'size_name' => $item->size->size_name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'subtotal' => $item->subtotal,
                        ]);

                        // حذف الحجز فقط (الطلب النشط لم يخصم من المخزون أصلاً)
                        $item->reservation()->delete();
                    }

                    // حذف السلة
                    $cart->items()->delete();
                    $cart->delete();
                });

                $archivedCount++;
                $this->info("تم أرشفة السلة: {$cart->cart_name} (ID: {$cart->id})");
            } catch (\Exception $e) {
                $this->error("فشل أرشفة السلة {$cart->id}: " . $e->getMessage());
            }
        }

        $this->info("تم أرشفة {$archivedCount} سلة بنجاح.");
        return 0;
    }
}
