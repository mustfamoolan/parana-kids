<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">قائمة المواد المطلوبة (مرتبة حسب الكود)</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    // Route للعودة من زر العودة في أعلى الصفحة (يعود إلى print-and-upload-orders)
                    $backRouteForButton = 'admin.alwaseet.print-and-upload-orders';
                    // Route للعودة من صفحة التعديل (يعود إلى materials-list-grouped)
                    $backRouteForEdit = 'admin.alwaseet.materials-list-grouped';
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
                        'alwaseet_sent' => request('alwaseet_sent'),
                        'alwaseet_complete' => request('alwaseet_complete'),
                    ]);
                @endphp
                <a href="{{ route($backRouteForButton, $backParams) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات
                </a>
            </div>
        </div>

        @if(count($materials) > 0)
            <!-- إحصائيات سريعة -->
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي المواد</h6>
                            <p class="text-xl font-bold text-primary">{{ count($materials) }}</p>
                        </div>
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي القطع</h6>
                            @php
                                $totalPieces = collect($materials)->sum('total_quantity');
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
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد الطلبات</h6>
                            @php
                                $totalOrders = collect($materials)->pluck('orders')->flatten(1)->unique('order_id')->count();
                            @endphp
                            <p class="text-xl font-bold text-info">{{ $totalOrders }}</p>
                        </div>
                        <div class="p-2 bg-info/10 rounded-lg">
                            <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- قائمة المواد -->
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">تفاصيل المواد المطلوبة (مرتبة حسب الكود)</h6>
                <p class="text-sm text-gray-500 dark:text-gray-400">جميع المواد المطلوبة مرتبة حسب كود المنتج، مع تجميع الأحجام المختلفة لنفس الكود</p>
            </div>

            <!-- بطاقات المواد -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($materials as $index => $material)
                    @if($material['product'])
                        <div class="panel relative">
                            <!-- رقم تسلسلي -->
                            <div class="absolute top-2 left-2 bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold shadow-lg z-10">
                                {{ $loop->iteration }}
                            </div>

                            <!-- الصورة والاسم والكود -->
                            <div class="flex items-start gap-4 mb-4">
                                <div class="flex-shrink-0">
                                    @if($material['product']->primaryImage)
                                        <img src="{{ $material['product']->primaryImage->image_url }}"
                                             class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity"
                                             alt="{{ $material['product']->name }}"
                                             onclick="openImageModal('{{ $material['product']->primaryImage->image_url }}', '{{ $material['product']->name }}')">
                                    @else
                                        <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h6 class="font-semibold text-base dark:text-white-light mb-1 line-clamp-2">{{ $material['product']->name }}</h6>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $material['product_code'] }}</p>
                                </div>
                            </div>

                            <!-- القياس والعدد بجانب بعض -->
                            <div class="mb-4 flex items-center justify-center gap-4">
                                @if($material['size_name'])
                                    <span class="badge badge-outline-primary text-2xl font-bold w-20 h-20 flex items-center justify-center rounded-lg border-2">{{ $material['size_name'] }}</span>
                                @endif
                                <span class="badge badge-outline-success text-2xl font-bold w-20 h-20 flex items-center justify-center rounded-lg border-2">{{ number_format((int)($material['total_quantity'] ?? 0), 0, '.', ',') }}</span>
                            </div>

                            <!-- التفاصيل -->
                            <div class="space-y-2 border-t pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">المخزن:</span>
                                    <span class="text-sm font-medium">{{ $material['product']->warehouse->name ?? 'غير محدد' }}</span>
                                </div>
                                @if(!empty($material['orders']))
                                    <div class="flex items-start justify-between pt-2 border-t">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">الطلبات:</span>
                                        <div class="text-left">
                                            @foreach(array_slice($material['orders'], 0, 3) as $order)
                                                <a href="{{ route('admin.orders.edit', $order['order_id']) }}?back_route={{ $backRouteForEdit }}&back_params={{ urlencode(json_encode($backParams)) }}"
                                                   class="block text-xs text-primary hover:underline mb-1"
                                                   title="تعديل الطلب">
                                                    {{ $order['order_number'] }} ({{ $order['quantity'] }})
                                                </a>
                                            @endforeach
                                            @if(count($material['orders']) > 3)
                                                <span class="text-xs text-gray-400">+ {{ count($material['orders']) - 3 }} طلبات أخرى</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
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
                <a href="{{ route($backRouteForButton, $backParams) }}" class="btn btn-primary">
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

