<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">رفع وطباع طلبات الوسيط</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    // حساب عدد الطلبات المرسلة (التي لديها qr_link)
                    $sentOrdersCount = 0;
                    foreach ($orders as $order) {
                        $shipment = $order->alwaseetShipment;
                        if ($shipment && !empty($shipment->qr_link)) {
                            $sentOrdersCount++;
                        }
                    }
                @endphp
                @if($sentOrdersCount > 0)
                    <button
                        type="button"
                        onclick="printAllOrders()"
                        class="btn btn-warning"
                        id="print-all-btn"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        طباعة الكل ({{ $sentOrdersCount }})
                    </button>
                @endif
                <a href="{{ route('admin.alwaseet.materials-list', array_filter([
                    'warehouse_id' => request('warehouse_id'),
                    'search' => request('search'),
                    'confirmed_by' => request('confirmed_by'),
                    'delegate_id' => request('delegate_id'),
                    'size_reviewed' => request('size_reviewed'),
                    'message_confirmed' => request('message_confirmed'),
                    'date_from' => request('date_from'),
                    'date_to' => request('date_to'),
                    'time_from' => request('time_from'),
                    'time_to' => request('time_to'),
                    'alwaseet_sent' => request('alwaseet_sent'),
                    'alwaseet_complete' => request('alwaseet_complete'),
                ])) }}" class="btn btn-success">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    تجهيز طلبات
                </a>
                <a href="{{ route('admin.alwaseet.materials-list-grouped', array_filter([
                    'warehouse_id' => request('warehouse_id'),
                    'search' => request('search'),
                    'confirmed_by' => request('confirmed_by'),
                    'delegate_id' => request('delegate_id'),
                    'size_reviewed' => request('size_reviewed'),
                    'message_confirmed' => request('message_confirmed'),
                    'date_from' => request('date_from'),
                    'date_to' => request('date_to'),
                    'time_from' => request('time_from'),
                    'time_to' => request('time_to'),
                    'alwaseet_sent' => request('alwaseet_sent'),
                    'alwaseet_complete' => request('alwaseet_complete'),
                ])) }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    عرض المواد مرتبة حسب الكود
                </a>
            </div>
        </div>

            <!-- إحصائيات سريعة - محسنة للجوال -->
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات غير المقيدة</h6>
                            @php
                                $pendingQuery = App\Models\Order::where('status', 'pending');
                                if (Auth::user()->isSupplier()) {
                                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                                    $pendingQuery->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                                    });
                                }
                                // فلتر المخزن
                                if (request()->filled('warehouse_id')) {
                                    $pendingQuery->whereHas('items.product', function($q) {
                                        $q->where('warehouse_id', request('warehouse_id'));
                                    });
                                }
                                $pendingCount = $pendingQuery->count();
                            @endphp
                            <p class="text-xl font-bold text-warning">{{ $pendingCount }}</p>
                        </div>
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- كاردات المبلغ الإجمالي للمدير فقط -->
            @if(auth()->user()->isAdmin())
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="panel p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الإجمالي للطلبات غير المقيدة</h6>
                                <p class="text-xl font-bold text-warning">{{ number_format($pendingTotalAmount, 0, '.', ',') }} دينار</p>
                            </div>
                            <div class="p-2 bg-warning/10 rounded-lg">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- كاردات الأرباح المتوقعة للمدير فقط -->
            @if(auth()->user()->isAdmin())
                @php
                    $pendingProfitAmount = $pendingProfitAmount ?? 0;
                @endphp
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="panel p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الأرباح المتوقعة - غير مقيد</h6>
                                <p class="text-xl font-bold text-primary">{{ number_format($pendingProfitAmount, 0, '.', ',') }} دينار</p>
                            </div>
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- فلتر وبحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('admin.alwaseet.print-and-upload-orders') }}" class="space-y-4">
                    <!-- الصف الأول: البحث -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                id="searchFilterPending"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، العنوان، رابط السوشل ميديا، الملاحظات، أو اسم/كود المنتج..."
                                value="{{ request('search') }}"
                            >
                        </div>
                    </div>

                    <!-- الصف الثاني: المخزن والمجهز والمندوب -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <select name="warehouse_id" class="form-select" id="warehouseFilterPending">
                                <option value="">كل المخازن</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="confirmed_by" id="confirmedByFilterPending" class="form-select">
                                <option value="">كل المجهزين والمديرين</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ request('confirmed_by') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} ({{ $supplier->code }}) - {{ $supplier->role === 'admin' ? 'مدير' : 'مجهز' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="delegate_id" id="delegateIdFilterPending" class="form-select">
                                <option value="">كل المندوبين</option>
                                @foreach($delegates as $delegate)
                                    <option value="{{ $delegate->id }}" {{ request('delegate_id') == $delegate->id ? 'selected' : '' }}>
                                        {{ $delegate->name }} ({{ $delegate->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="size_reviewed" id="sizeReviewedFilterPending" class="form-select">
                                <option value="">كل حالات التدقيق</option>
                                <option value="not_reviewed" {{ request('size_reviewed') === 'not_reviewed' ? 'selected' : '' }}>لم يتم التدقيق</option>
                                <option value="reviewed" {{ request('size_reviewed') === 'reviewed' ? 'selected' : '' }}>تم تدقيق القياس</option>
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="message_confirmed" id="messageConfirmedFilterPending" class="form-select">
                                <option value="">كل حالات الرسالة</option>
                                <option value="not_sent" {{ request('message_confirmed') === 'not_sent' ? 'selected' : '' }}>لم يرسل الرسالة</option>
                                <option value="waiting_response" {{ request('message_confirmed') === 'waiting_response' ? 'selected' : '' }}>تم الارسال رسالة وبالانتضار الرد</option>
                                <option value="not_confirmed" {{ request('message_confirmed') === 'not_confirmed' ? 'selected' : '' }}>لم يتم التاكيد الرسالة</option>
                                <option value="confirmed" {{ request('message_confirmed') === 'confirmed' ? 'selected' : '' }}>تم تاكيد الرسالة</option>
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="alwaseet_sent" id="alwaseetSentFilter" class="form-select">
                                <option value="">كل حالات الإرسال</option>
                                <option value="sent" {{ request('alwaseet_sent') === 'sent' ? 'selected' : '' }}>مرسل</option>
                                <option value="not_sent" {{ request('alwaseet_sent') === 'not_sent' ? 'selected' : '' }}>غير مرسل</option>
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="alwaseet_complete" id="alwaseetCompleteFilter" class="form-select">
                                <option value="">كل الطلبات</option>
                                <option value="complete" {{ request('alwaseet_complete') === 'complete' ? 'selected' : '' }}>مكتمل البيانات</option>
                                <option value="incomplete" {{ request('alwaseet_complete') === 'incomplete' ? 'selected' : '' }}>غير مكتمل البيانات</option>
                            </select>
                        </div>
                    </div>

                    <!-- الصف الثالث: التاريخ -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_from"
                                id="dateFromFilterPending"
                                class="form-input"
                                placeholder="من تاريخ"
                                value="{{ request('date_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_to"
                                id="dateToFilterPending"
                                class="form-input"
                                placeholder="إلى تاريخ"
                                value="{{ request('date_to') }}"
                            >
                        </div>
                        <div class="sm:w-32">
                            <input
                                type="time"
                                name="time_from"
                                id="timeFromFilterPending"
                                class="form-input"
                                placeholder="من الساعة"
                                value="{{ request('time_from') }}"
                            >
                        </div>
                        <div class="sm:w-32">
                            <input
                                type="time"
                                name="time_to"
                                id="timeToFilterPending"
                                class="form-input"
                                placeholder="إلى الساعة"
                                value="{{ request('time_to') }}"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            <a href="{{ route('admin.alwaseet.print-and-upload-orders') }}" class="btn btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                مسح الفلتر
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- نتائج البحث -->
            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            عرض {{ $orders->total() }} طلب غير مقيد
                            @if(request('search'))
                                للبحث: "{{ request('search') }}"
                            @endif
                            @if(request('date_from') || request('date_to'))
                                -
                                @if(request('date_from') && request('date_to'))
                                    من {{ request('date_from') }} إلى {{ request('date_to') }}
                                @elseif(request('date_from'))
                                    من {{ request('date_from') }}
                                @elseif(request('date_to'))
                                    حتى {{ request('date_to') }}
                                @endif
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            <!-- كروت الطلبات -->
            @if($orders->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($orders as $index => $order)
                        @php
                            // بيانات الطلب للواتساب
                            $orderWhatsAppData = [
                                'phone' => $order->customer_phone,
                                'orderNumber' => $order->order_number,
                                'customerPhone' => $order->customer_phone,
                                'pageName' => optional($order->delegate)->page_name ?? '',
                                'deliveryFee' => \App\Models\Setting::getDeliveryFee(),
                                'items' => $order->items->map(function($item) {
                                    return [
                                        'product_name' => $item->product_name ?? optional($item->product)->name ?? $item->product_code,
                                        'product_code' => $item->product_code,
                                        'unit_price' => $item->unit_price
                                    ];
                                }),
                                'totalAmount' => $order->total_amount
                            ];
                        @endphp
                        <div id="order-{{ $order->id }}" class="panel border-2 border-yellow-500 dark:border-yellow-600 relative">
                            <!-- رقم تسلسلي دائري -->
                            <div class="absolute top-2 right-2 bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold shadow-lg z-10">
                                {{ $orders->firstItem() + $index }}
                            </div>

                            <!-- هيدر الكارت -->
                            <div class="flex items-center justify-between mb-4 pl-12">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="text-lg font-bold text-primary dark:text-primary-light relative inline-block">
                                            رقم الطلب: {{ $order->order_number }}
                                            <!-- Badge للإشعارات غير المقروءة -->
                                            <span id="order-badge-{{ $order->id }}" class="hidden absolute -top-2 -right-2 w-4 h-4 bg-danger rounded-full border-2 border-white dark:border-gray-800 shadow-lg z-10"></span>
                                        </div>
                                        <button
                                            type="button"
                                            onclick="copyDeliveryCode('{{ $order->order_number }}', 'order')"
                                            class="btn btn-xs btn-outline-primary flex items-center gap-1"
                                            title="نسخ رقم الطلب"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            نسخ
                                        </button>
                                    </div>
                                </div>
                                <div class="text-right flex flex-col gap-1 items-end">
                                    @php
                                        // محاولة جلب shipment من العلاقة أولاً، ثم من query مباشر كـ fallback
                                        $shipment = $order->alwaseetShipment;
                                        if (!$shipment) {
                                            $shipment = \App\Models\AlWaseetShipment::where('order_id', $order->id)->first();
                                        }

                                        // محاولة الحصول على بيانات من API أولاً
                                        $apiOrderData = null;
                                        if ($shipment && isset($shipment->alwaseet_order_id) && isset($alwaseetOrdersData[$shipment->alwaseet_order_id])) {
                                            $apiOrderData = $alwaseetOrdersData[$shipment->alwaseet_order_id];
                                        }

                                        // تحديد حالة "مرسل" - من API إذا كان متوفر، وإلا من قاعدة البيانات المحلية
                                        $isSent = false;
                                        if ($apiOrderData) {
                                            // إذا كان موجود في API، فهو مرسل
                                            $isSent = true;
                                        } elseif ($shipment) {
                                            // Fallback إلى قاعدة البيانات المحلية
                                            $isSent = true;
                                        }

                                        // تحديد حالة "مطبوع" - من API إذا كان متوفر، وإلا من قاعدة البيانات المحلية
                                        $isPrinted = false;
                                        if ($apiOrderData) {
                                            // من API: التحقق من qr_link أو qr_id بشكل دقيق
                                            $hasQrLink = isset($apiOrderData['qr_link']) &&
                                                        $apiOrderData['qr_link'] !== null &&
                                                        $apiOrderData['qr_link'] !== '' &&
                                                        trim($apiOrderData['qr_link']) !== '';

                                            $hasQrId = isset($apiOrderData['qr_id']) &&
                                                      $apiOrderData['qr_id'] !== null &&
                                                      $apiOrderData['qr_id'] !== '' &&
                                                      trim($apiOrderData['qr_id']) !== '';

                                            $isPrinted = $hasQrLink || $hasQrId;
                                        } elseif ($shipment) {
                                            // Fallback إلى قاعدة البيانات المحلية
                                            // التحقق من qr_link أو qr_id أو printed_at
                                            $hasQrLink = isset($shipment->qr_link) &&
                                                       $shipment->qr_link !== null &&
                                                       $shipment->qr_link !== '' &&
                                                       trim($shipment->qr_link) !== '';

                                            $hasQrId = isset($shipment->qr_id) &&
                                                      $shipment->qr_id !== null &&
                                                      $shipment->qr_id !== '' &&
                                                      trim((string)$shipment->qr_id) !== '';

                                            $hasPrintedAt = isset($shipment->printed_at) &&
                                                           $shipment->printed_at !== null;

                                            $isPrinted = $hasQrLink || $hasQrId || $hasPrintedAt;
                                        }
                                    @endphp
                                    {{-- علامة مرسل/غير مرسل --}}
                                    <div class="flex flex-col gap-1 items-end">
                                        @if($isSent)
                                            <span class="badge bg-success text-white text-sm font-bold px-3 py-1.5">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                مرسل
                                            </span>
                                        @else
                                            <span class="badge badge-outline-danger text-sm font-bold px-3 py-1.5">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                غير مرسل
                                            </span>
                                        @endif


                                    </div>
                                </div>
                            </div>

                            <!-- معلومات الزبون -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الزبون</span>
                                    <p class="font-medium">{{ $order->customer_name }}</p>
                                </div>
                            </div>


                            <!-- عنوان الزبون -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">عنوان الزبون</span>
                                    <div class="text-2xl font-bold !text-primary dark:!text-primary-light">{{ $order->customer_address ?? 'لا يوجد عنوان' }}</div>
                                </div>
                            </div>

                            <!-- بيانات الواسط -->
                            @php
                                $cityName = '';
                                $regionName = '';
                                if ($order->alwaseet_city_id) {
                                    $selectedCity = collect($cities)->firstWhere('id', $order->alwaseet_city_id);
                                    $cityName = $selectedCity['city_name'] ?? '';
                                }
                                if ($order->alwaseet_region_id && isset($ordersWithRegions[$order->id])) {
                                    $selectedRegion = collect($ordersWithRegions[$order->id])->firstWhere('id', $order->alwaseet_region_id);
                                    $regionName = $selectedRegion['region_name'] ?? '';
                                }

                                // جلب بيانات API للحالة
                                $shipment = $order->alwaseetShipment;
                                if (!$shipment) {
                                    $shipment = \App\Models\AlWaseetShipment::where('order_id', $order->id)->first();
                                }
                                $apiOrderData = null;
                                if ($shipment && isset($shipment->alwaseet_order_id) && isset($alwaseetOrdersData[$shipment->alwaseet_order_id])) {
                                    $apiOrderData = $alwaseetOrdersData[$shipment->alwaseet_order_id];
                                }
                                $orderStatus = null;
                                if ($apiOrderData && isset($apiOrderData['status'])) {
                                    $orderStatus = $apiOrderData['status'];
                                } elseif ($shipment && $shipment->status) {
                                    $orderStatus = $shipment->status;
                                }

                                // جلب كود الوسيط (pickup_id أو qr_id فقط)
                                $alwaseetOrderId = null;
                                // الأولوية الأولى: pickup_id من API (الكود الصحيح من الواسط)
                                if ($apiOrderData && isset($apiOrderData['pickup_id']) && !empty($apiOrderData['pickup_id'])) {
                                    $alwaseetOrderId = (string)$apiOrderData['pickup_id'];
                                }
                                // الأولوية الثانية: qr_id من API (كود QR)
                                elseif ($apiOrderData && isset($apiOrderData['qr_id']) && !empty($apiOrderData['qr_id'])) {
                                    $alwaseetOrderId = (string)$apiOrderData['qr_id'];
                                }
                                // الأولوية الثالثة: qr_id من shipment المحلي
                                elseif ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id)) {
                                    $alwaseetOrderId = (string)$shipment->qr_id;
                                }
                                // الأولوية الرابعة: delivery_code من Order (فقط إذا كان متطابقاً مع pickup_id أو qr_id من API)
                                elseif ($order->delivery_code) {
                                    $expectedCode = null;
                                    if ($apiOrderData && isset($apiOrderData['pickup_id']) && !empty($apiOrderData['pickup_id'])) {
                                        $expectedCode = (string)$apiOrderData['pickup_id'];
                                    } elseif ($apiOrderData && isset($apiOrderData['qr_id']) && !empty($apiOrderData['qr_id'])) {
                                        $expectedCode = (string)$apiOrderData['qr_id'];
                                    } elseif ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id)) {
                                        $expectedCode = (string)$shipment->qr_id;
                                    }

                                    // استخدام delivery_code فقط إذا كان متطابقاً مع الكود المتوقع
                                    if ($expectedCode && $order->delivery_code == $expectedCode) {
                                        $alwaseetOrderId = $order->delivery_code;
                                    }
                                }
                            @endphp
                            <!-- بيانات الواسط -->
                            <div class="mb-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-3 rounded-lg">
                                    <span class="text-xs font-semibold text-blue-700 dark:text-blue-400 block mb-3">
                                        <svg class="w-4 h-4 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/>
                                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/>
                                        </svg>
                                        بيانات الواسط
                                    </span>

                                    <form id="alwaseet-form-{{ $order->id }}"
                                          class="space-y-3"
                                          onsubmit="updateAlwaseetFields(event, {{ $order->id }})"
                                          x-data="alwaseetSearch{{ $order->id }}({{ $order->id }}, @js($cities), @js($order->alwaseet_city_id ?? ''), @js($order->alwaseet_region_id ?? ''), @js($ordersWithRegions[$order->id] ?? []))"
                                          @click.outside="citySearchQuery = ''; regionSearchQuery = ''">
                                        @csrf

                                        <!-- محافظة -->
                                        <div>
                                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">محافظة <span class="text-danger">*</span></label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    :value="citySearchQuery || selectedCityName"
                                                    @input="citySearchQuery = $event.target.value; filterCities()"
                                                    @keydown.arrow-down.prevent="highlightNextCity()"
                                                    @keydown.arrow-up.prevent="highlightPrevCity()"
                                                    @keydown.enter.prevent="selectHighlightedCity()"
                                                    @focus="showCityDropdown = true; if (!citySearchQuery) citySearchQuery = ''"
                                                    @blur="setTimeout(() => { showCityDropdown = false; }, 200)"
                                                    placeholder="ابحث عن محافظة..."
                                                    class="form-input form-input-sm"
                                                >
                                                <input type="hidden" name="alwaseet_city_id" x-model="selectedCityId">
                                                <div x-show="showCityDropdown && filteredCities.length > 0"
                                                     x-cloak
                                                     class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                                                    <template x-for="(city, index) in filteredCities" :key="city.id">
                                                        <div
                                                            @click="selectCity(city)"
                                                            @mouseenter="highlightedCityIndex = index"
                                                            class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                                            :class="{ 'bg-gray-100 dark:bg-gray-700': highlightedCityIndex === index }"
                                                        >
                                                            <div class="font-medium" x-text="city.city_name"></div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- المنطقة -->
                                        <div>
                                            <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المنطقة <span class="text-danger">*</span></label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    :value="regionSearchQuery || selectedRegionName"
                                                    @input="regionSearchQuery = $event.target.value; filterRegions()"
                                                    @keydown.arrow-down.prevent="highlightNextRegion()"
                                                    @keydown.arrow-up.prevent="highlightPrevRegion()"
                                                    @keydown.enter.prevent="selectHighlightedRegion()"
                                                    @focus="showRegionDropdown = true; if (!regionSearchQuery) regionSearchQuery = ''"
                                                    @blur="setTimeout(() => { showRegionDropdown = false; }, 200)"
                                                    placeholder="ابحث عن المنطقة..."
                                                    class="form-input form-input-sm"
                                                    :disabled="!selectedCityId"
                                                >
                                                <input type="hidden" name="alwaseet_region_id" x-model="selectedRegionId">
                                                <div x-show="showRegionDropdown && filteredRegions.length > 0 && !isLoadingRegions"
                                                     x-cloak
                                                     class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                                                    <template x-for="(region, index) in filteredRegions" :key="region.id">
                                                        <div
                                                            @click="selectRegion(region)"
                                                            @mouseenter="highlightedRegionIndex = index"
                                                            class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                                            :class="{ 'bg-gray-100 dark:bg-gray-700': highlightedRegionIndex === index }"
                                                        >
                                                            <div class="font-medium" x-text="region.region_name"></div>
                                                        </div>
                                                    </template>
                                                </div>
                                                <div x-show="isLoadingRegions" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg p-4 text-center">
                                                    <div class="text-sm text-gray-500">جاري التحميل...</div>
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-sm w-full" style="background-color: #dc2626 !important; color: white !important; border-color: #dc2626 !important;">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            حفظ البيانات
                                        </button>
                                    </form>

                                    <div class="space-y-2 mt-3">
                                        @if($orderStatus)
                                            <div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">الحالة:</span>
                                                <p class="font-medium text-sm">{{ $orderStatus }}</p>
                                            </div>
                                        @endif
                                        @if($alwaseetOrderId)
                                            <div>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">كود الوسيط:</span>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="font-medium font-mono text-success text-sm" id="alwaseet-code-{{ $order->id }}">{{ $alwaseetOrderId }}</span>
                                                    <button onclick="copyToClipboard('{{ $alwaseetOrderId }}', 'alwaseet-code-{{ $order->id }}')" class="btn btn-xs btn-outline-secondary">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                        </svg>
                                                        نسخ
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                </div>
                            </div>

                            <!-- رابط السوشل ميديا -->
                            @if($order->customer_social_link)
                                <div class="mb-4">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-2">رابط السوشل ميديا</span>
                                        <a href="{{ $order->customer_social_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary w-full flex items-center justify-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            فتح الرابط
                                        </a>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate">{{ Str::limit($order->customer_social_link, 30) }}</p>

                                        @if(auth()->user()->isAdminOrSupplier())
                                            <!-- تغيير حالة تدقيق القياس -->
                                            <div class="mt-3">
                                                <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">تدقيق القياس</label>
                                                <select class="form-select form-select-sm" onchange="updateReviewStatus({{ $order->id }}, 'size_reviewed', this.value)">
                                                    <option value="not_reviewed" {{ $order->size_reviewed === 'not_reviewed' ? 'selected' : '' }}>لم يتم التدقيق</option>
                                                    <option value="reviewed" {{ $order->size_reviewed === 'reviewed' ? 'selected' : '' }}>تم تدقيق القياس</option>
                                                </select>
                                            </div>
                                        @endif

                                        <!-- زر الواتساب -->
                                        @if($order->customer_phone)
                                            <button onclick="openWhatsAppForOrder({{ $order->id }})" class="btn btn-sm btn-success w-full flex items-center justify-center gap-2 mt-2">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                                واتساب
                                            </button>
                                        @endif

                                        @if(auth()->user()->isAdminOrSupplier())
                                            <!-- تغيير حالة الرسالة -->
                                            <div class="mt-3">
                                                <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">حالة الرسالة</label>
                                                <select class="form-select form-select-sm" onchange="updateReviewStatus({{ $order->id }}, 'message_confirmed', this.value)">
                                                    <option value="not_sent" {{ $order->message_confirmed === 'not_sent' ? 'selected' : '' }}>لم يرسل الرسالة</option>
                                                    <option value="waiting_response" {{ $order->message_confirmed === 'waiting_response' ? 'selected' : '' }}>تم الارسال رسالة وبالانتضار الرد</option>
                                                    <option value="not_confirmed" {{ $order->message_confirmed === 'not_confirmed' ? 'selected' : '' }}>لم يتم التاكيد الرسالة</option>
                                                    <option value="confirmed" {{ $order->message_confirmed === 'confirmed' ? 'selected' : '' }}>تم تاكيد الرسالة</option>
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- معلومات المندوب -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المندوب</span>
                                    <p class="font-medium">{{ $order->delegate->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $order->delegate->code }}</p>
                                </div>
                            </div>

                            <!-- التاريخ -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">التاريخ</span>
                                    <p class="font-medium">{{ $order->created_at->format('Y-m-d') }}</p>
                                    <p class="text-sm text-gray-500">{{ $order->created_at->format('g:i') }} {{ $order->created_at->format('H') >= 12 ? 'مساءً' : 'نهاراً' }}</p>
                                </div>
                            </div>

                            <!-- الملاحظات -->
                            @if($order->notes)
                                <div class="mb-4">
                                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 rounded-lg">
                                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 block mb-1">
                                            <svg class="w-4 h-4 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                            </svg>
                                            ملاحظة
                                        </span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $order->notes }}</p>
                                    </div>
                                </div>
                            @endif

                            <!-- ملاحظة الوقت -->
                            <div class="mb-4" x-data="{ deliveryTimeNote: '{{ $order->alwaseet_delivery_time_note ?? '' }}', saving: false }">
                                <label class="text-xs text-gray-500 dark:text-gray-400 block mb-1">ملاحظة الوقت</label>
                                <select
                                    x-model="deliveryTimeNote"
                                    @change="
                                        saving = true;
                                        fetch('{{ route('admin.alwaseet.orders.update-delivery-time-note', $order->id) }}', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                            },
                                            body: JSON.stringify({ delivery_time_note: deliveryTimeNote || null })
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            saving = false;
                                            if (data.success) {
                                                showCopyNotification(data.message || 'تم حفظ ملاحظة الوقت بنجاح', 'success');
                                            } else {
                                                showCopyNotification(data.message || 'فشل حفظ ملاحظة الوقت', 'error');
                                            }
                                        })
                                        .catch(error => {
                                            saving = false;
                                            showCopyNotification('حدث خطأ أثناء حفظ ملاحظة الوقت', 'error');
                                        });
                                    "
                                    class="form-select form-select-sm"
                                    :disabled="saving"
                                >
                                    <option value="">-- اختر ملاحظة الوقت --</option>
                                    <option value="morning" :selected="deliveryTimeNote === 'morning'">توصيل صباحا</option>
                                    <option value="noon" :selected="deliveryTimeNote === 'noon'">توصيل ضهرا</option>
                                    <option value="evening" :selected="deliveryTimeNote === 'evening'">توصيل مسائا</option>
                                    <option value="urgent" :selected="deliveryTimeNote === 'urgent'">توصيل مستعجل</option>
                                </select>
                                <div x-show="saving" class="text-xs text-gray-500 mt-1">جاري الحفظ...</div>
                            </div>

                            <!-- أزرار الإجراءات -->
                            <div class="flex gap-2 flex-wrap">
                                @php
                                    $backRoute = 'admin.alwaseet.print-and-upload-orders';
                                    $backParams = urlencode(json_encode(request()->query()));
                                @endphp
                                <a href="{{ route('admin.orders.show', $order) }}?back_route={{ $backRoute }}&back_params={{ $backParams }}" class="btn btn-sm btn-primary flex-1" title="عرض">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    عرض
                                </a>

                                @can('update', $order)
                                    <a href="{{ route('admin.orders.edit', $order) }}?back_route={{ $backRoute }}&back_params={{ $backParams }}" class="btn btn-sm btn-warning flex-1" title="تعديل">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        تعديل
                                    </a>
                                @endcan
                                @can('process', $order)
                                    <a href="{{ route('admin.orders.process', $order) }}?back_route={{ $backRoute }}&back_params={{ $backParams }}" class="btn btn-sm btn-success flex-1" title="تجهيز">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        تجهيز
                                    </a>
                                @endcan
                            </div>

                            <!-- زر إرسال للواسط - يظهر فقط للطلبات غير المرسلة -->
                            @if(!$isSent)
                                @php
                                    $alwaseetCode = null;
                                    if (isset($apiOrderData[$order->id])) {
                                        $apiOrderDataItem = $apiOrderData[$order->id];
                                        $alwaseetCode = $apiOrderDataItem['pickup_id'] ?? $apiOrderDataItem['qr_id'] ?? null;
                                    }
                                    if (!$alwaseetCode && $order->alwaseetShipment) {
                                        $alwaseetCode = $order->alwaseetShipment->qr_id ?? $order->alwaseetShipment->alwaseet_order_id ?? null;
                                    }
                                    if (!$alwaseetCode && $order->delivery_code) {
                                        // التحقق من أن delivery_code يبدو ككود وسيط (رقم طويل)
                                        if (preg_match('/^\d{6,}$/', $order->delivery_code)) {
                                            $alwaseetCode = $order->delivery_code;
                                        }
                                    }
                                @endphp
                                <div class="mt-8" x-data="{ openSendModal: false, orderId: {{ $order->id }}, alwaseetCode: '{{ $alwaseetCode ?? '' }}' }">
                                    <button
                                        type="button"
                                        @click="openSendModal = true"
                                        class="btn btn-lg w-full"
                                        style="background-color: #9333ea !important; color: white !important; border-color: #9333ea !important; padding: 0.75rem 1.5rem !important; font-size: 1.1rem !important;"
                                        id="send-btn-{{ $order->id }}"
                                    >
                                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                        إرسال للواسط
                                    </button>

                                    <!-- modal تأكيد الإرسال -->
                                    <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto" :class="openSendModal && '!block'">
                                        <div class="flex items-start justify-center min-h-screen px-4" @click.self="openSendModal = false">
                                            <div x-show="openSendModal" x-transition x-transition.duration.300 class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-lg my-8">
                                                <div class="flex py-2 bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-center">
                                                    <span class="flex items-center justify-center w-16 h-16 rounded-full bg-purple/10">
                                                        <svg class="w-8 h-8 text-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="p-5">
                                                    <h3 class="text-lg font-semibold text-center dark:text-white-light mb-2">تأكيد الإرسال للواسط</h3>
                                                    <div class="py-5 text-white-dark text-center">
                                                        <p class="mb-3">هل أنت متأكد من إرسال الطلب رقم <span class="font-bold" style="color: #9333ea !important;">{{ $order->order_number }}</span> للواسط؟</p>
                                                    </div>
                                                    <div class="flex justify-end items-center mt-8">
                                                        <button type="button" class="btn btn-outline-secondary" @click="openSendModal = false">إلغاء</button>
                                                        <button type="button" class="btn ltr:ml-4 rtl:mr-4" style="background-color: #9333ea !important; color: white !important; border-color: #9333ea !important;" @click="openSendModal = false; sendOrderToAlWaseet(orderId);">
                                                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                                            </svg>
                                                            تأكيد الإرسال
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="panel">
                    <div class="flex flex-col items-center justify-center py-10">
                        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">لا توجد طلبات غير مقيدة</h3>
                        <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على أي طلبات غير مقيدة تطابق معايير البحث</p>
                    </div>
                </div>
            @endif

            <!-- Pagination -->
            <x-pagination :items="$orders" />
    </div>

    <script>
        // Local Storage لجميع الفلاتر
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasUrlParams = urlParams.has('warehouse_id') || urlParams.has('search') || urlParams.has('confirmed_by') ||
                                urlParams.has('delegate_id') || urlParams.has('size_reviewed') || urlParams.has('message_confirmed') ||
                                urlParams.has('date_from') || urlParams.has('date_to') || urlParams.has('time_from') || urlParams.has('time_to');

            // قائمة الفلاتر مع مفاتيح localStorage
            const filters = [
                { id: 'warehouseFilterPending', key: 'selectedWarehouse_alwaseet_print_upload', param: 'warehouse_id' },
                { id: 'searchFilterPending', key: 'selectedSearch_alwaseet_print_upload', param: 'search' },
                { id: 'confirmedByFilterPending', key: 'selectedConfirmedBy_alwaseet_print_upload', param: 'confirmed_by' },
                { id: 'delegateIdFilterPending', key: 'selectedDelegateId_alwaseet_print_upload', param: 'delegate_id' },
                { id: 'sizeReviewedFilterPending', key: 'selectedSizeReviewed_alwaseet_print_upload', param: 'size_reviewed' },
                { id: 'messageConfirmedFilterPending', key: 'selectedMessageConfirmed_alwaseet_print_upload', param: 'message_confirmed' },
                { id: 'dateFromFilterPending', key: 'selectedDateFrom_alwaseet_print_upload', param: 'date_from' },
                { id: 'dateToFilterPending', key: 'selectedDateTo_alwaseet_print_upload', param: 'date_to' },
                { id: 'timeFromFilterPending', key: 'selectedTimeFrom_alwaseet_print_upload', param: 'time_from' },
                { id: 'timeToFilterPending', key: 'selectedTimeTo_alwaseet_print_upload', param: 'time_to' }
            ];

            let hasSavedFilters = false;
            const savedParams = new URLSearchParams();

            filters.forEach(filter => {
                const element = document.getElementById(filter.id);
                if (element) {
                    // استرجاع الفلتر من Local Storage عند التحميل فقط إذا لم تكن هناك معاملات في URL
                    if (!hasUrlParams) {
                        const savedValue = localStorage.getItem(filter.key);
                        if (savedValue) {
                            element.value = savedValue;
                            savedParams.append(filter.param, savedValue);
                            hasSavedFilters = true;
                        }
                    }

                    // حفظ الفلتر في Local Storage عند التغيير
                    const eventType = element.tagName === 'SELECT' ? 'change' : 'input';
                    element.addEventListener(eventType, function() {
                        if (this.value) {
                            localStorage.setItem(filter.key, this.value);
                        } else {
                            localStorage.removeItem(filter.key);
                        }
                    });
                }
            });

            // تطبيق الفلاتر المحفوظة تلقائياً إذا كانت موجودة ولم تكن هناك معاملات في URL
            if (!hasUrlParams && hasSavedFilters && savedParams.toString()) {
                const form = document.querySelector('form[action*="print-and-upload-orders"]');
                if (form) {
                    // إضافة الفلاتر المحفوظة إلى النموذج
                    savedParams.forEach((value, key) => {
                        const existingInput = form.querySelector(`[name="${key}"]`);
                        if (existingInput) {
                            existingInput.value = value;
                        }
                    });
                    // إرسال النموذج تلقائياً
                    form.submit();
                }
            }

            // معالجة زر مسح الفلتر - حذف جميع الفلاتر من localStorage
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function(e) {
                    // حذف جميع الفلاتر من localStorage
                    filters.forEach(filter => {
                        localStorage.removeItem(filter.key);
                    });
                    // السماح بالانتقال الطبيعي للرابط
                });
            }
        });
    </script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // إذا كان هناك anchor في الرابط (#order-123)
        if (window.location.hash) {
            const target = document.querySelector(window.location.hash);
            if (target) {
                setTimeout(() => {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        }
        // وإلا إذا كان هناك موضع محفوظ
        else if (sessionStorage.getItem('ordersPendingScroll')) {
            const scrollPos = sessionStorage.getItem('ordersPendingScroll');
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem('ordersPendingScroll');
        }

        // حفظ موضع التمرير قبل الانتقال لصفحة أخرى
        const orderLinks = document.querySelectorAll('a[href*="/orders/"]');
        orderLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.setItem('ordersPendingScroll', window.scrollY);
            });
        });
    });
    </script>

    <script>
        // دالة نسخ النص إلى الحافظة (رقم الطلب أو كود الوسيط)
        function copyDeliveryCode(text, type = '') {
            // تحديد نوع الرسالة
            let successMessage = 'تم النسخ بنجاح!';
            let errorMessage = 'فشل في النسخ';

            if (type === 'order') {
                successMessage = 'تم نسخ رقم الطلب بنجاح!';
                errorMessage = 'فشل في نسخ رقم الطلب';
            } else if (type === 'delivery') {
                successMessage = 'تم نسخ كود الوسيط بنجاح!';
                errorMessage = 'فشل في نسخ كود الوسيط';
            }

            // إنشاء عنصر مؤقت
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            // تحديد ونسخ النص
            textarea.select();
            textarea.setSelectionRange(0, 99999); // للهواتف المحمولة

            try {
                document.execCommand('copy');
                showCopyNotification(successMessage);
            } catch (err) {
                // استخدام Clipboard API إذا كان متاحاً
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyNotification(successMessage);
                    }).catch(function() {
                        showCopyNotification(errorMessage, 'error');
                    });
                } else {
                    showCopyNotification(errorMessage, 'error');
                }
            }

            // إزالة العنصر المؤقت
            document.body.removeChild(textarea);
        }

        // دالة إظهار إشعار النسخ
        function showCopyNotification(message, type = 'success') {
            // إنشاء عنصر الإشعار
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300`;
            notification.textContent = message;

            // إضافة الإشعار للصفحة
            document.body.appendChild(notification);

            // إزالة الإشعار بعد 3 ثوان
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // التحقق من إشعارات الطلبات وإظهار badges
        async function checkOrderAlerts() {
            const orderCards = document.querySelectorAll('[id^="order-"]');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            for (const card of orderCards) {
                const orderId = card.id.replace('order-', '');
                try {
                    const response = await fetch(`/api/sweet-alerts/check-order/${orderId}`, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                        credentials: 'same-origin',
                    });

                    if (response.ok) {
                        const data = await response.json();
                        const badge = document.getElementById(`order-badge-${orderId}`);
                        if (badge) {
                            if (data.has_unread) {
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error checking order alert:', error);
                }
            }
        }

        // التحقق من الإشعارات عند تحميل الصفحة
        checkOrderAlerts();

        // تحديث الإشعارات كل 10 ثوانٍ
        setInterval(checkOrderAlerts, 10000);
    </script>

    <style>
        @keyframes ping {
            75%, 100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 5px rgba(239, 68, 68, 0.5), 0 0 10px rgba(239, 68, 68, 0.3);
            }
            50% {
                box-shadow: 0 0 10px rgba(239, 68, 68, 0.8), 0 0 20px rgba(239, 68, 68, 0.5);
            }
        }
        [id^="order-badge-"]:not(.hidden) {
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite, glow 2s ease-in-out infinite;
        }
        /* تحسين الموضع للموبايل والديسكتوب */
        @media (max-width: 640px) {
            [id^="order-badge-"] {
                width: 0.875rem !important;
                height: 0.875rem !important;
                top: -0.5rem !important;
                right: -0.5rem !important;
            }
        }
    </style>

    <script>
        // بيانات الطلبات للواتساب
        const ordersWhatsAppData = {};
        @foreach($orders as $order)
            @if($order->customer_phone)
                ordersWhatsAppData[{{ $order->id }}] = {
                    phone: '{{ $order->customer_phone }}',
                    orderNumber: '{{ $order->order_number }}',
                    customerPhone: '{{ $order->customer_phone }}',
                    pageName: '{{ optional($order->delegate)->page_name ?? '' }}',
                    deliveryFee: {{ \App\Models\Setting::getDeliveryFee() }},
                    items: @json($order->items->map(function($item) {
                        return [
                            'product_name' => $item->product_name ?? optional($item->product)->name ?? $item->product_code,
                            'product_code' => $item->product_code,
                            'unit_price' => $item->unit_price
                        ];
                    })),
                    totalAmount: {{ $order->total_amount }}
                };
            @endif
        @endforeach

        // دالة فتح واتساب للطلب
        function openWhatsAppForOrder(orderId) {
            const orderData = ordersWhatsAppData[orderId];
            if (orderData) {
                openWhatsApp(orderData.phone, orderData.items, orderData.totalAmount, orderData.orderNumber, orderData.customerPhone, orderData.pageName, orderData.deliveryFee);
            }
        }

        // دالة بناء رسالة الواتساب
        function generateWhatsAppMessage(orderItems, totalAmount, orderNumber, customerPhone, pageName, deliveryFee) {
            let message = '📦 أهلاً وسهلاً بيكم ❤️\n';
            // استخدام اسم البيج للمندوب أو "برنا كدز" كقيمة افتراضية
            const pageNameText = pageName || 'برنا كدز';
            message += `معكم مجهز ${pageNameText} 👗\n\n`;

            // إضافة رقم الزبون
            if (customerPhone) {
                message += `رقم الهاتف: ${customerPhone}\n\n`;
            }

            // إضافة قائمة المنتجات (باسم المنتج بدلاً من الكود)
            message += 'المنتجات:\n';
            orderItems.forEach(function(item) {
                const price = new Intl.NumberFormat('en-US').format(item.unit_price);
                const productName = item.product_name || item.product_code;
                message += `- ${productName} - ${price} د.ع\n`;
            });

            // حساب المجموع الكلي مع سعر التوصيل
            const totalWithDelivery = totalAmount + deliveryFee;
            const totalFormatted = new Intl.NumberFormat('en-US').format(totalAmount);
            const totalWithDeliveryFormatted = new Intl.NumberFormat('en-US').format(totalWithDelivery);
            message += `\nالمجموع الكلي: ${totalFormatted} د.ع\n`;
            message += `سعر التوصيل: ${new Intl.NumberFormat('en-US').format(deliveryFee)} د.ع\n`;
            message += `المجموع الكلي (مع التوصيل): ${totalWithDeliveryFormatted} د.ع\n\n`;

            // إضافة طلب التأكيد
            message += 'نرجو تأكيد الطلب من خلال الرد على هذه الرسالة بكلمة "تأكيد" حتى نبدأ بتجهيز الطلب وإرساله لكم 💨\n\n';
            message += 'التوصيل خلال 24 ساعه الى 36 ساعه بعد تاكيد الطلب من خلال الوتساب\n\n';
            message += 'في حال عدم الرد خلال فترة قصيرة، سيتم إلغاء الطلب تلقائيًا.\n';
            message += 'نشكر تعاونكم ويانا 🌸';

            return message;
        }

        // دالة فتح واتساب
        function openWhatsApp(phone, orderItems, totalAmount, orderNumber, customerPhone, pageName, deliveryFee) {
            // تنظيف رقم الهاتف (إزالة المسافات والرموز)
            let cleanPhone = phone.replace(/[^\d]/g, '');

            // إضافة كود الدولة 964 للعراق إذا لم يكن موجوداً
            if (!cleanPhone.startsWith('964')) {
                // إذا بدأ الرقم بـ 0، استبدله بـ 964
                if (cleanPhone.startsWith('0')) {
                    cleanPhone = '964' + cleanPhone.substring(1);
                } else if (cleanPhone.length < 12) {
                    cleanPhone = '964' + cleanPhone;
                }
            }

            // بناء الرسالة
            const message = generateWhatsAppMessage(orderItems, totalAmount, orderNumber, customerPhone, pageName, deliveryFee);
            const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;

            // فتح واتساب في نافذة جديدة
            window.open(whatsappUrl, '_blank');
        }

        // دالة تحديث حالة التدقيق
        function updateReviewStatus(orderId, field, value) {
            fetch(`/admin/orders/${orderId}/review-status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ field: field, value: value })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showCopyNotification === 'function') {
                        showCopyNotification(data.message);
                    } else {
                        alert(data.message);
                    }
                    // إعادة تحميل الصفحة لتحديث الحالة
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    if (typeof showCopyNotification === 'function') {
                        showCopyNotification('فشل في تحديث الحالة', 'error');
                    } else {
                        alert('فشل في تحديث الحالة');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showCopyNotification === 'function') {
                    showCopyNotification('حدث خطأ أثناء تحديث الحالة', 'error');
                } else {
                    alert('حدث خطأ أثناء تحديث الحالة');
                }
            });
        }

        // دالة نسخ النص إلى الحافظة (لكود الوسيط)
        function copyToClipboard(text, elementId = '') {
            const successMessage = 'تم نسخ كود الوسيط بنجاح!';
            const errorMessage = 'فشل في نسخ كود الوسيط';

            // إنشاء عنصر مؤقت
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            try {
                textarea.select();
                document.execCommand('copy');
                showCopyNotification(successMessage);
            } catch (err) {
                // استخدام Clipboard API إذا كان متاحاً
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyNotification(successMessage);
                    }).catch(function() {
                        showCopyNotification(errorMessage, 'error');
                    });
                } else {
                    showCopyNotification(errorMessage, 'error');
                }
            }

            // إزالة العنصر المؤقت
            document.body.removeChild(textarea);
        }

        // دالة إرسال الطلب إلى الواسط
        function sendOrderToAlWaseet(orderId) {
            const sendButton = document.getElementById(`send-btn-${orderId}`);
            const originalText = sendButton.innerHTML;

            sendButton.disabled = true;
            sendButton.innerHTML = '<svg class="animate-spin w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> جاري الإرسال...';

            fetch(`{{ route('admin.alwaseet.orders.send', ':id') }}`.replace(':id', orderId), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCopyNotification(data.message, 'success');

                    // عرض كود الوسيط (pickup_id) في الكارد إذا كان موجوداً
                    const pickupId = data.pickup_id || data.delivery_code;
                    if (pickupId) {
                        const codeElement = document.getElementById(`alwaseet-code-${orderId}`);
                        const codeContainer = codeElement ? codeElement.closest('.space-y-2') : null;

                        if (codeElement) {
                            // تحديث الكود الموجود
                            codeElement.textContent = pickupId;
                            // تحديث onclick للزر أيضاً
                            const copyButton = codeElement.nextElementSibling;
                            if (copyButton && copyButton.tagName === 'BUTTON') {
                                copyButton.setAttribute('onclick', `copyToClipboard('${pickupId}', 'alwaseet-code-${orderId}')`);
                            }
                        } else if (codeContainer) {
                            // إضافة الكود إذا لم يكن موجوداً
                            const codeDiv = document.createElement('div');
                            codeDiv.innerHTML = `
                                <span class="text-xs text-gray-500 dark:text-gray-400">كود الوسيط:</span>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="font-medium font-mono text-success text-sm" id="alwaseet-code-${orderId}">${pickupId}</span>
                                    <button onclick="copyToClipboard('${pickupId}', 'alwaseet-code-${orderId}')" class="btn btn-xs btn-outline-secondary">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        نسخ
                                    </button>
                                </div>
                            `;
                            codeContainer.appendChild(codeDiv);
                        }
                    }

                    // لا يتم تحميل PDF تلقائياً - يمكن استخدام زر "طباعة الكل" لاحقاً

                    // إعادة تحميل الصفحة بعد ثانية واحدة
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showCopyNotification(data.message || 'فشل إرسال الطلب', 'error');
                    sendButton.disabled = false;
                    sendButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCopyNotification('حدث خطأ أثناء إرسال الطلب', 'error');
                sendButton.disabled = false;
                sendButton.innerHTML = originalText;
            });
        }

        // دالة طباعة جميع الطلبات المرسلة
        function printAllOrders() {
            const printButton = document.getElementById('print-all-btn');
            const originalText = printButton.innerHTML;

            printButton.disabled = true;
            printButton.innerHTML = '<svg class="animate-spin w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> جاري الطباعة...';

            // جمع جميع query parameters من الصفحة الحالية
            const urlParams = new URLSearchParams(window.location.search);
            const formData = new FormData();

            // إضافة جميع query parameters إلى formData
            urlParams.forEach((value, key) => {
                formData.append(key, value);
            });

            fetch('{{ route("admin.alwaseet.print-all-orders") }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/pdf',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'فشل طباعة الطلبات');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                // إنشاء رابط تحميل للـ PDF المدمج
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'alwaseet-orders-' + new Date().toISOString().slice(0, 10) + '.pdf';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                showCopyNotification('تم تحميل ملف PDF بنجاح', 'success');
                printButton.disabled = false;
                printButton.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Error:', error);
                showCopyNotification(error.message || 'حدث خطأ أثناء طباعة الطلبات', 'error');
                printButton.disabled = false;
                printButton.innerHTML = originalText;
            });
        }

        // دالة جلب المناطق عند اختيار المحافظة (للاستخدام من Alpine.js)
        window.loadRegionsForOrder = function(orderId, cityId, alpineContext) {
            if (!cityId) {
                alpineContext.regions = [];
                alpineContext.selectedRegionId = '';
                alpineContext.selectedRegionName = '';
                return;
            }

            alpineContext.regions = [];
            alpineContext.isLoadingRegions = true;

            fetch(`{{ route('admin.alwaseet.api.regions') }}?city_id=${cityId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.regions && data.regions.length > 0) {
                    alpineContext.regions = data.regions;
                } else {
                    alpineContext.regions = [];
                }
                alpineContext.isLoadingRegions = false;
                alpineContext.filterRegions();
            })
            .catch(error => {
                console.error('Error:', error);
                alpineContext.regions = [];
                alpineContext.isLoadingRegions = false;
            });
        };

        // دالة حفظ بيانات الواسط
        function updateAlwaseetFields(event, orderId) {
            event.preventDefault();

            const form = document.getElementById(`alwaseet-form-${orderId}`);
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;

            submitButton.disabled = true;
            submitButton.innerHTML = '<svg class="animate-spin w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> جاري الحفظ...';

            fetch(`{{ route('admin.alwaseet.orders.update-alwaseet-fields', ':id') }}`.replace(':id', orderId), {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showCopyNotification(data.message, 'success');
                    // إعادة تحميل الصفحة بعد ثانية واحدة
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showCopyNotification(data.message || 'فشل حفظ البيانات', 'error');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showCopyNotification('حدث خطأ أثناء حفظ البيانات', 'error');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            });
        }
    </script>

    <script>
        // Alpine.js data function للبحث في المدن والمناطق
        document.addEventListener('alpine:init', () => {
            @foreach($orders as $order)
            Alpine.data('alwaseetSearch{{ $order->id }}', (orderId, cities, selectedCityId, selectedRegionId, initialRegions) => {
                // البحث عن المحافظة المختارة
                const selectedCity = cities.find(c => c.id == selectedCityId);
                const selectedCityName = selectedCity ? selectedCity.city_name : '';

                // البحث عن المنطقة المختارة
                const selectedRegion = initialRegions.find(r => r.id == selectedRegionId);
                const selectedRegionName = selectedRegion ? selectedRegion.region_name : '';

                return {
                    orderId: orderId,
                    cities: cities,
                    regions: initialRegions || [],
                    citySearchQuery: '',
                    regionSearchQuery: '',
                    selectedCityId: selectedCityId || '',
                    selectedCityName: selectedCityName,
                    selectedRegionId: selectedRegionId || '',
                    selectedRegionName: selectedRegionName,
                    showCityDropdown: false,
                    showRegionDropdown: false,
                    highlightedCityIndex: -1,
                    highlightedRegionIndex: -1,
                    isLoadingRegions: false,

                    get filteredCities() {
                        if (!this.citySearchQuery) {
                            return this.cities;
                        }
                        const query = this.citySearchQuery.toLowerCase();
                        return this.cities.filter(city =>
                            city.city_name.toLowerCase().includes(query)
                        );
                    },

                    get filteredRegions() {
                        if (!this.regionSearchQuery) {
                            return this.regions;
                        }
                        const query = this.regionSearchQuery.toLowerCase();
                        return this.regions.filter(region =>
                            region.region_name.toLowerCase().includes(query)
                        );
                    },

                    filterCities() {
                        this.showCityDropdown = true;
                        this.highlightedCityIndex = -1;
                    },

                    filterRegions() {
                        this.showRegionDropdown = true;
                        this.highlightedRegionIndex = -1;
                    },

                    selectCity(city) {
                        this.selectedCityId = city.id;
                        this.selectedCityName = city.city_name;
                        this.citySearchQuery = '';
                        this.showCityDropdown = false;
                        this.highlightedCityIndex = -1;

                        // جلب المناطق للمحافظة المختارة
                        if (window.loadRegionsForOrder) {
                            window.loadRegionsForOrder(this.orderId, city.id, this);
                        }

                        // إعادة تعيين المنطقة
                        this.selectedRegionId = '';
                        this.selectedRegionName = '';
                        this.regions = [];
                    },

                    selectRegion(region) {
                        this.selectedRegionId = region.id;
                        this.selectedRegionName = region.region_name;
                        this.regionSearchQuery = '';
                        this.showRegionDropdown = false;
                        this.highlightedRegionIndex = -1;
                    },

                    highlightNextCity() {
                        if (this.highlightedCityIndex < this.filteredCities.length - 1) {
                            this.highlightedCityIndex++;
                        }
                    },

                    highlightPrevCity() {
                        if (this.highlightedCityIndex > 0) {
                            this.highlightedCityIndex--;
                        }
                    },

                    selectHighlightedCity() {
                        if (this.highlightedCityIndex >= 0 && this.highlightedCityIndex < this.filteredCities.length) {
                            this.selectCity(this.filteredCities[this.highlightedCityIndex]);
                        }
                    },

                    highlightNextRegion() {
                        if (this.highlightedRegionIndex < this.filteredRegions.length - 1) {
                            this.highlightedRegionIndex++;
                        }
                    },

                    highlightPrevRegion() {
                        if (this.highlightedRegionIndex > 0) {
                            this.highlightedRegionIndex--;
                        }
                    },

                    selectHighlightedRegion() {
                        if (this.highlightedRegionIndex >= 0 && this.highlightedRegionIndex < this.filteredRegions.length) {
                            this.selectRegion(this.filteredRegions[this.highlightedRegionIndex]);
                        }
                    }
                };
            });
            @endforeach
        });
    </script>

</x-layout.admin>

