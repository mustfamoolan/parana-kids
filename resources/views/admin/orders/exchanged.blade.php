<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الطلبات المستبدلة</h5>
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
                <form method="GET" action="{{ route('admin.orders.exchanged') }}" class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، رابط السوشل ميديا، أو اسم المندوب..."
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
                            @if(request('search') || request('date_from') || request('date_to') || request('exchanged_from') || request('exchanged_to'))
                                <a href="{{ route('admin.orders.exchanged') }}" class="btn btn-outline-secondary">
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
                                name="exchanged_from"
                                class="form-input"
                                placeholder="من تاريخ الاستبدال"
                                value="{{ request('exchanged_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="exchanged_to"
                                class="form-input"
                                placeholder="إلى تاريخ الاستبدال"
                                value="{{ request('exchanged_to') }}"
                            >
                        </div>
                    </div>
                </form>
            </div>

            <!-- نتائج البحث -->
            @if(request('search') || request('date_from') || request('date_to') || request('exchanged_from') || request('exchanged_to'))
                <div class="mb-4 p-3 bg-info/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-info">
                            عرض {{ $orders->count() }} طلب مستبدل
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
                            @if(request('exchanged_from') || request('exchanged_to'))
                                @if(request('exchanged_from') && request('exchanged_to'))
                                    مستبدل من {{ request('exchanged_from') }} إلى {{ request('exchanged_to') }}
                                @elseif(request('exchanged_from'))
                                    مستبدل من {{ request('exchanged_from') }}
                                @elseif(request('exchanged_to'))
                                    مستبدل حتى {{ request('exchanged_to') }}
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
                                <th>نوع الاستبدال</th>
                                <th>المنتجات المستبدلة</th>
                                <th>تاريخ الطلب</th>
                                <th>تاريخ الاستبدال</th>
                                <th>المعالج بواسطة</th>
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
                                        @if($order->is_partial_exchange)
                                            <span class="badge badge-info">استبدال جزئي</span>
                                        @else
                                            <span class="badge badge-primary">استبدال كلي</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            {{ $order->exchangeItems->count() }} / {{ $order->items->count() }} منتج
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->created_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->exchanged_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->exchanged_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->processedBy->name ?? 'غير محدد' }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->processedBy->role ?? '' }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="font-semibold text-success">{{ number_format($order->total_amount, 0) }} دينار</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="flex gap-2 justify-center">
                                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                عرض
                                            </a>
                                            @if($order->exchangeItems->count() > 0)
                                                <a href="{{ route('admin.orders.exchange.details', $order) }}" class="btn btn-sm btn-info">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                    </svg>
                                                    تفاصيل الاستبدال
                                                </a>
                                            @endif
                                        </div>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <h6 class="text-lg font-semibold dark:text-white-light mb-2">
                        @if(request('search') || request('date_from') || request('date_to') || request('exchanged_from') || request('exchanged_to'))
                            لا توجد نتائج للبحث
                        @else
                            لا توجد طلبات مستبدلة
                        @endif
                    </h6>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        @if(request('search') || request('date_from') || request('date_to') || request('exchanged_from') || request('exchanged_to'))
                            لم يتم العثور على طلبات تطابق معايير البحث
                        @else
                            لم يتم استبدال أي طلبات بعد
                        @endif
                    </p>
                    @if(request('search') || request('date_from') || request('date_to') || request('exchanged_from') || request('exchanged_to'))
                        <a href="{{ route('admin.orders.exchanged') }}" class="btn btn-outline-secondary">
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
