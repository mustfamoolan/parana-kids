<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">قائمة المواد المطلوبة</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $status = request('status') ?: 'pending';
                    $backRoute = $status === 'pending' ? 'admin.orders.pending' : 'admin.orders.management';
                    $backParams = array_filter([
                        'warehouse_id' => request('warehouse_id'),
                        'search' => request('search'),
                        'confirmed_by' => request('confirmed_by'),
                        'delegate_id' => request('delegate_id'),
                        'size_reviewed' => request('size_reviewed'),
                        'message_confirmed' => request('message_confirmed'),
                        'date_from' => request('date_from'),
                        'date_to' => request('date_to'),
                        'time_from' => request('time_from'),
                        'time_to' => request('time_to'),
                        'status' => $status !== 'pending' ? $status : null,
                    ]);
                @endphp
                <a href="{{ route($backRoute, $backParams) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات
                </a>
            </div>
        </div>

        @if($orders->count() > 0)
            <!-- إحصائيات سريعة -->
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد الطلبات</h6>
                            <p class="text-xl font-bold text-primary">{{ $orders->count() }}</p>
                        </div>
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي القطع</h6>
                            @php
                                $totalPieces = $orders->sum(function($order) {
                                    return $order->items->sum('quantity');
                                });
                            @endphp
                            <p class="text-xl font-bold text-success">{{ $totalPieces }}</p>
                        </div>
                        <div class="p-2 bg-success/10 rounded-lg">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي المواد</h6>
                            @php
                                $totalMaterials = $orders->sum(function($order) {
                                    return $order->items->count();
                                });
                            @endphp
                            <p class="text-xl font-bold text-info">{{ $totalMaterials }}</p>
                        </div>
                        <div class="p-2 bg-info/10 rounded-lg">
                            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة الطلبات -->
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">تفاصيل المواد المطلوبة</h6>
                <p class="text-sm text-gray-500 dark:text-gray-400">جميع المواد المطلوبة من الطلبات الغير مقيدة</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($orders as $order)
                    <div class="panel relative">
                        <!-- معلومات الطلب -->
                        <div class="mb-4 pb-3 border-b">
                            <h6 class="font-semibold text-base dark:text-white-light mb-2">طلب #{{ $order->order_number }}</h6>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $order->customer_name }}</p>
                            @if($order->delegate)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">المندوب: {{ $order->delegate->name }}</p>
                            @endif

                            <!-- الأزرار -->
                            <div class="flex flex-col gap-2">
                                @can('process', $order)
                                    @php
                                        $backRoute = request('status') === 'pending' ? 'admin.orders.pending' : 'admin.orders.management';
                                        $backParams = urlencode(json_encode(array_filter([
                                            'warehouse_id' => request('warehouse_id'),
                                            'search' => request('search'),
                                            'confirmed_by' => request('confirmed_by'),
                                            'delegate_id' => request('delegate_id'),
                                            'size_reviewed' => request('size_reviewed'),
                                            'message_confirmed' => request('message_confirmed'),
                                            'date_from' => request('date_from'),
                                            'date_to' => request('date_to'),
                                            'time_from' => request('time_from'),
                                            'time_to' => request('time_to'),
                                            'status' => request('status'),
                                        ])));
                                    @endphp
                                    <a href="{{ route('admin.orders.process', $order) }}?back_route={{ $backRoute }}&back_params={{ $backParams }}" class="btn btn-success w-full" title="تجهيز">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        تجهيز
                                    </a>
                                @endcan
                            </div>
                        </div>

                        <!-- قائمة المنتجات -->
                        <div class="space-y-3">
                            @foreach($order->items as $item)
                                @if($item->product)
                                    <div class="flex items-start gap-3 pb-3 border-b last:border-0">
                                        <!-- الصورة -->
                                        <div class="flex-shrink-0">
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
                                        </div>

                                        <!-- المعلومات -->
                                        <div class="flex-1 min-w-0">
                                            <h6 class="font-semibold text-sm dark:text-white-light mb-1 line-clamp-1">{{ $item->product->name }}</h6>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mb-2">{{ $item->product->code }}</p>

                                            <!-- القياس والعدد -->
                                            <div class="flex items-center gap-2">
                                                @if($item->size_name)
                                                    <span class="badge badge-outline-primary text-lg font-bold w-16 h-16 flex items-center justify-center rounded-lg border-2">{{ $item->size_name }}</span>
                                                @endif
                                                <span class="badge badge-outline-success text-lg font-bold w-16 h-16 flex items-center justify-center rounded-lg border-2">{{ $item->quantity }}</span>
                                            </div>

                                            <!-- المخزن -->
                                            @if($item->product->warehouse)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">المخزن: {{ $item->product->warehouse->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- لا توجد مواد -->
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">لا توجد مواد مطلوبة</h6>
                <p class="text-gray-500 dark:text-gray-400 mb-4">لا توجد طلبات غير مقيدة حالياً</p>
                <a href="{{ route($backRoute, $backParams) }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات
                </a>
            </div>
        @endif
    </div>

    <!-- Modal لتكبير الصورة -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 z-[9999] hidden items-center justify-center p-4">
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
