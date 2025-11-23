<x-layout.admin>
    <div class="panel">
        <div class="flex justify-between items-center mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light">كشف حركة الطلبات</h5>
        </div>

        <!-- إحصائيات سريعة -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي الإضافات</h6>
                        <p class="text-xl font-bold text-success">{{ number_format($stats['total_additions']) }}</p>
                    </div>
                    <div class="p-2 bg-success/10 rounded-lg">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي المبيعات</h6>
                        <p class="text-xl font-bold text-primary">{{ number_format($stats['total_sales']) }}</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي الاسترجاعات</h6>
                        <p class="text-xl font-bold text-info">{{ number_format($stats['total_returns']) }}</p>
                    </div>
                    <div class="p-2 bg-info/10 rounded-lg">
                        <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="mb-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
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

                <div>
                    <label class="form-label">حالة الطلب</label>
                    <select name="order_status" class="form-select">
                        <option value="">جميع الحالات</option>
                        @foreach($orderStatuses as $key => $name)
                            <option value="{{ $key }}" {{ request('order_status') == $key ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input">
                </div>

                <div>
                    <label class="form-label">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    @if(request()->hasAny(['warehouse_id', 'movement_type', 'user_id', 'order_status', 'date_from', 'date_to']))
                        <a href="{{ route('admin.order-movements.index') }}" class="btn btn-outline-secondary">مسح الفلاتر</a>
                    @endif
                </div>
            </form>
        </div>

        @if($movements->count() > 0 && isset($groupedMovements) && $groupedMovements->count() > 0)
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">سجل الحركات</h6>
                <p class="text-sm text-gray-500 dark:text-gray-400">تم تجميع الحركات حسب الطلب</p>
            </div>

            <div class="space-y-4">
                @foreach($groupedMovements as $group)
                    <div class="panel">
                        <!-- رأس البطاقة: معلومات الطلب -->
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4 pb-4 border-b">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    @if($group['order'])
                                        <a href="{{ route('admin.orders.show', $group['order']) }}" class="font-bold text-lg text-primary hover:underline">
                                            {{ $group['order_number'] }}
                                        </a>
                                    @else
                                        <span class="font-bold text-lg text-gray-500">طلب محذوف</span>
                                    @endif
                                    @if($group['order_status'])
                                        <span class="badge badge-outline-secondary">
                                            {{ $orderStatuses[$group['order_status']] ?? $group['order_status'] }}
                                        </span>
                                    @endif
                                    <span class="badge bg-{{ $group['movements']->first()->movement_color }}">
                                        {{ $group['movements']->first()->movement_type_name }}
                                    </span>
                                </div>
                                <div class="space-y-1 text-sm">
                                    @if($group['customer_name'])
                                        <div class="text-gray-600 dark:text-gray-400">
                                            <strong>الزبون:</strong> {{ $group['customer_name'] }}
                                        </div>
                                    @endif
                                    <div class="text-gray-500 dark:text-gray-500">
                                        <strong>التاريخ:</strong> {{ $group['created_at']->format('Y-m-d H:i') }}
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-500">
                                        <strong>المستخدم:</strong> {{ $group['user']->name }}
                                        <span class="badge badge-outline-secondary text-xs mr-2">
                                            @if($group['user']->role === 'admin')
                                                مدير
                                            @elseif($group['user']->role === 'supplier')
                                                مجهز
                                            @else
                                                مندوب
                                            @endif
                                        </span>
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-500">
                                        <strong>عدد المنتجات:</strong> {{ $group['movements']->count() }} منتج/قياس
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-500">
                                        <strong>إجمالي الكمية:</strong>
                                        <span class="font-bold {{ $group['total_quantity'] > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $group['total_quantity'] > 0 ? '+' : '' }}{{ number_format($group['total_quantity'], 0, '.', ',') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- قائمة المنتجات والقياسات -->
                        <div class="space-y-3">
                            <h6 class="font-semibold text-base mb-3">المنتجات والقياسات:</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($group['movements'] as $movement)
                                    <div class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-800/50">
                                        <!-- معلومات المنتج -->
                                        @if($movement->product)
                                            <div class="mb-2">
                                                @if($movement->product->warehouse)
                                                    <a href="{{ route('admin.warehouses.products.show', [$movement->product->warehouse, $movement->product]) }}" class="font-medium text-sm text-primary hover:underline">
                                                        {{ $movement->product->name }}
                                                    </a>
                                                @else
                                                    <div class="font-medium text-sm">{{ $movement->product->name }}</div>
                                                @endif
                                                <div class="text-xs text-gray-500 dark:text-gray-400">كود: {{ $movement->product->code }}</div>
                                            </div>
                                        @endif

                                        <!-- القياس -->
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">القياس:</span>
                                            @if($movement->size)
                                                <span class="badge bg-primary text-xs">{{ $movement->size->size_name }}</span>
                                            @else
                                                <span class="badge bg-danger text-xs">قياس محذوف</span>
                                            @endif
                                        </div>

                                        <!-- المخزن -->
                                        @if($movement->warehouse)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                المخزن: {{ $movement->warehouse->name }}
                                            </div>
                                        @endif

                                        <!-- الكمية والرصيد -->
                                        <div class="space-y-1 pt-2 border-t">
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">الكمية:</span>
                                                <span class="font-semibold text-sm {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity, 0, '.', ',') }}
                                                </span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-xs text-gray-500 dark:text-gray-400">الرصيد:</span>
                                                <span class="font-semibold text-xs text-primary">{{ number_format($movement->balance_after, 0, '.', ',') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
</x-layout.admin>
