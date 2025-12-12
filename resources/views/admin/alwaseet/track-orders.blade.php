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
    @endphp
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تتبع طلبات الوسيط</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
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
                        <select name="delegate_id" id="delegateIdFilter" class="form-select">
                            <option value="">كل المندوبين</option>
                            @foreach($delegates as $delegate)
                                <option value="{{ $delegate->id }}" {{ request('delegate_id') == $delegate->id ? 'selected' : '' }}>
                                    {{ $delegate->name }} ({{ $delegate->code }})
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

                <!-- الصف الثالث: التاريخ -->
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
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            بحث
                        </button>
                        <a href="{{ route('admin.alwaseet.track-orders') }}" id="clearFiltersBtn" class="btn btn-outline-secondary">
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

                        // جلب بيانات API
                        $apiOrderData = null;
                        if (isset($alwaseetOrdersData) && $shipment && isset($shipment->alwaseet_order_id) && isset($alwaseetOrdersData[$shipment->alwaseet_order_id])) {
                            $apiOrderData = $alwaseetOrdersData[$shipment->alwaseet_order_id];
                        }

                        // جلب حالة الطلب من API
                        $orderStatus = null;
                        if ($apiOrderData) {
                            // الأولوية: استخدام status مباشرة إذا كان موجوداً
                            if (isset($apiOrderData['status']) && !empty($apiOrderData['status'])) {
                                $orderStatus = $apiOrderData['status'];
                            }
                            // إذا لم يكن status موجوداً، استخدام status_id مع statusesMap
                            elseif (isset($apiOrderData['status_id']) && isset($statusesMap) && isset($statusesMap[$apiOrderData['status_id']])) {
                                $orderStatus = $statusesMap[$apiOrderData['status_id']];
                            }
                        }
                        // Fallback إلى قاعدة البيانات المحلية
                        if (!$orderStatus && $shipment && isset($shipment->status) && $shipment->status) {
                            $orderStatus = $shipment->status;
                        }

                        // جلب كود الوسيط
                        $alwaseetCode = null;
                        // الأولوية الأولى: pickup_id من API
                        if ($apiOrderData && isset($apiOrderData['pickup_id']) && !empty($apiOrderData['pickup_id'])) {
                            $alwaseetCode = (string)$apiOrderData['pickup_id'];
                        }
                        // الأولوية الثانية: qr_id من API
                        elseif ($apiOrderData && isset($apiOrderData['qr_id']) && !empty($apiOrderData['qr_id'])) {
                            $alwaseetCode = (string)$apiOrderData['qr_id'];
                        }
                        // الأولوية الثالثة: qr_id من shipment
                        elseif ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id)) {
                            $alwaseetCode = (string)$shipment->qr_id;
                        }
                        // الأولوية الرابعة: delivery_code من Order
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
                        <div class="flex items-center justify-between mb-4 pl-12">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="text-lg font-bold text-primary dark:text-primary-light relative inline-block">
                                        رقم الطلب: {{ $order->order_number }}
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
                                {{-- حالة الطلب من API --}}
                                @if($orderStatus)
                                    <span class="badge bg-info text-white text-sm font-bold px-3 py-1.5" title="حالة الطلب من الوسيط">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{ $orderStatus }}
                                    </span>
                                @endif
                                <span class="badge bg-success text-white text-sm font-bold px-3 py-1.5">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    مقيد ومرسل
                                </span>
                            </div>
                        </div>

                        <!-- كود الوسيط -->
                        @if($alwaseetCode)
                            <div class="mb-4 text-center">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">كود الوسيط</span>
                                <div class="text-3xl font-bold font-mono" style="color: #2563eb !important;">{{ $alwaseetCode }}</div>
                            </div>
                        @endif

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
                                <div class="text-lg font-bold !text-primary dark:!text-primary-light">{{ $order->customer_address ?? 'لا يوجد عنوان' }}</div>
                            </div>
                        </div>

                        <!-- معلومات المندوب -->
                        @if($order->delegate)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المندوب</span>
                                    <p class="font-medium">{{ $order->delegate->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $order->delegate->code }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- التاريخ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">تاريخ التقييد</span>
                                <p class="font-medium">{{ $order->confirmed_at ? $order->confirmed_at->format('Y-m-d') : $order->created_at->format('Y-m-d') }}</p>
                                <p class="text-sm text-gray-500">{{ $order->confirmed_at ? $order->confirmed_at->format('g:i') : $order->created_at->format('g:i') }} {{ ($order->confirmed_at ? $order->confirmed_at->format('H') : $order->created_at->format('H')) >= 12 ? 'مساءً' : 'نهاراً' }}</p>
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
                                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $item->product->code }}</p>
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

                        <!-- أزرار الإجراءات -->
                        <div class="flex gap-2 flex-wrap">
                            @php
                                $backRoute = 'admin.alwaseet.track-orders';
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

                            <!-- زر الطباعة -->
                            @if($shipment && !empty($shipment->qr_link))
                                <button type="button" onclick="downloadOrderPdf({{ $order->id }})" class="btn btn-sm btn-info flex-1" id="print-btn-{{ $order->id }}" title="طباعة">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path>
                                    </svg>
                                    طباعة
                                </button>
                            @endif
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
        <x-pagination :items="$orders" />
    </div>

    <script>
        // دالة نسخ النص إلى الحافظة
        function copyDeliveryCode(text, type = '') {
            let successMessage = 'تم النسخ بنجاح!';
            if (type === 'order') {
                successMessage = 'تم نسخ رقم الطلب بنجاح!';
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            textarea.select();
            textarea.setSelectionRange(0, 99999);

            try {
                document.execCommand('copy');
                showCopyNotification(successMessage);
            } catch (err) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyNotification(successMessage);
                    }).catch(function() {
                        showCopyNotification('فشل في النسخ', 'error');
                    });
                } else {
                    showCopyNotification('فشل في النسخ', 'error');
                }
            }

            document.body.removeChild(textarea);
        }

        // دالة إظهار إشعار النسخ
        function showCopyNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300`;
            notification.textContent = message;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // دالة تحميل PDF لطلب واحد
        function downloadOrderPdf(orderId) {
            const printButton = document.getElementById(`print-btn-${orderId}`);
            const originalText = printButton.innerHTML;

            printButton.disabled = true;
            printButton.innerHTML = '<svg class="animate-spin w-4 h-4 inline-block" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> جاري التحميل...';

            fetch(`{{ route('admin.alwaseet.orders.download-pdf', ':id') }}`.replace(':id', orderId), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/pdf',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'فشل تحميل PDF');
                    });
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `alwaseet-order-${orderId}-${new Date().toISOString().slice(0, 10)}.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);

                if (typeof showCopyNotification === 'function') {
                    showCopyNotification('تم تحميل ملف PDF بنجاح', 'success');
                }

                printButton.disabled = false;
                printButton.innerHTML = originalText;
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof showCopyNotification === 'function') {
                    showCopyNotification(error.message || 'حدث خطأ أثناء تحميل PDF', 'error');
                } else {
                    alert(error.message || 'حدث خطأ أثناء تحميل PDF');
                }
                printButton.disabled = false;
                printButton.innerHTML = originalText;
            });
        }
    </script>
</x-layout.admin>

