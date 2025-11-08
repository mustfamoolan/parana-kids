<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إرجاع منتجات الطلب</h5>
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
                        $backUrl = route('admin.orders.show', $order);
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلب
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
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.orders.return.process', $order) }}" id="returnForm">
            @csrf

            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4">اختر المنتجات المراد إرجاعها</h6>

                    <!-- أزرار التحكم -->
                    <div class="flex gap-2 mb-4">
                        <button type="button" onclick="selectAll()" class="btn btn-outline-primary btn-sm">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            تحديد الكل
                        </button>
                        <button type="button" onclick="deselectAll()" class="btn btn-outline-secondary btn-sm">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            إلغاء التحديد
                        </button>
                        <button type="button" onclick="selectAllWithFullQuantity()" class="btn btn-outline-success btn-sm">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            إرجاع كلي
                        </button>
                    </div>

                    <!-- قائمة المنتجات -->
                    <div class="space-y-4">
                        @foreach($order->items as $index => $item)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 return-item" data-item-id="{{ $item->id }}">
                                <div class="flex items-start gap-4">
                                    <!-- Checkbox -->
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="return_items[{{ $index }}][selected]"
                                            class="form-checkbox return-checkbox"
                                            onchange="toggleReturnItem({{ $index }})"
                                        >
                                    </div>

                                    <!-- صورة المنتج -->
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                        @if($item->product->primaryImage)
                                            <img
                                                src="{{ Storage::url($item->product->primaryImage->path) }}"
                                                alt="{{ $item->product->name }}"
                                                class="w-full h-full object-cover"
                                            >
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- تفاصيل المنتج -->
                                    <div class="flex-1">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <h6 class="font-semibold text-lg">{{ $item->product->name }}</h6>
                                                <p class="text-sm text-gray-500">كود: {{ $item->product->code }}</p>
                                                @if($item->size)
                                                    <p class="text-sm text-gray-500">القياس: {{ $item->size->size_name }}</p>
                                                @endif
                                                <p class="text-sm text-gray-500">الكمية الأصلية: {{ $item->quantity }}</p>
                                            </div>

                                            <div class="space-y-3">
                                                <!-- الكمية المراد إرجاعها -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        الكمية المراد إرجاعها
                                                    </label>
                                                    <input
                                                        type="number"
                                                        name="return_items[{{ $index }}][quantity]"
                                                        class="form-input return-quantity"
                                                        min="1"
                                                        max="{{ $item->quantity }}"
                                                        value="{{ $item->quantity }}"
                                                        disabled
                                                        required
                                                    >
                                                </div>

                                                <!-- سبب الإرجاع -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        سبب الإرجاع <span class="text-red-500">*</span>
                                                    </label>
                                                    <textarea
                                                        name="return_items[{{ $index }}][reason]"
                                                        class="form-textarea return-reason"
                                                        rows="2"
                                                        placeholder="أدخل سبب إرجاع هذا المنتج..."
                                                        disabled
                                                        required
                                                    ></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- الحقول المخفية -->
                                <input type="hidden" name="return_items[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="return_items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="hidden" name="return_items[{{ $index }}][size_id]" value="{{ $item->size_id }}">
                                <input type="hidden" name="return_items[{{ $index }}][quantity]" id="hidden_quantity_{{ $index }}" value="{{ $item->quantity }}">
                                <input type="hidden" name="return_items[{{ $index }}][reason]" id="hidden_reason_{{ $index }}" value="">
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- ملاحظات عامة -->
                <div class="mb-5">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        ملاحظات عامة (اختياري)
                    </label>
                    <textarea
                        id="notes"
                        name="notes"
                        class="form-textarea"
                        rows="3"
                        placeholder="أي ملاحظات إضافية حول عملية الإرجاع..."
                    ></textarea>
                </div>

                <!-- ملخص الإرجاع -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-5">
                    <h6 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">ملخص الإرجاع</h6>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">إجمالي المنتجات:</span>
                            <span class="font-semibold" id="totalItems">{{ $order->items->count() }}</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">المنتجات المختارة:</span>
                            <span class="font-semibold text-blue-600" id="selectedItems">0</span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">نوع الإرجاع:</span>
                            <span class="font-semibold text-green-600" id="returnType">-</span>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="flex gap-3 justify-end">
                    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                        إلغاء
                    </a>
                    <button type="submit" class="btn btn-warning" id="submitBtn" disabled>
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        تأكيد الإرجاع
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function selectAll() {
            document.querySelectorAll('.return-checkbox').forEach(checkbox => {
                checkbox.checked = true;
                toggleReturnItem(Array.from(document.querySelectorAll('.return-checkbox')).indexOf(checkbox));
            });
        }

        function deselectAll() {
            document.querySelectorAll('.return-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                toggleReturnItem(Array.from(document.querySelectorAll('.return-checkbox')).indexOf(checkbox));
            });
        }

        function selectAllWithFullQuantity() {
            selectAll();
            updateSummary();
        }

        function toggleReturnItem(index) {
            const checkbox = document.querySelector(`input[name="return_items[${index}][selected]"]`);
            const quantityInput = document.querySelector(`input[name="return_items[${index}][quantity]"]`);
            const reasonTextarea = document.querySelector(`textarea[name="return_items[${index}][reason]"]`);
            const hiddenQuantity = document.getElementById(`hidden_quantity_${index}`);
            const hiddenReason = document.getElementById(`hidden_reason_${index}`);

            if (checkbox.checked) {
                quantityInput.disabled = false;
                reasonTextarea.disabled = false;
                // تحديث الحقول المخفية
                hiddenQuantity.value = quantityInput.value;
                hiddenReason.value = reasonTextarea.value;
            } else {
                quantityInput.disabled = true;
                reasonTextarea.disabled = true;
                quantityInput.value = quantityInput.max;
                reasonTextarea.value = '';
                // مسح الحقول المخفية
                hiddenQuantity.value = '';
                hiddenReason.value = '';
            }

            updateSummary();
        }

        function updateSummary() {
            const selectedCheckboxes = document.querySelectorAll('.return-checkbox:checked');
            const totalItems = document.querySelectorAll('.return-checkbox').length;
            const selectedItems = selectedCheckboxes.length;

            document.getElementById('selectedItems').textContent = selectedItems;

            if (selectedItems === 0) {
                document.getElementById('returnType').textContent = '-';
                document.getElementById('submitBtn').disabled = true;
            } else if (selectedItems === totalItems) {
                document.getElementById('returnType').textContent = 'إرجاع كلي';
                document.getElementById('submitBtn').disabled = false;
            } else {
                document.getElementById('returnType').textContent = 'إرجاع جزئي';
                document.getElementById('submitBtn').disabled = false;
            }
        }

        // تحديث الملخص عند تغيير الكمية
        document.querySelectorAll('.return-quantity').forEach(input => {
            input.addEventListener('input', function() {
                const index = Array.from(document.querySelectorAll('.return-quantity')).indexOf(this);
                const hiddenQuantity = document.getElementById(`hidden_quantity_${index}`);
                if (hiddenQuantity) {
                    hiddenQuantity.value = this.value;
                }
                updateSummary();
            });
        });

        // تحديث الحقول المخفية عند تغيير السبب
        document.querySelectorAll('.return-reason').forEach(textarea => {
            textarea.addEventListener('input', function() {
                const index = Array.from(document.querySelectorAll('.return-reason')).indexOf(this);
                const hiddenReason = document.getElementById(`hidden_reason_${index}`);
                if (hiddenReason) {
                    hiddenReason.value = this.value;
                }
            });
        });

        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('returnForm').addEventListener('submit', function(e) {
            const selectedItems = document.querySelectorAll('.return-checkbox:checked');
            if (selectedItems.length === 0) {
                e.preventDefault();
                alert('يرجى اختيار منتج واحد على الأقل للإرجاع');
                return;
            }

            // التحقق من ملء سبب الإرجاع للمنتجات المختارة
            let allReasonsFilled = true;
            selectedItems.forEach(checkbox => {
                const index = Array.from(document.querySelectorAll('.return-checkbox')).indexOf(checkbox);
                const hiddenReason = document.getElementById(`hidden_reason_${index}`);
                if (!hiddenReason || !hiddenReason.value.trim()) {
                    allReasonsFilled = false;
                }
            });

            if (!allReasonsFilled) {
                e.preventDefault();
                alert('يرجى ملء سبب الإرجاع لجميع المنتجات المختارة');
                return;
            }
        });
    </script>
</x-layout.admin>
