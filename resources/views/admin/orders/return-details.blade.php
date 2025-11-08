<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الإرجاع - طلب رقم {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $backUrl = request()->query('back_url');
                    if ($backUrl) {
                        $backUrl = urldecode($backUrl);
                        $parsed = parse_url($backUrl);
                        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
                        if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                            $backUrl = null;
                        }
                    }
                    if (!$backUrl) {
                        $backUrl = route('admin.orders.management', ['status' => 'returned']);
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    الطلبات المسترجعة
                </a>
            </div>
        </div>

        <!-- معلومات الطلب -->
        <div class="panel mb-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                </div>
                <div>
                    <h6 class="text-lg font-semibold">طلب رقم: {{ $order->order_number }}</h6>
                    <p class="text-sm text-gray-500">الزبون: {{ $order->customer_name }} - {{ $order->customer_phone }}</p>
                    <p class="text-sm text-gray-500">المندوب: {{ $order->delegate->name }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">نوع الإرجاع:</span>
                    <span class="font-semibold {{ $order->is_partial_return ? 'text-warning' : 'text-danger' }}">
                        {{ $order->is_partial_return ? 'إرجاع جزئي' : 'إرجاع كلي' }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">تاريخ الإرجاع:</span>
                    <span class="font-semibold">{{ $order->returned_at->format('Y-m-d H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">المعالج بواسطة:</span>
                    <span class="font-semibold">{{ $order->processedBy->name ?? 'غير محدد' }}</span>
                </div>
            </div>

            @if($order->return_notes)
                <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h6 class="font-semibold mb-2">ملاحظات الإرجاع:</h6>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $order->return_notes }}</p>
                </div>
            @endif
        </div>

        <!-- المنتجات المرجعة -->
        <div class="panel">
            <h6 class="text-lg font-semibold mb-4">المنتجات المرجعة</h6>

            @if($order->returnItems->count() > 0)
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>صورة المنتج</th>
                                <th>اسم المنتج</th>
                                <th>كود المنتج</th>
                                <th>القياس</th>
                                <th>الكمية المرجعة</th>
                                <th>سبب الإرجاع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->returnItems as $returnItem)
                                <tr>
                                    <td>
                                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                            @if($returnItem->product->primaryImage)
                                                <img
                                                    src="{{ Storage::url($returnItem->product->primaryImage->path) }}"
                                                    alt="{{ $returnItem->product->name }}"
                                                    class="w-full h-full object-cover cursor-pointer"
                                                    onclick="openImageModal('{{ Storage::url($returnItem->product->primaryImage->path) }}', '{{ $returnItem->product->name }}')"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="font-medium">{{ $returnItem->product->name }}</td>
                                    <td class="text-gray-500">{{ $returnItem->product->code }}</td>
                                    <td>{{ $returnItem->size->size_name ?? 'غير محدد' }}</td>
                                    <td class="text-center">
                                        <span class="font-semibold text-warning">{{ $returnItem->quantity_returned }}</span>
                                    </td>
                                    <td>
                                        <div class="max-w-xs">
                                            <p class="text-sm">{{ $returnItem->return_reason }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                    <h6 class="text-lg font-semibold dark:text-white-light mb-2">لا توجد منتجات مرجعة</h6>
                    <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على تفاصيل الإرجاع</p>
                </div>
            @endif
        </div>

        <!-- المنتجات المتبقية (في حالة الإرجاع الجزئي) -->
        @if($order->is_partial_return && $order->items->count() > $order->returnItems->count())
            <div class="panel mt-5">
                <h6 class="text-lg font-semibold mb-4">المنتجات المتبقية في الطلب</h6>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>صورة المنتج</th>
                                <th>اسم المنتج</th>
                                <th>كود المنتج</th>
                                <th>القياس</th>
                                <th>الكمية المتبقية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                @if(!$order->returnItems->where('order_item_id', $item->id)->first())
                                    <tr>
                                        <td>
                                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden">
                                                @if($item->product->primaryImage)
                                                    <img
                                                        src="{{ Storage::url($item->product->primaryImage->path) }}"
                                                        alt="{{ $item->product->name }}"
                                                        class="w-full h-full object-cover cursor-pointer"
                                                        onclick="openImageModal('{{ Storage::url($item->product->primaryImage->path) }}', '{{ $item->product->name }}')"
                                                    >
                                                @else
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="font-medium">{{ $item->product->name }}</td>
                                        <td class="text-gray-500">{{ $item->product->code }}</td>
                                        <td>{{ $item->size->size ?? 'غير محدد' }}</td>
                                        <td class="text-center">
                                            <span class="font-semibold text-success">{{ $item->quantity }}</span>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal عرض الصورة -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-2xl w-full">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold" id="imageModalTitle">صورة المنتج</h3>
                    <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="text-center">
                    <img id="modalImage" src="" alt="" class="max-w-full max-h-96 mx-auto rounded-lg">
                </div>
            </div>
        </div>
    </div>

    <script>
        function openImageModal(imageSrc, productName) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('modalImage').alt = productName;
            document.getElementById('imageModalTitle').textContent = productName;
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
