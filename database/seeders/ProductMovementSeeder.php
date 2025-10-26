<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ProductMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductSize;
use Illuminate\Support\Facades\DB;

class ProductMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('بدء إنشاء حركات المنتجات من البيانات القديمة...');

        // استخراج الحركات من الطلبات الموجودة
        $this->createMovementsFromOrders();

        // استخراج الحركات من الطلبات المحذوفة
        $this->createMovementsFromDeletedOrders();

        $this->command->info('تم إنشاء حركات المنتجات بنجاح!');
    }

    private function createMovementsFromOrders()
    {
        $this->command->info('معالجة الطلبات الموجودة...');

        $orders = Order::with(['items.size', 'items.product'])->get();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if ($item->size) {
                    // تسجيل حركة البيع
                    ProductMovement::create([
                        'product_id' => $item->product_id,
                        'size_id' => $item->size_id,
                        'warehouse_id' => $item->product->warehouse_id,
                        'order_id' => $order->id,
                        'user_id' => $order->delegate_id ?? 1, // استخدام المندوب أو المدير الافتراضي
                        'movement_type' => 'sale',
                        'quantity' => -$item->quantity, // سالب للبيع
                        'balance_after' => $item->size->quantity,
                        'order_status' => $order->status,
                        'notes' => "بيع من طلب #{$order->order_number}",
                        'created_at' => $order->created_at,
                        'updated_at' => $order->created_at,
                    ]);

                    // إذا كان الطلب مقيد، تسجيل حركة التقييد
                    if ($order->status === 'confirmed') {
                        ProductMovement::create([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'order_id' => $order->id,
                            'user_id' => $order->confirmed_by ?? 1,
                            'movement_type' => 'confirm',
                            'quantity' => 0, // لا تغيير في الكمية للتقييد
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "تقييد طلب #{$order->order_number}",
                            'created_at' => $order->confirmed_at ?? $order->created_at,
                            'updated_at' => $order->confirmed_at ?? $order->created_at,
                        ]);
                    }

                    // إذا كان الطلب مسترجع، تسجيل حركة الاسترجاع
                    if ($order->status === 'returned') {
                        ProductMovement::create([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'order_id' => $order->id,
                            'user_id' => $order->processed_by ?? 1,
                            'movement_type' => 'return',
                            'quantity' => $item->quantity, // موجب للاسترجاع
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "استرجاع من طلب #{$order->order_number}",
                            'created_at' => $order->returned_at ?? $order->updated_at,
                            'updated_at' => $order->returned_at ?? $order->updated_at,
                        ]);
                    }
                }
            }
        }
    }

    private function createMovementsFromDeletedOrders()
    {
        $this->command->info('معالجة الطلبات المحذوفة...');

        $deletedOrders = Order::onlyTrashed()->with(['items.size', 'items.product'])->get();

        foreach ($deletedOrders as $order) {
            foreach ($order->items as $item) {
                if ($item->size) {
                    // تسجيل حركة البيع الأصلية
                    ProductMovement::create([
                        'product_id' => $item->product_id,
                        'size_id' => $item->size_id,
                        'warehouse_id' => $item->product->warehouse_id,
                        'order_id' => $order->id,
                        'user_id' => $order->delegate_id ?? 1,
                        'movement_type' => 'sale',
                        'quantity' => -$item->quantity,
                        'balance_after' => $item->size->quantity,
                        'order_status' => $order->status,
                        'notes' => "بيع من طلب #{$order->order_number} (محذوف)",
                        'created_at' => $order->created_at,
                        'updated_at' => $order->created_at,
                    ]);

                    // تسجيل حركة الحذف
                    ProductMovement::create([
                        'product_id' => $item->product_id,
                        'size_id' => $item->size_id,
                        'warehouse_id' => $item->product->warehouse_id,
                        'order_id' => $order->id,
                        'user_id' => $order->deleted_by ?? 1,
                        'movement_type' => 'delete',
                        'quantity' => $item->quantity,
                        'balance_after' => $item->size->quantity,
                        'order_status' => $order->status,
                        'notes' => "حذف طلب #{$order->order_number}",
                        'created_at' => $order->deleted_at,
                        'updated_at' => $order->deleted_at,
                    ]);
                }
            }
        }
    }
}
