<x-layout.admin>
    @php
        // التأكد من تعريف $alwaseetOrdersData
        if (!isset($alwaseetOrdersData)) {
            $alwaseetOrdersData = [];
        }
        // التأكد من تعريف $statusesMap
        if (!isset($statusesMap)) {
            $statusesMap = [];
        }
        if (!isset($allStatuses)) {
            $allStatuses = [];
        }
        if (!isset($statusCounts)) {
            $statusCounts = [];
        }
        if (!isset($showStatusCards)) {
            $showStatusCards = true;
        }

        // ألوان ديناميكية للحالات
        $statusColors = [
            'primary' => ['bg' => 'from-primary/10 to-primary/5', 'border' => 'border-primary/20', 'icon' => 'text-primary', 'iconBg' => 'bg-primary/20'],
            'success' => ['bg' => 'from-success/10 to-success/5', 'border' => 'border-success/20', 'icon' => 'text-success', 'iconBg' => 'bg-success/20'],
            'warning' => ['bg' => 'from-warning/10 to-warning/5', 'border' => 'border-warning/20', 'icon' => 'text-warning', 'iconBg' => 'bg-warning/20'],
            'danger' => ['bg' => 'from-danger/10 to-danger/5', 'border' => 'border-danger/20', 'icon' => 'text-danger', 'iconBg' => 'bg-danger/20'],
            'info' => ['bg' => 'from-info/10 to-info/5', 'border' => 'border-info/20', 'icon' => 'text-info', 'iconBg' => 'bg-info/20'],
            'secondary' => ['bg' => 'from-secondary/10 to-secondary/5', 'border' => 'border-secondary/20', 'icon' => 'text-secondary', 'iconBg' => 'bg-secondary/20'],
        ];

        // دالة للحصول على لون حسب index
        $getStatusColor = function($index) use ($statusColors) {
            $colorKeys = array_keys($statusColors);
            return $statusColors[$colorKeys[$index % count($colorKeys)]];
        };
    @endphp
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تتبع طلبات الوسيط</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if(!$showStatusCards)
                    <a href="{{ route('admin.alwaseet.track-orders') }}" class="btn btn-outline-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة للكاردات
                    </a>
                @endif
                <a href="{{ route('admin.alwaseet.print-and-upload-orders', array_filter([
                    'warehouse_id' => request('warehouse_id'),
                    'search' => request('search'),
                    'confirmed_by' => request('confirmed_by'),
                    'delegate_id' => request('delegate_id'),
                    'date_from' => request('date_from'),
                    'date_to' => request('date_to'),
                    'time_from' => request('time_from'),
                    'time_to' => request('time_to'),
                ])) }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    رفع وطباع الطلبات
                </a>
            </div>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات المقيدة المرسلة</h6>
                        <p class="text-xl font-bold text-success">{{ $orders->total() }}</p>
                    </div>
                    <div class="p-2 bg-success/10 rounded-lg">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلتر وبحث -->
        <div class="mb-5">
            <form method="GET" action="{{ route('admin.alwaseet.track-orders') }}" class="space-y-4">
                @if(request('api_status_id'))
                    <input type="hidden" name="api_status_id" value="{{ request('api_status_id') }}">
                @endif
                <!-- الصف الأول: البحث -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            name="search"
                            id="searchFilter"
                            class="form-input"
                            placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، العنوان، كود الوسيط، أو اسم/كود المنتج..."
                            value="{{ request('search') }}"
                        >
                    </div>
                </div>

                <!-- الصف الثاني: المخزن والمجهز والمندوب وحالة الطلب -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="sm:w-48">
                        <select name="warehouse_id" class="form-select" id="warehouseFilter">
                            <option value="">كل المخازن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <select name="confirmed_by" id="confirmedByFilter" class="form-select">
                            <option value="">كل المجهزين والمديرين</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('confirmed_by') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }}) - {{ $supplier->role === 'admin' ? 'مدير' : 'مجهز' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        @php
                            $orderCreators = \App\Models\User::whereIn('role', ['delegate', 'admin', 'supplier'])->orderBy('role')->orderBy('name')->get();
                        @endphp
                        <select name="delegate_id" id="delegateIdFilter" class="form-select">
                            <option value="">كل المندوبين والمديرين والمجهزين</option>
                            @foreach($orderCreators as $creator)
                                <option value="{{ $creator->id }}" {{ request('delegate_id') == $creator->id ? 'selected' : '' }}>
                                    {{ $creator->name }} ({{ $creator->code }}) - {{ $creator->role === 'admin' ? 'مدير' : ($creator->role === 'supplier' ? 'مجهز' : 'مندوب') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <select name="api_status_id" id="apiStatusFilter" class="form-select">
                            <option value="">كل حالات الطلب</option>
                            @if(isset($allStatuses) && is_array($allStatuses))
                                @foreach($allStatuses as $status)
                                    <option value="{{ $status['id'] }}" {{ request('api_status_id') == $status['id'] ? 'selected' : '' }}>
                                        {{ $status['status'] }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- الصف الثالث: التاريخ والوقت -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="sm:w-48">
                        <input
                            type="date"
                            name="date_from"
                            id="dateFromFilter"
                            class="form-input"
                            placeholder="من تاريخ"
                            value="{{ request('date_from') }}"
                        >
                    </div>
                    <div class="sm:w-48">
                        <input
                            type="date"
                            name="date_to"
                            id="dateToFilter"
                            class="form-input"
                            placeholder="إلى تاريخ"
                            value="{{ request('date_to') }}"
                        >
                    </div>
                    <div class="sm:w-32">
                        <input
                            type="time"
                            name="time_from"
                            id="timeFromFilter"
                            class="form-input"
                            placeholder="من الساعة"
                            value="{{ request('time_from') }}"
                        >
                    </div>
                    <div class="sm:w-32">
                        <input
                            type="time"
                            name="time_to"
                            id="timeToFilter"
                            class="form-input"
                            placeholder="إلى الساعة"
                            value="{{ request('time_to') }}"
                        >
                    </div>
                    <div class="sm:w-48">
                        <select name="hours_ago" id="hoursAgoFilter" class="form-select">
                            <option value="">كل الطلبات</option>
                            @for($i = 2; $i <= 30; $i += 2)
                                <option value="{{ $i }}" {{ request('hours_ago') == $i ? 'selected' : '' }}>
                                    قبل {{ $i }} ساعة
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            بحث
                        </button>
                        @php
                            // الحفاظ على api_status_id عند مسح الفلاتر وإضافة معامل clear_filters
                            $clearFiltersParams = ['clear_filters' => '1'];
                            if (request('api_status_id')) {
                                $clearFiltersParams['api_status_id'] = request('api_status_id');
                            }
                            $clearFiltersUrl = route('admin.alwaseet.track-orders', $clearFiltersParams);
                        @endphp
                        <a href="{{ $clearFiltersUrl }}" id="clearFiltersBtn" class="btn btn-outline-secondary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            مسح الفلتر
                        </a>
                    </div>
                </div>
            </form>
        </div>

        @if($showStatusCards)
            <!-- عرض مربعات الحالات -->
            <div class="mb-5 grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                @foreach($allStatuses as $index => $status)
                    @php
                        $statusId = (string)$status['id'];
                        $statusText = $status['status'];
                        $count = isset($statusCounts[$statusId]) ? (int)$statusCounts[$statusId] : 0;
                        $amount = (auth()->user()->isAdmin() && isset($statusAmounts)) ? (isset($statusAmounts[$statusId]) ? (float)$statusAmounts[$statusId] : 0) : 0;
                        $color = $getStatusColor($index);
                    @endphp
                    @if($count > 0)
                    @php
                        // الحفاظ على جميع الفلاتر الحالية مع إضافة api_status_id
                        $allFilters = array_merge(
                            request()->except(['api_status_id', 'page']),
                            ['api_status_id' => $statusId]
                        );
                    @endphp
                    <a href="{{ route('admin.alwaseet.track-orders', array_filter($allFilters)) }}"
                       class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br {{ $color['bg'] }} border-2 {{ $color['border'] }}">
                        <div class="w-16 h-16 mx-auto mb-4 {{ $color['iconBg'] }} rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 {{ $color['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold {{ $color['icon'] }} mb-2">{{ $statusText }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span class="badge {{ $color['icon'] === 'text-primary' ? 'bg-primary' : ($color['icon'] === 'text-success' ? 'bg-success' : ($color['icon'] === 'text-warning' ? 'bg-warning' : ($color['icon'] === 'text-danger' ? 'bg-danger' : ($color['icon'] === 'text-info' ? 'bg-info' : 'bg-secondary')))) }} text-white">{{ $count }}</span> طلب
                        </p>
                        @auth
                            @if(auth()->user()->isAdmin() && $amount > 0)
                                <p class="text-base font-bold text-success dark:text-success-light mt-2">
                                    {{ rtrim(rtrim(number_format($amount, 2), '0'), '.') }} دينار
                                </p>
                            @endif
                        @endauth
                    </a>
                    @endif
                @endforeach
            </div>
        @else
            <!-- عرض الفلاتر والطلبات -->

        <!-- نتائج البحث -->
        @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        عرض {{ $orders->total() }} طلب مقيد ومرسل
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

        @auth
            @if(auth()->user()->isAdmin() && isset($totalOrdersAmount))
                <!-- كارد إحصائية المبلغ الكلي للطلبات المعروضة -->
                <div class="mb-5 panel p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500 mb-1">المبلغ الكلي للطلبات المعروضة</h6>
                            <p class="text-2xl font-bold text-success">{{ rtrim(rtrim(number_format($totalOrdersAmount, 2), '0'), '.') }} دينار</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $orders->total() }} طلب</p>
                        </div>
                        <div class="p-3 bg-success/10 rounded-lg">
                            <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            @endif
        @endauth

        <!-- تحذير عند وجود المزيد من الطلبات -->
        @if(isset($hasMoreOrders) && $hasMoreOrders)
            <div class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        تم عرض أول 30 طلب فقط. يرجى استخدام فلاتر إضافية (مثل التاريخ أو البحث) لتضييق النتائج.
                    </span>
                </div>
            </div>
        @endif

        <!-- كروت الطلبات -->
        @if($orders->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($orders as $index => $order)
                    @php
                        // جلب shipment (للبيانات الثابتة فقط)
                        $shipment = $order->alwaseetShipment;
                        if (!$shipment) {
                            $shipment = \App\Models\AlWaseetShipment::where('order_id', $order->id)->first();
                        }

                        // جلب بيانات الطلب من API مباشرة (للحصول على الحالة المحدثة)
                        $apiOrderData = null;
                        $orderStatus = null;
                        $apiStatusId = null;

                        if ($shipment && isset($shipment->alwaseet_order_id) && isset($alwaseetOrdersData[$shipment->alwaseet_order_id])) {
                            $apiOrderData = $alwaseetOrdersData[$shipment->alwaseet_order_id];
                            // استخدام الحالة من API مباشرة (أحدث البيانات)
                            $orderStatus = $apiOrderData['status'] ?? null;
                            $apiStatusId = $apiOrderData['status_id'] ?? null;
                        }

                        // Fallback: استخدام البيانات المحفوظة في قاعدة البيانات
                        if (!$orderStatus) {
                            if ($shipment && isset($shipment->status) && $shipment->status) {
                            $orderStatus = $shipment->status;
                                $apiStatusId = $shipment->status_id;
                        }
                        // Fallback: استخدام status_id مع statusesMap
                        elseif ($shipment && $shipment->status_id && isset($statusesMap) && isset($statusesMap[$shipment->status_id])) {
                            $orderStatus = $statusesMap[$shipment->status_id];
                                $apiStatusId = $shipment->status_id;
                            }
                        }

                        // جلب كود الوسيط (من API أولاً، ثم من قاعدة البيانات)
                        $alwaseetCode = null;
                        // الأولوية الأولى: من API مباشرة
                        if ($apiOrderData && isset($apiOrderData['pickup_id']) && !empty($apiOrderData['pickup_id'])) {
                            $alwaseetCode = (string)$apiOrderData['pickup_id'];
                        }
                        // الأولوية الثانية: pickup_id من shipment (من قاعدة البيانات)
                        elseif ($shipment && isset($shipment->pickup_id) && !empty($shipment->pickup_id)) {
                            $alwaseetCode = (string)$shipment->pickup_id;
                        }
                        // الأولوية الثالثة: qr_id من API
                        elseif ($apiOrderData && isset($apiOrderData['qr_id']) && !empty($apiOrderData['qr_id'])) {
                            $alwaseetCode = (string)$apiOrderData['qr_id'];
                        }
                        // الأولوية الرابعة: qr_id من shipment
                        elseif ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id)) {
                            $alwaseetCode = (string)$shipment->qr_id;
                        }
                        // الأولوية الخامسة: delivery_code من Order
                        elseif ($order->delivery_code && !empty(trim($order->delivery_code))) {
                            $alwaseetCode = (string)$order->delivery_code;
                        }
                    @endphp
                    <div id="order-{{ $order->id }}" class="panel border-2 border-success dark:border-success relative">
                        <!-- رقم تسلسلي دائري -->
                        <div class="absolute top-2 right-2 bg-primary text-white rounded-full w-10 h-10 flex items-center justify-center text-sm font-bold shadow-lg z-10">
                            {{ $orders->firstItem() + $index }}
                        </div>

                        <!-- هيدر الكارت -->
                        <div class="flex items-center justify-center mb-4 pl-12">
                            {{-- حالة الطلب من API - محسنة --}}
                            @if($orderStatus)
                                <div class="bg-gradient-to-r from-info/20 to-info/10 border-2 border-info/50 rounded-lg px-4 py-2">
                                    <span class="badge bg-info text-white text-lg font-bold px-4 py-2" title="حالة الطلب من الوسيط">
                                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $orderStatus }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <!-- رقم الوسيط -->
                        @if($alwaseetCode)
                            <div class="mb-4 text-center">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">رقم الوسيط</span>
                                <div class="flex items-center justify-center gap-2">
                                    <div class="text-3xl font-bold font-mono" style="color: #2563eb !important;" id="alwaseet-code-{{ $order->id }}">{{ $alwaseetCode }}</div>
                                    <button
                                        type="button"
                                        onclick="copyDeliveryCode('{{ $alwaseetCode }}', 'delivery')"
                                        class="btn btn-sm btn-primary"
                                        title="نسخ كود الوسيط"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- معلومات الزبون -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الزبون</span>
                                <p class="font-medium mb-2">{{ $order->customer_name }}</p>

                                <!-- عنوان الزبون -->
                                <div class="mb-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">عنوان الزبون</span>
                                    <div class="text-lg font-bold !text-primary dark:!text-primary-light">{{ $order->customer_address ?? 'لا يوجد عنوان' }}</div>
                                </div>

                                <!-- رابط السوشل ميديا -->
                                @if($order->customer_social_link)
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">رابط السوشل ميديا</span>
                                        <a href="{{ $order->customer_social_link }}" target="_blank" class="btn btn-sm btn-primary w-full">
                                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            فتح الرابط
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- أزرار الاتصال -->
                        @if($order->customer_phone || $order->customer_phone2)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    @if($order->customer_phone)
                                        <div class="mb-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-2">الرقم الأول: {{ $order->customer_phone }}</span>
                                            <div class="flex gap-2">
                                                <a href="tel:{{ $order->customer_phone }}" class="btn btn-sm btn-primary flex-1">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                    </svg>
                                                    اتصال
                                                </a>
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" target="_blank" class="btn btn-sm btn-success flex-1">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                    </svg>
                                                    واتساب
                                                </a>
                                            </div>
                                        </div>
                                    @endif

                                    @if($order->customer_phone2)
                                        <div class="{{ $order->customer_phone ? 'pt-2 border-t' : '' }}">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-2">الرقم الثاني: {{ $order->customer_phone2 }}</span>
                                            <div class="flex gap-2">
                                                <a href="tel:{{ $order->customer_phone2 }}" class="btn btn-sm btn-primary flex-1">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                    </svg>
                                                    اتصال
                                                </a>
                                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone2) }}" target="_blank" class="btn btn-sm btn-success flex-1">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                    </svg>
                                                    واتساب
                                                </a>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- المندوب والتاريخ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <div class="flex items-center justify-between gap-4">
                                    <!-- معلومات المندوب/المدير/المجهز -->
                                    @if($order->delegate)
                                        @php
                                            $userType = $order->delegate->role === 'admin' ? 'مدير' : ($order->delegate->role === 'supplier' ? 'مجهز' : 'مندوب');
                                        @endphp
                                        <div class="flex-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">{{ $userType }}</span>
                                            <p class="font-medium">{{ $order->delegate->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $order->delegate->code }}</p>
                                        </div>
                                    @endif

                                    <!-- التاريخ -->
                                    <div class="flex-1 text-right">
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">تاريخ التقييد</span>
                                        <p class="font-medium">{{ $order->confirmed_at ? $order->confirmed_at->format('Y-m-d') : $order->created_at->format('Y-m-d') }}</p>
                                        <p class="text-sm text-gray-500">{{ $order->confirmed_at ? $order->confirmed_at->format('g:i') : $order->created_at->format('g:i') }} {{ ($order->confirmed_at ? $order->confirmed_at->format('H') : $order->created_at->format('H')) >= 12 ? 'مساءً' : 'نهاراً' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- زر الإرجاع الجزئي (عندما يوجد api_status_id والطلب مقيد) -->
                        @if(request('api_status_id') && $order->status === 'confirmed')
                            <div class="mb-4">
                                <a href="{{ route('admin.orders.partial-return', $order->id) }}?return_to_track={{ request('api_status_id') }}" class="btn btn-warning w-full">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                    </svg>
                                    إرجاع جزئي
                                </a>
                            </div>
                        @endif

                        <!-- قائمة المنتجات -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-2">المنتجات</span>
                                <div class="space-y-2">
                                    @foreach($order->items as $item)
                                        @if($item->product)
                                            <div class="flex items-center gap-2 pb-2 border-b last:border-0">
                                                @if($item->product->primaryImage)
                                                    <img src="{{ $item->product->primaryImage->image_url }}"
                                                         class="w-12 h-12 object-cover rounded-lg"
                                                         alt="{{ $item->product->name }}">
                                                @else
                                                    <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <h6 class="font-semibold text-sm dark:text-white-light mb-1 line-clamp-1">{{ $item->product->name }}</h6>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">السعر: {{ number_format($item->unit_price ?? 0) }} دينار</p>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        @if($item->size_name)
                                                            <span class="badge badge-outline-primary text-sm font-bold">{{ $item->size_name }}</span>
                                                        @endif
                                                        <span class="badge badge-outline-success text-sm font-bold">{{ $item->quantity }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- السعر الكلي والمبلغ مع التوصيل -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">السعر الكلي</span>
                                        <span class="text-sm font-bold text-primary dark:text-primary-light">{{ number_format($order->total_amount ?? 0) }} دينار</span>
                                    </div>
                                    @php
                                        $deliveryFee = $order->delivery_fee_at_confirmation ?? 0;
                                        $totalWithDelivery = ($order->total_amount ?? 0) + $deliveryFee;
                                    @endphp
                                    <div class="flex items-center justify-between pt-2 border-t">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">المبلغ مع التوصيل</span>
                                        <span class="text-sm font-bold text-success dark:text-success-light">{{ number_format($totalWithDelivery) }} دينار</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline حالات الطلب -->
                        @if($shipment && !$shipment->statusHistory->isEmpty())
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <h4 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    سجل الحالات
                                </h4>

                                <!-- Timeline مصغر -->
                                <div class="relative">
                                    <div class="absolute right-2 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                                    @foreach($shipment->statusHistory as $history)
                                        <div class="relative flex items-center gap-3 mb-2 last:mb-0">
                                            <div class="relative z-10 w-5 h-5 rounded-full flex items-center justify-center
                                                {{ $history->status_id === ($apiStatusId ?? $shipment->status_id)
                                                    ? 'bg-success ring-2 ring-success/30' : 'bg-gray-300 dark:bg-gray-600' }}">
                                                @if($history->status_id === ($apiStatusId ?? $shipment->status_id))
                                                    <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                @else
                                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                                @endif
                                            </div>
                                            <div class="flex-1 text-xs">
                                                <span class="font-medium {{ $history->status_id === ($apiStatusId ?? $shipment->status_id) ? 'text-success' : 'text-gray-600 dark:text-gray-400' }}">
                                                    {{ $history->status_text }}
                                                </span>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                                        {{ $history->changed_at->format('m-d') }}
                                                    </span>
                                                    <span class="text-sm font-bold {{ $history->changed_at->format('H') < 12 ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                                                        {{ $history->changed_at->format('h:i') }}
                                                    </span>
                                                    <span class="px-1.5 py-0.5 rounded text-xs font-bold {{ $history->changed_at->format('H') < 12 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                                                        {{ $history->changed_at->format('H') < 12 ? 'صباحاً' : 'مساءً' }}
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- زر الحذف - يظهر فقط عند عرض الطلبات (ليس في صفحة الكاردات) - معطل مؤقتا --}}
                        {{-- @if(!$showStatusCards)
                            <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-800">
                                <button
                                    type="button"
                                    class="btn btn-danger btn-sm w-full delete-order-btn"
                                    data-order-id="{{ $order->id }}"
                                    data-order-number="{{ $order->order_number }}"
                                    title="حذف مؤقت - سيتم إزالة هذا الزر لاحقاً"
                                >
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    حذف من القائمة
                                </button>
                            </div>
                        @endif --}}

                    </div>
                @endforeach
            </div>
        @else
            <div class="panel">
                <div class="flex flex-col items-center justify-center py-10">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">لا توجد طلبات مقيدة ومرسلة</h3>
                    <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على أي طلبات مقيدة ومرسلة تطابق معايير البحث</p>
                </div>
            </div>
        @endif
        @endif

        <!-- Pagination -->
        @if(!$showStatusCards)
            <x-pagination :items="$orders" />
        @endif
    </div>

    <!-- Modal حذف طلب واحد -->
    <div id="deleteOrderModal" class="fixed inset-0 z-[9999] hidden bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-danger/20 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">حذف الطلب من القائمة</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="deleteOrderNumber"></p>
                    </div>
                </div>

                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    هل أنت متأكد من حذف هذا الطلب من القائمة؟<br>
                    <span class="text-sm text-gray-500">سيتم إخفاء الطلب فقط من هذه الصفحة.</span>
                </p>

                <form id="deleteOrderForm" method="POST" action="">
                    @csrf
                    @method('DELETE')

                    <div class="flex gap-3">
                        <button
                            type="button"
                            onclick="closeDeleteModal()"
                            class="btn btn-outline-secondary flex-1"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            class="btn btn-danger flex-1"
                        >
                            حذف من القائمة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

// حذف طلب واحد
function deleteOrderFromTrack(orderId, orderNumber) {
    const modal = document.getElementById('deleteOrderModal');
    const form = document.getElementById('deleteOrderForm');
    const orderNumberSpan = document.getElementById('deleteOrderNumber');

    // تعيين route الحذف
    form.action = '{{ route("admin.alwaseet.orders.delete", ":id") }}'.replace(':id', orderId);
    orderNumberSpan.textContent = 'الطلب #' + orderNumber;

    // عرض Modal
    modal.classList.remove('hidden');
}

// إغلاق Modal
function closeDeleteModal() {
    const modal = document.getElementById('deleteOrderModal');
    modal.classList.add('hidden');
}

// إغلاق Modal عند الضغط خارجها
document.addEventListener('DOMContentLoaded', function() {
    // إضافة event listeners لأزرار الحذف
    const deleteButtons = document.querySelectorAll('.delete-order-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const orderNumber = this.dataset.orderNumber;
            deleteOrderFromTrack(orderId, orderNumber);
        });
    });

    const modal = document.getElementById('deleteOrderModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
    });
}

    // Local Storage لجميع الفلاتر
    const urlParams = new URLSearchParams(window.location.search);
    // التحقق من وجود معامل clear_filters - إذا كان موجوداً، لا نستعيد الفلاتر من localStorage
    const shouldClearFilters = urlParams.has('clear_filters');
    // استثناء clear_filters و api_status_id من التحقق (api_status_id ليس فلتر عادي)
    const hasUrlParams = urlParams.has('warehouse_id') || urlParams.has('search') || urlParams.has('confirmed_by') ||
                        urlParams.has('delegate_id') || urlParams.has('date_from') ||
                        urlParams.has('date_to') || urlParams.has('time_from') || urlParams.has('time_to') || urlParams.has('hours_ago');

    // قائمة الفلاتر مع مفاتيح localStorage
    const filters = [
        { id: 'searchFilter', key: 'selectedSearch_track_orders', param: 'search' },
        { id: 'warehouseFilter', key: 'selectedWarehouse_track_orders', param: 'warehouse_id' },
        { id: 'confirmedByFilter', key: 'selectedConfirmedBy_track_orders', param: 'confirmed_by' },
        { id: 'delegateIdFilter', key: 'selectedDelegateId_track_orders', param: 'delegate_id' },
        { id: 'apiStatusFilter', key: 'selectedApiStatus_track_orders', param: 'api_status_id' },
        { id: 'dateFromFilter', key: 'selectedDateFrom_track_orders', param: 'date_from' },
        { id: 'dateToFilter', key: 'selectedDateTo_track_orders', param: 'date_to' },
        { id: 'timeFromFilter', key: 'selectedTimeFrom_track_orders', param: 'time_from' },
        { id: 'timeToFilter', key: 'selectedTimeTo_track_orders', param: 'time_to' },
        { id: 'hoursAgoFilter', key: 'selectedHoursAgo_track_orders', param: 'hours_ago' }
    ];

    let hasSavedFilters = false;
    const savedParams = new URLSearchParams();

    // إذا كان shouldClearFilters موجوداً، مسح جميع الفلاتر من localStorage ومسح قيم الحقول
    if (shouldClearFilters) {
        // مسح جميع الفلاتر من localStorage
        filters.forEach(filter => {
            localStorage.removeItem(filter.key);
            // مسح قيم الحقول في النموذج
            const element = document.getElementById(filter.id);
            if (element) {
                element.value = '';
            }
        });

        // الحفاظ على api_status_id إذا كان موجوداً
        const apiStatusId = urlParams.get('api_status_id');

        // إعادة تحميل الصفحة بدون معاملات (إلا api_status_id)
        let newUrl = window.location.pathname;
        if (apiStatusId) {
            newUrl += '?api_status_id=' + apiStatusId;
        }
        window.location.href = newUrl;
        return; // إيقاف تنفيذ باقي الكود
    }

    filters.forEach(filter => {
        const element = document.getElementById(filter.id);
        if (element) {
            // استرجاع الفلتر من Local Storage عند التحميل فقط إذا لم تكن هناك معاملات في URL ولم يتم طلب مسح الفلاتر
            if (!hasUrlParams && !shouldClearFilters) {
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

    // تطبيق الفلاتر المحفوظة تلقائياً إذا كانت موجودة ولم تكن هناك معاملات في URL ولم يتم طلب مسح الفلاتر
    if (!hasUrlParams && !shouldClearFilters && hasSavedFilters && savedParams.toString()) {
        const form = document.querySelector('form[action*="track-orders"]');
        if (form) {
            // الحفاظ على api_status_id إذا كان موجوداً في URL
            if (urlParams.has('api_status_id')) {
                savedParams.set('api_status_id', urlParams.get('api_status_id'));
            }

            // إضافة الفلاتر المحفوظة إلى النموذج
            savedParams.forEach((value, key) => {
                const existingInput = form.querySelector(`[name="${key}"]`);
                if (existingInput) {
                    existingInput.value = value;
                } else {
                    // إنشاء input مخفي إذا لم يكن موجوداً
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = key;
                    hiddenInput.value = value;
                    form.appendChild(hiddenInput);
                }
            });
            // إرسال النموذج تلقائياً
            form.submit();
        }
    }

    // معالجة زر مسح الفلتر - حذف جميع الفلاتر من localStorage ومسح قيم الحقول
    const clearFiltersBtn = document.getElementById('clearFiltersBtn');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // حذف جميع الفلاتر من localStorage
            filters.forEach(filter => {
                localStorage.removeItem(filter.key);
                // مسح قيم الحقول في النموذج
                const element = document.getElementById(filter.id);
                if (element) {
                    element.value = '';
                }
            });

            // الحفاظ على api_status_id إذا كان موجوداً في URL
            const urlParams = new URLSearchParams(window.location.search);
            const apiStatusId = urlParams.get('api_status_id');

            // الانتقال إلى الصفحة بدون معاملات (إلا api_status_id)
            let newUrl = '{{ route("admin.alwaseet.track-orders") }}';
            if (apiStatusId) {
                newUrl += '?api_status_id=' + apiStatusId;
            }
            window.location.href = newUrl;
        });
    }
});
</script>

</x-layout.admin>

