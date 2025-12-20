<x-layout.admin>
    <div class="panel">
        <div class="flex justify-between items-center mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light">كشف حركة المواد</h5>
        </div>

        <!-- فلاتر البحث -->
        <div class="mb-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <div>
                    <label class="form-label">المخزن</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">جميع المخازن</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">اسم المنتج أو الكود</label>
                    <input type="text" name="product_search" value="{{ request('product_search') }}" class="form-input" placeholder="ابحث بالاسم أو الكود...">
                </div>

                <div>
                    <label class="form-label">القياس</label>
                    <select name="size_id" class="form-select">
                        <option value="">جميع القياسات</option>
                        @foreach($sizes as $size)
                            <option value="{{ $size->id }}" {{ request('size_id') == $size->id ? 'selected' : '' }}>
                                {{ $size->size_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">نوع الحركة</label>
                    <select name="movement_type" class="form-select">
                        <option value="">جميع الحركات</option>
                        @foreach($movementTypes as $key => $name)
                            <option value="{{ $key }}" {{ request('movement_type') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">نوع المصدر</label>
                    <select name="source_type" class="form-select">
                        <option value="">جميع المصادر</option>
                        @foreach($sourceTypes as $key => $name)
                            <option value="{{ $key }}" {{ request('source_type') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">فلتر الإرجاعات</label>
                    <select name="show_returns_only" class="form-select">
                        <option value="">جميع الحركات</option>
                        <option value="1" {{ request('show_returns_only') == '1' ? 'selected' : '' }}>الإرجاعات فقط</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">المستخدم</label>
                    <select name="user_id" class="form-select">
                        <option value="">جميع المستخدمين</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->role }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-full border-t pt-4">
                    <h6 class="font-semibold mb-3 text-lg">التاريخ والوقت</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="form-label">من تاريخ</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">إلى تاريخ</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">من وقت</label>
                            <input type="time" name="time_from" value="{{ request('time_from') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">إلى وقت</label>
                            <input type="time" name="time_to" value="{{ request('time_to') }}" class="form-input">
                        </div>
                    </div>
                </div>

                <div class="col-span-full flex gap-2">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="{{ route('admin.product-movements.index') }}" id="clearFiltersBtn" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        مسح الفلاتر
                    </a>
                </div>
            </form>
        </div>

        @if($movements->count() > 0)
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">سجل الحركات</h6>
            </div>

            <!-- إحصائيات الإرجاعات -->
            @if(isset($returnsStats) && $returnsStats !== null)
                <div class="mb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="panel">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد القطع المرجعة</h6>
                                <p class="text-xl font-bold text-primary">{{ number_format($returnsStats['items_count'], 0, '.', ',') }} قطعة</p>
                            </div>
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="flex items-center justify-between">
                            <div>
                                <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الكلي للإرجاعات</h6>
                                <p class="text-xl font-bold text-danger">{{ number_format($returnsStats['total_amount'], 0, '.', ',') }} دينار</p>
                            </div>
                            <div class="p-2 bg-danger/10 rounded-lg">
                                <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($movements as $movement)
                    <div class="panel">
                        <!-- التاريخ ونوع الحركة -->
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="font-semibold text-base dark:text-white-light mb-1">{{ $movement->created_at->format('Y-m-d') }}</div>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-bold {{ $movement->created_at->format('H') < 12 ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' }}">
                                        {{ $movement->created_at->format('h:i') }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-xs font-bold {{ $movement->created_at->format('H') < 12 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' }}">
                                        {{ $movement->created_at->format('H') < 12 ? 'صباحاً' : 'مساءً' }}
                                    </span>
                                </div>
                            </div>
                            <span class="badge bg-{{ $movement->movement_color }}">
                                {{ $movement->movement_type_name }}
                            </span>
                        </div>

                        <!-- معلومات المنتج -->
                        <div class="border-t pt-3 mb-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المنتج:</span>
                            <div class="font-medium text-sm">{{ $movement->product->name }}</div>
                            <div class="text-xs text-gray-500 mt-1">كود: {{ $movement->product->code }}</div>
                            <div class="text-xs text-gray-500 mt-1">المخزن: {{ $movement->warehouse->name }}</div>
                        </div>

                        <!-- القياس -->
                        <div class="border-t pt-3 mb-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">القياس:</span>
                                @if($movement->size)
                                    <span class="badge bg-primary">{{ $movement->size->size_name }}</span>
                                @else
                                    <span class="badge bg-danger">قياس محذوف</span>
                                @endif
                            </div>
                            @if($movement->size)
                                <div class="text-xs text-gray-500 mt-1">ID: {{ $movement->size->id }}</div>
                            @else
                                <div class="text-xs text-gray-500 mt-1">ID: {{ $movement->size_id }} (محذوف)</div>
                            @endif
                        </div>

                        <!-- الكمية والرصيد -->
                        <div class="space-y-2 border-t pt-3 mb-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">الكمية:</span>
                                <span class="font-bold text-lg {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 0, '.', ',') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 dark:text-gray-400">الرصيد بعد الحركة:</span>
                                <span class="font-semibold text-primary">{{ number_format($movement->balance_after, 0, '.', ',') }}</span>
                            </div>
                        </div>

                        <!-- نوع المصدر والمستخدم -->
                        <div class="border-t pt-3 mb-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">نوع المصدر:</span>
                                @if($movement->order_id)
                                    <span class="badge bg-warning">طلب</span>
                                @else
                                    <span class="badge bg-secondary">إدارة المخزن</span>
                                @endif
                            </div>
                            @if($movement->order)
                                <div class="border-t pt-2 mt-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">رقم الطلب:</span>
                                    @php
                                        $backRoute = 'admin.product-movements.index';
                                        $backParams = request()->except(['page']);
                                        $backParamsJson = json_encode($backParams);
                                    @endphp
                                    <a href="{{ route('admin.orders.show', $movement->order) }}?back_route={{ urlencode($backRoute) }}&back_params={{ urlencode($backParamsJson) }}" class="font-bold text-lg text-primary hover:underline">
                                        {{ $movement->order->order_number }}
                                    </a>
                                </div>
                            @endif
                            <div class="border-t pt-2 mt-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المستخدم:</span>
                                <div class="font-medium text-sm">{{ $movement->user->name }}</div>
                                <span class="badge badge-outline-secondary text-xs mt-1">
                                    @if($movement->user->role === 'admin')
                                        مدير
                                    @elseif($movement->user->role === 'supplier')
                                        مجهز
                                    @else
                                        مندوب
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- الملاحظات -->
                        @if($movement->notes)
                            <div class="border-t pt-3">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">ملاحظات:</span>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $movement->notes }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $movements->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-gray-500 text-lg">لا توجد حركات</div>
            </div>
        @endif
    </div>

    <script>
        // معالجة زر مسح الفلتر - مسح جميع الفلاتر
        document.addEventListener('DOMContentLoaded', function() {
            const clearFiltersBtn = document.getElementById('clearFiltersBtn');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    // قائمة جميع حقول الفلاتر
                    const filterFields = [
                        'warehouse_id',
                        'product_search',
                        'size_id',
                        'movement_type',
                        'source_type',
                        'user_id',
                        'date_from',
                        'date_to',
                        'time_from',
                        'time_to',
                        'show_returns_only'
                    ];

                    // مسح جميع الحقول
                    filterFields.forEach(fieldName => {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (field) {
                            field.value = '';
                        }
                    });

                    // الانتقال إلى الصفحة بدون parameters
                    window.location.href = '{{ route('admin.product-movements.index') }}';
                });
            }
        });
    </script>
</x-layout.admin>
