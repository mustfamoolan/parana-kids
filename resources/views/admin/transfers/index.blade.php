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

        <div x-data="transferForm()" class="space-y-6">
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
                            @click="addAllSizes()"
                            class="btn btn-primary btn-sm"
                        >
                            نقل الكل
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="size in selectedProduct?.sizes || []" :key="size.id">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            :value="size.id"
                                            @change="toggleSize(size, $event.target.checked)"
                                            class="form-checkbox"
                                        >
                                        <span class="ml-2 font-medium" x-text="size.size_name"></span>
                                    </label>
                                    <span class="text-sm text-gray-500" x-text="'متاح: ' + size.quantity"></span>
                                </div>

                                <div x-show="isSizeSelected(size.id)">
                                    <label class="form-label text-sm">الكمية المراد نقلها</label>
                                    <input
                                        type="number"
                                        :max="size.quantity"
                                        min="1"
                                        @input="updateSizeQuantity(size.id, $event.target.value)"
                                        class="form-input"
                                        placeholder="الكمية"
                                    >
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- جدول المواد المختارة -->
            <div x-show="selectedSizes.length > 0" class="space-y-4">
                <h6 class="font-semibold">المواد المختارة للنقل</h6>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>القياس</th>
                                <th>الكمية المتاحة</th>
                                <th>الكمية المراد نقلها</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in selectedSizes" :key="item.sizeId">
                                <tr>
                                    <td x-text="selectedProduct?.name"></td>
                                    <td x-text="item.sizeName"></td>
                                    <td x-text="item.availableQuantity"></td>
                                    <td>
                                        <input
                                            type="number"
                                            x-model="item.quantity"
                                            :max="item.availableQuantity"
                                            min="1"
                                            class="form-input w-20"
                                        >
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            @click="removeSize(index)"
                                            class="btn btn-danger btn-sm"
                                        >
                                            حذف
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
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
        function transferForm() {
            return {
                sourceWarehouse: '',
                targetWarehouse: '',
                selectedProduct: null,
                selectedSizes: [],
                searchQuery: '',
                searchResults: [],
                highlightedIndex: -1,

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

                updateSizeQuantity(sizeId, quantity) {
                    const item = this.selectedSizes.find(item => item.sizeId === sizeId);
                    if (item) {
                        item.quantity = Math.min(parseInt(quantity) || 1, item.availableQuantity);
                    }
                },

                canSubmit() {
                    return this.sourceWarehouse &&
                           this.targetWarehouse &&
                           this.sourceWarehouse !== this.targetWarehouse &&
                           this.selectedSizes.length > 0 &&
                           this.selectedSizes.every(item => item.quantity > 0);
                },

                async submitTransfer() {
                    if (!this.canSubmit()) return;

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
