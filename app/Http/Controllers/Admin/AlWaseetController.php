<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlWaseetShipment;
use App\Models\Order;
use App\Models\ProductMovement;
use App\Models\Setting;
use App\Services\AlWaseetService;
use App\Services\ProfitCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AlWaseetController extends Controller
{
    protected $alWaseetService;

    public function __construct(AlWaseetService $alWaseetService)
    {
        $this->alWaseetService = $alWaseetService;
    }

    /**
     * صفحة Dashboard للوسيط
     */
    public function dashboard()
    {
        // التحقق من أن المستخدم مدير فقط
        if (!Auth::user()->isAdmin()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        return view('admin.alwaseet.dashboard');
    }

    /**
     * الصفحة الرئيسية مع sidebar
     */
    public function index()
    {
        // التحقق من وجود إعدادات الربط
        $username = Setting::getValue('alwaseet_username');
        $password = Setting::getValue('alwaseet_password');
        $isConnected = !empty($username) && !empty($password);

        return view('admin.alwaseet.index', compact('isConnected'));
    }

    /**
     * صفحة الطلبات المسجلة
     */
    public function orders(Request $request)
    {
        // التحقق من وجود إعدادات الربط
        $username = Setting::getValue('alwaseet_username');
        $password = Setting::getValue('alwaseet_password');
        $isConnected = !empty($username) && !empty($password);

        $query = AlWaseetShipment::query();

        // فلترة حسب الحالة
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('alwaseet_created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('alwaseet_created_at', '<=', $request->date_to);
        }

        // فلترة حسب المدينة
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // فلترة حسب المنطقة
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // بحث شامل
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_mobile', 'like', "%{$search}%")
                  ->orWhere('client_mobile2', 'like', "%{$search}%")
                  ->orWhere('alwaseet_order_id', 'like', "%{$search}%")
                  ->orWhere('city_name', 'like', "%{$search}%")
                  ->orWhere('region_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        // جلب حالات الطلبات للفلترة
        $statuses = [];
        try {
            $statuses = $this->alWaseetService->getOrderStatuses();
        } catch (\Exception $e) {
            $statuses = AlWaseetShipment::select('status_id', 'status')
                ->distinct()
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->status_id,
                        'status' => $item->status,
                    ];
                })
                ->toArray();
        }

        // جلب المدن والمناطق للفلترة
        $cities = [];
        $regions = [];
        try {
            $cities = $this->alWaseetService->getCities();
            if ($request->filled('city_id')) {
                $regions = $this->alWaseetService->getRegions($request->city_id);
            }
        } catch (\Exception $e) {
            $cities = AlWaseetShipment::select('city_id', 'city_name')
                ->distinct()
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->city_id,
                        'city_name' => $item->city_name,
                    ];
                })
                ->toArray();
        }

        return view('admin.alwaseet.orders', compact('orders', 'statuses', 'cities', 'regions', 'isConnected'));
    }

    /**
     * صفحة الوصولات (الطلبات التي لديها qr_link)
     */
    public function receipts(Request $request)
    {
        // التحقق من وجود إعدادات الربط
        $username = Setting::getValue('alwaseet_username');
        $password = Setting::getValue('alwaseet_password');
        $isConnected = !empty($username) && !empty($password);

        $query = AlWaseetShipment::whereNotNull('qr_link');

        // فلترة حسب الحالة
        if ($request->filled('status_id')) {
            $query->where('status_id', $request->status_id);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('alwaseet_created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('alwaseet_created_at', '<=', $request->date_to);
        }

        // فلترة حسب المدينة
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // فلترة حسب المنطقة
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        // فلترة حسب حالة الطباعة (متوفر/غير متوفر)
        if ($request->filled('printable')) {
            if ($request->printable === 'yes') {
                $query->whereNotNull('qr_link');
            } else {
                $query->whereNull('qr_link');
            }
        }

        // بحث شامل
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_mobile', 'like', "%{$search}%")
                  ->orWhere('client_mobile2', 'like', "%{$search}%")
                  ->orWhere('alwaseet_order_id', 'like', "%{$search}%")
                  ->orWhere('city_name', 'like', "%{$search}%")
                  ->orWhere('region_name', 'like', "%{$search}%");
            });
        }

        $receipts = $query->orderBy('created_at', 'desc')->paginate(20);

        // جلب حالات الطلبات للفلترة
        $statuses = [];
        try {
            $statuses = $this->alWaseetService->getOrderStatuses();
        } catch (\Exception $e) {
            $statuses = AlWaseetShipment::select('status_id', 'status')
                ->distinct()
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->status_id,
                        'status' => $item->status,
                    ];
                })
                ->toArray();
        }

        // جلب المدن والمناطق للفلترة
        $cities = [];
        $regions = [];
        try {
            $cities = $this->alWaseetService->getCities();
            if ($request->filled('city_id')) {
                $regions = $this->alWaseetService->getRegions($request->city_id);
            }
        } catch (\Exception $e) {
            $cities = AlWaseetShipment::select('city_id', 'city_name')
                ->distinct()
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->city_id,
                        'city_name' => $item->city_name,
                    ];
                })
                ->toArray();
        }

        return view('admin.alwaseet.receipts', compact('receipts', 'statuses', 'cities', 'regions', 'isConnected'));
    }

    /**
     * تحميل PDF للإيصال
     */
    public function downloadReceipt($id)
    {
        $shipment = AlWaseetShipment::findOrFail($id);

        if (empty($shipment->qr_link)) {
            return back()->withErrors(['receipt' => 'لا يوجد رابط للإيصال']);
        }

        try {
            return $this->alWaseetService->downloadReceiptPdf($shipment->qr_link);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Download receipt failed', [
                'error' => $e->getMessage(),
                'shipment_id' => $id,
            ]);

            return back()->withErrors(['receipt' => 'فشل تحميل الإيصال: ' . $e->getMessage()]);
        }
    }

    /**
     * تحميل PDF من qr_link مباشرة
     */
    public function downloadReceiptPdfByLink(Request $request)
    {
        $request->validate([
            'qr_link' => 'required|url',
        ]);

        try {
            return $this->alWaseetService->downloadReceiptPdf($request->qr_link);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Download receipt PDF by link failed', [
                'error' => $e->getMessage(),
                'qr_link' => $request->qr_link,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل تحميل الإيصال: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * صفحة إعدادات الربط
     */
    public function settings()
    {
        $username = Setting::getValue('alwaseet_username');
        $password = Setting::getValue('alwaseet_password');
        $isConfigured = !empty($username) && !empty($password);

        // اختبار الاتصال الفعلي
        $connectionStatus = ['success' => false, 'message' => 'غير متصل'];
        $tokenExists = false;
        $tokenPreview = null;
        $accountType = null;

        if ($isConfigured) {
            try {
                $connectionStatus = $this->alWaseetService->testConnection();
                $tokenExists = \Illuminate\Support\Facades\Cache::has('alwaseet_token') || !empty(Setting::getValue('alwaseet_token'));

                if ($tokenExists) {
                    $token = Setting::getValue('alwaseet_token') ?: \Illuminate\Support\Facades\Cache::get('alwaseet_token');
                    if ($token) {
                        $tokenPreview = substr($token, 0, 15) . '...';
                    }

                    // الحصول على معلومات نوع الحساب
                    try {
                        $accountType = $this->alWaseetService->getAccountType();
                    } catch (\Exception $e) {
                        // في حالة الخطأ، نحاول الحصول على نوع الحساب من Settings أو Token مباشرة
                        if ($token) {
                            $isMerchant = strpos($token, '@@') === 0;
                            $accountType = [
                                'is_merchant' => $isMerchant,
                                'is_merchant_user' => !$isMerchant,
                                'token_preview' => substr($token, 0, 15) . '...',
                                'token_starts_with' => substr($token, 0, 2),
                                'message' => $isMerchant
                                    ? '✅ أنت تستخدم Merchant Account (صلاحيات كاملة)'
                                    : '⚠️ أنت تستخدم Merchant User Account (صلاحيات محدودة)',
                                'warning' => !$isMerchant
                                    ? 'بعض APIs مثل Get Orders و Get Order Statuses تتطلب Merchant Account'
                                    : null,
                            ];
                        } else {
                            $accountType = null;
                        }
                    }
                }
            } catch (\Exception $e) {
                $connectionStatus = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return view('admin.alwaseet.settings', compact(
            'username',
            'isConfigured',
            'connectionStatus',
            'tokenExists',
            'tokenPreview',
            'accountType'  // إضافة هذا
        ));
    }

    /**
     * تحديث إعدادات الربط
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'nullable|string',
        ]);

        try {
            // حفظ البيانات
            Setting::setValue('alwaseet_username', $request->username);

            // تحديث كلمة المرور فقط إذا تم إدخالها
            if ($request->filled('password')) {
                Setting::setValue('alwaseet_password', $request->password);
                // مسح token القديم عند تغيير كلمة المرور
                $this->alWaseetService->clearToken();
            }

            // اختبار الاتصال (يحتاج password موجود)
            $currentPassword = Setting::getValue('alwaseet_password');
            if (empty($currentPassword)) {
                return back()->withErrors(['connection' => 'يرجى إدخال كلمة المرور'])->withInput();
            }

            $test = $this->alWaseetService->testConnection();

            if ($test['success']) {
                return redirect()->route('admin.alwaseet.settings')
                    ->with('success', 'تم ربط الواسط بنجاح!');
            } else {
                return back()->withErrors(['connection' => $test['message']])->withInput();
            }
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update settings failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['connection' => 'فشل الاتصال: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * تسجيل الخروج من الواسط (مسح الـ token)
     */
    public function logout()
    {
        try {
            $this->alWaseetService->clearToken();

            return redirect()->route('admin.alwaseet.settings')
                ->with('success', 'تم تسجيل الخروج بنجاح. تم مسح الـ token.');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Logout failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['logout' => 'فشل تسجيل الخروج: ' . $e->getMessage()]);
        }
    }

    /**
     * إعادة تسجيل الدخول (مسح الـ token القديم والحصول على token جديد)
     */
    public function reconnect()
    {
        try {
            // مسح الـ token القديم
            $this->alWaseetService->clearToken();

            // محاولة الحصول على token جديد
            $connectionStatus = $this->alWaseetService->testConnection();

            if ($connectionStatus['success']) {
                return redirect()->route('admin.alwaseet.settings')
                    ->with('success', 'تم إعادة تسجيل الدخول بنجاح!');
            } else {
                return back()->withErrors(['reconnect' => 'فشل إعادة تسجيل الدخول: ' . $connectionStatus['message']]);
            }
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Reconnect failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['reconnect' => 'فشل إعادة تسجيل الدخول: ' . $e->getMessage()]);
        }
    }

    /**
     * اختبار الاتصال (AJAX)
     */
    public function testConnection()
    {
        try {
            $connectionStatus = $this->alWaseetService->testConnection();
            $tokenExists = \Illuminate\Support\Facades\Cache::has('alwaseet_token') || !empty(Setting::getValue('alwaseet_token'));

            return response()->json([
                'success' => $connectionStatus['success'],
                'message' => $connectionStatus['message'],
                'token_exists' => $tokenExists,
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Test connection failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'token_exists' => false,
            ], 500);
        }
    }

    /**
     * مزامنة الشحنات من الواسط
     */
    public function sync(Request $request)
    {
        try {
            // جلب الطلبات من الواسط
            $orders = $this->alWaseetService->getOrders(
                $request->status_id,
                $request->date_from,
                $request->date_to
            );

            // التحقق من وجود طلبات
            if (empty($orders) || !is_array($orders)) {
                return redirect()->route('admin.alwaseet.index')
                    ->with('info', 'لا توجد شحنات في الواسط حالياً.');
            }

            $synced = 0;
            $updated = 0;

            DB::transaction(function() use ($orders, &$synced, &$updated) {
                foreach ($orders as $orderData) {
                    $shipment = AlWaseetShipment::updateOrCreate(
                        ['alwaseet_order_id' => $orderData['id']],
                        [
                            'client_name' => $orderData['client_name'] ?? '',
                            'client_mobile' => $orderData['client_mobile'] ?? '',
                            'client_mobile2' => $orderData['client_mobile2'] ?? null,
                            'city_id' => $orderData['city_id'] ?? '',
                            'city_name' => $orderData['city_name'] ?? '',
                            'region_id' => $orderData['region_id'] ?? '',
                            'region_name' => $orderData['region_name'] ?? '',
                            'location' => $orderData['location'] ?? '',
                            'price' => $orderData['price'] ?? 0,
                            'delivery_price' => $orderData['delivery_price'] ?? 0,
                            'package_size' => $orderData['package_size'] ?? '',
                            'type_name' => $orderData['type_name'] ?? '',
                            'status_id' => $orderData['status_id'] ?? '',
                            'status' => $orderData['status'] ?? '',
                            'items_number' => $orderData['items_number'] ?? '1',
                            'merchant_notes' => $orderData['merchant_notes'] ?? null,
                            'issue_notes' => $orderData['issue_notes'] ?? null,
                            'replacement' => isset($orderData['replacement']) && $orderData['replacement'] === '1',
                            'qr_id' => $orderData['qr_id'] ?? null,
                            'qr_link' => $orderData['qr_link'] ?? null,
                            'alwaseet_created_at' => isset($orderData['created_at']) ? \Carbon\Carbon::parse($orderData['created_at']) : null,
                            'alwaseet_updated_at' => isset($orderData['updated_at']) ? \Carbon\Carbon::parse($orderData['updated_at']) : null,
                            'synced_at' => now(),
                        ]
                    );

                    if ($shipment->wasRecentlyCreated) {
                        $synced++;
                    } else {
                        $updated++;
                    }
                }
            });

            return redirect()->route('admin.alwaseet.index')
                ->with('success', "تم المزامنة بنجاح! ({$synced} جديد، {$updated} محدث)");
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Sync failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['sync' => 'فشل المزامنة: ' . $e->getMessage()]);
        }
    }

    /**
     * عرض تفاصيل شحنة
     */
    public function show($id)
    {
        $shipment = AlWaseetShipment::findOrFail($id);

        // جلب الطلبات المتاحة للربط
        $availableOrders = Order::whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.alwaseet.show', compact('shipment', 'availableOrders'));
    }

    /**
     * ربط شحنة بطلب
     */
    public function linkToOrder(Request $request, $id)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $shipment = AlWaseetShipment::findOrFail($id);

        if ($shipment->order_id) {
            return back()->withErrors(['link' => 'هذه الشحنة مربوطة بالفعل بطلب آخر.']);
        }

        $order = Order::findOrFail($request->order_id);

        $shipment->update(['order_id' => $order->id]);

        return redirect()->route('admin.alwaseet.show', $shipment->id)
            ->with('success', 'تم ربط الشحنة بالطلب بنجاح!');
    }

    /**
     * إلغاء ربط شحنة
     */
    public function unlinkOrder($id)
    {
        $shipment = AlWaseetShipment::findOrFail($id);
        $shipment->update(['order_id' => null]);

        return back()->with('success', 'تم إلغاء الربط بنجاح!');
    }

    /**
     * صفحة إنشاء طلب جديد
     */
    public function createOrder()
    {
        try {
            $cities = $this->alWaseetService->getCities();
            $packageSizes = $this->alWaseetService->getPackageSizes();
        } catch (\Exception $e) {
            $cities = [];
            $packageSizes = [];
            Log::error('AlWaseetController: Failed to load form data', [
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.alwaseet.create', compact('cities', 'packageSizes'));
    }

    /**
     * حفظ طلب جديد
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'client_name' => 'required|string|max:255',
            'client_mobile' => 'required|string|regex:/^\+964[0-9]{9,10}$/',
            'client_mobile2' => 'nullable|string|regex:/^\+964[0-9]{9,10}$/',
            'city_id' => 'required|string',
            'region_id' => 'required|string',
            'location' => 'required|string',
            'price' => 'required|numeric|min:0',
            'package_size' => 'required|string',
            'type_name' => 'required|string|max:255',
            'items_number' => 'nullable|integer|min:1',
            'merchant_notes' => 'nullable|string',
            'replacement' => 'nullable|boolean',
        ]);

        try {
            // إعداد البيانات للإرسال
            $orderData = [
                'client_name' => $request->client_name,
                'client_mobile' => $request->client_mobile,
                'city_id' => $request->city_id,
                'region_id' => $request->region_id,
                'location' => $request->location,
                'price' => (string)$request->price,
                'package_size' => $request->package_size,
                'type_name' => $request->type_name,
                'items_number' => (string)($request->items_number ?? '1'), // مطلوب حسب API
            ];

            if ($request->filled('client_mobile2')) {
                $orderData['client_mobile2'] = $request->client_mobile2;
            }

            if ($request->filled('merchant_notes')) {
                $orderData['merchant_notes'] = $request->merchant_notes;
            }

            if ($request->has('replacement')) {
                $orderData['replacement'] = $request->replacement ? '1' : '0';
            }

            // إنشاء الطلب في الواسط
            $result = $this->alWaseetService->createOrder($orderData);

            if (!isset($result['id'])) {
                throw new \Exception('لم يتم إرجاع معرف الطلب من الواسط');
            }

            // جلب بيانات الطلب الكاملة من الواسط
            $orders = $this->alWaseetService->getOrdersByIds([$result['id']]);

            if (empty($orders)) {
                throw new \Exception('فشل جلب بيانات الطلب بعد الإنشاء');
            }

            $orderData = $orders[0];

            // حفظ في قاعدة البيانات
            $shipment = AlWaseetShipment::create([
                'alwaseet_order_id' => $orderData['id'],
                'client_name' => $orderData['client_name'] ?? $request->client_name,
                'client_mobile' => $orderData['client_mobile'] ?? $request->client_mobile,
                'client_mobile2' => $orderData['client_mobile2'] ?? $request->client_mobile2,
                'city_id' => $orderData['city_id'] ?? $request->city_id,
                'city_name' => $orderData['city_name'] ?? '',
                'region_id' => $orderData['region_id'] ?? $request->region_id,
                'region_name' => $orderData['region_name'] ?? '',
                'location' => $orderData['location'] ?? $request->location,
                'price' => $orderData['price'] ?? $request->price,
                'delivery_price' => $orderData['delivery_price'] ?? 0,
                'package_size' => $orderData['package_size'] ?? $request->package_size,
                'type_name' => $orderData['type_name'] ?? $request->type_name,
                'status_id' => $orderData['status_id'] ?? '1',
                'status' => $orderData['status'] ?? 'جديد',
                'items_number' => $orderData['items_number'] ?? '1',
                'merchant_notes' => $orderData['merchant_notes'] ?? $request->merchant_notes,
                'replacement' => isset($orderData['replacement']) && $orderData['replacement'] === '1',
                'qr_id' => $result['qr_id'] ?? null,
                'qr_link' => $result['qr_link'] ?? null,
                'alwaseet_created_at' => isset($orderData['created_at']) ? \Carbon\Carbon::parse($orderData['created_at']) : now(),
                'alwaseet_updated_at' => isset($orderData['updated_at']) ? \Carbon\Carbon::parse($orderData['updated_at']) : now(),
                'synced_at' => now(),
            ]);

            return redirect()->route('admin.alwaseet.show', $shipment->id)
                ->with('success', 'تم إنشاء الطلب بنجاح!');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Store order failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // تحسين رسالة الخطأ للمستخدم
            $errorMessage = $e->getMessage();

            // إذا كان الخطأ متعلق بالصلاحية
            if (str_contains($errorMessage, 'صلاحية') || str_contains($errorMessage, 'errNum')) {
                $errorMessage = 'فشل إنشاء الطلب: انتهت صلاحية الاتصال. يرجى المحاولة مرة أخرى أو التحقق من إعدادات الربط.';
            } elseif (str_contains($errorMessage, '400')) {
                $errorMessage = 'فشل إنشاء الطلب: البيانات المرسلة غير صحيحة. يرجى التحقق من جميع الحقول وإعادة المحاولة.';
            } else {
                $errorMessage = 'فشل إنشاء الطلب: ' . $errorMessage;
            }

            return back()->withErrors(['order' => $errorMessage])->withInput();
        }
    }

    /**
     * صفحة تعديل طلب
     */
    public function editOrder($id)
    {
        $shipment = AlWaseetShipment::findOrFail($id);

        if (!$shipment->canBeEdited()) {
            return redirect()->route('admin.alwaseet.show', $id)
                ->withErrors(['edit' => 'لا يمكن تعديل هذا الطلب لأنه تم استلامه من قبل المندوب']);
        }

        try {
            $cities = $this->alWaseetService->getCities();
            $regions = $this->alWaseetService->getRegions($shipment->city_id);
            $packageSizes = $this->alWaseetService->getPackageSizes();
        } catch (\Exception $e) {
            $cities = [];
            $regions = [];
            $packageSizes = [];
            Log::error('AlWaseetController: Failed to load form data', [
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.alwaseet.edit', compact('shipment', 'cities', 'regions', 'packageSizes'));
    }

    /**
     * تحديث طلب
     */
    public function updateOrder(Request $request, $id)
    {
        $shipment = AlWaseetShipment::findOrFail($id);

        if (!$shipment->canBeEdited()) {
            return redirect()->route('admin.alwaseet.show', $id)
                ->withErrors(['edit' => 'لا يمكن تعديل هذا الطلب لأنه تم استلامه من قبل المندوب']);
        }

        $request->validate([
            'client_name' => 'required|string|max:255',
            'client_mobile' => 'required|string|regex:/^\+964[0-9]{9,10}$/',
            'client_mobile2' => 'nullable|string|regex:/^\+964[0-9]{9,10}$/',
            'city_id' => 'required|string',
            'region_id' => 'required|string',
            'location' => 'required|string',
            'price' => 'required|numeric|min:0',
            'package_size' => 'required|string',
            'type_name' => 'required|string|max:255',
            'items_number' => 'nullable|integer|min:1',
            'merchant_notes' => 'nullable|string',
            'replacement' => 'nullable|boolean',
        ]);

        try {
            // إعداد البيانات للتحديث
            $orderData = [
                'client_name' => $request->client_name,
                'client_mobile' => $request->client_mobile,
                'city_id' => $request->city_id,
                'region_id' => $request->region_id,
                'location' => $request->location,
                'price' => (string)$request->price,
                'package_size' => $request->package_size,
                'type_name' => $request->type_name,
                'items_number' => (string)($request->items_number ?? '1'), // مطلوب حسب API
            ];

            if ($request->filled('client_mobile2')) {
                $orderData['client_mobile2'] = $request->client_mobile2;
            }

            if ($request->filled('merchant_notes')) {
                $orderData['merchant_notes'] = $request->merchant_notes;
            }

            if ($request->has('replacement')) {
                $orderData['replacement'] = $request->replacement ? '1' : '0';
            }

            // تحديث الطلب في الواسط
            $this->alWaseetService->editOrder($shipment->alwaseet_order_id, $orderData);

            // جلب بيانات الطلب المحدثة
            $orders = $this->alWaseetService->getOrdersByIds([$shipment->alwaseet_order_id]);

            if (!empty($orders)) {
                $orderData = $orders[0];

                // تحديث في قاعدة البيانات
                $shipment->update([
                    'client_name' => $orderData['client_name'] ?? $request->client_name,
                    'client_mobile' => $orderData['client_mobile'] ?? $request->client_mobile,
                    'client_mobile2' => $orderData['client_mobile2'] ?? $request->client_mobile2,
                    'city_id' => $orderData['city_id'] ?? $request->city_id,
                    'city_name' => $orderData['city_name'] ?? '',
                    'region_id' => $orderData['region_id'] ?? $request->region_id,
                    'region_name' => $orderData['region_name'] ?? '',
                    'location' => $orderData['location'] ?? $request->location,
                    'price' => $orderData['price'] ?? $request->price,
                    'delivery_price' => $orderData['delivery_price'] ?? $shipment->delivery_price,
                    'package_size' => $orderData['package_size'] ?? $request->package_size,
                    'type_name' => $orderData['type_name'] ?? $request->type_name,
                    'status_id' => $orderData['status_id'] ?? $shipment->status_id,
                    'status' => $orderData['status'] ?? $shipment->status,
                    'merchant_notes' => $orderData['merchant_notes'] ?? $request->merchant_notes,
                    'replacement' => isset($orderData['replacement']) && $orderData['replacement'] === '1',
                    'alwaseet_updated_at' => isset($orderData['updated_at']) ? \Carbon\Carbon::parse($orderData['updated_at']) : now(),
                    'synced_at' => now(),
                ]);
            } else {
                // إذا فشل جلب البيانات، نحدث فقط الحقول الأساسية
                $shipment->update([
                    'client_name' => $request->client_name,
                    'client_mobile' => $request->client_mobile,
                    'client_mobile2' => $request->client_mobile2,
                    'city_id' => $request->city_id,
                    'region_id' => $request->region_id,
                    'location' => $request->location,
                    'price' => $request->price,
                    'package_size' => $request->package_size,
                    'type_name' => $request->type_name,
                    'merchant_notes' => $request->merchant_notes,
                    'replacement' => $request->has('replacement') && $request->replacement,
                    'synced_at' => now(),
                ]);
            }

            return redirect()->route('admin.alwaseet.show', $shipment->id)
                ->with('success', 'تم تحديث الطلب بنجاح!');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update order failed', [
                'error' => $e->getMessage(),
                'shipment_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);

            // تحسين رسالة الخطأ للمستخدم
            $errorMessage = $e->getMessage();

            // إذا كان الخطأ متعلق بالصلاحية
            if (str_contains($errorMessage, 'صلاحية') || str_contains($errorMessage, 'errNum')) {
                $errorMessage = 'فشل تحديث الطلب: انتهت صلاحية الاتصال. يرجى المحاولة مرة أخرى أو التحقق من إعدادات الربط.';
            } elseif (str_contains($errorMessage, '400')) {
                $errorMessage = 'فشل تحديث الطلب: البيانات المرسلة غير صحيحة. يرجى التحقق من جميع الحقول وإعادة المحاولة.';
            } else {
                $errorMessage = 'فشل تحديث الطلب: ' . $errorMessage;
            }

            return back()->withErrors(['order' => $errorMessage])->withInput();
        }
    }

    /**
     * صفحة الفواتير
     */
    public function invoices(Request $request)
    {
        try {
            $invoices = $this->alWaseetService->getMerchantInvoices();
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Get invoices failed', [
                'error' => $e->getMessage(),
            ]);
            $invoices = [];
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $invoices = array_filter($invoices, function($invoice) use ($request) {
                return str_contains($invoice['status'] ?? '', $request->status);
            });
        }

        return view('admin.alwaseet.invoices.index', compact('invoices'));
    }

    /**
     * تفاصيل فاتورة
     */
    public function showInvoice($id)
    {
        try {
            $invoiceData = $this->alWaseetService->getInvoiceOrders($id);
            $invoice = $invoiceData['invoice'][0] ?? null;
            $orders = $invoiceData['orders'] ?? [];
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Get invoice orders failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $id,
            ]);

            return back()->withErrors(['invoice' => 'فشل جلب بيانات الفاتورة: ' . $e->getMessage()]);
        }

        if (!$invoice) {
            return back()->withErrors(['invoice' => 'الفاتورة غير موجودة']);
        }

        return view('admin.alwaseet.invoices.show', compact('invoice', 'orders'));
    }

    /**
     * تأكيد استلام فاتورة
     */
    public function receiveInvoice($id)
    {
        try {
            $this->alWaseetService->receiveInvoice($id);

            return redirect()->route('admin.alwaseet.invoices.show', $id)
                ->with('success', 'تم تأكيد استلام الفاتورة بنجاح!');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Receive invoice failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $id,
            ]);

            return back()->withErrors(['invoice' => 'فشل تأكيد الاستلام: ' . $e->getMessage()]);
        }
    }

    /**
     * API: جلب المناطق حسب المدينة (للاستخدام في AJAX)
     */
    public function getRegions(Request $request)
    {
        $request->validate([
            'city_id' => 'required|string',
        ]);

        try {
            $regions = $this->alWaseetService->getRegions($request->city_id);
            return response()->json(['regions' => $regions]);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Get regions failed', [
                'error' => $e->getMessage(),
                'city_id' => $request->city_id,
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * صفحة إعدادات التكامل التلقائي
     */
    public function autoIntegration()
    {
        $autoCreate = Setting::getValue('alwaseet_auto_create_shipment', '0');
        $defaultCityId = Setting::getValue('alwaseet_default_city_id');
        $defaultRegionId = Setting::getValue('alwaseet_default_region_id');
        $defaultPackageSizeId = Setting::getValue('alwaseet_default_package_size_id');
        $defaultTypeName = Setting::getValue('alwaseet_default_type_name', 'ملابس');

        $cities = [];
        $regions = [];
        $packageSizes = [];

        try {
            $cities = $this->alWaseetService->getCities();
            if ($defaultCityId) {
                $regions = $this->alWaseetService->getRegions($defaultCityId);
            }
            $packageSizes = $this->alWaseetService->getPackageSizes();
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Failed to load auto integration data', [
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.alwaseet.auto-integration', compact(
            'autoCreate',
            'defaultCityId',
            'defaultRegionId',
            'defaultPackageSizeId',
            'defaultTypeName',
            'cities',
            'regions',
            'packageSizes'
        ));
    }

    /**
     * تحديث إعدادات التكامل التلقائي
     */
    public function updateAutoIntegration(Request $request)
    {
        $request->validate([
            'auto_create_shipment' => 'nullable|boolean',
            'default_city_id' => 'nullable|string',
            'default_region_id' => 'nullable|string',
            'default_package_size_id' => 'nullable|string',
            'default_type_name' => 'nullable|string|max:255',
        ]);

        try {
            Setting::setValue('alwaseet_auto_create_shipment', $request->has('auto_create_shipment') ? '1' : '0');
            Setting::setValue('alwaseet_default_city_id', $request->default_city_id ?? '');
            Setting::setValue('alwaseet_default_region_id', $request->default_region_id ?? '');
            Setting::setValue('alwaseet_default_package_size_id', $request->default_package_size_id ?? '');
            Setting::setValue('alwaseet_default_type_name', $request->default_type_name ?? 'ملابس');

            return redirect()->route('admin.alwaseet.auto-integration')
                ->with('success', 'تم تحديث إعدادات التكامل التلقائي بنجاح!');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update auto integration failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'فشل تحديث الإعدادات: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * صفحة إدارة المزامنة التلقائية
     */
    public function autoSync()
    {
        $syncEnabled = Setting::getValue('alwaseet_auto_sync_enabled', '0');
        $syncInterval = Setting::getValue('alwaseet_auto_sync_interval', '60'); // دقائق
        $syncStatusIds = Setting::getValue('alwaseet_auto_sync_status_ids', '');

        $syncLogs = \App\Models\AlWaseetSyncLog::orderBy('created_at', 'desc')
            ->paginate(20);

        $statuses = [];
        try {
            $statuses = $this->alWaseetService->getOrderStatuses();
        } catch (\Exception $e) {
            // استخدام حالات من قاعدة البيانات
            $statuses = AlWaseetShipment::select('status_id', 'status')
                ->distinct()
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->status_id,
                        'status' => $item->status,
                    ];
                })
                ->toArray();
        }

        return view('admin.alwaseet.auto-sync', compact(
            'syncEnabled',
            'syncInterval',
            'syncStatusIds',
            'syncLogs',
            'statuses'
        ));
    }

    /**
     * تحديث إعدادات المزامنة التلقائية
     */
    public function updateAutoSync(Request $request)
    {
        $request->validate([
            'auto_sync_enabled' => 'nullable|boolean',
            'auto_sync_interval' => 'nullable|integer|min:5|max:1440',
            'auto_sync_status_ids' => 'nullable|string',
        ]);

        try {
            Setting::setValue('alwaseet_auto_sync_enabled', $request->has('auto_sync_enabled') ? '1' : '0');
            Setting::setValue('alwaseet_auto_sync_interval', $request->auto_sync_interval ?? '60');
            Setting::setValue('alwaseet_auto_sync_status_ids', $request->auto_sync_status_ids ?? '');

            return redirect()->route('admin.alwaseet.auto-sync')
                ->with('success', 'تم تحديث إعدادات المزامنة التلقائية بنجاح!');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update auto sync failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'فشل تحديث الإعدادات: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * صفحة الإشعارات
     */
    public function notifications(Request $request)
    {
        $query = \App\Models\AlWaseetNotification::with('shipment')
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_read')) {
            $query->where('is_read', $request->is_read === '1');
        }

        $notifications = $query->paginate(20);

        $notifyStatuses = Setting::getValue('alwaseet_notify_statuses', '');

        $statuses = [];
        try {
            $statuses = $this->alWaseetService->getOrderStatuses();
        } catch (\Exception $e) {
            $statuses = AlWaseetShipment::select('status_id', 'status')
                ->distinct()
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->status_id,
                        'status' => $item->status,
                    ];
                })
                ->toArray();
        }

        return view('admin.alwaseet.notifications', compact(
            'notifications',
            'notifyStatuses',
            'statuses'
        ));
    }

    /**
     * تحديث إعدادات الإشعارات
     */
    public function updateNotifications(Request $request)
    {
        $request->validate([
            'notify_statuses' => 'nullable|string',
        ]);

        try {
            Setting::setValue('alwaseet_notify_statuses', $request->notify_statuses ?? '');

            return redirect()->route('admin.alwaseet.notifications')
                ->with('success', 'تم تحديث إعدادات الإشعارات بنجاح!');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update notifications failed', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'فشل تحديث الإعدادات: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * تحديد إشعار كمقروء
     */
    public function markNotificationAsRead($id)
    {
        $notification = \App\Models\AlWaseetNotification::findOrFail($id);
        $notification->markAsRead();

        return back()->with('success', 'تم تحديد الإشعار كمقروء');
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    public function markAllNotificationsAsRead()
    {
        \App\Models\AlWaseetNotification::where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة');
    }

    /**
     * صفحة التقارير والإحصائيات
     */
    public function reports(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $query = AlWaseetShipment::whereBetween('alwaseet_created_at', [
            $dateFrom . ' 00:00:00',
            $dateTo . ' 23:59:59'
        ]);

        // إحصائيات عامة
        $totalShipments = (clone $query)->count();
        $totalAmount = (clone $query)->sum('price');
        $totalDeliveryFee = (clone $query)->sum('delivery_price');
        $deliveredCount = (clone $query)->where('status_id', '4')->count();

        // إحصائيات حسب الحالة
        $statusStats = (clone $query)
            ->select('status_id', 'status', DB::raw('count(*) as count'), DB::raw('sum(price) as total'))
            ->groupBy('status_id', 'status')
            ->get();

        // إحصائيات حسب المدينة
        $cityStats = (clone $query)
            ->select('city_name', DB::raw('count(*) as count'), DB::raw('sum(price) as total'))
            ->groupBy('city_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // إحصائيات حسب المنطقة
        $regionStats = (clone $query)
            ->select('region_name', DB::raw('count(*) as count'), DB::raw('sum(price) as total'))
            ->groupBy('region_name')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        // إحصائيات يومية
        $dailyStats = (clone $query)
            ->select(DB::raw('DATE(alwaseet_created_at) as date'), DB::raw('count(*) as count'), DB::raw('sum(price) as total'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return view('admin.alwaseet.reports', compact(
            'dateFrom',
            'dateTo',
            'totalShipments',
            'totalAmount',
            'totalDeliveryFee',
            'deliveredCount',
            'statusStats',
            'cityStats',
            'regionStats',
            'dailyStats'
        ));
    }

    /**
     * صفحة إدارة Rate Limiting
     */
    public function rateLimiting()
    {
        $rateLimitCount = \Illuminate\Support\Facades\Cache::get('alwaseet_rate_limit', 0);
        $rateLimitMax = 30;
        $rateLimitWindow = 30; // seconds

        // عدد Jobs المعلقة
        $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')
            ->where('queue', 'default')
            ->where('payload', 'like', '%AlWaseet%')
            ->count();

        // سجل الطلبات المعلقة
        $queueLogs = \Illuminate\Support\Facades\DB::table('failed_jobs')
            ->where('payload', 'like', '%AlWaseet%')
            ->orderBy('failed_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.alwaseet.rate-limiting', compact(
            'rateLimitCount',
            'rateLimitMax',
            'rateLimitWindow',
            'pendingJobs',
            'queueLogs'
        ));
    }

    /**
     * صفحة إضافة طلب الوسيط - عرض الطلبات غير المقيدة
     */
    public function addOrderFromPending(Request $request)
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

        // جلب المدن من API الواسط
        $cities = [];
        $ordersWithRegions = [];
        try {
            $cities = $this->alWaseetService->getCities();

            // جلب المناطق لكل طلب لديه city_id
            foreach ($orders as $order) {
                if ($order->alwaseet_city_id) {
                    try {
                        $regions = $this->alWaseetService->getRegions($order->alwaseet_city_id);
                        $ordersWithRegions[$order->id] = $regions;
                    } catch (\Exception $e) {
                        $ordersWithRegions[$order->id] = [];
                    }
                } else {
                    $ordersWithRegions[$order->id] = [];
                }
            }
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Failed to load cities in addOrderFromPending', [
                'error' => $e->getMessage(),
            ]);
        }

        return view('admin.alwaseet.add-order-from-pending', compact('orders', 'warehouses', 'suppliers', 'delegates', 'pendingTotalAmount', 'confirmedTotalAmount', 'pendingProfitAmount', 'confirmedProfitAmount', 'cities', 'ordersWithRegions'));
    }

    /**
     * صفحة رفع وطباع طلبات الوسيط
     */
    public function printAndUploadOrders(Request $request)
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

        // فلتر حالة الإرسال للواسط
        if ($request->filled('alwaseet_sent')) {
            if ($request->alwaseet_sent === 'sent') {
                // الطلبات المرسلة: لديها shipment
                $query->whereHas('alwaseetShipment');
            } elseif ($request->alwaseet_sent === 'not_sent') {
                // الطلبات غير المرسلة: لا لديها shipment
                $query->whereDoesntHave('alwaseetShipment');
            }
        }

        // فلتر اكتمال البيانات (المحافظة والمنطقة)
        if ($request->filled('alwaseet_complete')) {
            if ($request->alwaseet_complete === 'complete') {
                // الطلبات المكتملة: لديها محافظة ومنطقة
                $query->whereNotNull('alwaseet_city_id')
                      ->whereNotNull('alwaseet_region_id')
                      ->where('alwaseet_city_id', '!=', '')
                      ->where('alwaseet_region_id', '!=', '');
            } elseif ($request->alwaseet_complete === 'incomplete') {
                // الطلبات غير المكتملة: لا لديها محافظة أو منطقة
                $query->where(function($q) {
                    $q->whereNull('alwaseet_city_id')
                      ->orWhere('alwaseet_city_id', '=', '')
                      ->orWhereNull('alwaseet_region_id')
                      ->orWhere('alwaseet_region_id', '=', '');
                });
            }
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
        $query->with(['delegate', 'items.product.warehouse', 'items.product.primaryImage', 'confirmedBy', 'processedBy', 'alwaseetShipment']);

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

        // جلب المدن من API الواسط
        $cities = [];
        $ordersWithRegions = [];
        try {
            $cities = $this->alWaseetService->getCities();

            // جلب المناطق لكل طلب لديه city_id
            foreach ($orders as $order) {
                if ($order->alwaseet_city_id) {
                    try {
                        $regions = $this->alWaseetService->getRegions($order->alwaseet_city_id);
                        $ordersWithRegions[$order->id] = $regions;
                    } catch (\Exception $e) {
                        $ordersWithRegions[$order->id] = [];
                    }
                } else {
                    $ordersWithRegions[$order->id] = [];
                }
            }
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Failed to load cities in printAndUploadOrders', [
                'error' => $e->getMessage(),
            ]);
        }

        // جلب حالة الطلبات من API الواسط
        $alwaseetOrdersData = [];
        try {
            // جمع alwaseet_order_id من shipments المرتبطة
            $alwaseetOrderIds = [];
            foreach ($orders as $order) {
                if ($order->alwaseetShipment && $order->alwaseetShipment->alwaseet_order_id) {
                    $alwaseetOrderIds[] = $order->alwaseetShipment->alwaseet_order_id;
                }
            }

            // جلب الطلبات من API إذا كان هناك أي orders
            if (!empty($alwaseetOrderIds)) {
                $apiOrders = $this->alWaseetService->getOrdersByIds($alwaseetOrderIds);

                // إنشاء array يربط alwaseet_order_id بالبيانات من API
                foreach ($apiOrders as $apiOrder) {
                    if (isset($apiOrder['id'])) {
                        $alwaseetOrdersData[$apiOrder['id']] = $apiOrder;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Failed to load orders from AlWaseet API in printAndUploadOrders', [
                'error' => $e->getMessage(),
            ]);
            // في حالة الفشل، سنستخدم قاعدة البيانات المحلية كـ fallback
        }

        return view('admin.alwaseet.print-and-upload-orders', compact('orders', 'warehouses', 'suppliers', 'delegates', 'pendingTotalAmount', 'confirmedTotalAmount', 'pendingProfitAmount', 'confirmedProfitAmount', 'cities', 'ordersWithRegions', 'alwaseetOrdersData'));
    }

    /**
     * تحديث حقول الواسط للطلب
     */
    public function updateOrderAlwaseetFields(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);

        $request->validate([
            'alwaseet_city_id' => 'nullable|string',
            'alwaseet_region_id' => 'nullable|string',
        ]);

        try {
            $order->update([
                'alwaseet_city_id' => $request->alwaseet_city_id,
                'alwaseet_region_id' => $request->alwaseet_region_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الواسط بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update order alwaseet fields failed', [
                'error' => $e->getMessage(),
                'order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل تحديث البيانات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث ملاحظة الوقت للطلب
     */
    public function updateDeliveryTimeNote(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $this->authorize('update', $order);

        $request->validate([
            'delivery_time_note' => 'nullable|string|in:morning,noon,evening,urgent',
        ]);

        try {
            $order->update([
                'alwaseet_delivery_time_note' => $request->delivery_time_note,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ ملاحظة الوقت بنجاح',
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Update delivery time note failed', [
                'error' => $e->getMessage(),
                'order_id' => $id,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل حفظ ملاحظة الوقت: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إرسال الطلب إلى الواسط API
     */
    public function sendOrderToAlWaseet(Request $request, $id)
    {
        try {
            $order = Order::with('items.product')->findOrFail($id);
            $this->authorize('update', $order);

            // التحقق من وجود المحافظة والمنطقة
            if (empty($order->alwaseet_city_id) || empty($order->alwaseet_region_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد المحافظة والمنطقة أولاً',
                ], 400);
            }

            // جلب حجم الطرد "عادي"
            $packageSizes = $this->alWaseetService->getPackageSizes();
            $normalPackageSize = collect($packageSizes)->firstWhere('package_size_name', 'عادي');

            if (!$normalPackageSize) {
                // إذا لم نجد "عادي"، نأخذ الأول
                $normalPackageSize = $packageSizes[0] ?? null;
            }

            if (!$normalPackageSize) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل جلب أحجام الطرود من الواسط',
                ], 500);
            }

            // حساب السعر الكلي (يجب حسابه قبل بناء نوع البضاعة)
            $deliveryFee = Setting::getDeliveryFee();
            $totalPrice = $order->total_amount + $deliveryFee;

            // تنسيق نوع البضاعة بشكل كامل (مثل process page - بدون النص الثابت)
            $productParts = $order->items->map(function($item) {
                $rawName = optional($item->product)->name ?? $item->product_name ?? '';
                $name = $rawName;
                // إزالة النص بين الأقواس إذا كان موجوداً
                if (strpos($name, '(') !== false) {
                    $name = trim(substr($name, 0, strpos($name, '(')));
                }
                $name = trim($name);

                if (empty($name)) {
                    return '';
                }

                $unitPrice = $item->unit_price ?? 0;
                $quantity = $item->quantity ?? 0;

                // تنسيق: اسم المنتج سعر السعر العدد الكمية
                return "{$name} سعر {$unitPrice} العدد {$quantity}";
            })->filter();

            // حساب الإجماليات
            $totalQuantity = $order->items->sum('quantity');

            // بناء النص الكامل
            $productNames = '';
            if ($productParts->isNotEmpty()) {
                $productsText = $productParts->implode(' ');
                $productNames = "{$productsText} العدد الإجمالي {$totalQuantity} المبلغ الكلي مع التوصيل {$totalPrice}";
            } else {
                $productNames = 'ملابس'; // قيمة افتراضية
            }

            // تنسيق رقم الهاتف
            $phone = $this->formatPhoneForAlWaseet($order->customer_phone);
            $phone2 = $order->customer_phone2 ? $this->formatPhoneForAlWaseet($order->customer_phone2) : null;

            // جلب ملاحظة التاجر من الإعدادات
            $merchantNotes = Setting::getValue('alwaseet_merchant_notes', '');

            // دمج ملاحظة الوقت مع ملاحظة التاجر
            $deliveryTimeNoteText = '';
            if ($order->alwaseet_delivery_time_note) {
                $deliveryTimeNotes = [
                    'morning' => 'توصيل صباحا',
                    'noon' => 'توصيل ضهرا',
                    'evening' => 'توصيل مسائا',
                    'urgent' => 'توصيل مستعجل',
                ];
                $deliveryTimeNoteText = $deliveryTimeNotes[$order->alwaseet_delivery_time_note] ?? '';
            }

            // دمج ملاحظة التاجر مع ملاحظة الوقت (سطر جديد)
            $finalMerchantNotes = $merchantNotes ?? '';
            if ($deliveryTimeNoteText) {
                if (!empty($finalMerchantNotes)) {
                    $finalMerchantNotes .= "\n" . $deliveryTimeNoteText;
                } else {
                    $finalMerchantNotes = $deliveryTimeNoteText;
                }
            }

            // إعداد بيانات الطلب
            $orderData = [
                'client_name' => $order->customer_name,
                'client_mobile' => $phone,
                'city_id' => $order->alwaseet_city_id,
                'region_id' => $order->alwaseet_region_id,
                'location' => $order->customer_address ?? '',
                'price' => (string)$totalPrice,
                'package_size' => $normalPackageSize['id'],
                'type_name' => $productNames,
                'items_number' => (string)$order->items->count(),
            ];

            if ($phone2) {
                $orderData['client_mobile2'] = $phone2;
            }

            // إرسال ملاحظة التاجر المدمجة (حتى لو كانت فارغة)
            $orderData['merchant_notes'] = $finalMerchantNotes;

            // إرسال الطلب إلى الواسط
            $result = $this->alWaseetService->createOrder($orderData);

            if (!isset($result['id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل إنشاء الطلب في الواسط: ' . ($result['msg'] ?? 'خطأ غير معروف'),
                ], 500);
            }

            // جلب pickup_id من النتيجة (هذا هو الكود الصحيح من الواسط)
            $pickupId = $result['pickup_id'] ?? null;

            // جلب qr_id أيضاً (كود QR)
            $qrId = $result['qr_id'] ?? null;

            // حفظ/تحديث pickup_id في delivery_code (فقط إذا كان موجوداً)
            // إذا لم يكن pickup_id موجوداً، نستخدم qr_id
            $codeToSave = $pickupId ?? $qrId;
            if ($codeToSave) {
                $order->delivery_code = (string)$codeToSave;
                $order->save();
            }

            // حفظ shipment في قاعدة البيانات
            $shipment = AlWaseetShipment::updateOrCreate(
                ['alwaseet_order_id' => $result['id']],
                [
                    'order_id' => $order->id,
                    'alwaseet_order_id' => $result['id'],
                    'client_name' => $order->customer_name,
                    'client_mobile' => $phone,
                    'client_mobile2' => $phone2,
                    'city_id' => $order->alwaseet_city_id,
                    'city_name' => $result['city_name'] ?? '',
                    'region_id' => $order->alwaseet_region_id,
                    'region_name' => $result['region_name'] ?? '',
                    'location' => $order->customer_address ?? '',
                    'price' => $totalPrice,
                    'delivery_price' => $deliveryFee,
                    'package_size' => $normalPackageSize['id'],
                    'type_name' => $productNames,
                    'status_id' => $result['status_id'] ?? '1',
                    'status' => $result['status'] ?? 'جديد',
                    'items_number' => (string)$order->items->count(),
                    'merchant_notes' => $merchantNotes,
                    'replacement' => false,
                    'qr_id' => $result['qr_id'] ?? null,
                    'qr_link' => $result['qr_link'] ?? null,
                    'alwaseet_created_at' => isset($result['created_at']) ? \Carbon\Carbon::parse($result['created_at']) : now(),
                    'alwaseet_updated_at' => isset($result['updated_at']) ? \Carbon\Carbon::parse($result['updated_at']) : now(),
                    'synced_at' => now(),
                    'printed_at' => now(), // تحديث printed_at عند الإرسال
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الطلب إلى الواسط بنجاح',
                'shipment_id' => $result['id'],
                'alwaseet_order_id' => $result['id'],
                'pickup_id' => $pickupId,
                'delivery_code' => $pickupId, // إضافة delivery_code للتوافق
                'qr_link' => $result['qr_link'] ?? null,
                'qr_id' => $result['qr_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Send order to AlWaseet failed', [
                'error' => $e->getMessage(),
                'order_id' => $id,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال الطلب: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تنسيق رقم الهاتف للواسط (+964)
     */
    protected function formatPhoneForAlWaseet(string $phone): string
    {
        // إزالة المسافات والأرقام
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // إذا كان يبدأ بـ 0، استبدله بـ +964
        if (strpos($phone, '0') === 0) {
            $phone = '+964' . substr($phone, 1);
        } elseif (strpos($phone, '964') === 0) {
            $phone = '+' . $phone;
        } elseif (strpos($phone, '+964') !== 0) {
            $phone = '+964' . $phone;
        }

        return $phone;
    }

    /**
     * طباعة جميع الطلبات المرسلة في ملف PDF واحد
     */
    public function printAllOrders(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        try {
            // استخدام نفس الفلاتر من printAndUploadOrders
            $query = Order::where('status', 'pending');

            // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
            if (Auth::user()->isSupplier()) {
                $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                $query->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                    $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                });
            }

            // تطبيق نفس الفلاتر من request
            if ($request->filled('warehouse_id')) {
                $query->whereHas('items.product', function($q) use ($request) {
                    $q->where('warehouse_id', $request->warehouse_id);
                });
            }

            if ($request->filled('delegate_id')) {
                $query->where('delegate_id', $request->delegate_id);
            }

            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function($q) use ($searchTerm) {
                    $q->where('order_number', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_name', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_phone', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_social_link', 'like', "%{$searchTerm}%")
                      ->orWhere('customer_address', 'like', "%{$searchTerm}%")
                      ->orWhere('delivery_code', 'like', "%{$searchTerm}%");
                });
            }

            // جلب الطلبات مع shipments
            $orders = $query->with('alwaseetShipment')->get();

            // جمع qr_links من الطلبات المرسلة
            $qrLinks = [];
            foreach ($orders as $order) {
                $shipment = $order->alwaseetShipment;
                if ($shipment && !empty($shipment->qr_link)) {
                    $qrLinks[] = $shipment->qr_link;
                }
            }

            if (empty($qrLinks)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا توجد طلبات مرسلة لديها PDFs للطباعة',
                ], 400);
            }

            // دمج PDFs
            $mergedPdf = $this->alWaseetService->mergePdfs($qrLinks);

            // إرجاع الملف المدمج
            return response($mergedPdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="alwaseet-orders-' . date('Y-m-d-His') . '.pdf"')
                ->header('Content-Length', strlen($mergedPdf))
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public');
        } catch (\Exception $e) {
            Log::error('AlWaseetController: Print all orders failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'فشل طباعة الطلبات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get materials list for print-and-upload-orders page.
     */
    public function getMaterialsListForPrintUpload(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // Base query - نفس منطق printAndUploadOrders
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

        // فلتر حالة الإرسال للواسط
        if ($request->filled('alwaseet_sent')) {
            if ($request->alwaseet_sent === 'sent') {
                $query->whereHas('alwaseetShipment');
            } elseif ($request->alwaseet_sent === 'not_sent') {
                $query->whereDoesntHave('alwaseetShipment');
            }
        }

        // فلتر اكتمال البيانات
        if ($request->filled('alwaseet_complete')) {
            if ($request->alwaseet_complete === 'complete') {
                $query->whereNotNull('alwaseet_city_id')
                      ->whereNotNull('alwaseet_region_id')
                      ->where('alwaseet_city_id', '!=', '')
                      ->where('alwaseet_region_id', '!=', '');
            } elseif ($request->alwaseet_complete === 'incomplete') {
                $query->where(function($q) {
                    $q->whereNull('alwaseet_city_id')
                      ->orWhere('alwaseet_city_id', '=', '')
                      ->orWhereNull('alwaseet_region_id')
                      ->orWhere('alwaseet_region_id', '=', '');
                });
            }
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
                  })
                  ->orWhereHas('items.product', function($productQuery) use ($searchTerm) {
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
            'items.product.warehouse',
            'alwaseetShipment'
        ])->get();

        // فلترة items حسب المخزن والصلاحيات
        foreach ($orders as $order) {
            $order->items = $order->items->filter(function($item) use ($request) {
                if (!$item->product) return false;

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
            });
        }

        // إزالة الطلبات التي لا تحتوي على items بعد الفلترة
        $orders = $orders->filter(function($order) {
            return $order->items->count() > 0;
        });

        return view('admin.alwaseet.materials-list', compact('orders'));
    }

    /**
     * Get materials list grouped by product code for print-and-upload-orders page.
     */
    public function getMaterialsListGroupedForPrintUpload(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        // Base query - نفس منطق printAndUploadOrders
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

        // فلتر حالة الإرسال للواسط
        if ($request->filled('alwaseet_sent')) {
            if ($request->alwaseet_sent === 'sent') {
                $query->whereHas('alwaseetShipment');
            } elseif ($request->alwaseet_sent === 'not_sent') {
                $query->whereDoesntHave('alwaseetShipment');
            }
        }

        // فلتر اكتمال البيانات
        if ($request->filled('alwaseet_complete')) {
            if ($request->alwaseet_complete === 'complete') {
                $query->whereNotNull('alwaseet_city_id')
                      ->whereNotNull('alwaseet_region_id')
                      ->where('alwaseet_city_id', '!=', '')
                      ->where('alwaseet_region_id', '!=', '');
            } elseif ($request->alwaseet_complete === 'incomplete') {
                $query->where(function($q) {
                    $q->whereNull('alwaseet_city_id')
                      ->orWhere('alwaseet_city_id', '=', '')
                      ->orWhereNull('alwaseet_region_id')
                      ->orWhere('alwaseet_region_id', '=', '');
                });
            }
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
                  })
                  ->orWhereHas('items.product', function($productQuery) use ($searchTerm) {
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
                if (!$item->product) continue;

                // فلتر المخزن: عرض فقط منتجات المخزن المحدد
                if ($request->filled('warehouse_id')) {
                    if ($item->product->warehouse_id != $request->warehouse_id) {
                        continue;
                    }
                }

                // فلتر صلاحيات المجهز
                if (Auth::user()->isSupplier()) {
                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                    if (!in_array($item->product->warehouse_id, $accessibleWarehouseIds)) {
                        continue;
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
        ksort($materialsGrouped);

        foreach ($materialsGrouped as $productCode => $group) {
            ksort($group['sizes']);

            foreach ($group['sizes'] as $sizeKey => $sizeData) {
                $materials[] = [
                    'product' => $group['product'],
                    'product_code' => $productCode,
                    'size_name' => $sizeData['size_name'],
                    'total_quantity' => $sizeData['total_quantity'],
                    'orders' => $sizeData['orders']
                ];
            }
        }

        return view('admin.alwaseet.materials-list-grouped', compact('materials'));
    }

    /**
     * تقييد الطلب مباشرة (بدون form)
     */
    public function confirmOrder(Request $request, Order $order)
    {
        $this->authorize('update', $order);

        if ($order->status !== 'pending') {
            return back()->withErrors(['error' => 'لا يمكن تقييد الطلبات المقيدة']);
        }

        DB::transaction(function() use ($order) {
            // حفظ القيم الحالية من الإعدادات وقت التقييد
            $deliveryFee = Setting::getDeliveryFee();
            $profitMargin = Setting::getProfitMargin();

            // تحديث حالة الطلب
            $order->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
                'delivery_fee_at_confirmation' => $deliveryFee,
                'profit_margin_at_confirmation' => $profitMargin,
            ]);

            // تسجيل حركة التقييد لكل منتج في الطلب
            $order->load('items.product', 'items.size');
            foreach ($order->items as $item) {
                $balanceAfter = 0;
                if ($item->size_id && $item->size) {
                    $balanceAfter = $item->size->quantity;
                }

                ProductMovement::record([
                    'product_id' => $item->product_id,
                    'size_id' => $item->size_id,
                    'warehouse_id' => $item->product->warehouse_id,
                    'order_id' => $order->id,
                    'movement_type' => 'confirm',
                    'quantity' => 0,
                    'balance_after' => $balanceAfter,
                    'order_status' => 'confirmed',
                    'notes' => "تقييد طلب #{$order->order_number}"
                ]);
            }

            // تسجيل الربح عند التقييد
            $profitCalculator = new ProfitCalculator();
            $profitCalculator->recordOrderProfit($order);
        });

        // التحقق من back_route
        $backRoute = $request->input('back_route');
        $backParams = $request->input('back_params');

        if ($backRoute && \Illuminate\Support\Facades\Route::has($backRoute)) {
            $params = $backParams ? json_decode(urldecode($backParams), true) : [];
            if (!is_array($params)) {
                $params = [];
            }
            return redirect()->route($backRoute, $params)
                        ->with('success', 'تم تقييد الطلب بنجاح');
        }

        return redirect()->route('admin.alwaseet.print-and-upload-orders')
                    ->with('success', 'تم تقييد الطلب بنجاح');
    }
}
