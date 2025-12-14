<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الاستبدال - طلب رقم {{ $order->order_number }}</h5>
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
                        $backUrl = route('admin.orders.management', ['status' => 'exchanged']);
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    الطلبات المستبدلة
                </a>
            </div>
        </div>

        <!-- معلومات الطلب -->
        <div class="panel mb-5">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <div>
                    <h6 class="text-lg font-semibold">طلب رقم: {{ $order->order_number }}</h6>
                    <p class="text-sm text-gray-500">الزبون: {{ $order->customer_name }} - {{ $order->customer_phone }}</p>
                    <p class="text-sm text-gray-500">المندوب: {{ $order->delegate ? $order->delegate->name : '-' }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600 dark:text-gray-400">نوع الاستبدال:</span>
                    <span class="font-semibold {{ $order->is_partial_exchange ? 'text-info' : 'text-primary' }}">
                        {{ $order->is_partial_exchange ? 'استبدال جزئي' : 'استبدال كلي' }}
                    </span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">تاريخ الاستبدال:</span>
                    <span class="font-semibold">{{ $order->exchanged_at->format('Y-m-d H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-600 dark:text-gray-400">المعالج بواسطة:</span>
                    <span class="font-semibold">{{ $order->processedBy->name ?? 'غير محدد' }}</span>
                </div>
            </div>
        </div>

        <!-- المنتجات المستبدلة -->
        <div class="panel">
            <h6 class="text-lg font-semibold mb-4">تفاصيل الاستبدال</h6>

            @if($order->exchangeItems->count() > 0)
                <div class="space-y-6">
                    @foreach($order->exchangeItems as $exchangeItem)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- المنتج القديم -->
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                                    <h6 class="font-semibold text-red-800 dark:text-red-200 mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        المنتج القديم (المرجع)
                                    </h6>

                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                            @if($exchangeItem->oldProduct->primaryImage)
                                                <img
                                                    src="{{ Storage::url($exchangeItem->oldProduct->primaryImage->path) }}"
                                                    alt="{{ $exchangeItem->oldProduct->name }}"
                                                    class="w-full h-full object-cover cursor-pointer"
                                                    onclick="openImageModal('{{ Storage::url($exchangeItem->oldProduct->primaryImage->path) }}', '{{ $exchangeItem->oldProduct->name }}')"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex-1">
                                            <h6 class="font-semibold text-lg">{{ $exchangeItem->oldProduct->name }}</h6>
                                            <p class="text-sm text-gray-500">كود: {{ $exchangeItem->oldProduct->code }}</p>
                                            <p class="text-sm text-gray-500">القياس: {{ $exchangeItem->oldSize->size_name ?? 'غير محدد' }}</p>
                                            <p class="text-sm text-gray-500">الكمية: {{ $exchangeItem->old_quantity }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- المنتج الجديد -->
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                    <h6 class="font-semibold text-green-800 dark:text-green-200 mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        المنتج الجديد (البديل)
                                    </h6>

                                    <div class="flex items-start gap-4">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                            @if($exchangeItem->newProduct->primaryImage)
                                                <img
                                                    src="{{ Storage::url($exchangeItem->newProduct->primaryImage->path) }}"
                                                    alt="{{ $exchangeItem->newProduct->name }}"
                                                    class="w-full h-full object-cover cursor-pointer"
                                                    onclick="openImageModal('{{ Storage::url($exchangeItem->newProduct->primaryImage->path) }}', '{{ $exchangeItem->newProduct->name }}')"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex-1">
                                            <h6 class="font-semibold text-lg">{{ $exchangeItem->newProduct->name }}</h6>
                                            <p class="text-sm text-gray-500">كود: {{ $exchangeItem->newProduct->code }}</p>
                                            <p class="text-sm text-gray-500">القياس: {{ $exchangeItem->newSize->size_name ?? 'غير محدد' }}</p>
                                            <p class="text-sm text-gray-500">الكمية: {{ $exchangeItem->new_quantity }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- سبب الاستبدال -->
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <h6 class="font-semibold mb-2">سبب الاستبدال:</h6>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $exchangeItem->exchange_reason }}</p>
                            </div>

                            <!-- تأثير المخزون -->
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        <span class="font-semibold text-red-800 dark:text-red-200">إضافة للمخزن</span>
                                    </div>
                                    <p class="text-red-700 dark:text-red-300">
                                        {{ $exchangeItem->oldProduct->name }} - {{ $exchangeItem->oldSize->size_name ?? 'غير محدد' }}
                                        (+{{ $exchangeItem->old_quantity }} قطعة)
                                    </p>
                                </div>

                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                        <span class="font-semibold text-green-800 dark:text-green-200">خصم من المخزن</span>
                                    </div>
                                    <p class="text-green-700 dark:text-green-300">
                                        {{ $exchangeItem->newProduct->name }} - {{ $exchangeItem->newSize->size_name ?? 'غير محدد' }}
                                        (-{{ $exchangeItem->new_quantity }} قطعة)
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <h6 class="text-lg font-semibold dark:text-white-light mb-2">لا توجد منتجات مستبدلة</h6>
                    <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على تفاصيل الاستبدال</p>
                </div>
            @endif
        </div>

        <!-- المنتجات المتبقية (في حالة الاستبدال الجزئي) -->
        @if($order->is_partial_exchange && $order->items->count() > $order->exchangeItems->count())
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
                                @if(!$order->exchangeItems->where('order_item_id', $item->id)->first())
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
