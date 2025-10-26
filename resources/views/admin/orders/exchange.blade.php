<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">استبدال منتجات الطلب</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
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
                <div class="w-10 h-10 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <div>
                    <h6 class="text-lg font-semibold">طلب رقم: {{ $order->order_number }}</h6>
                    <p class="text-sm text-gray-500">الزبون: {{ $order->customer_name }} - {{ $order->customer_phone }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.orders.exchange.process', $order) }}" id="exchangeForm">
            @csrf

            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4">اختر المنتجات المراد استبدالها</h6>

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
                    <div class="space-y-6">
                        @foreach($order->items as $index => $item)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 exchange-item" data-item-id="{{ $item->id }}">
                                <div class="flex items-start gap-4">
                                    <!-- Checkbox -->
                                    <div class="flex items-center">
                                        <input
                                            type="checkbox"
                                            name="exchanges[{{ $index }}][selected]"
                                            class="form-checkbox exchange-checkbox"
                                            onchange="toggleExchangeItem({{ $index }})"
                                        >
                                    </div>

                                    <!-- صورة المنتج الحالي -->
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
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- تفاصيل المنتج الحالي -->
                                    <div class="flex-1">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                            <!-- المنتج الحالي -->
                                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                                <h6 class="font-semibold text-lg mb-2">المنتج الحالي</h6>
                                                <p class="font-medium">{{ $item->product->name }}</p>
                                                <p class="text-sm text-gray-500">كود: {{ $item->product->code }}</p>
                                                @if($item->size)
                                                    <p class="text-sm text-gray-500">القياس: {{ $item->size->size_name }}</p>
                                                @endif
                                                <p class="text-sm text-gray-500">الكمية: {{ $item->quantity }}</p>

                                                <!-- الكمية المراد استبدالها -->
                                                <div class="mt-3">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        الكمية المراد استبدالها
                                                    </label>
                                                    <input
                                                        type="number"
                                                        name="exchanges[{{ $index }}][old_quantity]"
                                                        class="form-input old-quantity"
                                                        min="1"
                                                        max="{{ $item->quantity }}"
                                                        value="{{ $item->quantity }}"
                                                        disabled
                                                        required
                                                    >
                                                </div>
                                            </div>

                                            <!-- المنتج البديل -->
                                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                                <h6 class="font-semibold text-lg mb-2">المنتج البديل</h6>

                                                <!-- اختيار المنتج -->
                                                <div class="mb-3" x-data="productSearch{{ $index }}()">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        اختر المنتج البديل
                                                    </label>

                                                    <!-- Live Search -->
                                                    <div>
                                                        <!-- searchbar -->
                                                        <form class="mx-auto w-full mb-3">
                                                            <div class="relative">
                                                                <input
                                                                    type="text"
                                                                    placeholder="ابحث بكود أو اسم المنتج..."
                                                                    class="form-input shadow-[0_0_4px_2px_rgb(31_45_61_/_10%)] bg-white rounded-full h-11 placeholder:tracking-wider"
                                                                    x-model="search"
                                                                    :disabled="!isEnabled"
                                                                    @input="searchProducts()"
                                                                />
                                                                <button type="button" class="btn btn-primary absolute ltr:right-1 rtl:left-1 inset-y-0 m-auto rounded-full w-9 h-9 p-0 flex items-center justify-center">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </form>

                                                        <!-- result -->
                                                        <div class="p-4 border border-white-dark/20 rounded-lg space-y-4 overflow-x-auto w-full block max-h-64 overflow-y-auto" x-show="search.length > 0">
                                                            <template x-for="item in searchResults" :key="item.id">
                                                                <div class="bg-white dark:bg-[#1b2e4b] rounded-xl shadow-[0_0_4px_2px_rgb(31_45_61_/_10%)] p-3 flex items-center justify-between
                                                                            text-gray-500 font-semibold min-w-[625px] hover:text-primary transition-all duration-300 hover:scale-[1.01] cursor-pointer"
                                                                     @click="selectProduct(item)">
                                                                    <div class="user-profile">
                                                                        <img :src="item.image" alt="image" class="w-8 h-8 rounded-md object-cover" />
                                                                    </div>
                                                                    <div x-text="item.name" class="flex-1 text-right"></div>
                                                                    <div x-text="'كود: ' + item.code" class="text-sm text-gray-400"></div>
                                                                    <div class="cursor-pointer">
                                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                                        </svg>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <!-- رسالة لا توجد نتائج -->
                                                            <div x-show="searchResults.length === 0 && search.length > 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                                                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-1.009-5.824-2.571M15 6.334A7.962 7.962 0 0112 4c-2.34 0-4.29 1.009-5.824 2.571"></path>
                                                                </svg>
                                                                <p class="text-sm">لا توجد منتجات مطابقة للبحث</p>
                                                            </div>
                                                        </div>

                                                        <!-- المنتج المختار -->
                                                        <div x-show="selectedProduct" class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                                            <div class="flex items-center gap-2">
                                                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded overflow-hidden">
                                                                    <img :src="selectedProduct.image" :alt="selectedProduct.name" class="w-full h-full object-cover">
                                                                </div>
                                                                <div class="flex-1">
                                                                    <div class="font-medium text-sm" x-text="selectedProduct.name"></div>
                                                                    <div class="text-xs text-gray-500" x-text="'كود: ' + selectedProduct.code"></div>
                                                                </div>
                                                                <button type="button" @click="clearSelection()" class="text-gray-400 hover:text-gray-600">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- الحقل المخفي -->
                                                        <input type="hidden" name="exchanges[{{ $index }}][new_product_id]" x-model="selectedProductId" :disabled="!selectedProductId" required>
                                                    </div>
                                                </div>

                                                <!-- اختيار القياس -->
                                                <div class="mb-3">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        اختر القياس
                                                    </label>
                                                    <select
                                                        name="exchanges[{{ $index }}][new_size_id]"
                                                        class="form-select new-size-select"
                                                        disabled
                                                        required
                                                        onchange="updateStockInfo({{ $index }})"
                                                    >
                                                        <option value="">اختر القياس...</option>
                                                    </select>
                                                </div>

                                                <!-- الكمية الجديدة -->
                                                <div class="mb-3">
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        الكمية الجديدة
                                                    </label>
                                                    <input
                                                        type="number"
                                                        name="exchanges[{{ $index }}][new_quantity]"
                                                        class="form-input new-quantity"
                                                        min="1"
                                                        disabled
                                                        required
                                                    >
                                                </div>

                                                <!-- معلومات المخزون -->
                                                <div class="text-sm text-gray-600 dark:text-gray-400" id="stockInfo{{ $index }}">
                                                    اختر المنتج والقياس أولاً
                                                </div>
                                            </div>
                                        </div>

                                        <!-- سبب الاستبدال -->
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                سبب الاستبدال <span class="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                name="exchanges[{{ $index }}][reason]"
                                                class="form-textarea exchange-reason"
                                                rows="2"
                                                placeholder="أدخل سبب استبدال هذا المنتج..."
                                                disabled
                                                required
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>

                                <!-- الحقول المخفية -->
                                <input type="hidden" name="exchanges[{{ $index }}][order_item_id]" value="{{ $item->id }}">
                                <input type="hidden" name="exchanges[{{ $index }}][old_product_id]" value="{{ $item->product_id }}">
                                <input type="hidden" name="exchanges[{{ $index }}][old_size_id]" value="{{ $item->size_id }}">
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- ملخص الاستبدال -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-5">
                    <h6 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">ملخص الاستبدال</h6>
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
                            <span class="text-gray-600 dark:text-gray-400">نوع الاستبدال:</span>
                            <span class="font-semibold text-green-600" id="exchangeType">-</span>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="flex gap-3 justify-end">
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
                        إلغاء
                    </a>
                    <button type="submit" class="btn btn-info" id="submitBtn" disabled>
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        تأكيد الاستبدال
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // بيانات المنتجات مع الصور (معالجة من Controller)
        const productsWithImages = @json($products);

        // استخراج بيانات الأحجام
        const productsData = {};
        productsWithImages.forEach(product => {
            productsData[product.id] = {};
            product.sizes.forEach(size => {
                productsData[product.id][size.id] = {
                    size: size.size_name,
                    quantity: size.quantity
                };
            });
        });

        // Alpine.js data for product search
        document.addEventListener('alpine:init', () => {
            @foreach($order->items as $index => $item)
            Alpine.data('productSearch{{ $index }}', () => ({
                search: '',
                selectedProduct: null,
                selectedProductId: null,
                isEnabled: false,
                products: @json($products),

                get searchResults() {
                    if (this.search.length < 2) return [];

                    return this.products.filter(product => {
                        return product.name.toLowerCase().includes(this.search.toLowerCase()) ||
                               product.code.toLowerCase().includes(this.search.toLowerCase());
                    });
                },

                searchProducts() {
                    // البحث يتم تلقائياً عبر computed property searchResults
                },

                selectProduct(product) {
                    this.selectedProduct = product;
                    this.selectedProductId = product.id;
                    this.search = '';

                    // تحميل الأحجام
                    loadProductSizes({{ $index }}, product.id);
                },

                clearSelection() {
                    this.selectedProduct = null;
                    this.selectedProductId = null;

                    // مسح القياسات والكمية
                    const sizeSelect = document.querySelector(`select[name="exchanges[{{ $index }}][new_size_id]"]`);
                    const quantityInput = document.querySelector(`input[name="exchanges[{{ $index }}][new_quantity]"]`);
                    if (sizeSelect) {
                        sizeSelect.innerHTML = '<option value="">اختر القياس...</option>';
                        sizeSelect.disabled = true;
                    }
                    if (quantityInput) {
                        quantityInput.value = '';
                        quantityInput.disabled = true;
                    }

                    document.getElementById(`stockInfo{{ $index }}`).textContent = 'اختر المنتج والقياس أولاً';
                },

                enable() {
                    this.isEnabled = true;
                },

                disable() {
                    this.isEnabled = false;
                    this.clearSelection();
                }
            }));
            @endforeach
        });

        function selectAll() {
            document.querySelectorAll('.exchange-checkbox').forEach(checkbox => {
                checkbox.checked = true;
                toggleExchangeItem(Array.from(document.querySelectorAll('.exchange-checkbox')).indexOf(checkbox));
            });
        }

        function deselectAll() {
            document.querySelectorAll('.exchange-checkbox').forEach(checkbox => {
                checkbox.checked = false;
                toggleExchangeItem(Array.from(document.querySelectorAll('.exchange-checkbox')).indexOf(checkbox));
            });
        }

        function toggleExchangeItem(index) {
            const checkbox = document.querySelector(`input[name="exchanges[${index}][selected]"]`);
            const oldQuantityField = document.querySelector(`input[name="exchanges[${index}][old_quantity]"]`);
            const reasonField = document.querySelector(`textarea[name="exchanges[${index}][reason]"]`);

            // تفعيل/إلغاء تفعيل الحقول
            oldQuantityField.disabled = !checkbox.checked;
            reasonField.disabled = !checkbox.checked;

            // تفعيل/إلغاء تفعيل Alpine.js component
            const alpineElement = document.querySelector(`[x-data*="productSearch${index}"]`);
            if (alpineElement && Alpine.$data(alpineElement)) {
                const alpineComponent = Alpine.$data(alpineElement);
                if (checkbox.checked) {
                    alpineComponent.enable();
                } else {
                    alpineComponent.disable();
                }
            }

            if (!checkbox.checked) {
                // إعادة تعيين القيم
                oldQuantityField.value = oldQuantityField.max;
                reasonField.value = '';
            }

            updateSummary();
        }


        function loadProductSizes(index, productId = null) {
            if (!productId) {
                // محاولة الحصول على productId من Alpine.js component
                const alpineElement = document.querySelector(`[x-data*="productSearch${index}"]`);
                if (alpineElement && Alpine.$data(alpineElement)) {
                    const alpineComponent = Alpine.$data(alpineElement);
                    if (alpineComponent.selectedProductId) {
                        productId = alpineComponent.selectedProductId;
                    } else {
                        return;
                    }
                } else {
                    return;
                }
            }

            const sizeSelect = document.querySelector(`select[name="exchanges[${index}][new_size_id]"]`);
            const quantityInput = document.querySelector(`input[name="exchanges[${index}][new_quantity]"]`);

            sizeSelect.innerHTML = '<option value="">اختر القياس...</option>';
            sizeSelect.disabled = false;
            quantityInput.disabled = false;

            if (productId && productsData[productId]) {
                Object.entries(productsData[productId]).forEach(([sizeId, sizeData]) => {
                    const option = document.createElement('option');
                    option.value = sizeId;
                    option.textContent = `${sizeData.size} (متوفر: ${sizeData.quantity})`;
                    option.dataset.quantity = sizeData.quantity;
                    sizeSelect.appendChild(option);
                });
            }

            updateStockInfo(index);
        }

        function updateStockInfo(index) {
            const sizeSelect = document.querySelector(`select[name="exchanges[${index}][new_size_id]"]`);
            const selectedOption = sizeSelect.options[sizeSelect.selectedIndex];
            const stockInfo = document.getElementById(`stockInfo${index}`);

            if (selectedOption && selectedOption.dataset.quantity) {
                const availableQuantity = parseInt(selectedOption.dataset.quantity);
                stockInfo.innerHTML = `المخزون المتاح: <span class="font-semibold ${availableQuantity > 0 ? 'text-green-600' : 'text-red-600'}">${availableQuantity}</span> قطعة`;

                // تحديث الحد الأقصى للكمية
                const quantityInput = document.querySelector(`input[name="exchanges[${index}][new_quantity]"]`);
                quantityInput.max = availableQuantity;
                if (parseInt(quantityInput.value) > availableQuantity) {
                    quantityInput.value = availableQuantity;
                }
            } else {
                stockInfo.textContent = 'اختر المنتج والقياس أولاً';
            }
        }

        function updateSummary() {
            const selectedCheckboxes = document.querySelectorAll('.exchange-checkbox:checked');
            const totalItems = document.querySelectorAll('.exchange-checkbox').length;
            const selectedItems = selectedCheckboxes.length;

            document.getElementById('selectedItems').textContent = selectedItems;

            if (selectedItems === 0) {
                document.getElementById('exchangeType').textContent = '-';
                document.getElementById('submitBtn').disabled = true;
            } else if (selectedItems === totalItems) {
                document.getElementById('exchangeType').textContent = 'استبدال كلي';
                document.getElementById('submitBtn').disabled = false;
            } else {
                document.getElementById('exchangeType').textContent = 'استبدال جزئي';
                document.getElementById('submitBtn').disabled = false;
            }
        }

        // التحقق من صحة النموذج قبل الإرسال
        document.getElementById('exchangeForm').addEventListener('submit', function(e) {
            const selectedItems = document.querySelectorAll('.exchange-checkbox:checked');
            if (selectedItems.length === 0) {
                e.preventDefault();
                alert('يرجى اختيار منتج واحد على الأقل للاستبدال');
                return;
            }

            // التحقق من ملء جميع الحقول المطلوبة للمنتجات المختارة
            let allFieldsFilled = true;
            let errorMessage = '';

            selectedItems.forEach(checkbox => {
                const index = Array.from(document.querySelectorAll('.exchange-checkbox')).indexOf(checkbox);

                // الحصول على productId من Alpine.js component
                const alpineElement = document.querySelector(`[x-data*="productSearch${index}"]`);
                const alpineComponent = alpineElement && Alpine.$data(alpineElement) ? Alpine.$data(alpineElement) : null;
                const selectedProductId = alpineComponent ? alpineComponent.selectedProductId : null;

                const newSize = document.querySelector(`select[name="exchanges[${index}][new_size_id]"]`);
                const newQuantity = document.querySelector(`input[name="exchanges[${index}][new_quantity]"]`);
                const reason = document.querySelector(`textarea[name="exchanges[${index}][reason]"]`);

                if (!selectedProductId) {
                    allFieldsFilled = false;
                    errorMessage = 'يرجى اختيار المنتج البديل لجميع المنتجات المختارة';
                } else if (!newSize.value) {
                    allFieldsFilled = false;
                    errorMessage = 'يرجى اختيار القياس لجميع المنتجات المختارة';
                } else if (!newQuantity.value) {
                    allFieldsFilled = false;
                    errorMessage = 'يرجى تحديد الكمية الجديدة لجميع المنتجات المختارة';
                } else if (!reason.value.trim()) {
                    allFieldsFilled = false;
                    errorMessage = 'يرجى ملء سبب الاستبدال لجميع المنتجات المختارة';
                }
            });

            if (!allFieldsFilled) {
                e.preventDefault();
                alert(errorMessage);
                return;
            }
        });
    </script>
</x-layout.admin>
