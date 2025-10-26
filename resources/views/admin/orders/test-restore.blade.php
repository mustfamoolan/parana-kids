<x-layout.admin>
    <div class="panel">
        <h5 class="font-semibold text-lg dark:text-white-light mb-5">اختبار استرجاع الطلبات</h5>

        @php
            $deletedOrders = \App\Models\Order::onlyTrashed()->with(['delegate', 'items.product'])->take(5)->get();
        @endphp

        @if($deletedOrders->count() > 0)
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>رقم الطلب</th>
                            <th>العميل</th>
                            <th>تاريخ الحذف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deletedOrders as $order)
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->deleted_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <button onclick="testRestore({{ $order->id }})" class="btn btn-success btn-sm">
                                        اختبار الاسترجاع
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <div class="text-gray-500 text-lg">لا توجد طلبات محذوفة للاختبار</div>
            </div>
        @endif

        <div class="mt-5 p-4 bg-blue-50 rounded-lg">
            <h6 class="font-semibold mb-2">ملاحظة مهمة:</h6>
            <p class="text-sm text-gray-600">
                هذا رابط اختبار فقط. للاستخدام العادي، استخدم صفحة الطلبات المحذوفة الرسمية.
            </p>
            <a href="{{ route('admin.orders.deleted') }}" class="btn btn-primary btn-sm mt-2">
                الذهاب لصفحة الطلبات المحذوفة
            </a>
        </div>
    </div>

    <script>
        function testRestore(orderId) {
            if (confirm('هل تريد اختبار استرجاع الطلب رقم ' + orderId + '؟')) {
                // إنشاء form وإرساله
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/orders/${orderId}/restore`;

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
</x-layout.admin>
