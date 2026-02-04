<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class MaterialApiController extends Controller
{
    /**
     * Get raw materials list (replicates getMaterialsListManagement).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Base query
        $query = Order::query();

        // فلتر الصلاحيات
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
            $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر الحالة (pending بشكل افتراضي)
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                // عرض فقط الطلبات المحذوفة التي حذفها المدير/المجهز
                $query->onlyTrashed()
                    ->whereNotNull('deleted_by')
                    ->whereNotNull('deletion_reason');
            } else {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('status', 'pending');
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب
        if ($request->filled('delegate_id')) {
            $query->where('delegate_id', $request->delegate_id);
        }

        // فلتر حالة التدقيق
        if ($request->filled('size_reviewed')) {
            $query->where('size_reviewed', $request->size_reviewed);
        }

        // فلتر حالة تأكيد الرسالة
        if ($request->filled('message_confirmed')) {
            $query->where('message_confirmed', $request->message_confirmed);
        }

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                    ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                    ->orWhereHas('delegate', function ($delegateQuery) use ($searchTerm) {
                        $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('items.product', function ($productQuery) use ($searchTerm) {
                        $productQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('code', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        $orders = $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse'
        ])->get();

        // فلترة items حسب المخزن والصلاحيات
        foreach ($orders as $order) {
            $order->items = $order->items->filter(function ($item) use ($request) {
                if (!$item->product)
                    return false;

                // فلتر المخزن: عرض فقط منتجات المخزن المحدد
                if ($request->filled('warehouse_id')) {
                    if ($item->product->warehouse_id != $request->warehouse_id) {
                        return false;
                    }
                }

                // فلتر صلاحيات المجهز
                if (Auth::user()->isSupplier()) {
                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                    if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds)) {
                        return false;
                    }
                }

                return true;
            })->values(); // Reset keys after filter
        }

        // إزالة الطلبات التي لا تحتوي على items بعد الفلترة
        $orders = $orders->filter(function ($order) {
            return $order->items->count() > 0;
        })->values(); // Reset keys

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'data' => $orders
        ]);
    }

    /**
     * Get grouped materials list (replicates getMaterialsListManagementGrouped).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function grouped(Request $request)
    {
        // Base query
        $query = Order::query();

        // فلتر الصلاحيات
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
            $query->whereHas('items.product', function ($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر الحالة (pending بشكل افتراضي)
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                // عرض فقط الطلبات المحذوفة التي حذفها المدير/المجهز
                $query->onlyTrashed()
                    ->whereNotNull('deleted_by')
                    ->whereNotNull('deletion_reason');
            } else {
                $query->where('status', $request->status);
            }
        } else {
            $query->where('status', 'pending');
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب
        if ($request->filled('delegate_id')) {
            $query->where('delegate_id', $request->delegate_id);
        }

        // فلتر حالة التدقيق
        if ($request->filled('size_reviewed')) {
            $query->where('size_reviewed', $request->size_reviewed);
        }

        // فلتر حالة تأكيد الرسالة
        if ($request->filled('message_confirmed')) {
            $query->where('message_confirmed', $request->message_confirmed);
        }

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                    ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                    ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                    ->orWhereHas('delegate', function ($delegateQuery) use ($searchTerm) {
                        $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('items.product', function ($productQuery) use ($searchTerm) {
                        $productQuery->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('code', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // فلتر حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        $orders = $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse'
        ])->get();

        // تجميع المواد حسب كود المنتج
        $materialsGrouped = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!$item->product)
                    continue;

                // فلتر المخزن: عرض فقط منتجات المخزن المحدد
                if ($request->filled('warehouse_id')) {
                    if ($item->product->warehouse_id != $request->warehouse_id) {
                        continue; // تجاهل المنتجات من مخازن أخرى
                    }
                }

                // فلتر صلاحيات المجهز
                if (Auth::user()->isSupplier()) {
                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                    if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds)) {
                        continue; // تجاهل المنتجات من مخازن ليس لديه صلاحية عليها
                    }
                }

                // استخدام كود المنتج كمفتاح للتجميع
                $productCode = $item->product->code;
                $sizeKey = $item->size_name ?? 'no_size';

                if (!isset($materialsGrouped[$productCode])) {
                    $materialsGrouped[$productCode] = [
                        'product' => $item->product,
                        'sizes' => []
                    ];
                }

                // إضافة الحجم إذا لم يكن موجوداً
                if (!isset($materialsGrouped[$productCode]['sizes'][$sizeKey])) {
                    $materialsGrouped[$productCode]['sizes'][$sizeKey] = [
                        'size_name' => $item->size_name,
                        'total_quantity' => 0,
                        'orders' => []
                    ];
                }

                $materialsGrouped[$productCode]['sizes'][$sizeKey]['total_quantity'] += $item->quantity;
                $materialsGrouped[$productCode]['sizes'][$sizeKey]['orders'][] = [
                    'order_number' => $order->order_number,
                    'quantity' => $item->quantity,
                    'order_id' => $order->id
                ];
            }
        }

        // تحويل إلى مصفوفة مسطحة مع ترتيب حسب الكود
        $materials = [];
        ksort($materialsGrouped); // ترتيب حسب كود المنتج

        foreach ($materialsGrouped as $productCode => $group) {
            // ترتيب الأحجام داخل كل منتج
            ksort($group['sizes']);

            // تحويل sizes إلى array index-based لسهولة الاستخدام في JSON
            $sizesList = [];

            foreach ($group['sizes'] as $sizeKey => $sizeData) {
                $sizesList[] = [
                    'size_name' => $sizeData['size_name'],
                    'total_quantity' => $sizeData['total_quantity'],
                    'orders' => $sizeData['orders']
                ];
            }

            $materials[] = [
                'product' => $group['product'],
                'product_code' => $productCode,
                'sizes' => $sizesList // نستخدم قائمة الأحجام بدلاً من تكرار المنتج لكل حجم
            ];
        }

        return response()->json([
            'success' => true,
            'count' => count($materials),
            'data' => $materials
        ]);
    }
}
