<?php

namespace App\Http\Controllers\Delegate;

use App\Http\Controllers\Controller;
use App\Models\ArchivedOrder;
use App\Models\Cart;
use App\Models\ProductSize;
use App\Models\StockReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArchivedOrderController extends Controller
{
    /**
     * عرض جميع الطلبات المؤرشفة
     */
    public function index()
    {
        $archivedOrders = ArchivedOrder::where('delegate_id', auth()->id())
                                      ->latest()
                                      ->paginate(20);

        return view('delegate.archived.index', compact('archivedOrders'));
    }

    /**
     * أرشفة الطلب الحالي
     */
    public function archiveCurrent()
    {
        $cartId = session('current_cart_id');
        $customerData = session('customer_data');

        if (!$cartId || !$customerData) {
            return response()->json(['error' => 'لا يوجد طلب نشط'], 400);
        }

        $cart = Cart::with('items.product', 'items.size')->findOrFail($cartId);

        DB::transaction(function() use ($cart, $customerData) {
            // حفظ في الأرشيف
            ArchivedOrder::create([
                'delegate_id' => auth()->id(),
                'customer_name' => $customerData['customer_name'],
                'customer_phone' => $customerData['customer_phone'],
                'customer_address' => $customerData['customer_address'],
                'customer_social_link' => $customerData['customer_social_link'],
                'notes' => $customerData['notes'] ?? null,
                'items' => $cart->items->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'size_id' => $item->size_id,
                    'product_name' => $item->product->name,
                    'product_code' => $item->product->code,
                    'size_name' => $item->size->size_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'subtotal' => $item->subtotal,
                ])->toArray(),
                'total_amount' => $cart->total_amount,
                'archived_at' => now(),
            ]);

            // حذف الحجوزات فقط (الطلب النشط لم يخصم من المخزون أصلاً)
            foreach ($cart->items as $item) {
                // حذف الحجز إن وجد
                if ($item->stockReservation) {
                    $item->stockReservation->delete();
                }
            }

            // حذف السلة
            $cart->delete();
        });

        session()->forget(['current_cart_id', 'customer_data']);

        return response()->json(['success' => true]);
    }

    /**
     * استرجاع من الأرشيف
     */
    public function restore($id)
    {
        // منع الاسترجاع إذا كان هناك طلب نشط
        if (session('current_cart_id')) {
            return back()->withErrors(['error' => 'يجب إكمال الطلب الحالي أولاً']);
        }

        $archived = ArchivedOrder::where('delegate_id', auth()->id())->findOrFail($id);

        try {
            DB::transaction(function() use ($archived) {
                // إنشاء سلة جديدة
                $cart = Cart::create([
                    'delegate_id' => auth()->id(),
                    'cart_name' => 'طلب مسترجع: ' . $archived->customer_name,
                    'status' => 'active',
                    'expires_at' => now()->addHours(24),
                ]);

                // إضافة المنتجات
                foreach ($archived->items as $item) {
                    $size = ProductSize::find($item['size_id']);

                    // التحقق من التوفر (نستخدم available_quantity التي تحسب quantity - reservations)
                    if (!$size || $size->available_quantity < $item['quantity']) {
                        throw new \Exception("المنتج {$item['product_name']} غير متوفر بالكمية المطلوبة");
                    }

                    // إضافة للسلة
                    $cartItem = $cart->items()->create([
                        'product_id' => $item['product_id'],
                        'size_id' => $item['size_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    // إنشاء حجز فقط (لا نخصم من المخزون)
                    StockReservation::create([
                        'product_size_id' => $item['size_id'],
                        'cart_item_id' => $cartItem->id,
                        'quantity_reserved' => $item['quantity'],
                    ]);
                }

                // حفظ في session
                session([
                    'current_cart_id' => $cart->id,
                    'customer_data' => [
                        'customer_name' => $archived->customer_name,
                        'customer_phone' => $archived->customer_phone,
                        'customer_address' => $archived->customer_address,
                        'customer_social_link' => $archived->customer_social_link,
                        'notes' => $archived->notes,
                    ]
                ]);

                // حذف من الأرشيف
                $archived->delete();
            });

            return redirect()->route('delegate.products.all')
                            ->with('success', 'تم استرجاع الطلب بنجاح!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * حذف من الأرشيف
     */
    public function destroy($id)
    {
        $archived = ArchivedOrder::where('delegate_id', auth()->id())->findOrFail($id);
        $archived->delete();

        return back()->with('success', 'تم حذف الطلب المؤرشف');
    }
}

