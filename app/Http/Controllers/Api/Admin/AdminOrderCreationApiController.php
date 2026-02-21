<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductMovement;
use App\Models\ProductSize;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminOrderCreationApiController extends Controller
{
    protected $sweetAlertService;

    public function __construct(SweetAlertService $sweetAlertService)
    {
        $this->sweetAlertService = $sweetAlertService;
    }

    /**
     * تنسيق رقم الهاتف إلى صيغة موحدة
     */
    /**
     * Search products for order creation
     */
    public function searchProducts(Request $request)
    {
        $user = Auth::user();
        $query = $request->input('query');
        $warehouseId = $request->input('warehouse_id');
        $genderType = $request->input('gender_type');
        $onSale = $request->boolean('on_sale');

        $productsQuery = \App\Models\Product::query();

        // Suppliers only see their own products
        if ($user->isSupplier()) {
            $warehouseIds = $user->warehouses->pluck('id')->toArray();
            $productsQuery->whereIn('warehouse_id', $warehouseIds);
        } elseif ($warehouseId) {
            $productsQuery->where('warehouse_id', $warehouseId);
        }

        if ($genderType) {
            if ($genderType == 'boys') {
                $productsQuery->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($genderType == 'girls') {
                $productsQuery->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
                $productsQuery->where('gender_type', $genderType);
            }
        }

        if ($query) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%");
            });
        }

        // Filter by discount if requested
        if ($onSale) {
            $productsQuery->where(function ($q) {
                // 1. Product specific active discount
                $q->where(function ($sq) {
                    $sq->whereNotNull('discount_type')
                        ->where('discount_type', '!=', 'none')
                        ->whereNotNull('discount_value')
                        ->where(function ($dq) {
                            $now = now();
                            $dq->where(function ($noDates) {
                                $noDates->whereNull('discount_start_date')
                                    ->whereNull('discount_end_date');
                            })->orWhere(function ($withDates) use ($now) {
                                $withDates->where(function ($sd) use ($now) {
                                    $sd->whereNull('discount_start_date')
                                        ->orWhere('discount_start_date', '<=', $now);
                                })->where(function ($ed) use ($now) {
                                    $ed->whereNull('discount_end_date')
                                        ->orWhere('discount_end_date', '>=', $now);
                                });
                            });
                        });
                })
                    // 2. OR Warehouse active promotion
                    ->orWhereHas('warehouse.promotions', function ($pq) {
                        $now = now();
                        $pq->where('is_active', true)
                            ->where('start_date', '<=', $now)
                            ->where('end_date', '>=', $now);
                    });
            });
        }

        $products = $productsQuery->with(['images', 'warehouse.activePromotion', 'sizes.reservations'])
            ->where('is_hidden', false)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $data = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'selling_price' => $product->selling_price,
                'effective_price' => $product->effective_price,
                'gender_type' => $product->gender_type,
                'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                'images' => $product->images->map(function ($img) {
                    return ['id' => $img->id, 'url' => $img->image_url];
                }),
                'has_discount' => $product->effective_price < $product->selling_price,
                'discount_info' => $product->getDiscountInfo(),
                'sizes' => $product->sizes->map(function ($size) {
                    $reserved = $size->reservations()->sum('quantity_reserved');
                    return [
                        'id' => $size->id,
                        'size_name' => $size->size_name,
                        'quantity' => $size->quantity,
                        'available_quantity' => $size->quantity - $reserved,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data->values(),
        ]);
    }

    /**
     * Get filter options for product search
     */
    public function getSearchFilters()
    {
        $warehouses = \App\Models\Warehouse::select('id', 'name')->get();

        // Gender types from Product model (enums or common values)
        $types = [
            ['id' => 'boys', 'name' => 'ولادي'],
            ['id' => 'girls', 'name' => 'بناتي'],
            ['id' => 'boys_girls', 'name' => 'للجنسين'],
            ['id' => 'accessories', 'name' => 'إكسسوارات'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $warehouses,
                'types' => $types,
            ],
        ]);
    }

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

    /**
     * Step 1: Initialize new order with customer info
     */
    public function initialize(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isSupplier()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $normalizedPhone = $this->normalizePhoneNumber($request->customer_phone);
        if ($normalizedPhone === null) {
            return response()->json(['success' => false, 'message' => 'رقم الهاتف غير صحيح.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // حذف السلة النشطة القديمة لنفس المسؤول
        Cart::where('created_by', $user->id)
            ->where('status', 'active')
            ->get()
            ->each(function ($cart) {
                foreach ($cart->items as $item) {
                    if ($item->stockReservation) {
                        $item->stockReservation->delete();
                    }
                }
                $cart->delete();
            });

        // إنشاء سلة جديدة
        $cart = Cart::create([
            'created_by' => $user->id,
            'cart_name' => 'طلب: ' . $request->customer_name,
            'status' => 'active',
            'expires_at' => now()->addHours(24),
            'customer_name' => $request->customer_name,
            'customer_phone' => $normalizedPhone,
            'customer_phone2' => $this->normalizePhoneNumber($request->customer_phone2),
            'customer_address' => $request->customer_address,
            'customer_social_link' => $request->customer_social_link,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء مسودة الطلب بنجاح.',
            'data' => $cart
        ]);
    }

    /**
     * Step 2: Add item to current cart
     */
    public function addItem(Request $request)
    {
        $user = Auth::user();
        $cart = Cart::where('created_by', $user->id)->where('status', 'active')->first();

        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'ابدأ طلباً جديداً أولاً.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'size_id' => 'required|exists:product_sizes,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $product = \App\Models\Product::findOrFail($request->product_id);
        $size = \App\Models\ProductSize::findOrFail($request->size_id);

        if ($size->product_id !== $product->id) {
            return response()->json(['success' => false, 'message' => 'القياس المحدد لا يخص هذا المنتج.'], 400);
        }

        $availableQuantity = $size->available_quantity;
        if ($availableQuantity < $request->quantity) {
            return response()->json(['success' => false, 'message' => "الكمية المطلوبة غير متوفرة. المتوفر: $availableQuantity"], 400);
        }

        $existingItem = $cart->items()->where('product_id', $product->id)->where('size_id', $size->id)->first();

        DB::transaction(function () use ($cart, $product, $size, $request, $existingItem) {
            if ($existingItem) {
                $newQuantity = $existingItem->quantity + $request->quantity;
                $existingItem->update(['quantity' => $newQuantity]);
                if ($existingItem->stockReservation) {
                    $existingItem->stockReservation->update(['quantity_reserved' => $newQuantity]);
                }
            } else {
                $cartItem = \App\Models\CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'quantity' => $request->quantity,
                    'price' => $product->effective_price,
                ]);

                \App\Models\StockReservation::create([
                    'product_size_id' => $size->id,
                    'cart_item_id' => $cartItem->id,
                    'quantity_reserved' => $request->quantity,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المنتج إلى السلة.',
            'data' => $cart->refresh()->load('items.product', 'items.size')
        ]);
    }

    /**
     * Update item quantity
     */
    public function updateItem(Request $request, $itemId)
    {
        $cartItem = \App\Models\CartItem::findOrFail($itemId);
        if ($cartItem->cart->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        $request->validate(['quantity' => 'required|integer|min:1']);

        $size = $cartItem->size;
        $availableQuantity = $size->available_quantity + $cartItem->quantity;

        if ($availableQuantity < $request->quantity) {
            return response()->json(['success' => false, 'message' => "الكمية المطلوبة غير متوفرة. المتوفر: $availableQuantity"], 400);
        }

        DB::transaction(function () use ($cartItem, $request) {
            $cartItem->update(['quantity' => $request->quantity]);
            if ($cartItem->stockReservation) {
                $cartItem->stockReservation->update(['quantity_reserved' => $request->quantity]);
            }
        });

        return response()->json(['success' => true, 'message' => 'تم تحديث الكمية.']);
    }

    /**
     * Remove item from cart
     */
    public function removeItem($itemId)
    {
        $cartItem = \App\Models\CartItem::findOrFail($itemId);
        if ($cartItem->cart->created_by !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح.'], 403);
        }

        DB::transaction(function () use ($cartItem) {
            $cartItem->stockReservation()->delete();
            $cartItem->delete();
        });

        return response()->json(['success' => true, 'message' => 'تم حذف المنتج من السلة.']);
    }

    /**
     * Get Current Active Cart
     */
    public function currentCart()
    {
        $cart = Cart::where('created_by', Auth::id())
            ->where('status', 'active')
            ->with(['items.product', 'items.size'])
            ->first();

        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'لا توجد سلة نشطة.'], 404);
        }

        return response()->json(['success' => true, 'data' => $cart]);
    }

    /**
     * Final Step: Submit the order
     */
    public function submit(Request $request)
    {
        $cart = Cart::where('created_by', Auth::id())
            ->where('status', 'active')
            ->with(['items.product', 'items.size'])
            ->first();

        if (!$cart || $cart->items->count() === 0) {
            return response()->json(['success' => false, 'message' => 'السلة فارغة أو غير موجودة.'], 422);
        }

        try {
            $order = DB::transaction(function () use ($cart) {
                $order = Order::create([
                    'cart_id' => $cart->id,
                    'delegate_id' => auth()->id(),
                    'customer_name' => $cart->customer_name,
                    'customer_phone' => $cart->customer_phone,
                    'customer_phone2' => $cart->customer_phone2,
                    'customer_address' => $cart->customer_address,
                    'customer_social_link' => $cart->customer_social_link,
                    'notes' => $cart->notes,
                    'status' => 'pending',
                    'total_amount' => $cart->total_amount,
                    'confirmed_by' => auth()->id(),
                ]);

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

                    $cartItem->size->decrement('quantity', $cartItem->quantity);

                    ProductMovement::record([
                        'product_id' => $cartItem->product_id,
                        'size_id' => $cartItem->size_id,
                        'warehouse_id' => $cartItem->product->warehouse_id,
                        'order_id' => $order->id,
                        'movement_type' => 'sell',
                        'quantity' => -$cartItem->quantity,
                        'balance_after' => $cartItem->size->refresh()->quantity,
                        'order_status' => 'pending',
                        'notes' => "بيع من طلب #{$order->order_number} (تطبيق الإدارة)"
                    ]);

                    if ($cartItem->stockReservation) {
                        $cartItem->stockReservation->delete();
                    }
                }

                $cart->update(['status' => 'completed']);
                return $order;
            });

            event(new \App\Events\OrderCreated($order));
            $this->sweetAlertService->notifyOrderCreated($order);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الطلب بنجاح.',
                'order_number' => $order->order_number
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'خطأ أثناء تنفيذ الطلب: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel the current active order/cart
     */
    public function cancel()
    {
        $user = Auth::user();
        $cart = Cart::where('created_by', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return response()->json(['success' => false, 'message' => 'لا توجد سلة نشطة لإلغائها.'], 404);
        }

        DB::transaction(function () use ($cart) {
            foreach ($cart->items as $item) {
                if ($item->stockReservation) {
                    $item->stockReservation->delete();
                }
            }
            $cart->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب ومسح المسودة بنجاح.'
        ]);
    }
}
