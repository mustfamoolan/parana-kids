<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الطلبات المقيدة</h5>
        </div>

            <!-- إحصائيات سريعة - محسنة للجوال -->
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات المقيدة</h6>
                            @php
                                $confirmedQuery = App\Models\Order::where('status', 'confirmed');
                                if (Auth::user()->isSupplier()) {
                                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                                    $confirmedQuery->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                                    });
                                }
                                // فلتر المخزن
                                if (request()->filled('warehouse_id')) {
                                    $confirmedQuery->whereHas('items.product', function($q) {
                                        $q->where('warehouse_id', request('warehouse_id'));
                                    });
                                }
                                $confirmedCount = $confirmedQuery->count();
                            @endphp
                            <p class="text-xl font-bold text-success">{{ $confirmedCount }}</p>
                        </div>
                        <div class="p-2 bg-success/10 rounded-lg">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الإجمالي للطلبات المقيدة</h6>
                                <p class="text-xl font-bold text-success">{{ number_format($confirmedTotalAmount, 0, '.', ',') }} دينار</p>
                            </div>
                            <div class="p-2 bg-success/10 rounded-lg">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    $confirmedProfitAmount = $confirmedProfitAmount ?? 0;
                @endphp
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="panel p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الأرباح المتوقعة - مقيد</h6>
                                <p class="text-xl font-bold text-success">{{ number_format($confirmedProfitAmount, 0, '.', ',') }} دينار</p>
                            </div>
                            <div class="p-2 bg-success/10 rounded-lg">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- فلتر وبحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('admin.orders.confirmed') }}" class="space-y-4">
                    <!-- الصف الأول: البحث -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، العنوان، رابط السوشل ميديا، الملاحظات، أو اسم/كود المنتج..."
                                value="{{ request('search') }}"
                            >
                        </div>
                    </div>

                    <!-- الصف الثاني: المخزن والمجهز والمندوب -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <select name="warehouse_id" class="form-select" id="warehouseFilterConfirmed">
                                <option value="">كل المخازن</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="confirmed_by" class="form-select">
                                <option value="">كل المجهزين والمديرين</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ request('confirmed_by') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} ({{ $supplier->code }}) - {{ $supplier->role === 'admin' ? 'مدير' : 'مجهز' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:w-48">
                            <select name="delegate_id" class="form-select">
                                <option value="">كل المندوبين</option>
                                @foreach($delegates as $delegate)
                                    <option value="{{ $delegate->id }}" {{ request('delegate_id') == $delegate->id ? 'selected' : '' }}>
                                        {{ $delegate->name }} ({{ $delegate->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- الصف الثالث: التاريخ والوقت -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_from"
                                class="form-input"
                                placeholder="من تاريخ"
                                value="{{ request('date_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_to"
                                class="form-input"
                                placeholder="إلى تاريخ"
                                value="{{ request('date_to') }}"
                            >
                        </div>
                        <div class="sm:w-32">
                            <input
                                type="time"
                                name="time_from"
                                class="form-input"
                                placeholder="من الساعة"
                                value="{{ request('time_from') }}"
                            >
                        </div>
                        <div class="sm:w-32">
                            <input
                                type="time"
                                name="time_to"
                                class="form-input"
                                placeholder="إلى الساعة"
                                value="{{ request('time_to') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <select name="hours_ago" class="form-select">
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
                            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('hours_ago') || request('warehouse_id') || request('confirmed_by') || request('delegate_id'))
                                <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-outline-secondary">
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
            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            عرض {{ $orders->total() }} طلب مقيد
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
                        <div id="order-{{ $order->id }}" class="panel border-2 border-green-500 dark:border-green-600">
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
                                    @if($order->delivery_code)
                                        <div class="flex items-center gap-2 mt-1">
                                            <div class="text-sm font-semibold text-success dark:text-success-light">
                                                كود الوسيط: {{ $order->delivery_code }}
                                            </div>
                                            <button
                                                type="button"
                                                onclick="copyDeliveryCode('{{ $order->delivery_code }}', 'delivery')"
                                                class="btn btn-xs btn-outline-success flex items-center gap-1"
                                                title="نسخ كود الوسيط"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                نسخ
                                            </button>
                                        </div>
                                    @endif
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        #{{ $orders->firstItem() + $index }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-outline-success">مقيد</span>
                                </div>
                            </div>

                            <!-- معلومات الزبون -->
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الزبون</span>
                                    <p class="font-medium">{{ $order->customer_name }}</p>
                                </div>
                            </div>

                            <!-- معلومات التقييد للطلبات المقيدة -->
                            @if($order->confirmed_at)
                                <div class="mb-4">
                                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 rounded-lg">
                                        <span class="text-xs font-semibold text-green-700 dark:text-green-400 block mb-1">
                                            <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            معلومات التقييد
                                        </span>
                                        <p class="text-sm text-gray-700 dark:text-gray-300 mb-1">
                                            <span class="font-medium">تاريخ التقييد:</span>
                                            {{ $order->confirmed_at->locale('ar')->translatedFormat('l، d/m/Y') }}
                                            <span class="text-gray-500">في الساعة {{ $order->confirmed_at->format('g:i') }} {{ $order->confirmed_at->format('H') >= 12 ? 'مساءً' : 'نهاراً' }}</span>
                                        </p>
                                        @if($order->confirmedBy)
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                <span class="font-medium">المقيد من:</span>
                                                {{ $order->confirmedBy->name }}
                                                @if($order->confirmedBy->code)
                                                    <span class="text-gray-500">({{ $order->confirmedBy->code }})</span>
                                                @endif
                                            </p>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400">غير محدد</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

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

                            <!-- أزرار الإجراءات -->
                            <div class="flex gap-2 flex-wrap">
                                @php
                                    $backRoute = 'admin.orders.confirmed';
                                    $backParams = urlencode(json_encode(request()->query()));
                                @endphp
                                <a href="{{ route('admin.orders.show', $order) }}?back_route={{ $backRoute }}&back_params={{ $backParams }}" class="btn btn-sm btn-primary flex-1" title="عرض">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    عرض
                                </a>

                                @if($order->canBeEdited())
                                    @can('update', $order)
                                        <a href="{{ route('admin.orders.edit', $order) }}?back_route={{ $backRoute }}&back_params={{ $backParams }}" class="btn btn-sm btn-warning flex-1" title="تعديل">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            تعديل
                                        </a>
                                    @endcan
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
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">لا توجد طلبات مقيدة</h3>
                        <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على أي طلبات مقيدة تطابق معايير البحث</p>
                    </div>
                </div>
            @endif

            <!-- Pagination -->
            <x-pagination :items="$orders" />
    </div>

    <script>
        // Local Storage للمخزن
        document.addEventListener('DOMContentLoaded', function() {
            const warehouseFilter = document.getElementById('warehouseFilterConfirmed');

            if (warehouseFilter) {
                // استرجاع الفلتر من Local Storage عند التحميل فقط إذا لم تكن هناك معاملات في URL
                const urlParams = new URLSearchParams(window.location.search);
                const savedWarehouse = localStorage.getItem('selectedWarehouse_confirmed');

                // لا نقوم بتطبيق الفلتر تلقائياً إلا إذا لم تكن هناك معاملات في URL
                if (savedWarehouse && !warehouseFilter.value && !urlParams.has('warehouse_id') && !urlParams.has('search') && !urlParams.has('confirmed_by') && !urlParams.has('delegate_id') && !urlParams.has('date_from') && !urlParams.has('date_to')) {
                    warehouseFilter.value = savedWarehouse;
                }

                // حفظ الفلتر في Local Storage عند التغيير
                warehouseFilter.addEventListener('change', function() {
                    if (this.value) {
                        localStorage.setItem('selectedWarehouse_confirmed', this.value);
                    } else {
                        localStorage.removeItem('selectedWarehouse_confirmed');
                    }
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
        else if (sessionStorage.getItem('ordersConfirmedScroll')) {
            const scrollPos = sessionStorage.getItem('ordersConfirmedScroll');
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem('ordersConfirmedScroll');
        }

        // حفظ موضع التمرير قبل الانتقال لصفحة أخرى
        const orderLinks = document.querySelectorAll('a[href*="/orders/"]');
        orderLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.setItem('ordersConfirmedScroll', window.scrollY);
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
</x-layout.admin>

