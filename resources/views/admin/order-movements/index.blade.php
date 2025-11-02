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

        @if($movements->count() > 0)
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">سجل الحركات</h6>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($movements as $movement)
                    <div class="panel">
                        <!-- التاريخ ونوع الحركة -->
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="font-semibold text-base dark:text-white-light">{{ $movement->created_at->format('Y-m-d') }}</div>
                                <div class="text-xs text-gray-500">{{ $movement->created_at->format('H:i') }}</div>
                            </div>
                            <span class="badge bg-{{ $movement->movement_color }}">
                                {{ $movement->movement_type_name }}
                            </span>
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

                        <!-- تفاصيل الطلب -->
                        <div class="border-t pt-3 mb-3">
                            <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">تفاصيل الطلب:</span>
                            @if($movement->order)
                                <div class="space-y-1">
                                    <div>
                                        <a href="{{ route('admin.orders.show', $movement->order) }}" class="font-medium text-primary hover:underline text-sm">
                                            {{ $movement->order->order_number }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $movement->order->customer_name }}</div>
                                    @if($movement->order_status)
                                        <div class="mt-1">
                                            <span class="badge badge-outline-secondary text-xs">
                                                {{ $orderStatuses[$movement->order_status] ?? $movement->order_status }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-500 text-sm">-</span>
                            @endif
                        </div>

                        <!-- المستخدم -->
                        <div class="border-t pt-3 mb-3">
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
</x-layout.admin>
