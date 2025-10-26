<x-layout.default>
    <div class="panel">
        <div class="flex justify-between items-center mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light">الطلبات المحذوفة</h5>
            <div class="text-sm text-gray-500">
                ملاحظة: يجب استخدام زر "استرجاع الطلب" وليس الوصول للرابط مباشرة
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="mb-5">
            <form method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="البحث في رقم الطلب، اسم العميل، الهاتف..."
                           class="form-input">
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           placeholder="من تاريخ" class="form-input">
                </div>
                <div>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           placeholder="إلى تاريخ" class="form-input">
                </div>
                <div>
                    <input type="time" name="time_from" value="{{ request('time_from') }}"
                           placeholder="من الساعة" class="form-input">
                </div>
                <div>
                    <input type="time" name="time_to" value="{{ request('time_to') }}"
                           placeholder="إلى الساعة" class="form-input">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">بحث</button>
                </div>
                @if(request()->hasAny(['search', 'date_from', 'date_to', 'time_from', 'time_to']))
                    <div>
                        <a href="{{ route('delegate.orders.deleted') }}" class="btn btn-outline-secondary">مسح الفلاتر</a>
                    </div>
                @endif
            </form>
        </div>

        @if($orders->count() > 0)
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>تفاصيل العميل</th>
                            <th>تفاصيل الطلب</th>
                            <th>تاريخ الحذف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                            <tr>
                                <td>
                                    <div class="font-semibold">{{ $order->order_number }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->status }}</div>
                                </td>
                                <td>
                                    <div class="space-y-1">
                                        <div><strong>الاسم:</strong> {{ $order->customer_name }}</div>
                                        <div><strong>الهاتف:</strong> {{ $order->customer_phone }}</div>
                                        <div><strong>العنوان:</strong> {{ $order->customer_address }}</div>
                                        @if($order->customer_social_link)
                                            <div><strong>السوشل:</strong>
                                                <a href="{{ $order->customer_social_link }}" target="_blank" class="text-blue-500 hover:underline">
                                                    {{ Str::limit($order->customer_social_link, 30) }}
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="space-y-1">
                                        <div><strong>إجمالي:</strong> {{ number_format($order->total_amount, 2) }} ريال</div>
                                        <div><strong>المنتجات:</strong></div>
                                        <div class="text-xs">
                                            @foreach($order->items as $item)
                                                <div class="flex justify-between">
                                                    <span>{{ $item->product_name }}</span>
                                                    <span class="text-gray-500">({{ $item->quantity }})</span>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if($order->notes)
                                            <div><strong>ملاحظات:</strong> {{ $order->notes }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $order->deleted_at->format('Y-m-d') }}</div>
                                    <div class="text-xs text-gray-500">{{ $order->deleted_at->format('H:i') }}</div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <button onclick="restoreOrder({{ $order->id }})"
                                                class="btn btn-success btn-sm">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                            </svg>
                                            استرجاع الطلب
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-gray-500 text-lg">لا توجد طلبات محذوفة</div>
            </div>
        @endif
    </div>

    <script>
        function restoreOrder(orderId) {
            if (confirm('هل أنت متأكد من استرجاع هذا الطلب؟ سيتم التحقق من توفر المنتجات تلقائياً.')) {
                // إنشاء form وإرساله
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/delegate/orders/${orderId}/restore`;

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</x-layout.default>
