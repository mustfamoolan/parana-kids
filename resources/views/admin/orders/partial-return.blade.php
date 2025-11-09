<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إرجاع جزئي - منتجات الطلب</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.partial-returns.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <!-- عرض أخطاء Validation -->
        @if($errors->any())
            <div class="panel mb-5 border-l-4 border-red-500">
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h6 class="font-semibold text-red-700 dark:text-red-300 mb-2">حدث خطأ أثناء معالجة الإرجاع:</h6>
                        <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="panel mb-5 border-l-4 border-red-500">
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-red-700 dark:text-red-300">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="panel mb-5 border-l-4 border-green-500">
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20">
                    <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- معلومات الطلب -->
        <div class="panel mb-5">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-success/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h6 class="text-lg font-semibold">طلب رقم: {{ $order->order_number }}</h6>
                    <p class="text-sm text-gray-500">الزبون: {{ $order->customer_name }} - {{ $order->customer_phone }}</p>
                    @if($order->delivery_code)
                        <div class="flex items-center gap-2 mt-2">
                            <p class="text-sm text-gray-500">كود الوسيط: <span class="font-medium">{{ $order->delivery_code }}</span></p>
                            <button
                                type="button"
                                onclick="copyDeliveryCode('{{ $order->delivery_code }}', 'delivery')"
                                class="btn btn-xs btn-outline-primary flex items-center gap-1"
                                title="نسخ كود الوسيط"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.orders.partial-return.process', $order) }}" id="partialReturnForm">
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
                    </div>

                    <!-- قائمة المنتجات -->
                    <div class="space-y-4">
                        @foreach($order->items as $index => $item)
                            @php
                                // حساب الكمية الأصلية = الكمية الحالية + مجموع الإرجاعات
                                $returnedQuantity = $item->returnItems()->sum('quantity_returned');
                                $originalQuantity = $item->quantity + $returnedQuantity;
                                $remainingQuantity = $item->quantity; // الكمية المتبقية = الكمية الحالية
                            @endphp
                            @if($remainingQuantity > 0)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3 sm:p-4 return-item" data-item-id="{{ $item->id }}">
                                    <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
                                        <!-- Checkbox -->
                                        <div class="flex items-center flex-shrink-0">
                                            <input
                                                type="checkbox"
                                                name="return_items[{{ $index }}][selected]"
                                                class="form-checkbox return-checkbox w-5 h-5"
                                                data-item-id="{{ $item->id }}"
                                                onchange="toggleReturnItem({{ $item->id }})"
                                            >
                                        </div>

                                        <!-- صورة المنتج -->
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden flex-shrink-0">
                                            @if($item->product && $item->product->primaryImage && $item->product->primaryImage->image_path)
                                                <img
                                                    src="{{ Storage::url($item->product->primaryImage->image_path) }}"
                                                    alt="{{ $item->product->name }}"
                                                    class="w-full h-full object-cover"
                                                    onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-gray-400\'><svg class=\'w-8 h-8\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z\'></path></svg></div>'"
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
                                        <div class="flex-1 min-w-0 w-full sm:w-auto">
                                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                                                <div class="min-w-0">
                                                    <h6 class="font-semibold text-base sm:text-lg truncate">{{ $item->product->name ?? 'N/A' }}</h6>
                                                    <p class="text-sm text-gray-500">كود: {{ $item->product->code ?? 'N/A' }}</p>
                                                    @if($item->size)
                                                        <p class="text-sm text-gray-500">القياس: {{ $item->size->size_name }}</p>
                                                    @endif
                                                    <div class="mt-2 space-y-1">
                                                        <p class="text-sm text-gray-500">
                                                            الكمية الأصلية: <span class="font-medium">{{ $originalQuantity }}</span>
                                                        </p>
                                                        <p class="text-sm text-gray-500">
                                                            الكمية المتبقية: <span class="font-medium text-primary">{{ $remainingQuantity }}</span>
                                                        </p>
                                                        @if($returnedQuantity > 0)
                                                            <p class="text-sm text-warning">
                                                                تم إرجاع: {{ $returnedQuantity }}
                                                            </p>
                                                        @endif
                                                        <p class="text-sm font-semibold text-success mt-2">
                                                            السعر: <span id="item_price_{{ $item->id }}" data-remaining-quantity="{{ $remainingQuantity }}">{{ number_format($item->unit_price * $remainingQuantity, 0, '.', ',') }}</span> دينار
                                                            <input type="hidden" id="item_unit_price_{{ $item->id }}" value="{{ $item->unit_price }}">
                                                            <input type="hidden" id="item_max_quantity_{{ $item->id }}" value="{{ $remainingQuantity }}">
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="space-y-3">
                                                    <!-- الكمية المراد إرجاعها -->
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                            الكمية المراد إرجاعها
                                                        </label>
                                                        <div class="flex items-center gap-2">
                                                            <button
                                                                type="button"
                                                                onclick="decreaseQuantity({{ $item->id }}, {{ $remainingQuantity }})"
                                                                class="btn btn-sm btn-outline-secondary quantity-btn flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                                                id="decrease_btn_{{ $item->id }}"
                                                                data-item-id="{{ $item->id }}"
                                                                disabled
                                                            >
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                                                </svg>
                                                            </button>
                                                            <input
                                                                type="number"
                                                                name="return_items[{{ $index }}][quantity]"
                                                                class="form-input return-quantity text-center flex-1"
                                                                min="1"
                                                                max="{{ $remainingQuantity }}"
                                                                value="{{ $remainingQuantity }}"
                                                                disabled
                                                                required
                                                                id="quantity_input_{{ $item->id }}"
                                                                data-item-id="{{ $item->id }}"
                                                                data-max-quantity="{{ $remainingQuantity }}"
                                                                onchange="updateQuantityInput({{ $item->id }}, {{ $remainingQuantity }})"
                                                            >
                                                            <button
                                                                type="button"
                                                                onclick="increaseQuantity({{ $item->id }}, {{ $remainingQuantity }})"
                                                                class="btn btn-sm btn-outline-primary quantity-btn flex-shrink-0 w-10 h-10 p-0 flex items-center justify-center"
                                                                id="increase_btn_{{ $item->id }}"
                                                                data-item-id="{{ $item->id }}"
                                                                disabled
                                                            >
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                        <p class="text-xs text-gray-500 mt-1 text-center">الحد الأقصى: {{ $remainingQuantity }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- الحقول المخفية -->
                                    <input type="hidden" name="return_items[{{ $index }}][order_item_id]" value="{{ $item->id }}" id="order_item_id_{{ $item->id }}">
                                    <input type="hidden" name="return_items[{{ $index }}][product_id]" value="{{ $item->product_id }}" id="product_id_{{ $item->id }}">
                                    <input type="hidden" name="return_items[{{ $index }}][size_id]" value="{{ $item->size_id ?? '' }}" id="size_id_{{ $item->id }}">
                                    <input type="hidden" name="return_items[{{ $index }}][quantity]" id="hidden_quantity_{{ $item->id }}" value="{{ $remainingQuantity }}">
                                </div>
                            @endif
                        @endforeach

                        @if($order->items->every(fn($item) => $item->remaining_quantity <= 0))
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h6 class="text-lg font-semibold dark:text-white-light mb-2">لا توجد منتجات قابلة للإرجاع</h6>
                                <p class="text-gray-500 dark:text-gray-400">تم إرجاع جميع منتجات هذا الطلب</p>
                            </div>
                        @endif
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

                <!-- ملخص الإرجاع والأسعار -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-5">
                    <h6 class="font-semibold text-blue-800 dark:text-blue-200 mb-4">ملخص الإرجاع والأسعار</h6>
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-3">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">إجمالي المنتجات:</span>
                                <span class="font-semibold" id="totalItems">{{ $order->items->count() }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">المنتجات المختارة:</span>
                                <span class="font-semibold text-blue-600" id="selectedItems">0</span>
                            </div>
                        </div>

                        <!-- الأسعار -->
                        <div class="border-t border-blue-200 dark:border-blue-700 pt-3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 dark:text-gray-400">مجموع أسعار المنتجات المتبقية:</span>
                                <span class="font-semibold text-lg" id="totalProductsPrice">
                                    @php
                                        $totalProducts = $order->items->sum(function($item) {
                                            return $item->unit_price * $item->quantity;
                                        });
                                    @endphp
                                    {{ number_format($totalProducts, 0, '.', ',') }}
                                </span>
                                <span class="text-gray-500">دينار</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-blue-200 dark:border-blue-700 pt-2 mt-2">
                                <span class="text-gray-700 dark:text-gray-300 font-semibold text-lg">السعر الكلي:</span>
                                <span class="font-bold text-xl text-primary" id="totalAmountWithDelivery">
                                    {{ number_format($totalProducts, 0, '.', ',') }}
                                </span>
                                <span class="text-gray-500 font-semibold">دينار</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-blue-200 dark:border-blue-700 pt-2 mt-2" id="returnAmountSection" style="display: none;">
                                <span class="text-gray-700 dark:text-gray-300 font-semibold">المبلغ المراد إرجاعه:</span>
                                <span class="font-bold text-lg text-warning" id="returnAmount">0</span>
                                <span class="text-gray-500">دينار</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-blue-200 dark:border-blue-700 pt-2 mt-2" id="remainingAmountSection" style="display: none;">
                                <span class="text-gray-700 dark:text-gray-300 font-semibold text-lg">المبلغ المتبقي بعد الإرجاع:</span>
                                <span class="font-bold text-xl text-success" id="remainingAmount">
                                    {{ number_format($totalProducts, 0, '.', ',') }}
                                </span>
                                <span class="text-gray-500 font-semibold">دينار</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="flex gap-3 justify-end">
                    <a href="{{ route('admin.orders.partial-returns.index') }}" class="btn btn-outline-secondary">
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
                const itemId = checkbox.getAttribute('data-item-id');
                if (itemId) {
                    toggleReturnItem(parseInt(itemId));
                }
            });
        }

        function deselectAll() {
            document.querySelectorAll('.return-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                const itemId = checkbox.getAttribute('data-item-id');
                if (itemId) {
                    toggleReturnItem(parseInt(itemId));
                }
            });
        }

        function toggleReturnItem(itemId) {
            const checkbox = document.querySelector(`.return-checkbox[data-item-id="${itemId}"]`);
            const quantityInput = document.getElementById(`quantity_input_${itemId}`);
            const hiddenQuantity = document.getElementById(`hidden_quantity_${itemId}`);
            const decreaseBtn = document.getElementById(`decrease_btn_${itemId}`);
            const increaseBtn = document.getElementById(`increase_btn_${itemId}`);
            const maxQuantity = parseInt(quantityInput?.getAttribute('data-max-quantity') || quantityInput?.getAttribute('max') || 1);

            if (checkbox && checkbox.checked) {
                if (quantityInput) {
                    quantityInput.disabled = false;
                    // استخدام القيمة الافتراضية (الكمية المتبقية) بدلاً من 1
                    quantityInput.value = maxQuantity;
                    if (hiddenQuantity) hiddenQuantity.value = maxQuantity;
                }
                if (decreaseBtn) decreaseBtn.disabled = maxQuantity <= 1; // عند 1 لا يمكن النقصان
                if (increaseBtn) increaseBtn.disabled = maxQuantity <= 1; // إذا كان الحد الأقصى 1 لا يمكن الزيادة
            } else {
                if (quantityInput) {
                    quantityInput.disabled = true;
                    // إعادة تعيين القيمة إلى القيمة الافتراضية (الكمية المتبقية)
                    quantityInput.value = maxQuantity;
                    if (hiddenQuantity) hiddenQuantity.value = maxQuantity;
                }
                if (decreaseBtn) decreaseBtn.disabled = true;
                if (increaseBtn) increaseBtn.disabled = true;
            }

            updateSummary();
            updateItemPrice(itemId);
            updateAmounts();
        }

        function increaseQuantity(itemId, maxQuantity) {
            const quantityInput = document.getElementById(`quantity_input_${itemId}`);
            const hiddenQuantity = document.getElementById(`hidden_quantity_${itemId}`);
            const decreaseBtn = document.getElementById(`decrease_btn_${itemId}`);
            const increaseBtn = document.getElementById(`increase_btn_${itemId}`);

            if (quantityInput && hiddenQuantity) {
                let currentValue = parseInt(quantityInput.value) || maxQuantity;
                if (currentValue < maxQuantity) {
                    currentValue++;
                    quantityInput.value = currentValue;
                    hiddenQuantity.value = currentValue;

                    // تحديث حالة الأزرار
                    if (decreaseBtn) decreaseBtn.disabled = false;
                    if (increaseBtn) increaseBtn.disabled = currentValue >= maxQuantity;

                    // تحديث السعر والمبلغ
                    updateItemPrice(itemId);
                    updateAmounts();
                }
            }
        }

        function decreaseQuantity(itemId, maxQuantity) {
            const quantityInput = document.getElementById(`quantity_input_${itemId}`);
            const hiddenQuantity = document.getElementById(`hidden_quantity_${itemId}`);
            const decreaseBtn = document.getElementById(`decrease_btn_${itemId}`);
            const increaseBtn = document.getElementById(`increase_btn_${itemId}`);

            if (quantityInput && hiddenQuantity) {
                let currentValue = parseInt(quantityInput.value) || maxQuantity;
                if (currentValue > 1) {
                    currentValue--;
                    quantityInput.value = currentValue;
                    hiddenQuantity.value = currentValue;

                    // تحديث حالة الأزرار
                    if (decreaseBtn) decreaseBtn.disabled = currentValue <= 1;
                    if (increaseBtn) increaseBtn.disabled = false;

                    // تحديث السعر والمبلغ
                    updateItemPrice(itemId);
                    updateAmounts();
                }
            }
        }

        function updateQuantityInput(itemId, maxQuantity) {
            const quantityInput = document.getElementById(`quantity_input_${itemId}`);
            const hiddenQuantity = document.getElementById(`hidden_quantity_${itemId}`);
            const decreaseBtn = document.getElementById(`decrease_btn_${itemId}`);
            const increaseBtn = document.getElementById(`increase_btn_${itemId}`);

            if (quantityInput && hiddenQuantity) {
                let value = parseInt(quantityInput.value) || maxQuantity;

                // التحقق من الحدود
                if (value < 1) value = 1;
                if (value > maxQuantity) value = maxQuantity;

                quantityInput.value = value;
                hiddenQuantity.value = value;

                // تحديث حالة الأزرار
                if (decreaseBtn) decreaseBtn.disabled = value <= 1;
                if (increaseBtn) increaseBtn.disabled = value >= maxQuantity;

                // تحديث السعر والمبلغ
                updateItemPrice(itemId);
                updateAmounts();
            }
        }

        // تحديث سعر المنتج الواحد
        function updateItemPrice(itemId) {
            const checkbox = document.querySelector(`.return-checkbox[data-item-id="${itemId}"]`);
            const quantityInput = document.getElementById(`quantity_input_${itemId}`);
            const unitPriceInput = document.getElementById(`item_unit_price_${itemId}`);
            const maxQuantityInput = document.getElementById(`item_max_quantity_${itemId}`);
            const priceSpan = document.getElementById(`item_price_${itemId}`);

            if (quantityInput && unitPriceInput && priceSpan && maxQuantityInput) {
                const unitPrice = parseFloat(unitPriceInput.value) || 0;
                const maxQuantity = parseInt(maxQuantityInput.value) || 0;

                if (checkbox && checkbox.checked) {
                    // المنتج مختار للإرجاع - عرض سعر الكمية المتبقية
                    const returnQuantity = parseInt(quantityInput.value) || 0;
                    const remainingQuantity = maxQuantity - returnQuantity;
                    const totalPrice = unitPrice * remainingQuantity;
                    priceSpan.textContent = totalPrice.toLocaleString('en-US');
                    priceSpan.setAttribute('data-remaining-quantity', remainingQuantity);
                } else {
                    // المنتج غير مختار - عرض سعر الكمية الكاملة
                    const totalPrice = unitPrice * maxQuantity;
                    priceSpan.textContent = totalPrice.toLocaleString('en-US');
                    priceSpan.setAttribute('data-remaining-quantity', maxQuantity);
                }
            }
        }

        // تحديث المبالغ الإجمالية
        function updateAmounts() {
            let totalProductsPrice = 0;
            let returnAmount = 0;

            // حساب مجموع أسعار المنتجات المتبقية والمبلغ المراد إرجاعه
            document.querySelectorAll('.return-checkbox').forEach(checkbox => {
                const itemId = checkbox.getAttribute('data-item-id');
                if (!itemId) return;

                const quantityInput = document.getElementById(`quantity_input_${itemId}`);
                const unitPriceInput = document.getElementById(`item_unit_price_${itemId}`);
                const maxQuantityInput = document.getElementById(`quantity_input_${itemId}`);

                if (quantityInput && unitPriceInput && maxQuantityInput) {
                    const unitPrice = parseFloat(unitPriceInput.value) || 0;
                    const maxQuantity = parseInt(maxQuantityInput.getAttribute('data-max-quantity')) || 0;

                    if (checkbox.checked) {
                        // المنتج مختار للإرجاع
                        const returnQuantity = parseInt(quantityInput.value) || 0;
                        const remainingQuantity = maxQuantity - returnQuantity;

                        // إضافة سعر المنتج المتبقي
                        totalProductsPrice += unitPrice * remainingQuantity;

                        // إضافة المبلغ المراد إرجاعه
                        returnAmount += unitPrice * returnQuantity;
                    } else {
                        // المنتج غير مختار للإرجاع - يبقى كاملاً
                        totalProductsPrice += unitPrice * maxQuantity;
                    }
                }
            });

            // السعر الكلي = فقط سعر المنتجات (بدون التوصيل)
            const totalAmountWithDelivery = totalProductsPrice;
            // المبلغ المتبقي بعد الإرجاع = فقط سعر المنتجات المتبقية (بدون التوصيل)
            const remainingAmount = totalProductsPrice;

            // تحديث العرض
            const totalProductsPriceEl = document.getElementById('totalProductsPrice');
            const totalAmountWithDeliveryEl = document.getElementById('totalAmountWithDelivery');
            const returnAmountEl = document.getElementById('returnAmount');
            const returnAmountSection = document.getElementById('returnAmountSection');
            const remainingAmountEl = document.getElementById('remainingAmount');
            const remainingAmountSection = document.getElementById('remainingAmountSection');

            if (totalProductsPriceEl) {
                totalProductsPriceEl.textContent = totalProductsPrice.toLocaleString('en-US');
            }

            if (totalAmountWithDeliveryEl) {
                totalAmountWithDeliveryEl.textContent = totalAmountWithDelivery.toLocaleString('en-US');
            }

            if (returnAmount > 0) {
                if (returnAmountEl) {
                    returnAmountEl.textContent = returnAmount.toLocaleString('en-US');
                }
                if (returnAmountSection) {
                    returnAmountSection.style.display = 'flex';
                }
                if (remainingAmountEl) {
                    remainingAmountEl.textContent = remainingAmount.toLocaleString('en-US');
                }
                if (remainingAmountSection) {
                    remainingAmountSection.style.display = 'flex';
                }
            } else {
                if (returnAmountSection) {
                    returnAmountSection.style.display = 'none';
                }
                if (remainingAmountSection) {
                    remainingAmountSection.style.display = 'none';
                }
            }
        }

        function updateSummary() {
            const selectedCheckboxes = document.querySelectorAll('.return-checkbox:checked');
            const selectedItems = selectedCheckboxes.length;

            document.getElementById('selectedItems').textContent = selectedItems;

            if (selectedItems === 0) {
                document.getElementById('submitBtn').disabled = true;
            } else {
                document.getElementById('submitBtn').disabled = false;
            }
        }

        // تحديث الملخص عند تغيير الكمية
        document.querySelectorAll('.return-quantity').forEach(input => {
            input.addEventListener('input', function() {
                const itemId = this.getAttribute('data-item-id');
                if (itemId) {
                    const max = parseInt(this.getAttribute('data-max-quantity') || this.getAttribute('max'));
                    updateQuantityInput(parseInt(itemId), max);
                }
            });
        });

        // تحديث المبالغ عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            updateAmounts();
        });

        function copyDeliveryCode(text, type = '') {
            // تحديد نوع الرسالة
            let successMessage = 'تم النسخ بنجاح!';
            let errorMessage = 'فشل في النسخ';

            if (type === 'order') {
                successMessage = 'تم نسخ رقم الطلب بنجاح!';
                errorMessage = 'فشل في نسخ رقم الطلب';
            } else if (type === 'delivery') {
                successMessage = 'تم نسخ كود الوسيط بنجاح!';
                errorMessage = 'فشل في نسخ كود الوسيط';
            }

            // إنشاء عنصر مؤقت
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            // تحديد النص ونسخه
            textarea.select();
            textarea.setSelectionRange(0, 99999); // للجوال

            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textarea);

                if (successful) {
                    // إظهار رسالة نجاح
                    if (typeof showNotification === 'function') {
                        showNotification(successMessage, 'success');
                    } else {
                        alert(successMessage);
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            } catch (err) {
                document.body.removeChild(textarea);
                // استخدام Clipboard API كبديل
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (typeof showNotification === 'function') {
                            showNotification(successMessage, 'success');
                        } else {
                            alert(successMessage);
                        }
                    }).catch(() => {
                        if (typeof showNotification === 'function') {
                            showNotification(errorMessage, 'error');
                        } else {
                            alert(errorMessage);
                        }
                    });
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            }
        }

        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('partialReturnForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const selectedItems = document.querySelectorAll('.return-checkbox:checked');
            if (selectedItems.length === 0) {
                alert('يرجى اختيار منتج واحد على الأقل للإرجاع');
                return false;
            }

            // التحقق من أن الكميات صحيحة وجمع البيانات
            let allValid = true;
            const returnItems = [];

            selectedItems.forEach(checkbox => {
                const itemId = checkbox.getAttribute('data-item-id');
                if (!itemId) {
                    allValid = false;
                    return;
                }

                const hiddenQuantity = document.getElementById(`hidden_quantity_${itemId}`);
                const quantityInput = document.getElementById(`quantity_input_${itemId}`);
                const orderItemId = document.getElementById(`order_item_id_${itemId}`);
                const productId = document.getElementById(`product_id_${itemId}`);
                const sizeId = document.getElementById(`size_id_${itemId}`);

                if (hiddenQuantity && quantityInput && orderItemId && productId && sizeId) {
                    const max = parseInt(quantityInput.getAttribute('data-max-quantity') || quantityInput.getAttribute('max'));
                    const value = parseInt(hiddenQuantity.value) || 0;
                    const sizeIdValue = sizeId.value;

                    // التحقق من الكمية (size_id يمكن أن يكون فارغاً، سنتعامل معه في الخادم)
                    if (value < 1 || value > max) {
                        allValid = false;
                        console.error(`Invalid quantity for item ${itemId}: value=${value}, max=${max}`);
                    } else {
                        returnItems.push({
                            order_item_id: orderItemId.value,
                            product_id: productId.value,
                            size_id: sizeIdValue,
                            quantity: value
                        });
                    }
                } else {
                    allValid = false;
                    console.error(`Missing fields for item ${itemId}:`, {
                        hiddenQuantity: !!hiddenQuantity,
                        quantityInput: !!quantityInput,
                        orderItemId: !!orderItemId,
                        productId: !!productId,
                        sizeId: !!sizeId,
                        sizeIdValue: sizeId ? sizeId.value : 'N/A'
                    });
                }
            });

            if (!allValid) {
                let errorDetails = 'يرجى التأكد من أن الكميات المدخلة صحيحة.\n\n';
                errorDetails += 'تفاصيل الأخطاء:\n';
                errorDetails += '- عدد المنتجات المختارة: ' + selectedItems.length + '\n';
                errorDetails += '- عدد المنتجات الصحيحة: ' + returnItems.length + '\n';
                errorDetails += 'تحقق من Console (F12) للتفاصيل الكاملة.';
                alert(errorDetails);
                console.error('Validation failed:', {
                    selectedItems: selectedItems.length,
                    validItems: returnItems.length,
                    allValid: allValid
                });
                return false;
            }

            // Debug: عرض البيانات قبل الإرسال
            console.log('Selected items:', returnItems);

            // إنشاء FormData جديد
            const formData = new FormData();
            formData.append('_token', document.querySelector('input[name="_token"]').value);

            const notesInput = document.getElementById('notes');
            if (notesInput && notesInput.value) {
                formData.append('notes', notesInput.value);
            }

            // إضافة return_items بالترتيب الصحيح
            returnItems.forEach((item, index) => {
                formData.append(`return_items[${index}][order_item_id]`, item.order_item_id);
                formData.append(`return_items[${index}][product_id]`, item.product_id);
                formData.append(`return_items[${index}][size_id]`, item.size_id);
                formData.append(`return_items[${index}][quantity]`, item.quantity);
            });

            // Debug: التحقق من البيانات النهائية
            console.log('Final form data:');
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }

            // إرسال البيانات باستخدام fetch
            const submitBtn = document.getElementById('submitBtn');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري المعالجة...';

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html'
                }
            })
            .then(response => {
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    return response.text();
                }
            })
            .then(html => {
                if (html) {
                    console.log('Response HTML received, length:', html.length);
                    // إذا كان هناك خطأ، عرضه
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const errorElement = doc.querySelector('.alert-danger, .text-danger, .panel.border-red-500');
                    if (errorElement) {
                        const errorText = errorElement.textContent.trim();
                        console.error('Error found in response:', errorText);
                        alert('خطأ من الخادم:\n' + errorText);
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    } else {
                        console.log('No errors found, reloading page...');
                        // إعادة تحميل الصفحة لعرض الأخطاء أو النجاح
                        window.location.reload();
                    }
                } else {
                    console.log('No HTML response, checking redirect...');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                console.error('Error details:', {
                    message: error.message,
                    stack: error.stack,
                    returnItems: returnItems,
                    formData: Array.from(formData.entries())
                });
                alert('حدث خطأ أثناء معالجة الطلب:\n' + error.message + '\n\nتحقق من Console (F12) للتفاصيل الكاملة.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });

            return false;
        });
    </script>
</x-layout.admin>

