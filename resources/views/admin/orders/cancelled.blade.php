<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الطلبات الملغية</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    الطلبات الغير مقيدة
                </a>
            </div>
        </div>

        <div class="panel">
            <!-- فلاتر البحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('admin.orders.cancelled') }}" class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، رابط السوشل ميديا، سبب الإلغاء، أو اسم المندوب..."
                                value="{{ request('search') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_from"
                                class="form-input"
                                placeholder="من تاريخ الطلب"
                                value="{{ request('date_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_to"
                                class="form-input"
                                placeholder="إلى تاريخ الطلب"
                                value="{{ request('date_to') }}"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            @if(request('search') || request('date_from') || request('date_to') || request('cancelled_from') || request('cancelled_to'))
                                <a href="{{ route('admin.orders.cancelled') }}" class="btn btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    مسح
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="cancelled_from"
                                class="form-input"
                                placeholder="من تاريخ الإلغاء"
                                value="{{ request('cancelled_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="cancelled_to"
                                class="form-input"
                                placeholder="إلى تاريخ الإلغاء"
                                value="{{ request('cancelled_to') }}"
                            >
                        </div>
                    </div>
                </form>
            </div>

            <!-- نتائج البحث -->
            @if(request('search') || request('date_from') || request('date_to') || request('cancelled_from') || request('cancelled_to'))
                <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-red-700 dark:text-red-300">
                            عرض {{ $orders->count() }} طلب ملغي
                            @if(request('search'))
                                للبحث: "{{ request('search') }}"
                            @endif
                            @if(request('date_from') || request('date_to'))
                                @if(request('date_from') && request('date_to'))
                                    من {{ request('date_from') }} إلى {{ request('date_to') }}
                                @elseif(request('date_from'))
                                    من {{ request('date_from') }}
                                @elseif(request('date_to'))
                                    حتى {{ request('date_to') }}
                                @endif
                            @endif
                            @if(request('cancelled_from') || request('cancelled_to'))
                                @if(request('cancelled_from') && request('cancelled_to'))
                                    ملغي من {{ request('cancelled_from') }} إلى {{ request('cancelled_to') }}
                                @elseif(request('cancelled_from'))
                                    ملغي من {{ request('cancelled_from') }}
                                @elseif(request('cancelled_to'))
                                    ملغي حتى {{ request('cancelled_to') }}
                                @endif
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            @if($orders->count() > 0)
                <!-- الجدول -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>اسم الزبون</th>
                                <th>المندوب</th>
                                <th>سبب الإلغاء</th>
                                <th>تاريخ الطلب</th>
                                <th>تاريخ الإلغاء</th>
                                <th>الملغي بواسطة</th>
                                <th>الإجمالي</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td class="whitespace-nowrap">
                                        <span class="font-semibold text-primary">{{ $order->order_number }}</span>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div>
                                            <div class="font-medium">{{ $order->customer_name }}</div>
                                            <div class="text-sm text-gray-500">{{ $order->customer_phone }}</div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <div>
                                            <div class="font-medium">{{ $order->delegate->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $order->delegate->code }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="max-w-xs truncate" title="{{ $order->cancellation_reason }}">
                                            {{ $order->cancellation_reason }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->created_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->cancelled_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->cancelled_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->processedBy->name ?? 'غير محدد' }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->processedBy->role ?? '' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="font-semibold text-success">{{ number_format($order->total_amount, 0) }} دينار</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            عرض
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @else
                <!-- لا توجد طلبات -->
                <div class="text-center py-12">
                    <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    <h6 class="text-lg font-semibold dark:text-white-light mb-2">
                        @if(request('search') || request('date_from') || request('date_to') || request('cancelled_from') || request('cancelled_to'))
                            لا توجد نتائج للبحث
                        @else
                            لا توجد طلبات ملغية
                        @endif
                    </h6>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        @if(request('search') || request('date_from') || request('date_to') || request('cancelled_from') || request('cancelled_to'))
                            لم يتم العثور على طلبات تطابق معايير البحث
                        @else
                            لم يتم إلغاء أي طلبات بعد
                        @endif
                    </p>
                    @if(request('search') || request('date_from') || request('date_to') || request('cancelled_from') || request('cancelled_to'))
                        <a href="{{ route('admin.orders.cancelled') }}" class="btn btn-outline-secondary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            مسح البحث
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
