<x-layout.admin>
    <div class="panel">
        <div class="flex justify-between items-center mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light">إرجاع طلبات</h5>
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

        <div x-data="bulkReturnForm()" class="space-y-6">
            <!-- اختيار المندوب والمخزن -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">اختر المندوب <span class="text-danger">*</span></label>
                    <select x-model="delegateId" class="form-select" required>
                        <option value="">اختر المندوب</option>
                        @foreach($delegates as $delegate)
                            <option value="{{ $delegate->id }}">{{ $delegate->name }} ({{ $delegate->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">اختر المخزن <span class="text-danger">*</span></label>
                    <select x-model="warehouseId" class="form-select" required>
                        <option value="">اختر المخزن</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- البحث عن المنتج -->
            <div x-show="delegateId && warehouseId" class="space-y-4">
                <div>
                    <label class="form-label">البحث عن المنتج</label>
                    <div class="relative">
                        <input
                            type="text"
                            x-model="searchQuery"
                            @input.debounce.300ms="searchProducts()"
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
                            @click="addAllSelectedSizes()"
                            class="btn btn-primary btn-sm"
                        >
                            إضافة الكل
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
                                            :checked="isSizeSelected(size.id)"
                                            @change="toggleSize(size, $event.target.checked)"
                                            class="form-checkbox"
                                        >
                                        <span class="ml-2 font-medium" x-text="size.size_name"></span>
                                    </label>
                                    <span class="text-sm text-gray-500" x-text="'متاح: ' + size.quantity"></span>
                                </div>

                                <div x-show="isSizeSelected(size.id)" class="space-y-2">
                                    <div>
                                        <label class="form-label text-sm">الكمية المرجعة</label>
                                        <input
                                            type="number"
                                            min="1"
                                            :value="getSizeQuantity(size.id)"
                                            @input="updateSizeQuantity(size.id, $event.target.value)"
                                            class="form-input"
                                            placeholder="الكمية"
                                        >
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- جدول المواد المختارة -->
            <div x-show="selectedItems.length > 0" class="space-y-4">
                <!-- كاردات الإجمالي -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div class="panel">
                        <div class="flex items-center justify-between mb-4">
                            <h5 class="font-semibold text-lg dark:text-white-light">عدد القطع الإجمالي</h5>
                            <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-success" x-text="getTotalPieces()"></div>
                    </div>

                    <div class="panel">
                        <div class="flex items-center justify-between mb-4">
                            <h5 class="font-semibold text-lg dark:text-white-light">قيمة الفاتورة</h5>
                            <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-info" x-text="getTotalAmount() + ' د.ع'"></div>
                    </div>
                </div>

                <h6 class="font-semibold">المواد المختارة للإرجاع</h6>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>القياس</th>
                                <th>الكمية</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in selectedItems" :key="index">
                                <tr>
                                    <td x-text="item.productName"></td>
                                    <td x-text="item.sizeName"></td>
                                    <td>
                                        <input
                                            type="number"
                                            x-model="item.quantity"
                                            min="1"
                                            class="form-input w-20"
                                            @input="updateItemQuantity(index, $event.target.value)"
                                        >
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            @click="removeItem(index)"
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

                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="submitReturn()"
                        class="btn btn-success"
                        :disabled="delegateId === '' || warehouseId === '' || selectedItems.length === 0"
                    >
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        إرجاع المواد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bulkReturnForm() {
            return {
                delegateId: '',
                warehouseId: '',
                searchQuery: '',
                searchResults: [],
                highlightedIndex: -1,
                selectedProduct: null,
                selectedSizes: [],
                selectedItems: [],
                warehouses: @json($warehouses),

                async searchProducts() {
                    if (this.searchQuery.length < 2) {
                        this.searchResults = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route('admin.bulk-returns.search') }}?search=${this.searchQuery}`);
                        const data = await response.json();
                        this.searchResults = data;
                    } catch (error) {
                        console.error('خطأ في البحث:', error);
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
                },

                selectProduct(product) {
                    this.selectedProduct = product;
                    this.searchQuery = '';
                    this.searchResults = [];
                    this.selectedSizes = [];
                },

                toggleSize(size, checked) {
                    if (checked) {
                        this.selectedSizes.push({
                            id: size.id,
                            sizeName: size.size_name,
                            quantity: 1,
                        });
                    } else {
                        this.selectedSizes = this.selectedSizes.filter(s => s.id !== size.id);
                    }
                },

                isSizeSelected(sizeId) {
                    return this.selectedSizes.some(s => s.id === sizeId);
                },

                getSizeQuantity(sizeId) {
                    const size = this.selectedSizes.find(s => s.id === sizeId);
                    return size ? size.quantity : 1;
                },

                updateSizeQuantity(sizeId, quantity) {
                    const size = this.selectedSizes.find(s => s.id === sizeId);
                    if (size) {
                        size.quantity = parseInt(quantity) || 1;
                    }
                },

                addAllSelectedSizes() {
                    if (this.selectedSizes.length === 0) {
                        alert('يرجى اختيار قياس واحد على الأقل');
                        return;
                    }

                    // إضافة جميع القياسات المحددة
                    this.selectedSizes.forEach(size => {
                        // التحقق من عدم تكرار نفس القياس
                        if (!this.selectedItems.some(item => item.sizeId === size.id)) {
                            this.selectedItems.push({
                                productId: this.selectedProduct.id,
                                productName: this.selectedProduct.name,
                                sizeId: size.id,
                                sizeName: size.sizeName,
                                quantity: size.quantity,
                                sellingPrice: this.selectedProduct.selling_price || 0,
                                warehouseId: this.warehouseId,
                                warehouseName: this.warehouses.find(w => w.id == this.warehouseId)?.name || ''
                            });
                        }
                    });

                    // إفراغ القياسات المحددة لإعدادها للبحث التالي
                    this.selectedSizes = [];
                    this.selectedProduct = null;
                    this.searchQuery = '';

                    // رسالة تأكيد
                    console.log('تم إضافة المواد. إجمالي المواد المختارة:', this.selectedItems.length);
                },

                updateItemQuantity(index, quantity) {
                    this.selectedItems[index].quantity = parseInt(quantity) || 1;
                },

                removeItem(index) {
                    this.selectedItems.splice(index, 1);
                },

                getTotalPieces() {
                    return this.selectedItems.reduce((total, item) => total + (parseInt(item.quantity) || 0), 0);
                },

                getTotalAmount() {
                    return this.selectedItems.reduce((total, item) => {
                        const quantity = parseInt(item.quantity) || 0;
                        const price = parseFloat(item.sellingPrice) || 0;
                        return total + (quantity * price);
                    }, 0).toFixed(2);
                },

                async submitReturn() {
                    if (this.delegateId === '') {
                        alert('يرجى اختيار المندوب');
                        return;
                    }

                    if (this.warehouseId === '') {
                        alert('يرجى اختيار المخزن');
                        return;
                    }

                    if (this.selectedItems.length === 0) {
                        alert('يرجى إضافة مواد للإرجاع');
                        return;
                    }

                    // التحقق من اكتمال البيانات
                    const incompleteItems = this.selectedItems.filter(item =>
                        !item.warehouseId || item.quantity <= 0
                    );

                    if (incompleteItems.length > 0) {
                        alert('يرجى التأكد من إدخال جميع البيانات بشكل صحيح');
                        return;
                    }

                    if (!confirm('هل أنت متأكد من إرجاع هذه المواد؟')) {
                        return;
                    }

                    try {
                        // تحويل البيانات إلى snake_case
                        const requestData = {
                            delegate_id: this.delegateId,
                            warehouse_id: this.warehouseId,
                            items: this.selectedItems.map(item => ({
                                product_id: item.productId,
                                size_id: item.sizeId,
                                quantity: parseInt(item.quantity) || 1
                            }))
                        };

                        console.log('بيانات الإرسال:', requestData);

                        const response = await fetch('{{ route('admin.bulk-returns.store') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(requestData)
                        });

                        // التحقق من نوع الرد
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('رد غير متوقع:', text);
                            alert('حدث خطأ. يرجى التحقق من الكونسول.');
                            return;
                        }

                        const data = await response.json();

                        if (data.success) {
                            alert('تم إرجاع المواد بنجاح!');
                            location.reload();
                        } else {
                            alert('خطأ: ' + data.message);
                        }
                    } catch (error) {
                        console.error('خطأ:', error);
                        alert('حدث خطأ أثناء الإرجاع');
                    }
                }
            }
        }
    </script>
</x-layout.admin>
