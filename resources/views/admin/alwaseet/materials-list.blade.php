<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">قائمة المواد المطلوبة</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $backRoute = 'admin.alwaseet.print-and-upload-orders';
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
                    @php
                        // جلب رقم الوسيط - نفس منطق print-and-upload-orders (بدون API)
                        $alwaseetCode = null;
                        $shipment = $order->alwaseetShipment;

                        // الأولوية الأولى: qr_id من shipment (الكود الصحيح من الواسط)
                        if ($shipment && isset($shipment->qr_id) && !empty($shipment->qr_id) && trim((string)$shipment->qr_id) !== '') {
                            $alwaseetCode = (string)$shipment->qr_id;
                        }
                        // الأولوية الثانية: delivery_code من Order (إذا كان موجوداً) - أولوية عالية
                        elseif ($order->delivery_code && !empty(trim($order->delivery_code))) {
                            $alwaseetCode = (string)$order->delivery_code;
                        }
                        // الأولوية الثالثة: alwaseet_order_id من shipment (كحل أخير فقط)
                        elseif ($shipment && isset($shipment->alwaseet_order_id) && !empty($shipment->alwaseet_order_id)) {
                            $alwaseetCode = (string)$shipment->alwaseet_order_id;
                        }
                    @endphp
                    <div class="panel relative">
                        <!-- معلومات الطلب -->
                        <div class="mb-4 pb-3 border-b">
                            @if($alwaseetCode)
                                <!-- رقم الوسيط - كبير وواضح -->
                                <div class="mb-3 text-center">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">كود الوسيط</span>
                                    <div class="text-4xl font-bold font-mono" style="color: #2563eb !important;">{{ $alwaseetCode }}</div>
                                </div>
                            @endif

                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ $order->customer_name }}</p>
                            @if($order->delegate)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">المندوب: {{ $order->delegate->name }}</p>
                            @endif

                            <!-- رقم الطلب - في الأسفل -->
                            <h6 class="font-semibold text-sm dark:text-white-light text-gray-600 dark:text-gray-400">طلب #{{ $order->order_number }}</h6>

                            <!-- الأزرار -->
                            <div class="flex flex-col gap-2" x-data="{ open: false }">
                                @can('update', $order)
                                    @if($order->status === 'pending' && $alwaseetCode)
                                        <form method="POST" action="{{ route('admin.alwaseet.orders.confirm', $order) }}" class="w-full" id="confirm-form-{{ $order->id }}">
                                            @csrf
                                            @method('POST')
                                            <input type="hidden" name="back_route" value="admin.alwaseet.materials-list">
                                            <input type="hidden" name="back_params" value="{{ urlencode(json_encode(array_filter([
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
                                            ]))) }}">
                                            <button type="button" @click="open = true" class="btn btn-warning w-full" title="تقيد">
                                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                تقيد
                                            </button>

                                            <!-- modal -->
                                            <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto" :class="open && '!block'">
                                                <div class="flex items-start justify-center min-h-screen px-4" @click.self="open = false">
                                                    <div x-show="open" x-transition x-transition.duration.300 class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-lg my-8">
                                                        <div class="flex py-2 bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-center">
                                                            <span class="flex items-center justify-center w-16 h-16 rounded-full bg-warning/10">
                                                                <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                                </svg>
                                                            </span>
                                                        </div>
                                                        <div class="p-5">
                                                            <h3 class="text-lg font-semibold text-center dark:text-white-light mb-2">تأكيد التقييد</h3>
                                                            <div class="py-5 text-white-dark text-center">
                                                                @if($alwaseetCode)
                                                                    <p class="mb-3">هل أنت متأكد من تقييد الطلب برمز الوسيط:</p>
                                                                    <div class="text-3xl font-bold font-mono mb-3" style="color: #2563eb !important;">{{ $alwaseetCode }}</div>
                                                                    <p class="text-sm text-gray-500 dark:text-gray-400">(طلب #{{ $order->order_number }})</p>
                                                                @else
                                                                    <p>هل أنت متأكد من تقييد الطلب رقم <span class="font-bold" style="color: #2563eb !important;">{{ $order->order_number }}</span>؟</p>
                                                                @endif
                                                            </div>
                                                            <div class="flex justify-end items-center mt-8">
                                                                <button type="button" class="btn btn-outline-secondary" @click="open = false">إلغاء</button>
                                                                <button type="submit" class="btn btn-warning ltr:ml-4 rtl:mr-4" @click="open = false">
                                                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    </svg>
                                                                    تأكيد التقييد
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    @endif
                                @endcan

                                <!-- زر التعديل -->
                                @can('update', $order)
                                    @php
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
                                    <a href="{{ route('admin.orders.edit', $order) }}?back_route=admin.alwaseet.materials-list&back_params={{ urlencode(json_encode($backParams)) }}" class="btn btn-primary w-full">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        تعديل
                                    </a>
                                @endcan

                                <!-- زر الطباعة -->
                                @if($shipment && !empty($shipment->qr_link))
                                    <a href="{{ $shipment->qr_link }}" target="_blank" class="btn btn-info w-full">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2z"></path>
                                        </svg>
                                        طباعة الملف الوسيط
                                    </a>
                                @endif
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
