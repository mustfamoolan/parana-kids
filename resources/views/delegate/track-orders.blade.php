<x-layout.default>
    @php
        // التأكد من تعريف المتغيرات
        if (!isset($alwaseetOrdersData)) {
            $alwaseetOrdersData = [];
        }
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
            <h5 class="text-lg font-semibold dark:text-white-light">تتبع طلباتي</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if(!$showStatusCards)
                    <a href="{{ route('delegate.orders.track') }}" class="btn btn-outline-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة للمربعات
                    </a>
                @endif
                <a href="{{ route('delegate.dashboard') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    لوحة التحكم
                </a>
            </div>
        </div>

        @if($showStatusCards)
            <!-- عرض مربعات الحالات -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                @foreach($allStatuses as $index => $status)
                    @php
                        $statusId = $status['id'];
                        $statusText = $status['status'];
                        $count = $statusCounts[$statusId] ?? 0;
                        $color = $getStatusColor($index);
                    @endphp
                    <a href="{{ route('delegate.orders.track', ['api_status_id' => $statusId]) }}" 
                       class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br {{ $color['bg'] }} border-2 {{ $color['border'] }}">
                        <div class="w-16 h-16 mx-auto mb-4 {{ $color['iconBg'] }} rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 {{ $color['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold {{ $color['icon'] }} mb-2">{{ $statusText }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="badge {{ $color['icon'] === 'text-primary' ? 'bg-primary' : ($color['icon'] === 'text-success' ? 'bg-success' : ($color['icon'] === 'text-warning' ? 'bg-warning' : ($color['icon'] === 'text-danger' ? 'bg-danger' : ($color['icon'] === 'text-info' ? 'bg-info' : 'bg-secondary')))) }} text-white">{{ $count }}</span> طلب
                        </p>
                    </a>
                @endforeach
            </div>
        @else
            <!-- عرض الطلبات -->
            @php
                $selectedStatus = null;
                if (request('api_status_id')) {
                    foreach ($allStatuses as $status) {
                        if ($status['id'] == request('api_status_id')) {
                            $selectedStatus = $status;
                            break;
                        }
                    }
                }
            @endphp
            
            @if($selectedStatus)
                <div class="mb-5 p-4 bg-info/10 border-2 border-info/20 rounded-lg">
                    <h6 class="text-lg font-bold text-info mb-2">حالة الطلب: {{ $selectedStatus['status'] }}</h6>
                    <p class="text-sm text-gray-600 dark:text-gray-400">عرض {{ $orders->total() }} طلب</p>
                </div>
            @endif

            <!-- فلتر وبحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('delegate.orders.track') }}" class="space-y-4">
                    <!-- إضافة api_status_id hidden إذا كان موجود -->
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
                            placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، العنوان، أو اسم/كود المنتج..."
                            value="{{ request('search') }}"
                        >
                    </div>
                </div>

                <!-- الصف الثاني: المخزن وحالة الطلب -->
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
                        <a href="{{ route('delegate.orders.track') }}" id="clearFiltersBtn" class="btn btn-outline-secondary">
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
        @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('hours_ago'))
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
                        @if(request('hours_ago'))
                            - قبل {{ request('hours_ago') }} ساعة
                        @endif
                    </span>
                </div>
            </div>
        @endif

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
                        // جلب shipment
                        $shipment = $order->alwaseetShipment;
                        if (!$shipment) {
                            $shipment = \App\Models\AlWaseetShipment::where('order_id', $order->id)->first();
                        }

                        // استخدام البيانات المحفوظة من قاعدة البيانات مباشرة (أسرع بكثير)
                        // Job في الخلفية يقوم بتحديث status_id و status تلقائياً
                        $orderStatus = null;
                        if ($shipment) {
                            // استخدام status من قاعدة البيانات مباشرة
                            if ($shipment->status && !empty($shipment->status)) {
                                $orderStatus = $shipment->status;
                            }
                            // Fallback: استخدام status_id مع statusesMap
                            elseif ($shipment->status_id && isset($statusesMap) && isset($statusesMap[$shipment->status_id])) {
                                $orderStatus = $statusesMap[$shipment->status_id];
                            }
                        }

                        // جلب كود الوسيط (من قاعدة البيانات مباشرة)
                        $alwaseetCode = null;
                        // الأولوية الأولى: qr_id من shipment
                        if ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id)) {
                            $alwaseetCode = (string)$shipment->qr_id;
                        }
                        // الأولوية الثانية: delivery_code من Order
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
                            {{-- حالة الطلب من API --}}
                            @if($orderStatus)
                                <span class="badge bg-info text-white text-lg font-bold px-4 py-2" title="حالة الطلب من الوسيط">
                                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $orderStatus }}
                                </span>
                            @endif
                        </div>

                        <!-- رقم الوسيط -->
                        @if($alwaseetCode)
                            <div class="mb-4 text-center">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">رقم الوسيط</span>
                                <div class="text-3xl font-bold font-mono" style="color: #2563eb !important;">{{ $alwaseetCode }}</div>
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
                                        <a href="{{ $order->customer_social_link }}" target="_blank" class="text-primary dark:text-primary-light hover:underline break-all text-sm">
                                            {{ $order->customer_social_link }}
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
                                    <!-- معلومات المندوب -->
                                    @if($order->delegate)
                                        <div class="flex-1">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المندوب</span>
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

            <!-- Pagination -->
            @if(isset($orders))
                <x-pagination :items="$orders" />
            @endif
        @endif
    </div>

</x-layout.default>

