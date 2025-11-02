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
                    @if(request()->hasAny(['warehouse_id', 'product_id', 'size_id', 'movement_type', 'source_type', 'user_id', 'order_status', 'date_from', 'date_to', 'time_from', 'time_to', 'product_search']))
                        <a href="{{ route('admin.product-movements.index') }}" class="btn btn-outline-secondary">مسح الفلاتر</a>
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
                                <span class="badge bg-primary">{{ $movement->size->size_name }}</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">ID: {{ $movement->size->id }}</div>
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
</x-layout.admin>
