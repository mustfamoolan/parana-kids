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
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ والوقت</th>
                            <th>نوع الحركة</th>
                            <th>المنتج</th>
                            <th>القياس</th>
                            <th>الكمية</th>
                            <th>الرصيد بعد الحركة</th>
                            <th>نوع المصدر</th>
                            <th>المستخدم</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>
                                    <div>{{ $movement->created_at->format('Y-m-d') }}</div>
                                    <div class="text-xs text-gray-500">{{ $movement->created_at->format('h:i A') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $movement->movement_color }}">
                                        {{ $movement->movement_type_name }}
                                    </span>
                                </td>
                                <td>
                                    <div class="font-medium">{{ $movement->product->name }}</div>
                                    <div class="text-xs text-gray-500">كود: {{ $movement->product->code }}</div>
                                    <div class="text-xs text-gray-500">المخزن: {{ $movement->warehouse->name }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $movement->size->size_name }}</span>
                                    <div class="text-xs text-gray-500 mt-1">ID: {{ $movement->size->id }}</div>
                                </td>
                                <td>
                                    <span class="font-semibold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-semibold">{{ $movement->balance_after }}</span>
                                </td>
                                <td>
                                    @if($movement->order_id)
                                        <span class="badge bg-warning">طلب</span>
                                    @else
                                        <span class="badge bg-secondary">إدارة المخزن</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $movement->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $movement->user->role }}</div>
                                </td>
                                <td>
                                    @if($movement->notes)
                                        <span class="text-sm">{{ $movement->notes }}</span>
                                    @else
                                        <span class="text-gray-500">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $movements->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-gray-500 text-lg">لا توجد حركات</div>
            </div>
        @endif
    </div>
</x-layout.admin>
