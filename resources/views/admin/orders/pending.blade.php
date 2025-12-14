<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الطلبات غير المقيدة</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.materials.management', array_filter([
                    'status' => 'pending',
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
                    'hours_filter' => request('hours_filter'),
                ])) }}" class="btn btn-success">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    عرض المواد المطلوبة
                </a>
                <a href="{{ route('admin.orders.materials.management-grouped', array_filter([
                    'status' => 'pending',
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
                    'hours_filter' => request('hours_filter'),
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
                <form method="GET" action="{{ route('admin.orders.pending') }}" class="space-y-4">
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
                            @php
                                $orderCreators = \App\Models\User::whereIn('role', ['delegate', 'admin', 'supplier'])->orderBy('role')->orderBy('name')->get();
                            @endphp
                            <select name="delegate_id" id="delegateIdFilterPending" class="form-select">
                                <option value="">كل المندوبين والمديرين والمجهزين</option>
                                @foreach($orderCreators as $creator)
                                    <option value="{{ $creator->id }}" {{ request('delegate_id') == $creator->id ? 'selected' : '' }}>
                                        {{ $creator->name }} ({{ $creator->code }}) - {{ $creator->role === 'admin' ? 'مدير' : ($creator->role === 'supplier' ? 'مجهز' : 'مندوب') }}
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
                        <div class="sm:w-40">
                            <select name="hours_filter" id="hoursFilterPending" class="form-select">
                                <option value="">كل الأوقات</option>
                                @foreach(range(2, 30, 2) as $hours)
                                    <option value="{{ $hours }}" {{ request('hours_filter') == $hours ? 'selected' : '' }}>
                                        آخر {{ $hours }} ساعة
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('warehouse_id') || request('confirmed_by') || request('delegate_id') || request('size_reviewed') || request('message_confirmed') || request('hours_filter'))
                                <a href="{{ route('admin.orders.pending') }}" class="btn btn-outline-secondary" id="clearFiltersBtn">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    مسح
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- نتائج البحث -->
            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('hours_filter'))
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
                        <div id="order-{{ $order->id }}" class="panel border-2 border-yellow-500 dark:border-yellow-600">
                            <!-- هيدر الكارت -->
                            <div class="flex items-center justify-between mb-4">
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
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        #{{ $orders->firstItem() + $index }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-outline-warning">غير مقيد</span>
                                </div>
                            </div>

                            <!-- معلومات الزبون -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الزبون</span>
                                    <p class="font-medium">{{ $order->customer_name }}</p>
                                </div>
                            </div>

                            <!-- حالة التدقيق والتأكيد للطلبات غير المقيدة -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">تدقيق القياس:</span>
                                        <span class="badge {{ $order->size_review_status_badge_class }}">{{ $order->size_review_status_text }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">حالة الرسالة:</span>
                                        <span class="badge {{ $order->message_confirmation_status_badge_class }}">{{ $order->message_confirmation_status_text }}</span>
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

                            <!-- معلومات المندوب/المدير/المجهز -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    @if($order->delegate)
                                        @php
                                            $userType = $order->delegate->role === 'admin' ? 'مدير' : ($order->delegate->role === 'supplier' ? 'مجهز' : 'مندوب');
                                        @endphp
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">{{ $userType }}</span>
                                        <p class="font-medium">{{ $order->delegate->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $order->delegate->code }}</p>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">-</span>
                                        <p class="font-medium text-gray-400">-</p>
                                    @endif
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

                            <!-- أزرار الإجراءات -->
                            <div class="flex gap-2 flex-wrap">
                                @php
                                    $backRoute = 'admin.orders.pending';
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
                { id: 'warehouseFilterPending', key: 'selectedWarehouse_pending', param: 'warehouse_id' },
                { id: 'searchFilterPending', key: 'selectedSearch_pending', param: 'search' },
                { id: 'confirmedByFilterPending', key: 'selectedConfirmedBy_pending', param: 'confirmed_by' },
                { id: 'delegateIdFilterPending', key: 'selectedDelegateId_pending', param: 'delegate_id' },
                { id: 'sizeReviewedFilterPending', key: 'selectedSizeReviewed_pending', param: 'size_reviewed' },
                { id: 'messageConfirmedFilterPending', key: 'selectedMessageConfirmed_pending', param: 'message_confirmed' },
                { id: 'dateFromFilterPending', key: 'selectedDateFrom_pending', param: 'date_from' },
                { id: 'dateToFilterPending', key: 'selectedDateTo_pending', param: 'date_to' },
                { id: 'timeFromFilterPending', key: 'selectedTimeFrom_pending', param: 'time_from' },
                { id: 'timeToFilterPending', key: 'selectedTimeTo_pending', param: 'time_to' }
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
                const form = document.querySelector('form[action*="orders-pending"]');
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
    </script>
</x-layout.admin>

