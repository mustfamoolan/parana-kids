<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">طلبات المتجر (تطبيق Paraná Kids)</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.materials.management', array_filter(['warehouse_id' => request('warehouse_id'), 'status' => request('status') ?: 'pending'])) }}" class="btn btn-success">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    عرض كل المواد المطلوبة
                </a>
            </div>
        </div>

            <!-- إحصائيات سريعة - محسنة للجوال -->
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="panel p-4 border-2 border-warning/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات غير المقيدة (معلق)</h6>
                            @php
                                $pendingQuery = App\Models\Order::where('status', 'pending')->where('source', 'store');
                                if (Auth::user()->isSupplier()) {
                                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                                    $pendingQuery->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                                    });
                                }
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
                <div class="panel p-4 border-2 border-success/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات المقيدة (قيد التجهيز)</h6>
                            @php
                                $confirmedQuery = App\Models\Order::where('status', 'confirmed')->where('source', 'store');
                                if (Auth::user()->isSupplier()) {
                                    $accessibleWarehouseIds = Auth::user()->warehouses->pluck('id')->toArray();
                                    $confirmedQuery->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                                        $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                                    });
                                }
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

            <!-- مبالغ الطلبات -->
            @if(auth()->user()->isAdmin())
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="panel p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي مبالغ "المعلق"</h6>
                                <p class="text-xl font-bold text-warning">{{ number_format($pendingTotalAmount, 0, '.', ',') }} دينار</p>
                            </div>
                            <div class="p-2 bg-warning/10 rounded-lg">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="panel p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي مبالغ "المقيد"</h6>
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

            <!-- فلاتر الحالة السريعة -->
            <div class="mb-5">
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                    @php
                        $allFilters = request()->except(['status', 'page']);
                    @endphp
                    <a href="{{ route('admin.orders.store_management', array_filter($allFilters)) }}"
                       class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                        جميع طلبات المتجر
                    </a>
                    <a href="{{ route('admin.orders.store_management', array_filter(array_merge($allFilters, ['status' => 'pending']))) }}"
                       class="btn {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }} btn-sm">
                        معلق
                    </a>
                    <a href="{{ route('admin.orders.store_management', array_filter(array_merge($allFilters, ['status' => 'confirmed']))) }}"
                       class="btn {{ request('status') === 'confirmed' ? 'btn-success' : 'btn-outline-success' }} btn-sm">
                        مقيد (قيد التجهيز)
                    </a>
                </div>
            </div>

            <!-- نموذج البحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('admin.orders.store_management') }}" class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف..."
                                value="{{ request('search') }}"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">بحث</button>
                            @if(request()->hasAny(['search', 'status', 'warehouse_id']))
                                <a href="{{ route('admin.orders.store_management') }}" class="btn btn-outline-secondary">مسح</a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- عرض الطلبات -->
            @if($orders->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($orders as $index => $order)
                        <div class="panel border-2 {{ $order->status === 'pending' ? 'border-warning' : 'border-success' }}">
                            <div class="flex justify-between items-center mb-4">
                                <h6 class="font-bold text-primary">#{{ $order->order_number }}</h6>
                                <span class="badge {{ $order->status === 'pending' ? 'badge-outline-warning' : 'badge-outline-success' }}">
                                    {{ $order->app_status }}
                                </span>
                            </div>

                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">الزبون:</span>
                                    <span class="font-medium">{{ $order->customer_name }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">الهاتف:</span>
                                    <span class="font-medium">{{ $order->customer_phone }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">العنوان:</span>
                                    <span class="font-medium">{{ $order->customer_address }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">الإجمالي:</span>
                                    <span class="font-bold text-primary">{{ number_format($order->total_amount, 0) }} د.ع</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">التاريخ:</span>
                                    <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>

                            <div class="mt-4 flex gap-2">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary flex-1">عرض التفاصيل</a>
                                @if($order->status === 'pending')
                                    <a href="{{ route('admin.orders.process', $order) }}" class="btn btn-sm btn-success flex-1">تجهيز الطلب</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-5">
                    {{ $orders->links() }}
                </div>
            @else
                <div class="panel text-center py-10">
                    <p class="text-gray-500">لا توجد طلبات متجر حالياً.</p>
                </div>
            @endif
    </div>
</x-layout.admin>
