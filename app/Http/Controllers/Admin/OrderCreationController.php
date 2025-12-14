<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductMovement;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderCreationController extends Controller
{
    protected $sweetAlertService;

    public function __construct(SweetAlertService $sweetAlertService)
    {
        $this->sweetAlertService = $sweetAlertService;
    }

    /**
     * Show the form for starting a new order
     */
    public function start()
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        return view('admin.orders.create.start');
    }

    /**
     * تنسيق رقم الهاتف إلى صيغة موحدة
     */
    private function normalizePhoneNumber($phone)
    {
        // إزالة كل شيء غير الأرقام
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // إزالة البادئات الدولية
        if (strpos($cleaned, '00964') === 0) {
            $cleaned = substr($cleaned, 5);
        } elseif (strpos($cleaned, '964') === 0) {
            $cleaned = substr($cleaned, 3);
        }

        // إضافة 0 في البداية إذا لم تكن موجودة
        if (!empty($cleaned) && !str_starts_with($cleaned, '0')) {
            $cleaned = '0' . $cleaned;
        }

        // التأكد من 11 رقم فقط - إذا كان أكثر من 11، نأخذ أول 11 رقم
        if (strlen($cleaned) > 11) {
            $cleaned = substr($cleaned, 0, 11);
        }

        // إذا كان أقل من 11 رقم، نرفضه
        if (strlen($cleaned) < 11) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Initialize new order with customer info
     */
    public function initialize(Request $request)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        // تنسيق رقم الهاتف قبل التحقق
        $normalizedPhone = $this->normalizePhoneNumber($request->customer_phone);

        if ($normalizedPhone === null || strlen($normalizedPhone) !== 11) {
            return redirect()->back()
                ->withErrors(['customer_phone' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم بعد التنسيق. مثال: 07742209251'])
                ->withInput();
        }

        // استبدال رقم الهاتف بالتنسيق الموحد
        $request->merge(['customer_phone' => $normalizedPhone]);

        // تنسيق رقم الهاتف الثاني إن وجد
        $normalizedPhone2 = null;
        if ($request->filled('customer_phone2')) {
            $normalizedPhone2 = $this->normalizePhoneNumber($request->customer_phone2);
            if ($normalizedPhone2 !== null && strlen($normalizedPhone2) === 11) {
                $request->merge(['customer_phone2' => $normalizedPhone2]);
            } else {
                $request->merge(['customer_phone2' => null]);
            }
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|digits:11',
            'customer_phone2' => 'nullable|string|digits:11',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'customer_phone.digits' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم',
            'customer_phone2.digits' => 'رقم الهاتف الثاني يجب أن يكون بالضبط 11 رقم',
        ]);

        // حذف أي سلة نشطة قديمة للمدير/المجهز (لتجنب التكرار)
        Cart::where('created_by', auth()->id())
            ->where('status', 'active')
            ->get()
            ->each(function($cart) {
                // إرجاع الحجوزات
                foreach ($cart->items as $item) {
                    if ($item->stockReservation) {
                        $item->stockReservation->delete();
                    }
                }
                $cart->delete();
            });

        // إنشاء سلة جديدة مع بيانات الزبون
        $cart = Cart::create([
            'delegate_id' => null, // لا يوجد مندوب للمدير/المجهز
            'created_by' => auth()->id(),
            'cart_name' => 'طلب: ' . $request->customer_name,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_phone2' => $request->customer_phone2,
            'customer_address' => $request->customer_address,
            'customer_social_link' => $request->customer_social_link,
            'notes' => $request->notes,
        ]);

        // حفظ cart_id في session
        session(['current_cart_id' => $cart->id]);

        // التوجيه لصفحة المنتجات مع إظهار رسالة
        return redirect()->route('admin.products.index')
                        ->with('success', 'تم بدء الطلب! الآن اختر المنتجات')
                        ->with('show_cart_info', true);
    }

    /**
     * Submit the current order
     */
    public function submit(Request $request)
    {
        // التأكد من أن المستخدم مدير أو مجهز
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        // قراءة cart_id من session
        $cartId = session('current_cart_id');

        if (!$cartId) {
            return redirect()->route('admin.orders.create.start')
                           ->with('error', 'لا يوجد طلب نشط');
        }

        $cart = Cart::with('items.product', 'items.size')->findOrFail($cartId);

        // التأكد من أن السلة تخص المدير/المجهز الحالي
        if ($cart->created_by !== auth()->id()) {
            abort(403);
        }

        if ($cart->items->count() === 0) {
            return back()->withErrors(['cart' => 'أضف منتجات أولاً']);
        }

        // التحقق من وجود بيانات الزبون في Cart
        if (!$cart->customer_name || !$cart->customer_phone) {
            return redirect()->route('admin.orders.create.start')
                           ->with('error', 'بيانات الزبون غير موجودة. يرجى إنشاء طلب جديد');
        }

        // إنشاء الطلب
        $order = DB::transaction(function() use ($cart) {
            $order = Order::create([
                'cart_id' => $cart->id,
                'delegate_id' => auth()->id(), // المدير/المجهز هو الذي أنشأ الطلب
                'customer_name' => $cart->customer_name,
                'customer_phone' => $cart->customer_phone,
                'customer_phone2' => $cart->customer_phone2,
                'customer_address' => $cart->customer_address,
                'customer_social_link' => $cart->customer_social_link,
                'notes' => $cart->notes,
                'status' => 'pending',
                'total_amount' => $cart->total_amount,
                'confirmed_by' => auth()->id(), // المدير/المجهز هو الذي أنشأ الطلب
            ]);

            // نسخ المنتجات وخصم المخزون
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'size_id' => $cartItem->size_id,
                    'product_name' => $cartItem->product->name,
                    'product_code' => $cartItem->product->code,
                    'size_name' => $cartItem->size->size_name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->price,
                    'subtotal' => $cartItem->subtotal,
                ]);

                // تحديث المخزون الفعلي (خصم الكمية)
                $cartItem->size->decrement('quantity', $cartItem->quantity);

                // تسجيل حركة المواد
                ProductMovement::record([
                    'product_id' => $cartItem->product_id,
                    'size_id' => $cartItem->size_id,
                    'warehouse_id' => $cartItem->product->warehouse_id,
                    'order_id' => $order->id,
                    'movement_type' => 'sell',
                    'quantity' => -$cartItem->quantity,
                    'balance_after' => $cartItem->size->refresh()->quantity,
                    'order_status' => 'pending',
                    'notes' => "بيع من طلب #{$order->order_number} (منشأ من قبل المدير/المجهز)"
                ]);

                // حذف الحجز
                if ($cartItem->stockReservation) {
                    $cartItem->stockReservation->delete();
                }
            }

            // تحديث حالة السلة
            $cart->update(['status' => 'completed']);

            return $order;
        });

        // إرسال Event لإنشاء شحنة في الواسط
        event(new \App\Events\OrderCreated($order));

        // إرسال SweetAlert للمجهز (نفس المخزن) أو المدير
        try {
            $this->sweetAlertService->notifyOrderCreated($order);
        } catch (\Exception $e) {
            \Log::error('Admin/OrderCreationController: Error sending SweetAlert for order_created: ' . $e->getMessage());
        }

        // مسح session
        session()->forget('current_cart_id');

        return redirect()->route('admin.dashboard')
                        ->with('success', 'تم إرسال الطلب بنجاح! رقم الطلب: ' . $order->order_number);
    }
}
