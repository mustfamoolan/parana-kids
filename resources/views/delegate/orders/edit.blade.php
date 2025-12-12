<x-layout.default>
    <div x-data="orderEditForm()">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل الطلب: {{ $order->order_number }}</h5>
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
                        $backUrl = route('delegate.orders.show', $order);
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

        @if($order->status !== 'pending' && !$order->canBeEdited())
            <div class="panel mb-5">
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <h6 class="font-semibold text-red-700 dark:text-red-300">لا يمكن تعديل هذا الطلب</h6>
                        <p class="text-sm text-red-600 dark:text-red-400">مر أكثر من 5 ساعات على تقييد هذا الطلب</p>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('delegate.orders.update', $order) }}" id="editForm">
            @method('PUT')
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
                            type="tel"
                            id="customer_phone"
                            name="customer_phone"
                            class="form-input @error('customer_phone') border-red-500 @enderror"
                            value="{{ old('customer_phone', $order->customer_phone) }}"
                            placeholder="07742209251"
                            oninput="formatPhoneNumber(this)"
                            onpaste="handlePhonePaste(event)"
                            required
                        >
                        <p id="phone_error" class="text-danger text-xs mt-1" style="display: none;">الرقم يجب أن يكون بالضبط 11 رقم بعد التنسيق</p>
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

                    <div>
                        <label for="customer_phone2" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            رقم الهاتف الثاني (اختياري)
                        </label>
                        <input
                            type="tel"
                            id="customer_phone2"
                            name="customer_phone2"
                            class="form-input @error('customer_phone2') border-red-500 @enderror"
                            value="{{ old('customer_phone2', $order->customer_phone2) }}"
                            placeholder="07742209251"
                            oninput="formatPhoneNumber2(this)"
                            onpaste="handlePhonePaste2(event)"
                        >
                        <p id="phone2_error" class="text-danger text-xs mt-1" style="display: none;">الرقم يجب أن يكون بالضبط 11 رقم بعد التنسيق</p>
                        <button type="button" onclick="copyToClipboard('customer_phone2')" class="btn btn-sm btn-outline-secondary mt-2">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ
                        </button>
                        @error('customer_phone2')
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="panel">
                            <div class="flex items-center gap-3 mb-3">
                                <button type="button" @click="openImageModal(item.product_image, item.product_name)" class="w-16 h-16 flex-shrink-0 rounded overflow-hidden">
                                    <img :src="item.product_image" :alt="item.product_name" class="w-full h-full object-cover hover:opacity-90 cursor-pointer">
                                </button>
                                <div class="flex-1">
                                    <div class="font-semibold text-sm" x-text="item.product_name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.product_code"></div>
                                </div>
                                <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-2">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">القياس:</span>
                                    <select @change="changeItemSize(index, $event.target.value)" class="form-select text-sm mt-1">
                                        <template x-for="size in getProductSizes(item.product_id)" :key="size.id">
                                            <option :value="size.id" :selected="size.id == item.size_id" x-text="size.size_name + ' (' + size.available_quantity + ')'"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mb-2 block">الكمية:</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="if(item.quantity > 1){ item.quantity--; normalizeAndUpdate(index); }" class="btn btn-sm btn-outline-danger">-</button>
                                        <input type="number" x-model="item.quantity" @input="normalizeAndUpdate(index)" class="form-input w-20 text-center text-sm" min="1" :max="item.max_quantity">
                                        <button type="button" @click="if(item.quantity < item.max_quantity){ item.quantity++; normalizeAndUpdate(index); }" class="btn btn-sm btn-outline-success">+</button>
                                    </div>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">سعر الوحدة:</span>
                                    <div class="font-medium" x-text="formatPrice(item.unit_price)"></div>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">الإجمالي:</span>
                                    <div class="font-bold text-success" x-text="formatPrice(item.subtotal)"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

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
                    <button type="button" @click="submitOrder()" class="btn btn-success flex-1">
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('delegate.orders.show', $order) }}" class="btn btn-outline-secondary">
                        إلغاء
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Modal لتكبير الصورة -->
    <div id="imageModal" class="fixed inset-0 bg-black/80 z-[9999] hidden items-center justify-center p-4">
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
            Alpine.data('orderEditForm', () => ({
                items: {!! json_encode($order->items->map(function($item) use ($order) {
                    // للطلبات المقيدة: الكمية المتاحة = الكمية في المخزن + الكمية الحالية في الطلب
                    // للطلبات pending: الكمية المتاحة = الكمية في المخزن فقط
                    $maxQuantity = $item->size ? $item->size->quantity : 0;
                    if ($order->status === 'confirmed' && $item->size) {
                        $maxQuantity += $item->quantity;
                    }

                    return [
                        'product_id' => (int)$item->product_id,
                        'size_id' => (int)$item->size_id,
                        'product_name' => $item->product_name,
                        'product_code' => $item->product_code,
                        'size_name' => $item->size_name,
                        'quantity' => (int)$item->quantity,
                        'unit_price' => (float)$item->unit_price,
                        'subtotal' => (float)$item->subtotal,
                        'max_quantity' => (int)$maxQuantity,
                        'product_image' => $item->product->primaryImage ? $item->product->primaryImage->image_url : '/assets/images/no-image.png'
                    ];
                })) !!},
                products: {!! json_encode($products->map(function($product) {
                    return [
                        'id' => (int)$product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'selling_price' => (float)$product->effective_price,
                        'primary_image' => $product->primaryImage ? $product->primaryImage->image_url : '/assets/images/no-image.png',
                        'sizes' => $product->sizes->map(function($size) {
                            return [
                                'id' => (int)$size->id,
                                'size_name' => $size->size_name,
                                'available_quantity' => (int)$size->quantity
                            ];
                        })
                    ];
                })) !!},
                searchTerm: '',
                selectedProduct: null,
                selectedSize: null,
                quantity: 1,

                get totalAmount() {
                    if (!this.items || this.items.length === 0) return 0;
                    return this.items.reduce((sum, item) => {
                        const subtotal = Number(item.subtotal) || 0;
                        return sum + subtotal;
                    }, 0);
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
                    if (!item) return;
                    const max = Number(item?.max_quantity || 1);
                    let q = Math.floor(Number(item?.quantity || 1));
                    if (q < 1) q = 1;
                    if (q > max) q = max;
                    item.quantity = q;
                    const unitPrice = Number(item?.unit_price || 0);
                    item.subtotal = q * unitPrice;
                },

                normalizeAndUpdate(index) {
                    // توحيد معالجة الكمية لضمان عدم التعليق مع النصوص والأرقام
                    this.updateItemQuantity(index);
                },

                changeItemSize(index, newSizeId) {
                    const item = this.items[index];
                    const product = this.products.find(p => p.id === item.product_id);
                    const newSize = product.sizes.find(s => s.id == newSizeId);

                    if (newSize) {
                        item.size_id = Number(newSize.id);
                        item.size_name = newSize.size_name;
                        item.max_quantity = Number(newSize.available_quantity);
                        this.normalizeAndUpdate(index);
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

                    if (!size) {
                        alert('حدث خطأ: القياس غير موجود');
                        return;
                    }

                    const unitPrice = parseFloat(this.selectedProduct.selling_price) || 0;
                    const qty = parseInt(this.quantity) || 1;
                    const subtotal = qty * unitPrice;

                    this.items.push({
                        product_id: parseInt(this.selectedProduct.id),
                        size_id: parseInt(this.selectedSize),
                        product_name: this.selectedProduct.name,
                        product_code: this.selectedProduct.code,
                        size_name: size.size_name,
                        quantity: qty,
                        unit_price: unitPrice,
                        subtotal: subtotal,
                        max_quantity: parseInt(size.available_quantity) || 0,
                        product_image: this.selectedProduct.primary_image
                    });

                    alert('تم إضافة المنتج بنجاح! الإجمالي: ' + this.items.length);

                    // إعادة تعيين
                    this.cancelProductSelection();
                },

                async submitOrder() {
                    if (this.items.length === 0) {
                        alert('يجب إضافة منتج واحد على الأقل');
                        return;
                    }

                    if (confirm('هل أنت متأكد من حفظ التعديلات على هذا الطلب؟')) {
                        document.getElementById('editForm').submit();
                    }
                },

                formatPrice(price) {
                    const num = Number(price);
                    if (isNaN(num) || !isFinite(num)) {
                        return '0 د.ع';
                    }
                    return new Intl.NumberFormat('en-US').format(num) + ' د.ع';
                }
            }));
        });

        function openImageModal(imageUrl, productName) {
            const modal = document.getElementById('imageModal');
            if (!modal) return;

            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalImage').alt = productName || 'صورة المنتج';
            document.getElementById('modalTitle').textContent = productName || 'صورة المنتج';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');
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

        // دالة تحويل الأرقام العربية إلى إنجليزية
        function convertArabicToEnglishNumbers(str) {
            const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            let result = str;
            for (let i = 0; i < arabicNumbers.length; i++) {
                result = result.replace(new RegExp(arabicNumbers[i], 'g'), englishNumbers[i]);
            }
            return result;
        }

        // معالجة اللصق
        function handlePhonePaste(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const convertedText = convertArabicToEnglishNumbers(pastedText);
            const input = e.target;
            input.value = convertedText;
            formatPhoneNumber(input);
        }

        function formatPhoneNumber(input) {
            let value = input.value;

            // تحويل الأرقام العربية إلى إنجليزية أولاً
            value = convertArabicToEnglishNumbers(value);

            // إزالة كل شيء غير الأرقام
            let cleaned = value.replace(/[^0-9]/g, '');

            // إزالة البادئات الدولية
            if (cleaned.startsWith('00964')) {
                cleaned = cleaned.substring(5); // إزالة 00964
            } else if (cleaned.startsWith('964')) {
                cleaned = cleaned.substring(3); // إزالة 964
            }

            // إضافة 0 في البداية إذا لم تكن موجودة
            if (cleaned.length > 0 && !cleaned.startsWith('0')) {
                cleaned = '0' + cleaned;
            }

            // التأكد من 11 رقم فقط - إذا كان أكثر من 11، نأخذ أول 11 رقم
            if (cleaned.length > 11) {
                cleaned = cleaned.substring(0, 11);
            }

            // تحديث قيمة الحقل
            input.value = cleaned;

            // التحقق من أن الرقم بالضبط 11 رقم
            const errorElement = document.getElementById('phone_error');
            const form = input.closest('form');
            const submitButton = form.querySelector('button[type="submit"]');

            if (cleaned.length > 0 && cleaned.length !== 11) {
                if (errorElement) errorElement.style.display = 'block';
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.style.opacity = '0.5';
                    submitButton.style.cursor = 'not-allowed';
                }
            } else {
                if (errorElement) errorElement.style.display = 'none';
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.style.opacity = '1';
                    submitButton.style.cursor = 'pointer';
                }
            }
        }

        // معالجة اللصق للهاتف الثاني
        function handlePhonePaste2(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const convertedText = convertArabicToEnglishNumbers(pastedText);
            const input = e.target;
            input.value = convertedText;
            formatPhoneNumber2(input);
        }

        function formatPhoneNumber2(input) {
            let value = input.value;

            // تحويل الأرقام العربية إلى إنجليزية أولاً
            value = convertArabicToEnglishNumbers(value);

            // إزالة كل شيء غير الأرقام
            let cleaned = value.replace(/[^0-9]/g, '');

            // إزالة البادئات الدولية
            if (cleaned.startsWith('00964')) {
                cleaned = cleaned.substring(5); // إزالة 00964
            } else if (cleaned.startsWith('964')) {
                cleaned = cleaned.substring(3); // إزالة 964
            }

            // إضافة 0 في البداية إذا لم تكن موجودة
            if (cleaned.length > 0 && !cleaned.startsWith('0')) {
                cleaned = '0' + cleaned;
            }

            // التأكد من 11 رقم فقط - إذا كان أكثر من 11، نأخذ أول 11 رقم
            if (cleaned.length > 11) {
                cleaned = cleaned.substring(0, 11);
            }

            // تحديث قيمة الحقل
            input.value = cleaned;

            // التحقق من أن الرقم بالضبط 11 رقم (اختياري - لا نعطل الزر)
            const errorElement = document.getElementById('phone2_error');
            if (cleaned.length > 0 && cleaned.length !== 11) {
                if (errorElement) errorElement.style.display = 'block';
            } else {
                if (errorElement) errorElement.style.display = 'none';
            }
        }

        // تطبيق التنسيق عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value) {
                formatPhoneNumber(phoneInput);
            }
            const phone2Input = document.getElementById('customer_phone2');
            if (phone2Input && phone2Input.value) {
                formatPhoneNumber2(phone2Input);
            }
        });

        // التحقق من وجود قياس واحد على الأقل قبل الإرسال
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value) {
                const cleaned = phoneInput.value.replace(/[^0-9]/g, '');
                if (cleaned.length !== 11) {
                    e.preventDefault();
                    alert('الرقم يجب أن يكون بالضبط 11 رقم');
                    return false;
                }
            }
        });
    </script>

    <!-- Timeline حالات الطلب من الوسيط -->
    @if($order->alwaseetShipment)
        <div class="mt-5">
            <x-alwaseet-status-timeline 
                :timeline="$order->alwaseetShipment->getStatusTimelineWithCache()" 
                :currentStatusId="$order->alwaseetShipment->status_id" 
            />
        </div>
    @endif
</x-layout.default>
