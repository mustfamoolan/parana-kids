<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerOrderApiController extends Controller
{
    /**
     * Get orders for the authenticated customer
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::with(['items.product'])
            ->where('customer_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Store a new order from the customer app
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string',
            'customer_phone2' => 'nullable|string',
            'customer_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Normalize phones
        $phone1 = $this->normalizePhoneNumber($request->customer_phone);
        $phone2 = $request->customer_phone2 ? $this->normalizePhoneNumber($request->customer_phone2) : null;

        if (!$phone1) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهاتف الأول غير صالح، يجب أن يكون ١١ رقم.'
            ], 422);
        }

        try {
            $order = DB::transaction(function () use ($request, $user, $phone1, $phone2) {
                $totalAmount = 0;
                $orderItemsData = [];

                // 1. Validate stock and calculate total
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $size = ProductSize::findOrFail($item['size_id']);

                    if ($size->quantity < $item['quantity']) {
                        throw new \Exception("الكمية المطلوبة للمنتج {$product->name} (قياس {$size->size_name}) غير متوفرة حالياً.");
                    }

                    $subtotal = $product->effective_price * $item['quantity'];
                    $totalAmount += $subtotal;

                    $orderItemsData[] = [
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'product_name' => $product->name,
                        'product_code' => $product->code,
                        'size_name' => $size->size_name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $product->effective_price,
                        'subtotal' => $subtotal,
                    ];
                }

                // 2. Create Order
                $order = Order::create([
                    'customer_id' => $user->id,
                    'source' => 'store',
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $phone1,
                    'customer_phone2' => $phone2,
                    'customer_address' => $request->customer_address,
                    'customer_social_link' => 'https://parana-kids-main-sbv4op.laravel.cloud/admin/dashboard',
                    'notes' => $request->notes,
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                ]);

                // 3. Create Items and Deduct Stock
                foreach ($orderItemsData as $itemData) {
                    $itemData['order_id'] = $order->id;
                    OrderItem::create($itemData);

                    $size = ProductSize::find($itemData['size_id']);
                    $size->decrement('quantity', $itemData['quantity']);

                    // Record Movement
                    ProductMovement::record([
                        'product_id' => $itemData['product_id'],
                        'size_id' => $itemData['size_id'],
                        'warehouse_id' => Product::find($itemData['product_id'])->warehouse_id,
                        'order_id' => $order->id,
                        'movement_type' => 'sell',
                        'quantity' => -$itemData['quantity'],
                        'balance_after' => $size->refresh()->quantity,
                        'order_status' => 'pending',
                        'notes' => "طلب من المتجر (تطبيق) #{$order->order_number}"
                    ]);
                }

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'تم استلام طلبك بنجاح وسيتم تجهيزه قريباً.',
                'data' => $order->load('items')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Format Phone Number logic duplicated from OrderCreationController for consistency
     */
    private function normalizePhoneNumber($phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        if (strpos($cleaned, '00964') === 0) {
            $cleaned = substr($cleaned, 5);
        } elseif (strpos($cleaned, '964') === 0) {
            $cleaned = substr($cleaned, 3);
        }
        if (!empty($cleaned) && !str_starts_with($cleaned, '0')) {
            $cleaned = '0' . $cleaned;
        }
        if (strlen($cleaned) > 11) {
            $cleaned = substr($cleaned, 0, 11);
        }
        if (strlen($cleaned) < 11) {
            return null;
        }
        return $cleaned;
    }
}
