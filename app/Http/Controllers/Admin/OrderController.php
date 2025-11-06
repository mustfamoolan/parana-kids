<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\ReturnItem;
use App\Models\ExchangeItem;
use App\Models\OrderItem;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * صفحة إدارة الطلبات الموحدة (pending + confirmed فقط كبداية)
     */
    public function management(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // جلب قائمة المخازن حسب الصلاحيات
        if (Auth::user()->isSupplier()) {
            $warehouses = Auth::user()->warehouses;
        } else {
            $warehouses = \App\Models\Warehouse::all();
        }

        // جلب قائمة المجهزين (المديرين والمجهزين) والمندوبين للفلترة
        $suppliers = \App\Models\User::whereIn('role', ['admin', 'supplier'])->get();
        $delegates = \App\Models\User::where('role', 'delegate')->get();

        // Base query
        $query = Order::query();

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر الحالة
        if ($request->status === 'deleted') {
            // عرض فقط الطلبات المحذوفة التي حذفها المدير/المجهز (لها deleted_by و deletion_reason)
            // لا نعرض الطلبات المحذوفة من المندوب لأنها حذف نهائي
            $query->onlyTrashed()
                  ->whereNotNull('deleted_by')
                  ->whereNotNull('deletion_reason')
                  ->with(['deletedByUser']);
        } elseif ($request->filled('status') && in_array($request->status, ['pending', 'confirmed'])) {
            $query->where('status', $request->status);
        } else {
            // افتراضياً: عرض pending و confirmed مع المحذوفة (الكل)
            // نستخدم withTrashed() ليشمل الطلبات المحذوفة أيضاً
            $query->withTrashed()->where(function($q) {
                // الطلبات النشطة (pending أو confirmed) - غير محذوفة
                $q->where(function($subQ) {
                    $subQ->whereNull('deleted_at')
                         ->whereIn('status', ['pending', 'confirmed']);
                })->orWhere(function($subQ) {
                    // الطلبات المحذوفة التي حذفها المدير/المجهز (soft deleted)
                    $subQ->whereNotNull('deleted_at')
                         ->whereNotNull('deleted_by')
                         ->whereNotNull('deletion_reason');
                });
            });
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز (الطلبات التي قيدها المجهز)
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب (الطلبات التي أنشأها المندوب)
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
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
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

        // فلتر حسب تاريخ التقييد (للطلبات المقيدة)
        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }

        // فلتر حسب تاريخ الإرجاع (للطلبات المسترجعة)
        if ($request->filled('returned_from')) {
            $query->whereDate('returned_at', '>=', $request->returned_from);
        }

        if ($request->filled('returned_to')) {
            $query->whereDate('returned_at', '<=', $request->returned_to);
        }

        $perPage = $request->input('per_page', 15);

        // تحميل العلاقات المطلوبة
        $query->with(['delegate', 'items.product.warehouse', 'items.product.primaryImage', 'confirmedBy', 'processedBy']);

        // إضافة deletedByUser للطلبات المحذوفة
        if ($request->status === 'deleted' || (!$request->filled('status') || $request->status !== 'pending' && $request->status !== 'confirmed')) {
            $query->with('deletedByUser');
        }

        // ترتيب الطلبات: للطلبات المحذوفة استخدم deleted_at، وإلا استخدم created_at
        if ($request->status === 'deleted') {
            $orders = $query->latest('deleted_at')
                       ->paginate($perPage)
                       ->appends($request->except('page'));
        } else {
            // ترتيب مختلط: للطلبات المحذوفة deleted_at، للباقي created_at
            $orders = $query->orderByRaw('CASE WHEN deleted_at IS NOT NULL THEN deleted_at ELSE created_at END DESC')
                           ->paginate($perPage)
                           ->appends($request->except('page'));
        }

        // حساب المبالغ الإجمالية والأرباح للمدير فقط
        $pendingTotalAmount = 0;
        $confirmedTotalAmount = 0;
        $pendingProfitAmount = 0;
        $confirmedProfitAmount = 0;

        if (Auth::user()->isAdmin()) {
            $accessibleWarehouseIdsForTotal = null;
            if (Auth::user()->isSupplier()) {
                $accessibleWarehouseIdsForTotal = Auth::user()->warehouses->pluck('id')->toArray();
            }

            // دالة مساعدة لتطبيق نفس الفلاتر
            $applyFilters = function($query) use ($request, $accessibleWarehouseIdsForTotal) {
                // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
                if ($accessibleWarehouseIdsForTotal !== null) {
                    $query->whereHas('items.product', function($q) use ($accessibleWarehouseIdsForTotal) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIdsForTotal);
                    });
                }

                // فلتر المخزن
                if ($request->filled('warehouse_id')) {
                    $query->whereHas('items.product', function($q) use ($request) {
                        $q->where('warehouse_id', $request->warehouse_id);
                    });
                }

                // البحث في الطلبات
                if ($request->filled('search')) {
                    $searchTerm = $request->search;
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('order_number', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                          ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                          ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                              $delegateQuery->where('name', 'like', "%{$searchTerm}%");
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

                return $query;
            };

            // حساب المبلغ الإجمالي والأرباح للطلبات غير المقيدة (pending) - دائماً
            $pendingQuery = Order::where('status', 'pending');
            $pendingQuery = $applyFilters($pendingQuery);

            // حساب المبلغ من order_items مباشرة لضمان الدقة
            $pendingOrderIds = $pendingQuery->pluck('id');
            $pendingTotalAmount = 0;
            $pendingProfitAmount = 0;
            if ($pendingOrderIds->count() > 0) {
                $pendingTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $pendingOrderIds)
                    ->sum('subtotal') ?? 0;

                // حساب الأرباح المتوقعة للطلبات غير المقيدة باستخدام DB query محسّن
                // استخدام COALESCE لمعالجة purchase_price NULL أو 0
                $pendingProfitAmount = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $pendingOrderIds)
                    ->selectRaw('SUM((order_items.unit_price - COALESCE(products.purchase_price, 0)) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }

            // حساب المبلغ الإجمالي والأرباح للطلبات المقيدة (confirmed) - دائماً
            $confirmedQuery = Order::where('status', 'confirmed');
            $confirmedQuery = $applyFilters($confirmedQuery);

            // حساب المبلغ من order_items مباشرة لضمان الدقة
            $confirmedOrderIds = $confirmedQuery->pluck('id');
            $confirmedTotalAmount = 0;
            $confirmedProfitAmount = 0;
            if ($confirmedOrderIds->count() > 0) {
                $confirmedTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $confirmedOrderIds)
                    ->sum('subtotal') ?? 0;

                // حساب الأرباح المتوقعة للطلبات المقيدة باستخدام DB query محسّن
                // حساب الربح فقط للمنتجات التي لديها purchase_price
                $confirmedProfitAmount = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $confirmedOrderIds)
                    ->whereNotNull('products.purchase_price')
                    ->where('products.purchase_price', '>', 0)
                    ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }
        }

        return view('admin.orders.management', compact('orders', 'warehouses', 'suppliers', 'delegates', 'pendingTotalAmount', 'confirmedTotalAmount', 'pendingProfitAmount', 'confirmedProfitAmount'));
    }

    /**
     * صفحة الطلبات غير المقيدة (pending فقط)
     */
    public function pendingOrders(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // جلب قائمة المخازن حسب الصلاحيات
        if (Auth::user()->isSupplier()) {
            $warehouses = Auth::user()->warehouses;
        } else {
            $warehouses = \App\Models\Warehouse::all();
        }

        // جلب قائمة المجهزين (المديرين والمجهزين) والمندوبين للفلترة
        $suppliers = \App\Models\User::whereIn('role', ['admin', 'supplier'])->get();
        $delegates = \App\Models\User::where('role', 'delegate')->get();

        // Base query - فرض حالة pending دائماً
        $query = Order::where('status', 'pending');

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز (الطلبات التي قيدها المجهز) - لا ينطبق على pending لكن نتركه للتوافق
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب (الطلبات التي أنشأها المندوب)
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
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
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

        $perPage = $request->input('per_page', 15);

        // تحميل العلاقات المطلوبة
        $query->with(['delegate', 'items.product.warehouse', 'items.product.primaryImage', 'confirmedBy', 'processedBy']);

        // ترتيب الطلبات
        $orders = $query->latest('created_at')
                   ->paginate($perPage)
                   ->appends($request->except('page'));

        // حساب المبالغ الإجمالية والأرباح للمدير فقط
        $pendingTotalAmount = 0;
        $confirmedTotalAmount = 0;
        $pendingProfitAmount = 0;
        $confirmedProfitAmount = 0;

        if (Auth::user()->isAdmin()) {
            $accessibleWarehouseIdsForTotal = null;
            if (Auth::user()->isSupplier()) {
                $accessibleWarehouseIdsForTotal = Auth::user()->warehouses->pluck('id')->toArray();
            }

            // دالة مساعدة لتطبيق نفس الفلاتر
            $applyFilters = function($query) use ($request, $accessibleWarehouseIdsForTotal) {
                if ($accessibleWarehouseIdsForTotal !== null) {
                    $query->whereHas('items.product', function($q) use ($accessibleWarehouseIdsForTotal) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIdsForTotal);
                    });
                }

                if ($request->filled('warehouse_id')) {
                    $query->whereHas('items.product', function($q) use ($request) {
                        $q->where('warehouse_id', $request->warehouse_id);
                    });
                }

                if ($request->filled('search')) {
                    $searchTerm = $request->search;
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('order_number', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                          ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                          ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                              $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                          });
                    });
                }

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

                return $query;
            };

            // حساب المبلغ الإجمالي والأرباح للطلبات غير المقيدة (pending)
            $pendingQuery = Order::where('status', 'pending');
            $pendingQuery = $applyFilters($pendingQuery);

            $pendingOrderIds = $pendingQuery->pluck('id');
            if ($pendingOrderIds->count() > 0) {
                $pendingTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $pendingOrderIds)
                    ->sum('subtotal') ?? 0;

                $pendingProfitAmount = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $pendingOrderIds)
                    ->selectRaw('SUM((order_items.unit_price - COALESCE(products.purchase_price, 0)) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }

            // حساب المبلغ الإجمالي والأرباح للطلبات المقيدة (confirmed)
            $confirmedQuery = Order::where('status', 'confirmed');
            $confirmedQuery = $applyFilters($confirmedQuery);

            $confirmedOrderIds = $confirmedQuery->pluck('id');
            if ($confirmedOrderIds->count() > 0) {
                $confirmedTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $confirmedOrderIds)
                    ->sum('subtotal') ?? 0;

                $confirmedProfitAmount = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $confirmedOrderIds)
                    ->whereNotNull('products.purchase_price')
                    ->where('products.purchase_price', '>', 0)
                    ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }
        }

        return view('admin.orders.pending', compact('orders', 'warehouses', 'suppliers', 'delegates', 'pendingTotalAmount', 'confirmedTotalAmount', 'pendingProfitAmount', 'confirmedProfitAmount'));
    }

    /**
     * صفحة الطلبات المقيدة (confirmed فقط)
     */
    public function confirmedOrders(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // جلب قائمة المخازن حسب الصلاحيات
        if (Auth::user()->isSupplier()) {
            $warehouses = Auth::user()->warehouses;
        } else {
            $warehouses = \App\Models\Warehouse::all();
        }

        // جلب قائمة المجهزين (المديرين والمجهزين) والمندوبين للفلترة
        $suppliers = \App\Models\User::whereIn('role', ['admin', 'supplier'])->get();
        $delegates = \App\Models\User::where('role', 'delegate')->get();

        // Base query - فرض حالة confirmed دائماً
        $query = Order::where('status', 'confirmed');

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلتر المخزن
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // فلتر المجهز (الطلبات التي قيدها المجهز)
        if ($request->filled('confirmed_by')) {
            $query->where('confirmed_by', $request->confirmed_by);
        }

        // فلتر المندوب (الطلبات التي أنشأها المندوب)
        if ($request->filled('delegate_id')) {
            $query->where('delegate_id', $request->delegate_id);
        }

        // البحث في الطلبات
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                  ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
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

        // فلتر حسب تاريخ التقييد (للطلبات المقيدة)
        if ($request->filled('confirmed_from')) {
            $query->whereDate('confirmed_at', '>=', $request->confirmed_from);
        }

        if ($request->filled('confirmed_to')) {
            $query->whereDate('confirmed_at', '<=', $request->confirmed_to);
        }

        $perPage = $request->input('per_page', 15);

        // تحميل العلاقات المطلوبة
        $query->with(['delegate', 'items.product.warehouse', 'items.product.primaryImage', 'confirmedBy', 'processedBy']);

        // ترتيب الطلبات
        $orders = $query->latest('confirmed_at')
                   ->paginate($perPage)
                   ->appends($request->except('page'));

        // حساب المبالغ الإجمالية والأرباح للمدير فقط
        $pendingTotalAmount = 0;
        $confirmedTotalAmount = 0;
        $pendingProfitAmount = 0;
        $confirmedProfitAmount = 0;

        if (Auth::user()->isAdmin()) {
            $accessibleWarehouseIdsForTotal = null;
            if (Auth::user()->isSupplier()) {
                $accessibleWarehouseIdsForTotal = Auth::user()->warehouses->pluck('id')->toArray();
            }

            // دالة مساعدة لتطبيق نفس الفلاتر
            $applyFilters = function($query) use ($request, $accessibleWarehouseIdsForTotal) {
                if ($accessibleWarehouseIdsForTotal !== null) {
                    $query->whereHas('items.product', function($q) use ($accessibleWarehouseIdsForTotal) {
                        $q->whereIn('warehouse_id', $accessibleWarehouseIdsForTotal);
                    });
                }

                if ($request->filled('warehouse_id')) {
                    $query->whereHas('items.product', function($q) use ($request) {
                        $q->where('warehouse_id', $request->warehouse_id);
                    });
                }

                if ($request->filled('search')) {
                    $searchTerm = $request->search;
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('order_number', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                          ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                          ->orWhere('delivery_code', 'like', "%{$searchTerm}%")
                          ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                              $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                          });
                    });
                }

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

                return $query;
            };

            // حساب المبلغ الإجمالي والأرباح للطلبات غير المقيدة (pending)
            $pendingQuery = Order::where('status', 'pending');
            $pendingQuery = $applyFilters($pendingQuery);

            $pendingOrderIds = $pendingQuery->pluck('id');
            if ($pendingOrderIds->count() > 0) {
                $pendingTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $pendingOrderIds)
                    ->sum('subtotal') ?? 0;

                $pendingProfitAmount = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $pendingOrderIds)
                    ->selectRaw('SUM((order_items.unit_price - COALESCE(products.purchase_price, 0)) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }

            // حساب المبلغ الإجمالي والأرباح للطلبات المقيدة (confirmed)
            $confirmedQuery = Order::where('status', 'confirmed');
            $confirmedQuery = $applyFilters($confirmedQuery);

            $confirmedOrderIds = $confirmedQuery->pluck('id');
            if ($confirmedOrderIds->count() > 0) {
                $confirmedTotalAmount = DB::table('order_items')
                    ->whereIn('order_id', $confirmedOrderIds)
                    ->sum('subtotal') ?? 0;

                $confirmedProfitAmount = DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->whereIn('order_items.order_id', $confirmedOrderIds)
                    ->whereNotNull('products.purchase_price')
                    ->where('products.purchase_price', '>', 0)
                    ->selectRaw('SUM((order_items.unit_price - products.purchase_price) * order_items.quantity) as total_profit')
                    ->value('total_profit') ?? 0;
            }
        }

        return view('admin.orders.confirmed', compact('orders', 'warehouses', 'suppliers', 'delegates', 'pendingTotalAmount', 'confirmedTotalAmount', 'pendingProfitAmount', 'confirmedProfitAmount'));
    }

    /**
     * Display a unified listing of all orders with filters.
     */

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'cart',
            'confirmedBy',
            'processedBy'
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Get materials list for all pending orders.
     */
    public function getMaterialsList()
    {
        $this->authorize('viewAny', Order::class);

        // جلب الطلبات حسب الصلاحيات
        $orders = $this->getAccessibleOrders();

        // تجميع المواد
        $materials = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                // التأكد من وجود المنتج
                if (!$item->product) {
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

        return view('admin.orders.materials-list', compact('materials'));
    }

    /**
     * Get materials list for management page with warehouse filter support.
     */
    public function getMaterialsListManagement(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // Base query
        $query = Order::query();

        // فلتر الصلاحيات
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
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

        // فلتر المخزن ⭐ الميزة الجديدة
        if ($request->filled('warehouse_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        $orders = $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse'
        ])->get();

        // تجميع المواد
        $materials = [];
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                if (!$item->product) continue;

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

        return view('admin.orders.materials-list', compact('materials'));
    }

    /**
     * Get orders accessible by current user.
     */
    private function getAccessibleOrders()
    {
        $query = Order::where('status', 'pending');

        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        return $query->with([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse'
        ])->get();
    }

    /**
     * Show the form for processing an order.
     */
    public function process(Order $order)
    {
        $this->authorize('update', $order);

        $order->load([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'cart'
        ]);

        return view('admin.orders.process', compact('order'));
    }

    /**
     * Show the comprehensive order processing page.
     */
    public function showProcess(Order $order)
    {
        $this->authorize('process', $order);

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return redirect()->route('admin.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن تجهيز الطلبات المقيدة']);
        }

        // تحميل العلاقات
        $order->load(['items.product.primaryImage', 'items.size', 'delegate']);

        // جلب المنتجات المتوفرة للمخازن التي يمكن للمستخدم الوصول إليها
        $warehouses = $this->getAccessibleWarehouses();
        $products = Product::whereIn('warehouse_id', $warehouses->pluck('id'))
                          ->with(['primaryImage', 'sizes' => function($q) {
                              $q->where('quantity', '>', 0);
                          }])
                          ->get();

        return view('admin.orders.process', compact('order', 'products'));
    }

    /**
     * Process the order with comprehensive modifications.
     */
    public function processOrder(Request $request, Order $order)
    {
        $this->authorize('process', $order);

        if ($order->status !== 'pending') {
            return redirect()->route('admin.orders.show', $order)
                            ->withErrors(['order' => 'لا يمكن تجهيز الطلبات المقيدة']);
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'delivery_code' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function() use ($order, $request) {
            // تحديث معلومات الطلب فقط (بدون تعديل المنتجات لأن التعديل يتم من صفحة التعديل)
            $order->update([
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'customer_social_link' => $request->customer_social_link,
                'delivery_code' => $request->delivery_code,
                'notes' => $request->notes,
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);

            // تسجيل حركة التقييد/التجهيز لكل منتج في الطلب (فقط للتسجيل، بدون خصم من المخزن)
            $order->load('items.product', 'items.size');
            foreach ($order->items as $item) {
                // حساب balance_after: إذا كان size موجوداً نستخدم quantity، وإلا 0
                $balanceAfter = 0;
                if ($item->size_id && $item->size) {
                    $balanceAfter = $item->size->quantity;
                }

                ProductMovement::record([
                    'product_id' => $item->product_id,
                    'size_id' => $item->size_id, // قد يكون null إذا تم حذف size
                    'warehouse_id' => $item->product->warehouse_id,
                    'order_id' => $order->id,
                    'movement_type' => 'confirm',
                    'quantity' => 0, // لا خصم، فقط تسجيل الحركة
                    'balance_after' => $balanceAfter, // الرصيد الحالي (لم يتغير)
                    'order_status' => 'confirmed',
                    'notes' => "تقييد/تجهيز طلب #{$order->order_number}"
                ]);
            }

            // تسجيل الربح عند التقييد
            $profitCalculator = new ProfitCalculator();
            $profitCalculator->recordOrderProfit($order);

            // ملاحظة: المنتجات لا يتم تعديلها هنا لأنها سبق خصمها من المخزن عند رفع المندوب للطلب
            // حركات المواد تسجل عند رفع المندوب للطلب وليس عند التجهيز
            // التعديل على المنتجات يتم من صفحة التعديل (admin.orders.edit)
        });

        return redirect()->route('admin.orders.management', ['status' => 'confirmed'])
                        ->with('success', 'تم تجهيز وتقييد الطلب بنجاح');
    }

    /**
     * Get warehouses accessible by current user.
     */
    private function getAccessibleWarehouses()
    {
        if (Auth::user()->isAdmin()) {
            return \App\Models\Warehouse::all();
        }

        if (Auth::user()->isSupplier()) {
            return Auth::user()->warehouses;
        }

        return collect();
    }

    /**
     * Confirm the order.
     */
    public function confirm(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'delivery_code' => 'required|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $order->update([
            'delivery_code' => $request->delivery_code,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_address' => $request->customer_address,
            'customer_social_link' => $request->customer_social_link,
            'notes' => $request->notes,
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ]);

        return redirect()->route('admin.orders.management', ['status' => 'confirmed'])
                        ->with('success', 'تم تقييد الطلب بنجاح');
    }


    /**
     * Show the form for editing the order.
     */
    public function edit(Order $order)
    {
        $this->authorize('update', $order);

        // السماح بالتعديل للطلبات pending أو المقيدة خلال 5 ساعات
        if ($order->status !== 'pending' && !$order->canBeEdited()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)']);
        }

        $order->load([
            'delegate',
            'items.product.primaryImage',
            'items.product.warehouse',
            'items.size',
            'cart'
        ]);

        // جلب المنتجات حسب صلاحيات المستخدم
        $productsQuery = Product::with(['sizes', 'primaryImage']);

        // للمجهز: فقط منتجات المخازن المسموح له بها
        if (Auth::user()->isSupplier()) {
            $warehouseIds = Auth::user()->warehouses()->pluck('warehouses.id');
            $productsQuery->whereIn('warehouse_id', $warehouseIds);
        }
        // للمدير: كل المنتجات (لا حاجة للفلترة)

        $products = $productsQuery->get();

        return view('admin.orders.edit', compact('order', 'products'));
    }

    /**
     * Update the order.
     */
    public function update(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        // السماح بالتعديل للطلبات pending أو المقيدة خلال 5 ساعات
        if ($order->status !== 'pending' && !$order->canBeEdited()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)']);
        }

        $request->validate([
            'delivery_code' => 'nullable|string|max:255',
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_address' => 'required|string',
            'customer_social_link' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function() use ($request, $order) {
                // تحميل العناصر القديمة مع العلاقات
                $oldItems = $order->items()->with(['size', 'product'])->get();

                // تحديث معلومات الطلب
                $order->update($request->only([
                    'delivery_code',
                    'customer_name',
                    'customer_phone',
                    'customer_address',
                    'customer_social_link',
                    'notes',
                ]));

                // للطلبات غير المقيدة (pending) أو المقيدة التي لم يمر عليها 5 ساعات: مقارنة القديمة والجديدة وتطبيق التغييرات على المخزن
                if ($order->status === 'pending' || ($order->status === 'confirmed' && $order->canBeEdited())) {
                    // إنشاء خريطة للعناصر القديمة: key = product_id_size_id
                    $oldItemsMap = [];
                    foreach ($oldItems as $oldItem) {
                        $key = $oldItem->product_id . '_' . $oldItem->size_id;
                        $oldItemsMap[$key] = $oldItem;
                    }

                    // معالجة العناصر القديمة
                    foreach ($oldItemsMap as $key => $oldItem) {
                        if (!$oldItem->size) continue;

                        // البحث عن العنصر في الطلب الجديد
                        $foundNewItem = null;
                        foreach ($request->items as $newItem) {
                            $newKey = $newItem['product_id'] . '_' . $newItem['size_id'];
                            if ($newKey === $key) {
                                $foundNewItem = $newItem;
                                break;
                            }
                        }

                        if ($foundNewItem === null) {
                            // المنتج محذوف → إرجاع كامل للمخزن
                            $oldItem->size->increment('quantity', $oldItem->quantity);
                            ProductMovement::record([
                                'product_id' => $oldItem->product_id,
                                'size_id' => $oldItem->size_id,
                                'warehouse_id' => $oldItem->product->warehouse_id,
                                'order_id' => $order->id,
                                'movement_type' => 'order_edit_remove',
                                'quantity' => $oldItem->quantity,
                                'balance_after' => $oldItem->size->refresh()->quantity,
                                'order_status' => $order->status,
                                'notes' => "تعديل طلب #{$order->order_number} - إرجاع منتج: {$oldItem->product_name} ({$oldItem->size_name})"
                            ]);
                        } else {
                            // المنتج موجود → مقارنة الكميات
                            $quantityDiff = $foundNewItem['quantity'] - $oldItem->quantity;

                            if ($quantityDiff > 0) {
                                // زيادة الكمية → خصم الفرق من المخزن
                                // للطلبات المقيدة: الكمية المتاحة = الكمية في المخزن (لأن المنتج كان محجوزاً)
                                // للطلبات pending: الكمية المتاحة = الكمية في المخزن
                                $availableQuantity = $oldItem->size->quantity;

                                if ($availableQuantity < $quantityDiff) {
                                    throw new \Exception("الكمية المتوفرة من {$oldItem->product->name} - {$oldItem->size->size_name} غير كافية. المطلوب: {$quantityDiff}، المتوفر: {$availableQuantity}");
                                }
                                $oldItem->size->decrement('quantity', $quantityDiff);
                                ProductMovement::record([
                                    'product_id' => $oldItem->product_id,
                                    'size_id' => $oldItem->size_id,
                                    'warehouse_id' => $oldItem->product->warehouse_id,
                                    'order_id' => $order->id,
                                    'movement_type' => 'order_edit_increase',
                                    'quantity' => -$quantityDiff,
                                    'balance_after' => $oldItem->size->refresh()->quantity,
                                    'order_status' => $order->status,
                                    'notes' => "تعديل طلب #{$order->order_number} - زيادة كمية: {$oldItem->product_name} ({$oldItem->size_name}) من {$oldItem->quantity} إلى {$foundNewItem['quantity']}"
                                ]);
                            } elseif ($quantityDiff < 0) {
                                // إنقاص الكمية → إرجاع الفرق للمخزن
                                $oldItem->size->increment('quantity', abs($quantityDiff));
                                ProductMovement::record([
                                    'product_id' => $oldItem->product_id,
                                    'size_id' => $oldItem->size_id,
                                    'warehouse_id' => $oldItem->product->warehouse_id,
                                    'order_id' => $order->id,
                                    'movement_type' => 'order_edit_decrease',
                                    'quantity' => abs($quantityDiff),
                                    'balance_after' => $oldItem->size->refresh()->quantity,
                                    'order_status' => $order->status,
                                    'notes' => "تعديل طلب #{$order->order_number} - إنقاص كمية: {$oldItem->product_name} ({$oldItem->size_name}) من {$oldItem->quantity} إلى {$foundNewItem['quantity']}"
                                ]);
                            }
                            // إذا كانت الكمية نفسها، لا حاجة لتغيير
                        }
                    }

                    // معالجة المنتجات الجديدة (غير موجودة في القديمة)
                    foreach ($request->items as $newItem) {
                        $newKey = $newItem['product_id'] . '_' . $newItem['size_id'];
                        if (!isset($oldItemsMap[$newKey])) {
                            // منتج جديد → خصم الكمية من المخزن
                            $product = Product::findOrFail($newItem['product_id']);
                            $size = ProductSize::findOrFail($newItem['size_id']);

                            // التحقق من توفر الكمية
                            // للطلبات المقيدة: الكمية المتاحة = الكمية في المخزن (لأن المنتج محجوز)
                            // للطلبات pending: الكمية المتاحة = الكمية في المخزن
                            $availableQuantity = $size->quantity;

                            if ($availableQuantity < $newItem['quantity']) {
                                throw new \Exception("الكمية المتوفرة من {$product->name} - {$size->size_name} غير كافية. المتوفر: {$availableQuantity}");
                            }

                            $size->decrement('quantity', $newItem['quantity']);
                            ProductMovement::record([
                                'product_id' => $newItem['product_id'],
                                'size_id' => $newItem['size_id'],
                                'warehouse_id' => $product->warehouse_id,
                                'order_id' => $order->id,
                                'movement_type' => 'order_edit_add',
                                'quantity' => -$newItem['quantity'],
                                'balance_after' => $size->refresh()->quantity,
                                'order_status' => $order->status,
                                'notes' => "تعديل طلب #{$order->order_number} - إضافة منتج جديد: {$product->name} ({$size->size_name})"
                            ]);
                        }
                    }
                } elseif ($order->status === 'confirmed' && !$order->canBeEdited()) {
                    // للطلبات المقيدة التي مر عليها أكثر من 5 ساعات: نرجع المنتجات القديمة للمخزن أولاً
                    foreach ($oldItems as $oldItem) {
                        if ($oldItem->size) {
                            $oldItem->size->increment('quantity', $oldItem->quantity);

                            // تسجيل حركة الإرجاع
                            ProductMovement::record([
                                'product_id' => $oldItem->product_id,
                                'size_id' => $oldItem->size_id,
                                'warehouse_id' => $oldItem->product->warehouse_id,
                                'order_id' => $order->id,
                                'movement_type' => 'cancel',
                                'quantity' => $oldItem->quantity,
                                'balance_after' => $oldItem->size->refresh()->quantity,
                                'order_status' => $order->status,
                                'notes' => "تعديل طلب #{$order->order_number} - إرجاع المنتجات القديمة"
                            ]);
                        }
                    }

                    // التحقق من توفر الكمية قبل إضافة المنتجات الجديدة
                    foreach ($request->items as $item) {
                        $product = Product::findOrFail($item['product_id']);
                        $size = ProductSize::findOrFail($item['size_id']);

                        if ($size->quantity < $item['quantity']) {
                            throw new \Exception("الكمية المتوفرة من {$product->name} - {$size->size_name} غير كافية. المتوفر: {$size->quantity}");
                        }
                    }
                }

                // حذف المنتجات القديمة
                $order->items()->delete();

                // إضافة المنتجات الجديدة
                $totalAmount = 0;
                foreach ($request->items as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $size = ProductSize::findOrFail($item['size_id']);

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

                    // خصم من المخزن (للطلبات المقيدة التي مر عليها أكثر من 5 ساعات فقط - لأن pending والقابلة للتعديل تم معالجتها أعلاه)
                    if ($order->status === 'confirmed' && !$order->canBeEdited()) {
                        $size->decrement('quantity', $item['quantity']);

                        // تسجيل حركة البيع الجديدة
                        ProductMovement::record([
                            'product_id' => $item['product_id'],
                            'size_id' => $item['size_id'],
                            'warehouse_id' => $product->warehouse_id,
                            'order_id' => $order->id,
                            'movement_type' => 'sell',
                            'quantity' => -$item['quantity'],
                            'balance_after' => $size->refresh()->quantity,
                            'order_status' => $order->status,
                            'notes' => "تعديل طلب #{$order->order_number} - إضافة منتج جديد"
                        ]);
                    }
                }

                // تحديث المبلغ الإجمالي
                $order->update(['total_amount' => $totalAmount]);
            });

            return redirect()->route('admin.orders.management')
                            ->with('success', 'تم تحديث الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء تحديث الطلب: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * عرض صفحة الإرجاع
     */
    public function showReturn(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن إرجاع منتجات هذا الطلب']);
        }

        $order->load(['items.product.primaryImage', 'items.size']);

        return view('admin.orders.return', compact('order'));
    }

    /**
     * تنفيذ الإرجاع
     */
    public function processReturn(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'return_items' => 'required|array|min:1',
            'return_items.*.order_item_id' => 'required|exists:order_items,id',
            'return_items.*.product_id' => 'required|exists:products,id',
            'return_items.*.size_id' => 'required|exists:product_sizes,id',
            'return_items.*.quantity' => 'required|integer|min:1',
            'return_items.*.reason' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        try {
            $returnData = collect($request->return_items)->map(function($item) use ($request) {
                $item['notes'] = $request->notes;
                return $item;
            })->toArray();

            $order->processReturn($returnData, auth()->id());

            return redirect()->route('admin.orders.returned')
                            ->with('success', 'تم إرجاع المنتجات بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * عرض صفحة الاستبدال
     */
    public function showExchange(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن استبدال منتجات هذا الطلب']);
        }

        $order->load(['items.product.primaryImage', 'items.size']);

        // تحضير المنتجات مع الصور بشكل صحيح
        $products = Product::with(['sizes', 'primaryImage'])->get()->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'image' => $product->primaryImage ? Storage::url($product->primaryImage->path) : '/assets/images/no-image.png',
                'sizes' => $product->sizes
            ];
        });

        return view('admin.orders.exchange', compact('order', 'products'));
    }

    /**
     * تنفيذ الاستبدال
     */
    public function processExchange(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'exchanges' => 'required|array|min:1',
            'exchanges.*.order_item_id' => 'required|exists:order_items,id',
            'exchanges.*.old_product_id' => 'required|exists:products,id',
            'exchanges.*.old_size_id' => 'required|exists:product_sizes,id',
            'exchanges.*.old_quantity' => 'required|integer|min:1',
            'exchanges.*.new_product_id' => 'required|exists:products,id',
            'exchanges.*.new_size_id' => 'required|exists:product_sizes,id',
            'exchanges.*.new_quantity' => 'required|integer|min:1',
            'exchanges.*.reason' => 'required|string',
        ]);

        try {
            $order->processExchange($request->exchanges, auth()->id());
            return redirect()->route('admin.orders.exchanged')
                            ->with('success', 'تم استبدال المنتجات بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * إلغاء الطلب (كلي فقط)
     */
    public function cancel(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ]);

        if ($order->status !== 'confirmed') {
            return back()->withErrors(['error' => 'لا يمكن إلغاء هذا الطلب']);
        }

        try {
            // تحميل العلاقات المطلوبة
            $order->load('items.size');

            $order->cancel($request->cancellation_reason, auth()->id());
            return redirect()->route('admin.orders.cancelled')
                            ->with('success', 'تم إلغاء الطلب بنجاح');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * عرض الطلبات الملغية
     */
    public function cancelled(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::where('status', 'cancelled');

        // للمجهز: عرض الطلبات من مخازنه فقط
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلاتر البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhere('cancellation_reason', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلاتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت للطلب
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        if ($request->filled('cancelled_from')) {
            $query->whereDate('cancelled_at', '>=', $request->cancelled_from);
        }

        if ($request->filled('cancelled_to')) {
            $query->whereDate('cancelled_at', '<=', $request->cancelled_to);
        }

        // فلتر حسب الوقت للإلغاء
        if ($request->filled('cancelled_time_from')) {
            $cancelledDateFrom = $request->cancelled_from ?? now()->format('Y-m-d');
            $query->where('cancelled_at', '>=', $cancelledDateFrom . ' ' . $request->cancelled_time_from . ':00');
        }

        if ($request->filled('cancelled_time_to')) {
            $cancelledDateTo = $request->cancelled_to ?? now()->format('Y-m-d');
            $query->where('cancelled_at', '<=', $cancelledDateTo . ' ' . $request->cancelled_time_to . ':00');
        }

        $orders = $query->with(['delegate', 'processedBy', 'items.product.primaryImage'])
                       ->latest('cancelled_at')
                       ->paginate(15);

        return view('admin.orders.cancelled', compact('orders'));
    }

    /**
     * عرض الطلبات المسترجعة
     */

    /**
     * عرض الطلبات المستبدلة
     */
    public function exchanged(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::where('status', 'exchanged');

        // للمجهز: عرض الطلبات من مخازنه فقط
        if (Auth::user()->isSupplier()) {
            $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();

            $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
            });
        }

        // فلاتر البحث
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                  ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                  ->orWhereHas('delegate', function($delegateQuery) use ($searchTerm) {
                      $delegateQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // فلاتر التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // فلتر حسب الوقت للطلب
        if ($request->filled('time_from')) {
            $dateFrom = $request->date_from ?? now()->format('Y-m-d');
            $query->where('created_at', '>=', $dateFrom . ' ' . $request->time_from . ':00');
        }

        if ($request->filled('time_to')) {
            $dateTo = $request->date_to ?? now()->format('Y-m-d');
            $query->where('created_at', '<=', $dateTo . ' ' . $request->time_to . ':00');
        }

        if ($request->filled('exchanged_from')) {
            $query->whereDate('exchanged_at', '>=', $request->exchanged_from);
        }

        if ($request->filled('exchanged_to')) {
            $query->whereDate('exchanged_at', '<=', $request->exchanged_to);
        }

        // فلتر حسب الوقت للاستبدال
        if ($request->filled('exchanged_time_from')) {
            $exchangedDateFrom = $request->exchanged_from ?? now()->format('Y-m-d');
            $query->where('exchanged_at', '>=', $exchangedDateFrom . ' ' . $request->exchanged_time_from . ':00');
        }

        if ($request->filled('exchanged_time_to')) {
            $exchangedDateTo = $request->exchanged_to ?? now()->format('Y-m-d');
            $query->where('exchanged_at', '<=', $exchangedDateTo . ' ' . $request->exchanged_time_to . ':00');
        }

        $orders = $query->with(['delegate', 'processedBy', 'items.product.primaryImage', 'exchangeItems'])
                       ->latest('exchanged_at')
                       ->paginate(15);

        return view('admin.orders.exchanged', compact('orders'));
    }

    /**
     * عرض تفاصيل الإرجاع
     */
    public function returnDetails(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['returnItems.product.primaryImage', 'returnItems.size']);
        return view('admin.orders.return-details', compact('order'));
    }

    /**
     * عرض تفاصيل الاستبدال
     */
    public function exchangeDetails(Order $order)
    {
        $this->authorize('view', $order);

        $order->load([
            'exchangeItems.oldProduct.primaryImage',
            'exchangeItems.newProduct.primaryImage',
            'exchangeItems.oldSize',
            'exchangeItems.newSize'
        ]);
        return view('admin.orders.exchange-details', compact('order'));
    }

    /**
     * استرجاع مباشر للطلب (بسيط جداً)
     */
    public function returnDirect(Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'confirmed') {
            return redirect()->route('admin.orders.management', ['status' => 'confirmed'])
                            ->withErrors(['error' => 'لا يمكن استرجاع هذا الطلب']);
        }

        try {
            DB::transaction(function() use ($order) {
                // تحميل العلاقات المطلوبة
                $order->load('items.size');

                // إرجاع جميع المنتجات للمخزن
                foreach ($order->items as $item) {
                    if ($item->size) {
                        $item->size->increment('quantity', $item->quantity);

                        // تسجيل حركة الاسترجاع
                        ProductMovement::record([
                            'product_id' => $item->product_id,
                            'size_id' => $item->size_id,
                            'warehouse_id' => $item->product->warehouse_id,
                            'order_id' => $order->id,
                            'movement_type' => 'return',
                            'quantity' => $item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "استرجاع من طلب #{$order->order_number}"
                        ]);
                    }
                }

                // تحديث حالة الطلب فقط
                $order->update([
                    'status' => 'returned',
                    'returned_at' => now(),
                    'processed_by' => auth()->id(),
                ]);
            });

            return redirect()->route('admin.orders.management', ['status' => 'confirmed'])
                            ->with('success', 'تم استرجاع الطلب بنجاح وإرجاع جميع المنتجات للمخزن');
        } catch (\Exception $e) {
            return redirect()->route('admin.orders.management', ['status' => 'confirmed'])
                            ->withErrors(['error' => 'حدث خطأ أثناء استرجاع الطلب: ' . $e->getMessage()]);
        }
    }

    /**
     * حذف الطلب (soft delete) مع إرجاع المنتجات للمخزن
     */
    public function destroy(Request $request, Order $order)
    {
        $this->authorize('delete', $order);

        // التحقق من أن الطلب يمكن حذفه (pending أو confirmed)
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return redirect()->back()
                            ->withErrors(['error' => 'لا يمكن حذف هذا الطلب']);
        }

        // التحقق من وجود سبب الحذف
        $request->validate([
            'deletion_reason' => 'required|string|min:3|max:1000',
        ], [
            'deletion_reason.required' => 'يجب كتابة سبب الحذف',
            'deletion_reason.min' => 'سبب الحذف يجب أن يكون على الأقل 3 أحرف',
            'deletion_reason.max' => 'سبب الحذف يجب أن يكون أقل من 1000 حرف',
        ]);

        try {
            DB::transaction(function() use ($order, $request) {
                // تحميل العلاقات المطلوبة
                $order->load('items.size');

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
                            'movement_type' => 'delete',
                            'quantity' => $item->quantity,
                            'balance_after' => $item->size->quantity,
                            'order_status' => $order->status,
                            'notes' => "حذف طلب #{$order->order_number}"
                        ]);
                    }
                }

                // تسجيل من قام بالحذف وسبب الحذف
                $order->deleted_by = auth()->id();
                $order->deletion_reason = $request->deletion_reason;
                $order->save();

                // soft delete للطلب
                $order->delete();
            });

            return redirect()->route('admin.orders.management', ['status' => 'deleted'])
                            ->with('success', 'تم حذف الطلب بنجاح وإرجاع جميع المنتجات للمخزن');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء حذف الطلب: ' . $e->getMessage()]);
        }
    }



    /**
     * الحذف النهائي للطلب (لا يمكن استرجاعه)
     */
    public function forceDelete(Request $request, $order)
    {
        try {
            // جلب الطلب مع soft deleted (لأن Route Model Binding لا يعمل بشكل صحيح مع soft deleted في بعض الحالات)
            // $order قد يكون ID (string) من الـ route
            $orderId = is_numeric($order) ? (int)$order : ($order instanceof Order ? $order->id : $order);
            $order = Order::withTrashed()->findOrFail($orderId);

            // التأكد من أن الطلب محذوف (soft deleted)
            if (!$order->trashed()) {
                return redirect()->back()
                            ->withErrors(['error' => 'يمكن حذف الطلبات المحذوفة فقط نهائياً']);
            }

            $this->authorize('forceDelete', $order);

            DB::transaction(function () use ($order) {
                // حذف حركات المنتجات المرتبطة بالطلب
                ProductMovement::where('order_id', $order->id)->delete();

                // حذف عناصر الطلب
                $order->items()->forceDelete();

                // الحذف النهائي للطلب
                $order->forceDelete();
            });

            return redirect()->route('admin.orders.management', ['status' => 'deleted'])
                            ->with('success', 'تم حذف الطلب نهائياً بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                            ->withErrors(['error' => 'حدث خطأ أثناء الحذف النهائي: ' . $e->getMessage()]);
        }
    }

    /**
     * تحديث حالة التدقيق للطلب غير المقيد
     */
    public function updateReviewStatus(Request $request, Order $order)
    {
        // التحقق من أن المستخدم هو المدير أو المجهز
        if (!auth()->user()->isAdminOrSupplier()) {
            abort(403);
        }

        // التحقق من أن الطلب غير مقيد
        if ($order->status !== 'pending') {
            return response()->json(['error' => 'يمكن تحديث الحالة للطلبات غير المقيدة فقط'], 400);
        }

        if ($request->field === 'size_reviewed') {
            $request->validate([
                'field' => 'required|in:size_reviewed',
                'value' => 'required|in:not_reviewed,reviewed',
            ]);
            $order->update([$request->field => $request->value]);
        } else {
            $request->validate([
                'field' => 'required|in:message_confirmed',
                'value' => 'required|in:not_sent,waiting_response,not_confirmed,confirmed',
            ]);
            $order->update([$request->field => $request->value]);
        }

        $order->refresh();

        if ($request->field === 'size_reviewed') {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة التدقيق بنجاح',
                'size_reviewed' => $order->size_reviewed,
                'size_review_status_text' => $order->size_review_status_text,
                'size_review_status_badge_class' => $order->size_review_status_badge_class,
            ]);
        } else {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الرسالة بنجاح',
                'message_confirmed' => $order->message_confirmed,
                'message_confirmation_status_text' => $order->message_confirmation_status_text,
                'message_confirmation_status_badge_class' => $order->message_confirmation_status_badge_class,
            ]);
        }
    }
}
