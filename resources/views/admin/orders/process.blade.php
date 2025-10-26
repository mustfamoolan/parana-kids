<x-layout.admin>
    <div x-data="orderProcessForm()">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تجهيز الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلب
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.orders.process.submit', $order) }}" id="processForm">
            @csrf

            <!-- معلومات الزبون -->
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4">معلومات الزبون</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            اسم الزبون <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            class="form-input @error('customer_name') border-red-500 @enderror"
                            value="{{ old('customer_name', $order->customer_name) }}"
                            required
                        >
                        <button type="button" onclick="copyToClipboard('customer_name')" class="btn btn-sm btn-outline-secondary mt-2">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ
                        </button>
                        @error('customer_name')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            رقم الهاتف <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="customer_phone"
                            name="customer_phone"
                            class="form-input @error('customer_phone') border-red-500 @enderror"
                            value="{{ old('customer_phone', $order->customer_phone) }}"
                            required
                        >
                        <button type="button" onclick="copyToClipboard('customer_phone')" class="btn btn-sm btn-outline-secondary mt-2">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ
                        </button>
                        @error('customer_phone')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="customer_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            العنوان <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="customer_address"
                            name="customer_address"
                            rows="3"
                            class="form-textarea @error('customer_address') border-red-500 @enderror"
                            required
                        >{{ old('customer_address', $order->customer_address) }}</textarea>
                        <button type="button" onclick="copyToClipboard('customer_address')" class="btn btn-sm btn-outline-secondary mt-2">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ
                        </button>
                        @error('customer_address')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="customer_social_link" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            رابط السوشل ميديا <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="url"
                            id="customer_social_link"
                            name="customer_social_link"
                            class="form-input @error('customer_social_link') border-red-500 @enderror"
                            value="{{ old('customer_social_link', $order->customer_social_link) }}"
                            required
                        >
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="openLink('customer_social_link')" class="btn btn-sm btn-outline-info">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                فتح
                            </button>
                            <button type="button" onclick="copyToClipboard('customer_social_link')" class="btn btn-sm btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                        </div>
                        @error('customer_social_link')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ملاحظات
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="3"
                            class="form-textarea @error('notes') border-red-500 @enderror"
                            placeholder="ملاحظات إضافية..."
                        >{{ old('notes', $order->notes) }}</textarea>
                        <button type="button" onclick="copyToClipboard('notes')" class="btn btn-sm btn-outline-secondary mt-2">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ
                        </button>
                        @error('notes')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- المنتجات الحالية -->
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4">منتجات الطلب</h6>
                <template x-for="(item, index) in items" :key="index">
                    <div class="flex items-center gap-4 p-4 border rounded mb-3">
                        <!-- صورة المنتج -->
                        <div class="w-16 h-16 flex-shrink-0">
                            <img :src="item.product_image" :alt="item.product_name" class="w-full h-full object-cover rounded cursor-pointer hover:opacity-80" @click="openImageModal(item.product_image, item.product_name)">
                        </div>

                        <!-- اسم المنتج -->
                        <div class="flex-1">
                            <div class="font-semibold" x-text="item.product_name"></div>
                            <div class="text-sm text-gray-500" x-text="item.product_code"></div>
                        </div>

                        <!-- القياس (قابل للتغيير) -->
                        <div class="w-32">
                            <select @change="changeItemSize(index, $event.target.value)" class="form-select text-sm">
                                <template x-for="size in getProductSizes(item.product_id)" :key="size.id">
                                    <option :value="size.id" :selected="size.id == item.size_id" x-text="size.size_name + ' (' + size.available_quantity + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- الكمية (قابلة للتعديل) -->
                        <div class="flex items-center gap-2 w-24">
                            <button type="button" @click="item.quantity > 1 ? item.quantity-- : null" class="btn btn-sm btn-outline-danger">-</button>
                            <input type="number" x-model="item.quantity" @change="updateItemQuantity(index)" class="form-input w-16 text-center text-sm" min="1" :max="item.max_quantity">
                            <button type="button" @click="item.quantity < item.max_quantity ? item.quantity++ : null" class="btn btn-sm btn-outline-success">+</button>
                        </div>

                        <!-- السعر والمجموع -->
                        <div class="text-right w-32">
                            <div class="text-sm text-gray-500" x-text="formatPrice(item.unit_price) + ' × ' + item.quantity"></div>
                            <div class="font-bold text-success" x-text="formatPrice(item.subtotal)"></div>
                        </div>

                        <!-- زر الحذف -->
                        <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </template>

                <div x-show="items.length === 0" class="text-center py-8 text-gray-500">
                    <p>لا توجد منتجات في هذا الطلب.</p>
                </div>
            </div>

            <!-- إضافة منتجات جديدة -->
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4">إضافة منتجات جديدة</h6>

                <!-- بحث عن المنتج -->
                <div class="mb-4">
                    <input
                        type="text"
                        x-model="searchTerm"
                        class="form-input"
                        placeholder="ابحث بالكود أو الاسم..."
                        @input="searchTerm = $event.target.value"
                    >
                </div>

                <!-- نتائج البحث -->
                <div x-show="filteredProducts.length > 0" class="mb-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <div class="border rounded p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800" @click="selectProduct(product)">
                                <div class="flex items-center gap-3">
                                    <img :src="product.primary_image" :alt="product.name" class="w-12 h-12 object-cover rounded">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm" x-text="product.name"></div>
                                        <div class="text-xs text-gray-500" x-text="product.code"></div>
                                        <div class="text-xs text-primary font-bold" x-text="formatPrice(product.selling_price)"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- اختيار القياس والكمية -->
                <div x-show="selectedProduct" class="border rounded p-4 bg-gray-50 dark:bg-gray-800">
                    <div class="flex items-center gap-3 mb-3">
                        <img :src="selectedProduct.primary_image" :alt="selectedProduct.name" class="w-12 h-12 object-cover rounded">
                        <div>
                            <div class="font-semibold" x-text="selectedProduct.name"></div>
                            <div class="text-sm text-gray-500" x-text="selectedProduct.code"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-2">اختر القياس:</label>
                            <select x-model="selectedSize" class="form-select">
                                <option value="">اختر القياس</option>
                                <template x-for="size in selectedProduct.sizes" :key="size.id">
                                    <option :value="size.id" x-text="size.size_name + ' (' + size.available_quantity + ' متوفر)'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">الكمية:</label>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="quantity > 1 ? quantity-- : null" class="btn btn-sm btn-outline-danger">-</button>
                                <input type="number" x-model="quantity" class="form-input w-20 text-center" min="1" :max="selectedSize ? getSelectedSizeMaxQuantity() : 1">
                                <button type="button" @click="quantity < getSelectedSizeMaxQuantity() ? quantity++ : null" class="btn btn-sm btn-outline-success">+</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-3">
                        <button type="button" @click="addProduct()" class="btn btn-primary btn-sm" :disabled="!selectedSize || !quantity">
                            إضافة للمنتجات
                        </button>
                        <button type="button" @click="cancelProductSelection()" class="btn btn-outline-secondary btn-sm">
                            إلغاء
                        </button>
                    </div>
                </div>
            </div>

            <!-- كود التوصيل -->
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4">كود التوصيل (كود الوسيط)</h6>
                <input
                    type="text"
                    x-model="deliveryCode"
                    name="delivery_code"
                    class="form-input"
                    placeholder="أدخل كود شركة التوصيل"
                    required
                >
                <button type="button" onclick="copyToClipboard('deliveryCode')" class="btn btn-sm btn-outline-secondary mt-2">
                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    نسخ
                </button>
            </div>

            <!-- ملخص وأزرار -->
            <div class="panel">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-semibold">الإجمالي:</span>
                    <span class="text-2xl font-bold text-success" x-text="formatPrice(totalAmount)"></span>
                </div>

                <!-- إخفاء الحقول المخفية للعناصر -->
                <template x-for="(item, index) in items" :key="index">
                    <div>
                        <input type="hidden" :name="`items[${index}][product_id]`" :value="item.product_id">
                        <input type="hidden" :name="`items[${index}][size_id]`" :value="item.size_id">
                        <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                    </div>
                </template>

                <div class="flex gap-3">
                    <button type="button" @click="submitOrder()" class="btn btn-primary flex-1">
                        تجهيز وتقييد الطلب
                    </button>
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
                        إلغاء
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal لتكبير الصورة -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4">
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
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderProcessForm', () => ({
                items: {!! json_encode($order->items->map(function($item) {
                    return [
                        'product_id' => $item->product_id,
                        'size_id' => $item->size_id,
                        'product_name' => $item->product_name,
                        'product_code' => $item->product_code,
                        'size_name' => $item->size_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'max_quantity' => $item->size ? $item->size->quantity : 0,
                        'product_image' => $item->product->primaryImage ? $item->product->primaryImage->image_url : '/assets/images/no-image.png'
                    ];
                })) !!},
                products: {!! json_encode($products->map(function($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'selling_price' => $product->selling_price,
                        'primary_image' => $product->primaryImage ? $product->primaryImage->image_url : '/assets/images/no-image.png',
                        'sizes' => $product->sizes->map(function($size) {
                            return [
                                'id' => $size->id,
                                'size_name' => $size->size_name,
                                'available_quantity' => $size->quantity
                            ];
                        })
                    ];
                })) !!},
                searchTerm: '',
                selectedProduct: null,
                selectedSize: null,
                quantity: 1,
                deliveryCode: '{{ $order->delivery_code ?? '' }}',

                get totalAmount() {
                    return this.items.reduce((sum, item) => sum + item.subtotal, 0);
                },

                get filteredProducts() {
                    if (!this.searchTerm) return [];
                    return this.products.filter(p =>
                        p.name.includes(this.searchTerm) ||
                        p.code.includes(this.searchTerm)
                    );
                },

                getProductSizes(productId) {
                    const product = this.products.find(p => p.id === productId);
                    return product ? product.sizes : [];
                },

                updateItemQuantity(index) {
                    const item = this.items[index];
                    item.subtotal = item.quantity * item.unit_price;
                },

                changeItemSize(index, newSizeId) {
                    const item = this.items[index];
                    const product = this.products.find(p => p.id === item.product_id);
                    const newSize = product.sizes.find(s => s.id == newSizeId);

                    if (newSize) {
                        item.size_id = newSize.id;
                        item.size_name = newSize.size_name;
                        item.max_quantity = newSize.available_quantity;

                        // تحديث الكمية إذا كانت أكبر من المتوفر
                        if (item.quantity > newSize.available_quantity) {
                            item.quantity = newSize.available_quantity;
                        }

                        this.updateItemQuantity(index);
                    }
                },

                removeItem(index) {
                    if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                        this.items.splice(index, 1);
                    }
                },

                selectProduct(product) {
                    this.selectedProduct = product;
                    this.selectedSize = null;
                    this.quantity = 1;
                },

                cancelProductSelection() {
                    this.selectedProduct = null;
                    this.selectedSize = null;
                    this.quantity = 1;
                    this.searchTerm = '';
                },

                getSelectedSizeMaxQuantity() {
                    if (!this.selectedSize) return 1;
                    const size = this.selectedProduct.sizes.find(s => s.id == this.selectedSize);
                    return size ? size.available_quantity : 1;
                },

                addProduct() {
                    if (!this.selectedProduct || !this.selectedSize || !this.quantity) {
                        alert('يرجى اختيار المنتج والقياس والكمية');
                        return;
                    }

                    const size = this.selectedProduct.sizes.find(s => s.id == this.selectedSize);
                    const subtotal = this.quantity * this.selectedProduct.selling_price;

                    this.items.push({
                        product_id: this.selectedProduct.id,
                        size_id: this.selectedSize,
                        product_name: this.selectedProduct.name,
                        product_code: this.selectedProduct.code,
                        size_name: size.size_name,
                        quantity: this.quantity,
                        unit_price: this.selectedProduct.selling_price,
                        subtotal: subtotal,
                        max_quantity: size.available_quantity,
                        product_image: this.selectedProduct.primary_image
                    });

                    // إعادة تعيين
                    this.cancelProductSelection();
                },

                async submitOrder() {
                    if (this.items.length === 0) {
                        alert('يجب إضافة منتج واحد على الأقل');
                        return;
                    }

                    if (!this.deliveryCode) {
                        alert('يرجى إدخال كود التوصيل');
                        return;
                    }

                    if (confirm('هل أنت متأكد من تجهيز وتقييد هذا الطلب؟ لا يمكن التراجع عن هذا الإجراء.')) {
                        document.getElementById('processForm').submit();
                    }
                },

                formatPrice(price) {
                    return new Intl.NumberFormat('ar-IQ').format(price) + ' د.ع';
                }
            }));
        });

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

        // دالة نسخ النص إلى الحافظة
        function copyToClipboard(elementId) {
            let element;
            if (elementId === 'deliveryCode') {
                // للعنصر الذي يستخدم Alpine.js
                element = document.querySelector('[x-model="deliveryCode"]');
            } else {
                element = document.getElementById(elementId);
            }

            if (element) {
                element.select();
                element.setSelectionRange(0, 99999); // للهواتف المحمولة

                try {
                    document.execCommand('copy');
                    showCopyNotification('تم نسخ النص بنجاح!');
                } catch (err) {
                    // استخدام Clipboard API إذا كان متاحاً
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(element.value).then(function() {
                            showCopyNotification('تم نسخ النص بنجاح!');
                        });
                    } else {
                        showCopyNotification('فشل في نسخ النص');
                    }
                }
            }
        }

        // دالة فتح الرابط
        function openLink(elementId) {
            const element = document.getElementById(elementId);
            if (element && element.value) {
                let url = element.value;
                // إضافة http:// إذا لم يكن موجوداً
                if (!url.match(/^https?:\/\//)) {
                    url = 'http://' + url;
                }
                window.open(url, '_blank');
            }
        }

        // دالة إظهار إشعار النسخ
        function showCopyNotification(message) {
            // إنشاء عنصر الإشعار
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
            notification.textContent = message;

            // إضافة الإشعار للصفحة
            document.body.appendChild(notification);

            // إزالة الإشعار بعد 3 ثوان
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</x-layout.admin>
