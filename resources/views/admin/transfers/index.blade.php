<x-layout.admin>
    <div class="panel">
        <div class="flex justify-between items-center mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light">نقل المواد بين المخازن</h5>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div x-data="transferForm({{ json_encode($warehouses->pluck('name', 'id')) }})" class="space-y-6">
            <!-- اختيار المخزن المصدر -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">المخزن المصدر</label>
                    <select x-model="sourceWarehouse" @change="clearProductSelection()" class="form-select">
                        <option value="">اختر المخزن المصدر</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="form-label">المخزن المستهدف</label>
                    <select x-model="targetWarehouse" class="form-select">
                        <option value="">اختر المخزن المستهدف</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" x-show="sourceWarehouse != '{{ $warehouse->id }}'">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- البحث عن المنتج -->
            <div x-show="sourceWarehouse" class="space-y-4">
                <div>
                    <label class="form-label">البحث عن المنتج</label>
                    <div class="relative">
                        <input
                            type="text"
                            x-model="searchQuery"
                            @input="searchProducts()"
                            @keydown.arrow-down="highlightNext()"
                            @keydown.arrow-up="highlightPrev()"
                            @keydown.enter.prevent="selectHighlighted()"
                            placeholder="ابحث بالاسم أو الكود..."
                            class="form-input"
                        >
                        <div x-show="searchResults.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto">
                            <template x-for="(product, index) in searchResults" :key="product.id">
                                <div
                                    @click="selectProduct(product)"
                                    @mouseenter="highlightedIndex = index"
                                    class="px-4 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                    :class="{ 'bg-gray-100 dark:bg-gray-700': highlightedIndex === index }"
                                >
                                    <div class="font-medium" x-text="product.name"></div>
                                    <div class="text-sm text-gray-500" x-text="'كود: ' + product.code"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- عرض القياسات -->
                <div x-show="selectedProduct" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h6 class="font-semibold">قياسات المنتج: <span x-text="selectedProduct?.name"></span></h6>
                        <button
                            type="button"
                            @click="confirmAddAllSizes()"
                            class="btn btn-primary btn-sm"
                        >
                            نقل الكل
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="size in selectedProduct?.sizes || []" :key="size.id">
                            <div class="panel" :class="{ 'opacity-60': isSizeSelected(size.id) }">
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :value="size.id"
                                            @change="toggleSize(size, $event.target.checked)"
                                            class="form-checkbox"
                                            :checked="isSizeSelected(size.id)"
                                        >
                                        <span class="mr-2 font-semibold" x-text="size.size_name"></span>
                                    </label>
                                    <span class="text-xs text-gray-500" x-text="'متاح: ' + size.quantity"></span>
                                </div>
                                <div x-show="isSizeSelected(size.id)" class="mt-2 text-xs text-success">
                                    تم الاختيار - يمكنك تعديل الكمية من قسم المواد المختارة أدناه
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- المواد المختارة -->
            <div x-show="selectedSizes.length > 0" class="space-y-4">
                <h6 class="font-semibold">المواد المختارة للنقل</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(item, index) in selectedSizes" :key="item.sizeId">
                        <div class="panel">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h6 class="font-semibold text-base" x-text="selectedProduct?.name"></h6>
                                    <p class="text-sm text-gray-500 mt-1">
                                        القياس: <span x-text="item.sizeName" class="font-medium"></span>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        متاح: <span x-text="item.availableQuantity"></span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="removeSize(index)"
                                    class="btn btn-danger btn-sm"
                                    title="حذف"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="border-t pt-3">
                                <label class="form-label text-xs mb-2">الكمية المراد نقلها</label>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="decrementSelectedQuantity(index)"
                                        class="btn btn-sm btn-outline-danger"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <input
                                        type="number"
                                        x-model="item.quantity"
                                        @input="updateSelectedQuantity(index, $event.target.value, item.availableQuantity)"
                                        :max="item.availableQuantity"
                                        min="1"
                                        class="form-input w-24 text-center"
                                    >
                                    <button
                                        type="button"
                                        @click="incrementSelectedQuantity(index, item.availableQuantity)"
                                        class="btn btn-sm btn-outline-success"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- زر تنفيذ النقل -->
                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="submitTransfer()"
                        :disabled="!canSubmit()"
                        class="btn btn-success"
                    >
                        تنفيذ النقل
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function transferForm(warehouses) {
            return {
                sourceWarehouse: '',
                targetWarehouse: '',
                selectedProduct: null,
                selectedSizes: [],
                searchQuery: '',
                searchResults: [],
                highlightedIndex: -1,
                warehouses: warehouses || {},

                async searchProducts() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route('admin.transfers.search') }}?warehouse_id=${this.sourceWarehouse}&search=${this.searchQuery}`);
                        this.searchResults = await response.json();
                        this.highlightedIndex = -1;
                    } catch (error) {
                        console.error('خطأ في البحث:', error);
                    }
                },

                selectProduct(product) {
                    this.selectedProduct = product;
                    this.searchQuery = product.name;
                    this.searchResults = [];
                    this.selectedSizes = [];
                },

                clearProductSelection() {
                    this.selectedProduct = null;
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.selectedSizes = [];
                },

                toggleSize(size, isChecked) {
                    if (isChecked) {
                        this.addSize(size);
                    } else {
                        this.removeSizeById(size.id);
                    }
                },

                addSize(size) {
                    const existingIndex = this.selectedSizes.findIndex(item => item.sizeId === size.id);
                    if (existingIndex === -1) {
                        this.selectedSizes.push({
                            sizeId: size.id,
                            sizeName: size.size_name,
                            availableQuantity: size.quantity,
                            quantity: Math.min(size.quantity, 1)
                        });
                    }
                },

                addAllSizes() {
                    this.selectedSizes = [];
                    this.selectedProduct.sizes.forEach(size => {
                        if (size.quantity > 0) {
                            this.selectedSizes.push({
                                sizeId: size.id,
                                sizeName: size.size_name,
                                availableQuantity: size.quantity,
                                quantity: size.quantity
                            });
                        }
                    });
                },

                removeSize(index) {
                    this.selectedSizes.splice(index, 1);
                },

                removeSizeById(sizeId) {
                    const index = this.selectedSizes.findIndex(item => item.sizeId === sizeId);
                    if (index !== -1) {
                        this.selectedSizes.splice(index, 1);
                    }
                },

                isSizeSelected(sizeId) {
                    return this.selectedSizes.some(item => item.sizeId === sizeId);
                },


                incrementSelectedQuantity(index, maxQty) {
                    if (this.selectedSizes[index] && this.selectedSizes[index].quantity < maxQty) {
                        this.selectedSizes[index].quantity = Math.min(this.selectedSizes[index].quantity + 1, maxQty);
                    }
                },

                decrementSelectedQuantity(index) {
                    if (this.selectedSizes[index] && this.selectedSizes[index].quantity > 1) {
                        this.selectedSizes[index].quantity = Math.max(1, this.selectedSizes[index].quantity - 1);
                    }
                },

                updateSelectedQuantity(index, quantity, maxQty) {
                    if (this.selectedSizes[index]) {
                        const qty = parseInt(quantity) || 1;
                        this.selectedSizes[index].quantity = Math.max(1, Math.min(qty, maxQty));
                    }
                },

                confirmAddAllSizes() {
                    if (confirm('هل تريد نقل جميع القياسات المتاحة؟')) {
                        this.addAllSizes();
                    }
                },

                canSubmit() {
                    return this.sourceWarehouse &&
                           this.targetWarehouse &&
                           this.sourceWarehouse !== this.targetWarehouse &&
                           this.selectedSizes.length > 0 &&
                           this.selectedSizes.every(item => item.quantity > 0);
                },

                getSourceWarehouseName() {
                    return this.warehouses[this.sourceWarehouse] || '';
                },

                getTargetWarehouseName() {
                    return this.warehouses[this.targetWarehouse] || '';
                },

                getTransferSummary() {
                    const totalQuantity = this.selectedSizes.reduce((sum, item) => sum + item.quantity, 0);
                    let summary = `ملخص النقل:\n\n`;
                    summary += `المخزن المصدر: ${this.getSourceWarehouseName()}\n`;
                    summary += `المخزن المستهدف: ${this.getTargetWarehouseName()}\n`;
                    summary += `المنتج: ${this.selectedProduct?.name}\n\n`;
                    summary += `المواد المراد نقلها:\n`;
                    this.selectedSizes.forEach(item => {
                        summary += `- ${item.sizeName}: ${item.quantity} قطعة (متاح: ${item.availableQuantity})\n`;
                    });
                    summary += `\nإجمالي الكمية: ${totalQuantity} قطعة`;
                    return summary;
                },

                async submitTransfer() {
                    if (!this.canSubmit()) return;

                    // عرض ملخص قبل التنفيذ
                    const summary = this.getTransferSummary();
                    if (!confirm(summary + '\n\nهل تريد متابعة النقل؟')) {
                        return;
                    }

                    console.log('بدء عملية النقل...');
                    console.log('المخزن المصدر:', this.sourceWarehouse);
                    console.log('المخزن المستهدف:', this.targetWarehouse);
                    console.log('المواد المختارة:', this.selectedSizes);

                    const formData = new FormData();
                    formData.append('from_warehouse_id', this.sourceWarehouse);
                    formData.append('to_warehouse_id', this.targetWarehouse);

                    this.selectedSizes.forEach((item, index) => {
                        formData.append(`items[${index}][product_id]`, this.selectedProduct.id);
                        formData.append(`items[${index}][size_id]`, item.sizeId);
                        formData.append(`items[${index}][quantity]`, item.quantity);
                    });

                    console.log('إرسال البيانات...');

                    try {
                        const response = await fetch('{{ route("admin.transfers.store") }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });

                        console.log('استجابة الخادم:', response.status);

                        const data = await response.json();
                        console.log('البيانات المستلمة:', data);

                        if (response.ok && data.success) {
                            console.log('تم النقل بنجاح!');
                            alert(data.message);
                            window.location.reload();
                        } else {
                            console.error('خطأ:', data);
                            alert('خطأ: ' + (data.message || 'حدث خطأ أثناء النقل'));
                        }
                    } catch (error) {
                        console.error('خطأ في النقل:', error);
                        alert('حدث خطأ أثناء النقل: ' + error.message);
                    }
                },

                highlightNext() {
                    if (this.highlightedIndex < this.searchResults.length - 1) {
                        this.highlightedIndex++;
                    }
                },

                highlightPrev() {
                    if (this.highlightedIndex > 0) {
                        this.highlightedIndex--;
                    }
                },

                selectHighlighted() {
                    if (this.highlightedIndex >= 0 && this.highlightedIndex < this.searchResults.length) {
                        this.selectProduct(this.searchResults[this.highlightedIndex]);
                    }
                }
            }
        }
    </script>
</x-layout.admin>
