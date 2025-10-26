<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('delegate.orders.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات
                </a>
            </div>
        </div>

        <!-- معلومات الطلب الأساسية -->
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary">{{ $order->order_number }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">رقم الطلب</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold {{ $order->status === 'pending' ? 'text-warning' : 'text-success' }}">
                        {{ $order->status === 'pending' ? 'غير مقيد' : 'مقيد' }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">حالة الطلب</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-success">{{ number_format($order->total_amount, 0) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">دينار عراقي</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-info">{{ $order->items->sum('quantity') }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي القطع</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- معلومات الزبون -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الزبون</h6>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الاسم:</span>
                        <span class="font-medium">{{ $order->customer_name }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                        <span class="font-medium">{{ $order->customer_phone }}</span>
                    </div>

                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">العنوان:</span>
                        <div class="text-right max-w-xs">
                            <span class="font-medium">{{ $order->customer_address }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">السوشل ميديا:</span>
                        <a href="{{ $order->customer_social_link }}" target="_blank" class="font-medium text-primary hover:underline">
                            {{ Str::limit($order->customer_social_link, 30) }}
                        </a>
                    </div>

                    @if($order->notes)
                        <div class="flex items-start justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ملاحظات:</span>
                            <div class="text-right max-w-xs">
                                <span class="font-medium">{{ $order->notes }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">تاريخ الطلب:</span>
                        <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الطلب -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">إحصائيات الطلب</h6>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                        <span class="font-medium">{{ $order->items->count() }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">إجمالي القطع:</span>
                        <span class="font-medium">{{ $order->items->sum('quantity') }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">المبلغ الإجمالي:</span>
                        <span class="font-medium text-success">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">المندوب:</span>
                        <span class="font-medium">{{ $order->delegate->name }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">السلة الأصلية:</span>
                        <span class="font-medium">{{ $order->cart->cart_name }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- منتجات الطلب -->
        <div class="panel mt-6">
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">منتجات الطلب</h6>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-right py-3 px-4 font-semibold dark:text-white-light">المنتج</th>
                            <th class="text-right py-3 px-4 font-semibold dark:text-white-light">القياس</th>
                            <th class="text-right py-3 px-4 font-semibold dark:text-white-light">الكمية</th>
                            <th class="text-right py-3 px-4 font-semibold dark:text-white-light">سعر الوحدة</th>
                            <th class="text-right py-3 px-4 font-semibold dark:text-white-light">المجموع الفرعي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <!-- صورة المنتج -->
                                        <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                            @if($item->product && $item->product->primaryImage)
                                                <img src="{{ $item->product->primaryImage->image_url }}"
                                                     alt="{{ $item->product_name }}"
                                                     class="w-full h-full object-cover">
                                            @else
                                                <div class="w-full h-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium dark:text-white-light">{{ $item->product_name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $item->product_code }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="badge badge-outline-primary">{{ $item->size_name }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium">{{ $item->quantity }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium">{{ number_format($item->unit_price, 0) }} دينار عراقي</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-success">{{ number_format($item->subtotal, 0) }} دينار عراقي</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                            <td colspan="4" class="py-3 px-4 text-right font-semibold dark:text-white-light">الإجمالي:</td>
                            <td class="py-3 px-4">
                                <span class="text-xl font-bold text-success">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- ملاحظة مهمة -->
        <div class="panel mt-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-500 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h6 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-2">معلومات مهمة</h6>
                    <div class="space-y-2 text-sm text-blue-600 dark:text-blue-400">
                        <p>• تم خصم جميع المنتجات من المخزون عند إرسال الطلب</p>
                        <p>• الطلب في حالة "{{ $order->status === 'pending' ? 'غير مقيد' : 'مقيد' }}" حالياً</p>
                        <p>• يمكنك التواصل مع الإدارة لتأكيد أو تعديل حالة الطلب</p>
                        <p>• جميع المعلومات محفوظة ويمكن الرجوع إليها في أي وقت</p>
                    </div>
                </div>
            </div>

            @if($order->status === 'pending')
                <!-- أزرار العمل للطلبات غير المقيدة -->
                <div class="panel mt-6">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('delegate.orders.edit', $order) }}" class="btn btn-warning btn-lg flex-1 sm:flex-none">
                            <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            تعديل الطلب
                        </a>

                        <form method="POST" action="{{ route('delegate.orders.cancel', $order) }}" class="flex-1 sm:flex-none" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن وحذف الطلب نهائياً.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-lg w-full">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                إلغاء الطلب
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if(in_array($order->status, ['pending', 'confirmed']))
                <!-- زر حذف الطلب -->
                <div class="panel mt-6">
                    <div class="flex justify-center">
                        <button onclick="deleteOrder({{ $order->id }})" class="btn btn-outline-danger btn-lg">
                            <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            حذف الطلب
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function deleteOrder(orderId) {
            if (confirm('هل أنت متأكد من حذف هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/delegate/orders/${orderId}`;

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
</x-layout.default>
