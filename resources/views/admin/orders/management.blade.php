<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة الطلبات</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.materials.management', ['warehouse_id' => request('warehouse_id'), 'status' => request('status')]) }}" class="btn btn-success">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    عرض كل المواد المطلوبة
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
                                    $pendingQuery->whereHas('items.product.warehouse.users', function($q) {
                                        $q->where('user_id', Auth::id())->where('can_manage', true);
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
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات المقيدة</h6>
                            @php
                                $confirmedQuery = App\Models\Order::where('status', 'confirmed');
                                if (Auth::user()->isSupplier()) {
                                    $confirmedQuery->whereHas('items.product.warehouse.users', function($q) {
                                        $q->where('user_id', Auth::id())->where('can_manage', true);
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
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الطلبات المسترجعة</h6>
                            @php
                                $returnedQuery = App\Models\Order::where('status', 'returned');
                                if (Auth::user()->isSupplier()) {
                                    $returnedQuery->whereHas('items.product.warehouse.users', function($q) {
                                        $q->where('user_id', Auth::id())->where('can_manage', true);
                                    });
                                }
                                $returnedCount = $returnedQuery->count();
                            @endphp
                            <p class="text-xl font-bold text-info">{{ $returnedCount }}</p>
                        </div>
                        <div class="p-2 bg-info/10 rounded-lg">
                            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- روابط سريعة - محسنة للجوال -->
            <div class="mb-5">
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                    <a href="{{ route('admin.orders.management') }}"
                       class="btn {{ !request('status') && !request('date_from') && !request('created_today') && !request('created_last_7_days') ? 'btn-primary' : 'btn-outline-primary' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <span class="hidden sm:inline">جميع الطلبات</span>
                        <span class="sm:hidden">الكل</span>
                    </a>
                    <a href="{{ route('admin.orders.management', ['status' => 'pending']) }}"
                       class="btn {{ request('status') === 'pending' ? 'btn-warning' : 'btn-outline-warning' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات غير المقيدة</span>
                        <span class="sm:hidden">غير مقيد</span>
                    </a>
                    <a href="{{ route('admin.orders.management', ['status' => 'confirmed']) }}"
                       class="btn {{ request('status') === 'confirmed' ? 'btn-success' : 'btn-outline-success' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات المقيدة</span>
                        <span class="sm:hidden">مقيد</span>
                    </a>
                    <a href="{{ route('admin.orders.management', ['status' => 'deleted']) }}"
                       class="btn {{ request('status') === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات المحذوفة</span>
                        <span class="sm:hidden">محذوف</span>
                    </a>
                    <a href="{{ route('admin.orders.management', ['status' => 'returned']) }}"
                       class="btn {{ request('status') === 'returned' ? 'btn-info' : 'btn-outline-info' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات المسترجعة</span>
                        <span class="sm:hidden">مسترجع</span>
                    </a>
                    <a href="{{ route('admin.orders.management', ['created_today' => 1]) }}"
                       class="btn {{ request('created_today') ? 'btn-secondary' : 'btn-outline-secondary' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">طلبات اليوم</span>
                        <span class="sm:hidden">اليوم</span>
                    </a>
                    <a href="{{ route('admin.orders.management', ['created_last_7_days' => 1]) }}"
                       class="btn {{ request('created_last_7_days') ? 'btn-secondary' : 'btn-outline-secondary' }} btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">آخر 7 أيام</span>
                        <span class="sm:hidden">أسبوع</span>
                    </a>
                </div>
            </div>

            <!-- فلتر وبحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('admin.orders.management') }}" class="space-y-4">
                    <!-- الصف الأول: البحث والحالة -->
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
                        <div class="sm:w-48">
                            <select name="status" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>غير مقيد</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>مقيد</option>
                                <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>مسترجع</option>
                                <option value="deleted" {{ request('status') == 'deleted' ? 'selected' : '' }}>محذوف</option>
                            </select>
                        </div>
                    </div>

                    <!-- الصف الثاني: التاريخ -->
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
                            <select name="warehouse_id" class="form-select" id="warehouseFilterManagement">
                                <option value="">كل المخازن</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
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
                            @if(request('search') || request('status') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('warehouse_id'))
                                <a href="{{ route('admin.orders.management') }}" class="btn btn-outline-secondary">
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
            @if(request('search') || request('status') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            عرض {{ $orders->total() }} طلب
                            @if(request('search'))
                                للبحث: "{{ request('search') }}"
                            @endif
                            @if(request('status'))
                                - الحالة: {{ ['pending' => 'غير مقيد', 'confirmed' => 'مقيد', 'returned' => 'مسترجع', 'deleted' => 'محذوف'][request('status')] ?? request('status') }}
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

            <!-- الجدول -->
            @if($orders->count() > 0)
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>رقم الطلب</th>
                                <th>اسم الزبون</th>
                                <th>المندوب</th>
                                <th>المخزن</th>
                                <th>التاريخ</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">عدد المنتجات</th>
                                <th class="text-center">الإجمالي</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $index => $order)
                                <tr class="{{ $order->trashed() ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                                    <!-- الرقم التسلسلي -->
                                    <td class="text-center font-semibold">{{ $orders->firstItem() + $index }}</td>

                                    <!-- رقم الطلب -->
                                    <td class="whitespace-nowrap">
                                        @php
                                            // تنظيف رقم الهاتف من المسافات والرموز
                                            $cleanPhone = preg_replace('/[^0-9]/', '', $order->customer_phone);
                                            // إضافة كود الدولة إذا لم يكن موجوداً (العراق: 964)
                                            if (!str_starts_with($cleanPhone, '964')) {
                                                $cleanPhone = '964' . ltrim($cleanPhone, '0');
                                            }
                                            $whatsappUrl = "https://wa.me/{$cleanPhone}?text=" . urlencode("مرحباً، بخصوص الطلب رقم: {$order->order_number}");
                                        @endphp
                                        <a href="{{ $whatsappUrl }}" target="_blank"
                                           class="font-semibold text-primary hover:text-success transition-colors inline-flex items-center gap-1"
                                           title="فتح واتساب">
                                            <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                            </svg>
                                            {{ $order->order_number }}
                                        </a>
                                    </td>

                                    <!-- اسم الزبون -->
                                    <td class="whitespace-nowrap">
                                        <div>
                                            <div class="font-medium">{{ $order->customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $order->customer_phone }}</div>
                                        </div>
                                    </td>

                                    <!-- المندوب -->
                                    <td class="whitespace-nowrap">
                                        <div>
                                            <div class="font-medium">{{ $order->delegate->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $order->delegate->code }}</div>
                                        </div>
                                    </td>

                                    <!-- المخزن -->
                                    <td class="whitespace-nowrap">
                                        @php
                                            $orderWarehouses = $order->items->pluck('product.warehouse')->unique()->filter();
                                        @endphp
                                        @foreach($orderWarehouses as $warehouse)
                                            <span class="badge badge-outline-info mb-1">{{ $warehouse->name }}</span>
                                            @if(!$loop->last)<br>@endif
                                        @endforeach
                                    </td>

                                    <!-- التاريخ -->
                                    <td class="whitespace-nowrap">
                                        <div class="text-sm">{{ $order->created_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->created_at->format('H:i') }}</div>
                                    </td>

                                    <!-- الحالة -->
                                    <td class="text-center">
                                        @if($order->trashed())
                                            <span class="badge badge-outline-danger">محذوف</span>
                                        @else
                                            @php
                                                $statusBadge = [
                                                    'pending' => ['class' => 'badge-outline-warning', 'text' => 'غير مقيد'],
                                                    'confirmed' => ['class' => 'badge-outline-success', 'text' => 'مقيد'],
                                                    'returned' => ['class' => 'badge-outline-info', 'text' => 'مسترجع'],
                                                ];
                                                $badge = $statusBadge[$order->status] ?? ['class' => 'badge-outline-secondary', 'text' => $order->status];
                                            @endphp
                                            <span class="badge {{ $badge['class'] }}">{{ $badge['text'] }}</span>
                                        @endif
                                    </td>

                                    <!-- عدد المنتجات -->
                                    <td class="text-center">
                                        <span class="badge badge-outline-primary">{{ $order->items->count() }} منتج</span>
                                    </td>

                                    <!-- الإجمالي -->
                                    <td class="text-center">
                                        <span class="font-semibold text-success">{{ number_format($order->total_amount, 0) }} دينار</span>
                                    </td>

                                    <!-- الإجراءات -->
                                    <td class="text-center">
                                        <div class="flex gap-2 justify-center flex-wrap">
                                            @if($order->trashed())
                                                <!-- أزرار الطلبات المحذوفة -->
                                                <a href="{{ route('admin.orders.show', $order->id) }}" class="btn btn-sm btn-primary" title="عرض التفاصيل">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('admin.orders.restore', $order) }}" class="inline-block">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('هل تريد استرجاع هذا الطلب؟')" title="استرجاع">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.orders.forceDelete', $order) }}" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من الحذف النهائي؟ لن يمكن استرجاع هذا الطلب!')" title="حذف نهائي">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @else
                                                <!-- أزرار الطلبات العادية -->
                                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary" title="عرض">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>

                                                @if($order->status === 'pending')
                                                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-warning" title="تعديل">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </a>
                                                    <a href="{{ route('admin.orders.process', $order) }}" class="btn btn-sm btn-success" title="تجهيز">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="panel">
                    <div class="flex flex-col items-center justify-center py-10">
                        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">لا توجد طلبات</h3>
                        <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على أي طلبات تطابق معايير البحث</p>
                    </div>
                </div>
            @endif

            <!-- Pagination -->
            <x-pagination :items="$orders" />
    </div>

    <script>
        // Local Storage للمخزن
        document.addEventListener('DOMContentLoaded', function() {
            const warehouseFilter = document.getElementById('warehouseFilterManagement');

            if (warehouseFilter) {
                // استرجاع الفلتر من Local Storage عند التحميل
                const savedWarehouse = localStorage.getItem('selectedWarehouse_management');
                if (savedWarehouse && !warehouseFilter.value) {
                    warehouseFilter.value = savedWarehouse;
                    // تطبيق الفلتر تلقائياً
                    warehouseFilter.form.submit();
                }

                // حفظ الفلتر في Local Storage عند التغيير
                warehouseFilter.addEventListener('change', function() {
                    if (this.value) {
                        localStorage.setItem('selectedWarehouse_management', this.value);
                    } else {
                        localStorage.removeItem('selectedWarehouse_management');
                    }
                });
            }
        });

        function deleteOrder(orderId) {
            if (confirm('هل أنت متأكد من حذف هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/orders/${orderId}`;

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                form.appendChild(methodField);
                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</x-layout.default>
