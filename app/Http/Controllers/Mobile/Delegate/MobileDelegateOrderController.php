<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileDelegateOrderController extends Controller
{
    /**
     * جلب قائمة طلبات المندوب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // 1. تحديد نوع الطلبات المطلوبة بناءً على status
        if ($request->status === 'deleted') {
            // عرض فقط الطلبات المحذوفة التي حذفها المجهز (لها deleted_by و deletion_reason)
            $query = Order::onlyTrashed()
                ->where('delegate_id', $user->id)
                ->whereNotNull('deleted_by')
                ->whereNotNull('deletion_reason')
                ->with(['items', 'deletedByUser']);

            // تطبيق البحث في الطلبات المحذوفة
            if ($request->filled('search')) {
                $searchTerm = trim($request->search);
                if (!empty($searchTerm)) {
                    $normalizedSearchTerm = $this->normalizePhoneNumber($searchTerm);
                    $phoneSearchTerm = $normalizedSearchTerm ?: $searchTerm;
                    $this->applyExactSearch($query, $searchTerm, $phoneSearchTerm, true);
                }
            }

            // فلتر حسب التاريخ (للطلبات المحذوفة)
            if ($request->filled('date_from')) {
                $query->whereDate('deleted_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('deleted_at', '<=', $request->date_to);
            }

            // فلتر حسب الوقت (للطلبات المحذوفة)
            if ($request->filled('time_from')) {
                $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                $query->where('deleted_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
            }

            if ($request->filled('time_to')) {
                $dateTo = $request->date_to ?? now()->format('Y-m-d');
                $query->where('deleted_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
            }

            $perPage = min($request->input('per_page', 15), 100);
            $orders = $query->latest('deleted_at')->paginate($perPage);

            // تنسيق البيانات للإرجاع
            $formattedOrders = $orders->map(function($order) {
                return $this->formatOrderListItem($order);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ]);
        }
        // الطلبات العادية (pending/confirmed) والمحذوفة
        else {
            // إذا لم يكن هناك فلتر status، نجلب كل الطلبات (النشطة والمحذوفة)
            if (!$request->filled('status')) {
                $query = Order::withTrashed()
                    ->where('delegate_id', $user->id)
                    ->with(['items']);

                // تنظيف رقم الهاتف للبحث إذا كان البحث برقم هاتف
                $searchTerm = $request->filled('search') ? trim($request->search) : null;
                $phoneSearchTerm = null;
                if ($searchTerm && !empty($searchTerm)) {
                    $normalizedSearchTerm = $this->normalizePhoneNumber($searchTerm);
                    $phoneSearchTerm = $normalizedSearchTerm ?: $searchTerm;
                }

                // فلتر الحالة: نشطة (pending/confirmed) أو محذوفة (soft deleted)
                $query->where(function($q) use ($searchTerm, $phoneSearchTerm) {
                    // الطلبات النشطة (pending أو confirmed) - غير محذوفة
                    $q->where(function($subQ) use ($searchTerm, $phoneSearchTerm) {
                        $subQ->whereNull('deleted_at')
                             ->whereIn('status', ['pending', 'confirmed']);

                        // تطبيق البحث على الطلبات النشطة
                        if ($searchTerm && !empty($searchTerm)) {
                            $this->applyExactSearch($subQ, $searchTerm, $phoneSearchTerm);
                        }
                    })->orWhere(function($subQ) use ($searchTerm, $phoneSearchTerm) {
                        // الطلبات المحذوفة التي حذفها المجهز/المدير (soft deleted)
                        $subQ->whereNotNull('deleted_at')
                             ->whereNotNull('deleted_by')
                             ->whereNotNull('deletion_reason');

                        // تطبيق البحث على الطلبات المحذوفة
                        if ($searchTerm && !empty($searchTerm)) {
                            $this->applyExactSearch($subQ, $searchTerm, $phoneSearchTerm, true);
                        }
                    });
                });

                // تطبيق فلاتر التاريخ
                if ($request->filled('date_from')) {
                    $query->where(function($q) use ($request) {
                        $q->where(function($subQ) use ($request) {
                            $subQ->whereNull('deleted_at')
                                 ->whereDate('created_at', '>=', $request->date_from);
                        })->orWhere(function($subQ) use ($request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->whereDate('deleted_at', '>=', $request->date_from);
                        });
                    });
                }
                if ($request->filled('date_to')) {
                    $query->where(function($q) use ($request) {
                        $q->where(function($subQ) use ($request) {
                            $subQ->whereNull('deleted_at')
                                 ->whereDate('created_at', '<=', $request->date_to);
                        })->orWhere(function($subQ) use ($request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->whereDate('deleted_at', '<=', $request->date_to);
                        });
                    });
                }
                if ($request->filled('time_from')) {
                    $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                    $query->where(function($q) use ($dateFrom, $request) {
                        $q->where(function($subQ) use ($dateFrom, $request) {
                            $subQ->whereNull('deleted_at')
                                 ->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
                        })->orWhere(function($subQ) use ($dateFrom, $request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->where('deleted_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
                        });
                    });
                }
                if ($request->filled('time_to')) {
                    $dateTo = $request->date_to ?? now()->format('Y-m-d');
                    $query->where(function($q) use ($dateTo, $request) {
                        $q->where(function($subQ) use ($dateTo, $request) {
                            $subQ->whereNull('deleted_at')
                                 ->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
                        })->orWhere(function($subQ) use ($dateTo, $request) {
                            $subQ->whereNotNull('deleted_at')
                                 ->where('deleted_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
                        });
                    });
                }

                // إضافة deletedByUser للعلاقات
                $query->with('deletedByUser');

                // ترتيب مختلط: للطلبات المحذوفة deleted_at، للباقي created_at
                $perPage = min($request->input('per_page', 15), 100);
                $orders = $query->orderByRaw('CASE WHEN deleted_at IS NOT NULL THEN deleted_at ELSE created_at END DESC')
                               ->paginate($perPage);

                // تنسيق البيانات للإرجاع
                $formattedOrders = $orders->map(function($order) {
                    return $this->formatOrderListItem($order);
                });

                return response()->json([
                    'success' => true,
                    'data' => $formattedOrders,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'last_page' => $orders->lastPage(),
                        'has_more' => $orders->hasMorePages(),
                    ],
                ]);
            }

            // إذا كان هناك فلتر status (pending/confirmed)
            $query = Order::where('delegate_id', $user->id)->with(['items']);

            // تطبيق البحث أولاً ثم فلتر الحالة
            if ($request->filled('search')) {
                $searchTerm = trim($request->search);
                if (!empty($searchTerm)) {
                    $normalizedSearchTerm = $this->normalizePhoneNumber($searchTerm);
                    $phoneSearchTerm = $normalizedSearchTerm ?: $searchTerm;
                    $this->applyExactSearch($query, $searchTerm, $phoneSearchTerm);
                }
            }

            // تطبيق فلتر الحالة (pending/confirmed) بعد البحث
            if ($request->filled('status') && in_array($request->status, ['pending', 'confirmed'])) {
                $query->where('status', $request->status);
            }

            // فلاتر التاريخ والوقت
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('time_from')) {
                $dateFrom = $request->date_from ?? now()->format('Y-m-d');
                $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
            }

            if ($request->filled('time_to')) {
                $dateTo = $request->date_to ?? now()->format('Y-m-d');
                $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
            }

            $perPage = min($request->input('per_page', 15), 100);
            $orders = $query->latest()->paginate($perPage);

            // تنسيق البيانات للإرجاع
            $formattedOrders = $orders->map(function($order) {
                return $this->formatOrderListItem($order);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedOrders,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'last_page' => $orders->lastPage(),
                    'has_more' => $orders->hasMorePages(),
                ],
            ]);
        }
    }

    /**
     * جلب تفاصيل طلب واحد
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // جلب الطلب مع العلاقات (بما في ذلك المحذوفة)
        $order = Order::withTrashed()
                     ->with([
                         'items.product.primaryImage',
                         'items.size',
                         'alwaseetShipment',
                         'deletedByUser'
                     ])
                     ->where('id', $id)
                     ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
                'error_code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        // التحقق من أن الطلب يخص المندوب
        if ($order->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                'error_code' => 'FORBIDDEN_ORDER',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'order' => $this->formatOrderData($order),
            ],
        ]);
    }

    /**
     * تنسيق بيانات الطلب للقائمة (مبسط)
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderListItem(Order $order)
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_phone2' => $order->customer_phone2,
            'customer_address' => $order->customer_address,
            'customer_social_link' => $order->customer_social_link,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'items_count' => $order->items->count(),
            'delivery_code' => $order->delivery_code,
            'created_at' => $order->created_at->toIso8601String(),
            'confirmed_at' => $order->confirmed_at ? $order->confirmed_at->toIso8601String() : null,
            'deleted_at' => $order->deleted_at ? $order->deleted_at->toIso8601String() : null,
            'deleted_by' => $order->deleted_by,
            'deletion_reason' => $order->deletion_reason,
            'deleted_by_user' => $order->deletedByUser ? [
                'id' => $order->deletedByUser->id,
                'name' => $order->deletedByUser->name,
            ] : null,
        ];
    }

    /**
     * تنسيق بيانات الطلب الكاملة
     *
     * @param Order $order
     * @return array
     */
    private function formatOrderData(Order $order)
    {
        // التأكد من تحميل العلاقات
        if (!$order->relationLoaded('items')) {
            $order->load('items.product.primaryImage', 'items.size');
        }

        // تنسيق عناصر الطلب
        $items = $order->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'product_code' => $item->product_code,
                'size_id' => $item->size_id,
                'size_name' => $item->size_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'subtotal' => (float) $item->subtotal,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'code' => $item->product->code,
                    'primary_image' => $item->product->primaryImage ? $item->product->primaryImage->image_url : null,
                ] : null,
            ];
        });

        // تنسيق معلومات الشحنة
        $alwaseetShipment = null;
        if ($order->alwaseetShipment) {
            $shipment = $order->alwaseetShipment;
            $alwaseetShipment = [
                'id' => $shipment->id,
                'alwaseet_order_id' => $shipment->alwaseet_order_id,
                'client_name' => $shipment->client_name,
                'client_mobile' => $shipment->client_mobile,
                'client_mobile2' => $shipment->client_mobile2,
                'city_id' => $shipment->city_id,
                'city_name' => $shipment->city_name,
                'region_id' => $shipment->region_id,
                'region_name' => $shipment->region_name,
                'location' => $shipment->location,
                'price' => (float) $shipment->price,
                'delivery_price' => (float) $shipment->delivery_price,
                'package_size' => $shipment->package_size,
                'type_name' => $shipment->type_name,
                'status_id' => $shipment->status_id,
                'status' => $shipment->status,
                'items_number' => $shipment->items_number,
                'merchant_notes' => $shipment->merchant_notes,
                'issue_notes' => $shipment->issue_notes,
                'replacement' => (bool) $shipment->replacement,
                'qr_id' => $shipment->qr_id,
                'qr_link' => $shipment->qr_link,
                'alwaseet_created_at' => $shipment->alwaseet_created_at ? $shipment->alwaseet_created_at->toIso8601String() : null,
                'alwaseet_updated_at' => $shipment->alwaseet_updated_at ? $shipment->alwaseet_updated_at->toIso8601String() : null,
                'synced_at' => $shipment->synced_at ? $shipment->synced_at->toIso8601String() : null,
            ];
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_phone2' => $order->customer_phone2,
            'customer_address' => $order->customer_address,
            'customer_social_link' => $order->customer_social_link,
            'notes' => $order->notes,
            'status' => $order->status,
            'total_amount' => (float) $order->total_amount,
            'delivery_code' => $order->delivery_code,
            'items' => $items,
            'alwaseet_shipment' => $alwaseetShipment,
            'created_at' => $order->created_at->toIso8601String(),
            'confirmed_at' => $order->confirmed_at ? $order->confirmed_at->toIso8601String() : null,
            'deleted_at' => $order->deleted_at ? $order->deleted_at->toIso8601String() : null,
            'deleted_by' => $order->deleted_by,
            'deletion_reason' => $order->deletion_reason,
            'deleted_by_user' => $order->deletedByUser ? [
                'id' => $order->deletedByUser->id,
                'name' => $order->deletedByUser->name,
            ] : null,
        ];
    }

    /**
     * تنسيق رقم الهاتف إلى صيغة موحدة
     *
     * @param string $phone
     * @return string|null
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
     * تطبيق البحث الدقيق على جميع مكونات الطلب
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $searchTerm
     * @param string|null $phoneSearchTerm
     * @param bool $includeDeletedFields
     * @return void
     */
    private function applyExactSearch($query, $searchTerm, $phoneSearchTerm = null, $includeDeletedFields = false)
    {
        // البحث في جميع الحقول (مطابقة دقيقة)
        $query->where(function($q) use ($searchTerm, $phoneSearchTerm, $includeDeletedFields) {
            $q->where('order_number', '=', $searchTerm)
              ->orWhere('customer_name', '=', $searchTerm)
              ->orWhere('customer_phone', '=', $phoneSearchTerm ?: $searchTerm)
              ->orWhere('customer_social_link', '=', $searchTerm)
              ->orWhere('customer_address', '=', $searchTerm)
              ->orWhere(function($subQ) use ($searchTerm) {
                  $subQ->whereNotNull('delivery_code')
                       ->where('delivery_code', $searchTerm);
              })
              ->orWhere('notes', '=', $searchTerm);

            // إضافة البحث في deletion_reason للطلبات المحذوفة
            if ($includeDeletedFields) {
                $q->orWhere('deletion_reason', '=', $searchTerm);
            }

            // البحث في عناصر الطلب (product_name, product_code, size_name)
            $q->orWhereHas('items', function($itemQuery) use ($searchTerm) {
                $itemQuery->where('product_name', '=', $searchTerm)
                         ->orWhere('product_code', '=', $searchTerm)
                         ->orWhere('size_name', '=', $searchTerm);
            });
        });
    }

    /**
     * تحديث الطلب
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // جلب الطلب
        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
                'error_code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                'error_code' => 'FORBIDDEN_ORDER',
            ], 403);
        }

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل الطلبات المقيدة',
                'error_code' => 'ORDER_CONFIRMED',
            ], 400);
        }

        // تنسيق رقم الهاتف
        $normalizedPhone = $this->normalizePhoneNumber($request->customer_phone);
        if ($normalizedPhone === null || strlen($normalizedPhone) !== 11) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم بعد التنسيق',
                'error_code' => 'INVALID_PHONE',
            ], 422);
        }
        $request->merge(['customer_phone' => $normalizedPhone]);

        // تنسيق رقم الهاتف الثاني إن وجد
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
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ], [
            'customer_phone.digits' => 'رقم الهاتف يجب أن يكون بالضبط 11 رقم',
            'customer_phone2.digits' => 'رقم الهاتف الثاني يجب أن يكون بالضبط 11 رقم',
        ]);

        try {
            DB::transaction(function() use ($request, $order, $user) {
                // تحميل العناصر القديمة
                $oldItems = $order->items()->get();

                // إرجاع المنتجات القديمة للمخزون
                foreach ($oldItems as $oldItem) {
                    if ($oldItem->size) {
                        $oldItem->size->increment('quantity', $oldItem->quantity);
                    }
                }

                // حذف المنتجات القديمة
                $order->items()->delete();

                // تحديث معلومات الزبون
                $order->update($request->only([
                    'customer_name',
                    'customer_phone',
                    'customer_phone2',
                    'customer_address',
                    'customer_social_link',
                    'notes',
                ]));

                // إضافة المنتجات الجديدة
                $totalAmount = 0;
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $size = ProductSize::findOrFail($item['size_id']);

                    // التحقق من توفر الكمية
                    if ($size->quantity < $item['quantity']) {
                        throw new \Exception("الكمية المتوفرة من {$product->name} - {$size->size_name} غير كافية. المتوفر: {$size->quantity}");
                    }

                    // استخدام effective_price (يشمل التخفيضات النشطة)
                    $unitPrice = $product->effective_price;
                    $subtotal = $unitPrice * $item['quantity'];
                    $totalAmount += $subtotal;

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'size_id' => $item['size_id'],
                        'product_code' => $product->code,
                        'product_name' => $product->name,
                        'size_name' => $size->size_name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                    ]);

                    // خصم من المخزون
                    $size->decrement('quantity', $item['quantity']);
                }

                // تحديث المبلغ الإجمالي
                $order->update(['total_amount' => $totalAmount]);
            });

            // إعادة تحميل الطلب مع العلاقات
            $order->refresh();
            $order->load(['items.product.primaryImage', 'items.size', 'alwaseetShipment']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الطلب بنجاح',
                'data' => [
                    'order' => $this->formatOrderData($order),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateOrderController: Error updating order', [
                'order_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الطلب: ' . $e->getMessage(),
                'error_code' => 'UPDATE_ERROR',
            ], 500);
        }
    }

    /**
     * حذف الطلب (soft delete) مع إرجاع المنتجات للمخزن
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // جلب الطلب
        $order = Order::where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود',
                'error_code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                'error_code' => 'FORBIDDEN_ORDER',
            ], 403);
        }

        // التحقق من أن الطلب يمكن حذفه (pending أو confirmed)
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذا الطلب',
                'error_code' => 'ORDER_CANNOT_BE_DELETED',
            ], 400);
        }

        $request->validate([
            'deletion_reason' => 'required|string|max:500',
        ], [
            'deletion_reason.required' => 'يجب إدخال سبب الحذف',
        ]);

        try {
            DB::transaction(function() use ($order, $request, $user) {
                // تحميل العلاقات المطلوبة
                $order->load('items.size', 'items.product');

                // إرجاع جميع المنتجات للمخزن
                foreach ($order->items as $item) {
                    if ($item->size) {
                        $item->size->increment('quantity', $item->quantity);

                        // تسجيل حركة الحذف
                        ProductMovement::record([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'order_id' => $order->id,
                            'delegate_id' => $user->id,
                            'movement_type' => 'delete',
                            'quantity' => $item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "حذف طلب #{$order->order_number}"
                        ]);
                    }
                }

                // تسجيل من قام بالحذف وسبب الحذف
                $order->deleted_by = $user->id;
                $order->deletion_reason = $request->deletion_reason;
                $order->save();

                // إرسال SweetAlert للمجهز (نفس المخزن) أو المدير أو المندوب
                try {
                    $sweetAlertService = app(SweetAlertService::class);
                    $sweetAlertService->notifyOrderDeleted($order);
                } catch (\Exception $e) {
                    Log::error('MobileDelegateOrderController: Error sending SweetAlert for order_deleted', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                // soft delete للطلب
                $order->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطلب بنجاح وإرجاع جميع المنتجات للمخزن',
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateOrderController: Error deleting order', [
                'order_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage(),
                'error_code' => 'DELETE_ERROR',
            ], 500);
        }
    }

    /**
     * استرجاع الطلب مع خصم المنتجات من المخزن
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id, Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // جلب الطلب المحذوف
        $order = Order::onlyTrashed()->where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود أو غير محذوف',
                'error_code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                'error_code' => 'FORBIDDEN_ORDER',
            ], 403);
        }

        try {
            // التحقق من التوفر أولاً
            $order->load('items.size', 'items.product');
            $allAvailable = true;
            $shortages = [];

            foreach ($order->items as $item) {
                $available = $item->size ? $item->size->quantity : 0;
                if ($available < $item->quantity) {
                    $allAvailable = false;
                    $shortages[] = "{$item->product_name} ({$item->size_name}): المطلوب {$item->quantity}، المتوفر {$available}";
                }
            }

            if (!$allAvailable) {
                $errorMessage = 'لا يمكن استرجاع الطلب - المنتجات التالية غير متوفرة بالكمية المطلوبة: ' . implode(' | ', $shortages);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error_code' => 'INSUFFICIENT_STOCK',
                    'shortages' => $shortages,
                ], 400);
            }

            DB::transaction(function() use ($order, $user) {
                // خصم المنتجات من المخزن
                foreach ($order->items as $item) {
                    if ($item->size) {
                        $item->size->decrement('quantity', $item->quantity);

                        // تسجيل حركة الاسترجاع من الحذف
                        ProductMovement::record([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'order_id' => $order->id,
                            'delegate_id' => $user->id,
                            'movement_type' => 'restore',
                            'quantity' => -$item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "استرجاع من حذف طلب #{$order->order_number}"
                        ]);
                    }
                }

                // استرجاع الطلب
                $order->restore();
                $order->status = 'pending';
                $order->deleted_by = null;
                $order->deletion_reason = null;
                $order->save();
            });

            // إعادة تحميل الطلب مع العلاقات
            $order->refresh();
            $order->load(['items.product.primaryImage', 'items.size', 'alwaseetShipment']);

            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع الطلب بنجاح وخصم المنتجات من المخزن',
                'data' => [
                    'order' => $this->formatOrderData($order),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateOrderController: Error restoring order', [
                'order_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استرجاع الطلب: ' . $e->getMessage(),
                'error_code' => 'RESTORE_ERROR',
            ], 500);
        }
    }

    /**
     * حذف الطلب نهائياً (hard delete)
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete($id, Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        // جلب الطلب المحذوف
        $order = Order::onlyTrashed()->where('id', $id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'الطلب غير موجود أو غير محذوف',
                'error_code' => 'ORDER_NOT_FOUND',
            ], 404);
        }

        // التأكد من أن الطلب يخص المندوب الحالي
        if ($order->delegate_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للوصول إلى هذا الطلب',
                'error_code' => 'FORBIDDEN_ORDER',
            ], 403);
        }

        // التأكد من أن الطلب محذوف (soft deleted)
        if (!$order->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'يمكن الحذف النهائي فقط للطلبات المحذوفة',
                'error_code' => 'ORDER_NOT_DELETED',
            ], 400);
        }

        try {
            DB::transaction(function () use ($order) {
                // حذف عناصر الطلب نهائياً
                $order->items()->forceDelete();

                // حذف الطلب نهائياً
                $order->forceDelete();
            });

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الطلب نهائياً',
            ]);
        } catch (\Exception $e) {
            Log::error('MobileDelegateOrderController: Error force deleting order', [
                'order_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الحذف النهائي: ' . $e->getMessage(),
                'error_code' => 'FORCE_DELETE_ERROR',
            ], 500);
        }
    }
}

