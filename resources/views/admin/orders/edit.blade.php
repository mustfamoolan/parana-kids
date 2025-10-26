<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات المقيدة
                </a>
            </div>
        </div>

        @if(!$order->canBeEdited())
            <div class="panel mb-5">
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <h6 class="font-semibold text-red-700 dark:text-red-300">لا يمكن تعديل هذا الطلب</h6>
                        <p class="text-sm text-red-600 dark:text-red-400">مر أكثر من 5 ساعات على تقييد هذا الطلب</p>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.orders.update', $order) }}" x-data="orderEdit">
            @csrf
            @method('PUT')

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
                            <span class="badge badge-success">مقيد</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المندوب:</span>
                            <span class="font-medium">{{ $order->delegate->name }} ({{ $order->delegate->code }})</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ التقييد:</span>
                            <span class="font-medium">{{ $order->confirmed_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المقيد بواسطة:</span>
                            <span class="font-medium">{{ $order->confirmedBy->name ?? 'غير محدد' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">الإجمالي الكلي:</span>
                            <span class="font-bold text-primary">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                        </div>
                    </div>
                </div>

                <!-- كود الوسيط -->
                <div class="panel">
                    <div class="mb-5">
                        <h6 class="text-lg font-semibold dark:text-white-light">كود الوسيط (شركة التوصيل)</h6>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <label for="delivery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                كود الوسيط <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="delivery_code"
                                name="delivery_code"
                                class="form-input @error('delivery_code') border-red-500 @enderror"
                                placeholder="أدخل كود شركة التوصيل"
                                value="{{ old('delivery_code', $order->delivery_code) }}"
                                required
                                @if(!$order->canBeEdited()) disabled @endif
                            >
                            @error('delivery_code')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
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
                                <th>الصورة</th>
                                <th>كود المنتج</th>
                                <th>اسم المنتج</th>
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
                                        @if($item->product->primaryImage)
                                            <img src="{{ $item->product->primaryImage->image_url }}"
                                                 class="w-16 h-16 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                                                 alt="{{ $item->product->name }}"
                                                 onclick="openImageModal('{{ $item->product->primaryImage->image_url }}', '{{ $item->product->name }}')">
                                        @else
                                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-mono text-sm text-primary">{{ $item->product_code }}</span>
                                    </td>
                                    <td>
                                        <div class="font-medium">{{ $item->product_name }}</div>
                                    </td>
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
                                        <span class="text-sm text-gray-500">{{ $item->product->warehouse->name ?? 'غير محدد' }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-right">الإجمالي الكلي:</th>
                                <th class="text-center">{{ number_format($order->total_amount, 0) }} دينار عراقي</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- معلومات الزبون -->
            <div class="panel mt-5">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الزبون</h6>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            اسم الزبون <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            class="form-input @error('customer_name') border-red-500 @enderror"
                            value="{{ old('customer_name', $order->customer_name) }}"
                            required
                            @if(!$order->canBeEdited()) disabled @endif
                        >
                        @error('customer_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            رقم الهاتف <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="customer_phone"
                            name="customer_phone"
                            class="form-input @error('customer_phone') border-red-500 @enderror"
                            value="{{ old('customer_phone', $order->customer_phone) }}"
                            required
                            @if(!$order->canBeEdited()) disabled @endif
                        >
                        @error('customer_phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="customer_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            العنوان <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="customer_address"
                            name="customer_address"
                            rows="3"
                            class="form-textarea @error('customer_address') border-red-500 @enderror"
                            required
                            @if(!$order->canBeEdited()) disabled @endif
                        >{{ old('customer_address', $order->customer_address) }}</textarea>
                        @error('customer_address')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_social_link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            رابط السوشل ميديا <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="url"
                            id="customer_social_link"
                            name="customer_social_link"
                            class="form-input @error('customer_social_link') border-red-500 @enderror"
                            value="{{ old('customer_social_link', $order->customer_social_link) }}"
                            required
                            @if(!$order->canBeEdited()) disabled @endif
                        >
                        @error('customer_social_link')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ملاحظات
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-textarea @error('notes') border-red-500 @enderror"
                            placeholder="ملاحظات إضافية..."
                            @if(!$order->canBeEdited()) disabled @endif
                        >{{ old('notes', $order->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- أزرار العمل -->
            <div class="panel mt-5">
                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <a href="{{ route('admin.orders.confirmed') }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        إلغاء
                    </a>
                    @if($order->canBeEdited())
                        <button type="submit" class="btn btn-success">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            حفظ التعديلات
                        </button>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Modal لتكبير الصورة -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl max-h-full overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="modalTitle" class="text-lg font-semibold dark:text-white-light">صورة المنتج</h3>
                <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <img id="modalImage" src="" alt="" class="max-w-full max-h-96 mx-auto object-contain">
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderEdit', () => ({
                // يمكن إضافة منطق إضافي هنا إذا لزم الأمر
            }));
        });

        function openImageModal(imageUrl, productName) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalImage').alt = productName;
            document.getElementById('modalTitle').textContent = productName;
            document.getElementById('imageModal').classList.remove('hidden');
            document.getElementById('imageModal').classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.getElementById('imageModal').classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        // إغلاق الـ modal عند الضغط على الخلفية
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // إغلاق الـ modal عند الضغط على Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</x-layout.admin>
