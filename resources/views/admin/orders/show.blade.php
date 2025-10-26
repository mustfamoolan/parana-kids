<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات
                </a>
                @if($order->status === 'pending')
                    <a href="{{ route('admin.orders.process', $order) }}" class="btn btn-success">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        تجهيز الطلب
                    </a>
                @elseif($order->status === 'confirmed')
                    <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-outline-success">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        الطلبات المقيدة
                    </a>
                    @if($order->canBeEdited())
                        <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-warning">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            تعديل الطلب
                        </a>
                    @endif

                @elseif($order->status === 'cancelled')
                    <a href="{{ route('admin.orders.cancelled') }}" class="btn btn-outline-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        الطلبات الملغية
                    </a>
                @elseif($order->status === 'returned')
                    <a href="{{ route('admin.orders.returned') }}" class="btn btn-outline-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        الطلبات المسترجعة
                    </a>
                @elseif($order->status === 'exchanged')
                    <a href="{{ route('admin.orders.exchanged') }}" class="btn btn-outline-info">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        الطلبات المستبدلة
                    </a>
                @endif
                @if(in_array($order->status, ['pending', 'confirmed']))
                    @can('delete', $order)
                        <button onclick="deleteOrder({{ $order->id }})" class="btn btn-danger">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            حذف الطلب
                        </button>
                    @endcan
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- معلومات الطلب -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الطلب</h6>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رقم الطلب:</span>
                        <span class="font-medium">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">تاريخ الطلب:</span>
                        <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الحالة:</span>
                        @if($order->status === 'pending')
                            <span class="badge badge-warning">غير مقيد</span>
                        @else
                            <span class="badge badge-success">مقيد</span>
                        @endif
                    </div>
                    @if($order->status === 'confirmed')
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">كود الوسيط:</span>
                            <div class="flex items-center gap-2">
                                <span class="font-medium font-mono text-success">{{ $order->delivery_code }}</span>
                                <button onclick="copyToClipboard('{{ $order->delivery_code }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="نسخ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ التقييد:</span>
                            <span class="font-medium">{{ $order->confirmed_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المقيد بواسطة:</span>
                            <span class="font-medium">{{ $order->confirmedBy->name ?? 'غير محدد' }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">المندوب:</span>
                        <span class="font-medium">{{ $order->delegate->name }} ({{ $order->delegate->code }})</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الإجمالي الكلي:</span>
                        <span class="font-bold text-primary">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                    </div>

                    @if($order->status === 'confirmed')
                        <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-semibold text-green-800 dark:text-green-200">الطلب مقيد</span>
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">
                                <p>تم تقييد الطلب في: {{ $order->confirmed_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->confirmedBy->name ?? 'غير محدد' }}</p>
                                @if($order->delivery_code)
                                    <p>كود الوسيط: {{ $order->delivery_code }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($order->status === 'cancelled')
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span class="font-semibold text-red-800 dark:text-red-200">الطلب ملغي</span>
                            </div>
                            <div class="text-sm text-red-700 dark:text-red-300">
                                <p>تم إلغاء الطلب في: {{ $order->cancelled_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->processedBy->name ?? 'غير محدد' }}</p>
                                @if($order->cancellation_reason)
                                    <p>سبب الإلغاء: {{ $order->cancellation_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($order->status === 'returned')
                        <div class="mt-4 p-3 bg-warning/20 border border-warning rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                <span class="font-semibold text-warning">الطلب مسترجع</span>
                            </div>
                            <div class="text-sm text-warning">
                                <p>تم إرجاع الطلب في: {{ $order->returned_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->processedBy->name ?? 'غير محدد' }}</p>
                                <p>نوع الإرجاع: {{ $order->is_partial_return ? 'جزئي' : 'كلي' }}</p>
                                @if($order->return_notes)
                                    <p>ملاحظات: {{ $order->return_notes }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($order->status === 'exchanged')
                        <div class="mt-4 p-3 bg-info/20 border border-info rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span class="font-semibold text-info">الطلب مستبدل</span>
                            </div>
                            <div class="text-sm text-info">
                                <p>تم استبدال الطلب في: {{ $order->exchanged_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->processedBy->name ?? 'غير محدد' }}</p>
                                <p>نوع الاستبدال: {{ $order->is_partial_exchange ? 'جزئي' : 'كلي' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- معلومات الزبون -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الزبون</h6>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الاسم:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $order->customer_name }}</span>
                            <button onclick="copyToClipboard('{{ $order->customer_name }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="نسخ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                        <div class="flex items-center gap-2">
                            <span class="font-medium">{{ $order->customer_phone }}</span>
                            <button onclick="copyToClipboard('{{ $order->customer_phone }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="نسخ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">العنوان:</span>
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-right max-w-xs">{{ $order->customer_address }}</span>
                            <button onclick="copyToClipboard('{{ $order->customer_address }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-1" title="نسخ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رابط السوشل ميديا:</span>
                        <div class="flex items-start gap-2">
                            <a href="{{ $order->customer_social_link }}" target="_blank" class="font-medium text-primary hover:underline text-right max-w-xs">
                                {{ Str::limit($order->customer_social_link, 30) }}
                            </a>
                            <button onclick="openLink('{{ $order->customer_social_link }}')" class="text-blue-400 hover:text-blue-600 dark:hover:text-blue-300 mt-1" title="فتح الرابط">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </button>
                            <button onclick="copyToClipboard('{{ $order->customer_social_link }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-1" title="نسخ">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @if($order->notes)
                        <div class="flex items-start justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ملاحظات:</span>
                            <div class="flex items-start gap-2">
                                <span class="font-medium text-right max-w-xs">{{ $order->notes }}</span>
                                <button onclick="copyToClipboard('{{ $order->notes }}')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 mt-1" title="نسخ">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- منتجات الطلب -->
        <div class="panel mt-5">
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">منتجات الطلب ({{ $order->items->count() }} منتج)</h6>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>الكود</th>
                            <th>القياس</th>
                            <th>الكمية</th>
                            <th>سعر الوحدة</th>
                            <th>الإجمالي</th>
                            <th>المخزن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($item->product->primaryImage)
                                            <img src="{{ $item->product->primaryImage->image_url }}"
                                                 class="w-12 h-12 object-cover rounded-lg"
                                                 alt="{{ $item->product->name }}">
                                        @else
                                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium">{{ $item->product_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item->product_code }}</td>
                                <td>
                                    <span class="badge badge-outline-primary">{{ $item->size_name }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="font-semibold">{{ $item->quantity }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="font-medium">{{ number_format($item->unit_price, 0) }} دينار</span>
                                </td>
                                <td class="text-center">
                                    <span class="font-bold text-success">{{ number_format($item->subtotal, 0) }} دينار</span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-500">{{ $item->product->warehouse->name }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">الإجمالي الكلي:</th>
                            <th class="text-center">{{ number_format($order->total_amount, 0) }} دينار عراقي</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>


    <script>
        // دالة نسخ النص إلى الحافظة
        function copyToClipboard(text) {
            try {
                navigator.clipboard.writeText(text).then(function() {
                    showCopyNotification('تم نسخ النص بنجاح!');
                });
            } catch (err) {
                // استخدام الطريقة القديمة إذا فشلت الطريقة الحديثة
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopyNotification('تم نسخ النص بنجاح!');
            }
        }

        // دالة فتح الرابط
        function openLink(url) {
            if (url) {
                let link = url;
                // إضافة http:// إذا لم يكن موجوداً
                if (!link.match(/^https?:\/\//)) {
                    link = 'http://' + link;
                }
                window.open(link, '_blank');
            }
        }

        // دالة إظهار إشعار النسخ
        function showCopyNotification(message) {
            // إنشاء عنصر الإشعار
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
            notification.textContent = message;

            // إضافة الإشعار للصفحة
            document.body.appendChild(notification);

            // إزالة الإشعار بعد 3 ثوان
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // دالة حذف الطلب
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
