<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\OrderController;
use App\Models\Order;

try {
    echo "Testing materials list:\n";

    // إنشاء instance من الـ controller
    $controller = new OrderController();

    // جلب الطلبات
    $orders = Order::where('status', 'pending')
        ->with(['delegate', 'items.product.primaryImage', 'items.product.warehouse'])
        ->get();

    echo "Orders found: " . $orders->count() . "\n";

    // تجميع المواد
    $materials = [];
    foreach ($orders as $order) {
        foreach ($order->items as $item) {
            // التأكد من وجود المنتج
            if (!$item->product) {
                echo "Item without product: " . $item->id . "\n";
                continue;
            }

            $key = $item->product_id . '_' . $item->size_name;

            if (!isset($materials[$key])) {
                $materials[$key] = [
                    'product' => $item->product,
                    'size_name' => $item->size_name,
                    'total_quantity' => 0,
                    'orders' => []
                ];
            }

            $materials[$key]['total_quantity'] += $item->quantity;
            $materials[$key]['orders'][] = [
                'order_number' => $order->order_number,
                'quantity' => $item->quantity,
                'order_id' => $order->id
            ];
        }
    }

    echo "Materials found: " . count($materials) . "\n";

    foreach ($materials as $key => $material) {
        echo "Material: " . $key . " - " . $material['product']->name . " - " . $material['size_name'] . " - " . $material['total_quantity'] . "\n";
    }

    echo "Success!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
