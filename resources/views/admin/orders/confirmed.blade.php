<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الطلبات المقيدة</h5>
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
                <form method="GET" action="{{ route('admin.orders.confirmed') }}" class="space-y-4">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، رابط السوشل ميديا، كود الوسيط، أو اسم المندوب..."
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
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('confirmed_from') || request('confirmed_to'))
                                <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-outline-secondary">
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
                                name="confirmed_from"
                                class="form-input"
                                placeholder="من تاريخ التقييد"
                                value="{{ request('confirmed_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="confirmed_to"
                                class="form-input"
                                placeholder="إلى تاريخ التقييد"
                                value="{{ request('confirmed_to') }}"
                            >
                        </div>
                    </div>
                </form>
            </div>

            <!-- نتائج البحث -->
            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to') || request('confirmed_from') || request('confirmed_to'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            عرض {{ $orders->count() }} طلب مقيد
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
                            @if(request('confirmed_from') || request('confirmed_to'))
                                @if(request('confirmed_from') && request('confirmed_to'))
                                    مقيد من {{ request('confirmed_from') }} إلى {{ request('confirmed_to') }}
                                @elseif(request('confirmed_from'))
                                    مقيد من {{ request('confirmed_from') }}
                                @elseif(request('confirmed_to'))
                                    مقيد حتى {{ request('confirmed_to') }}
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
                                <th>كود الوسيط</th>
                                <th>تاريخ الطلب</th>
                                <th>تاريخ التقييد</th>
                                <th>المقيد بواسطة</th>
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
                                    <td class="whitespace-nowrap">
                                        <span class="font-mono text-sm text-success">{{ $order->delivery_code }}</span>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->created_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->confirmed_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->confirmed_at->format('h:i A') }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm">{{ $order->confirmedBy->name ?? 'غير محدد' }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->confirmedBy->role ?? '' }}</div>
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
                                            @if($order->canBeEdited())
                                                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-warning">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    تعديل
                                                </a>
                                            @else
                                                <span class="btn btn-sm btn-outline-secondary cursor-not-allowed" title="لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                    مقفل
                                                </span>
                                            @endif
                                            <button onclick="returnOrder({{ $order->id }})" class="btn btn-sm btn-danger" title="استرجاع الطلب وإرجاع جميع المنتجات للمخزن">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                                </svg>
                                                استرجاع
                                            </button>
                                            @can('delete', $order)
                                                <button onclick="deleteOrder({{ $order->id }})" class="btn btn-sm btn-outline-danger" title="حذف الطلب وإرجاع جميع المنتجات للمخزن">
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    حذف
                                                </button>
                                            @endcan
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h6 class="text-lg font-semibold dark:text-white-light mb-2">
                        @if(request('search') || request('date_from') || request('date_to') || request('confirmed_from') || request('confirmed_to'))
                            لا توجد نتائج للبحث
                        @else
                            لا توجد طلبات مقيدة
                        @endif
                    </h6>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        @if(request('search') || request('date_from') || request('date_to') || request('confirmed_from') || request('confirmed_to'))
                            لم يتم العثور على طلبات تطابق معايير البحث
                        @else
                            لم يتم تقييد أي طلبات بعد
                        @endif
                    </p>
                    @if(request('search') || request('date_from') || request('date_to') || request('confirmed_from') || request('confirmed_to'))
                        <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-outline-secondary">
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

    <script>
        function returnOrder(orderId) {
            if (confirm('هل أنت متأكد من استرجاع هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن وتغيير حالة الطلب إلى مسترجع.')) {
                // إنشاء form بسيط
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/orders/${orderId}/return-direct`;

                // إضافة CSRF token
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                // إرسال النموذج
                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteOrder(orderId) {
            if (confirm('هل أنت متأكد من حذف هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن.')) {
                // إنشاء form وإرساله
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
</x-layout.admin>
