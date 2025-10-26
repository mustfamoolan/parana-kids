<x-layout.admin>
    <div class="panel">
        <div class="flex justify-between items-center mb-5">
            <div>
                <h5 class="font-semibold text-lg dark:text-white-light">كشف حركة المواد</h5>
                <p class="text-sm text-gray-500">{{ $product->name }} - {{ $size->size }}</p>
            </div>
            <a href="{{ route('admin.products.show', [$warehouse, $product]) }}" class="btn btn-outline-secondary">
                العودة للمنتج
            </a>
        </div>

        <!-- إحصائيات للقياس -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="panel p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الكمية الحالية</h6>
                        <p class="text-xl font-bold text-primary">{{ $stats['current_quantity'] }}</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>
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
                        <p class="text-xl font-bold text-danger">{{ number_format($stats['total_sales']) }}</p>
                    </div>
                    <div class="p-2 bg-danger/10 rounded-lg">
                        <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        @if($movements->count() > 0)
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>التاريخ والوقت</th>
                            <th>نوع الحركة</th>
                            <th>الكمية</th>
                            <th>الرصيد بعد الحركة</th>
                            <th>تفاصيل الطلب</th>
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
                                    <span class="font-semibold {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-semibold">{{ $movement->balance_after }}</span>
                                </td>
                                <td>
                                    @if($movement->order)
                                        <div>
                                            <a href="{{ route('admin.orders.show', $movement->order) }}" class="text-primary hover:underline">
                                                {{ $movement->order->order_number }}
                                            </a>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $movement->order->customer_name }}</div>
                                        @if($movement->order_status)
                                            <div class="text-xs">
                                                <span class="badge badge-outline-secondary">{{ $movement->order_status }}</span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-500">-</span>
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
                <div class="text-gray-500 text-lg">لا توجد حركات لهذا القياس</div>
            </div>
        @endif
    </div>
</x-layout.admin>
