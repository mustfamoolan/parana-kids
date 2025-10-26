<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الطلبات الغير مقيدة</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.materials') }}" class="btn btn-success">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                    عرض كل المواد المطلوبة
                </a>
            </div>
        </div>

        <div class="panel" x-data="ordersTable">
            <!-- فلاتر البحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="space-y-4">
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
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            @if(request('search') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
                                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
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
            @if(request('search') || request('date_from') || request('date_to'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            عرض {{ $orders->count() }} طلب
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
                                <th>التاريخ</th>
                                <th>عدد المنتجات</th>
                                <th>الإجمالي</th>
                                <th class="text-center">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
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
                                        <div class="text-sm">{{ $order->created_at->format('Y-m-d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $order->created_at->format('H:i') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-outline-primary">{{ $order->items->count() }} منتج</span>
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
                                            <a href="{{ route('admin.orders.process', $order) }}" class="btn btn-sm btn-success">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                تجهيز
                                            </a>
                                            @can('delete', $order)
                                                <button onclick="deleteOrder({{ $order->id }})" class="btn btn-sm btn-danger">
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
                        @if(request('search') || request('date_from') || request('date_to'))
                            لا توجد نتائج للبحث
                        @else
                            لا توجد طلبات غير مقيدة
                        @endif
                    </h6>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        @if(request('search') || request('date_from') || request('date_to'))
                            لم يتم العثور على طلبات تطابق معايير البحث
                        @else
                            جميع الطلبات مقيدة حالياً
                        @endif
                    </p>
                    @if(request('search') || request('date_from') || request('date_to'))
                        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
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
