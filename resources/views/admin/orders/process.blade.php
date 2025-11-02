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

            <!-- المنتجات الحالية (للقراءة فقط) -->
            <div class="panel mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="text-lg font-semibold">منتجات الطلب</h6>
                    <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-warning btn-sm">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        تعديل المنتجات
                    </a>
                </div>
                <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        <strong>ملاحظة:</strong> للتعديل على المنتجات أو إضافتها، يرجى استخدام صفحة <a href="{{ route('admin.orders.edit', $order) }}" class="underline font-semibold">تعديل الطلب</a>.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="panel">
                            <div class="flex items-center gap-3 mb-3">
                                <button type="button" @click="openImageModal(item.product_image, item.product_name)" class="w-14 h-14 flex-shrink-0 rounded overflow-hidden">
                                    <img :src="item.product_image" :alt="item.product_name" class="w-full h-full object-cover hover:opacity-90">
                                </button>
                                <div class="flex-1">
                                    <div class="font-semibold" x-text="item.product_name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.product_code"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">القياس</label>
                                    <div class="font-medium" x-text="item.size_name"></div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">الكمية</label>
                                    <div class="font-medium" x-text="item.quantity"></div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">سعر الوحدة</label>
                                    <div class="font-medium" x-text="formatPrice(item.unit_price)"></div>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">الإجمالي</label>
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

            <!-- صندوق أسماء المنتجات للنص الجاهز + زر النسخ -->
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4">أسماء المنتجات</h6>
                <div class="flex gap-2 items-start">
                    <textarea class="form-textarea w-full overflow-auto" rows="2" wrap="off" readonly x-text="productNamesText"></textarea>
                    <button type="button" class="btn btn-outline-secondary whitespace-nowrap"
                        @click="navigator.clipboard.writeText(productNamesText).then(()=>showCopyNotification('تم نسخ النص بنجاح!'))">
                        نسخ
                    </button>
                </div>
            </div>

            <!-- ملخص وأزرار -->
            <div class="panel">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-semibold">الإجمالي:</span>
                    <span class="text-2xl font-bold text-success" x-text="formatPrice(totalAmount)"></span>
                </div>


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
                        'product_name' => optional($item->product)->name ?? $item->product_name,
                        'product_code' => optional($item->product)->code ?? $item->product_code,
                        'size_name' => optional($item->size)->size_name ?? $item->size_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'subtotal' => $item->subtotal,
                        'max_quantity' => $item->size ? $item->size->quantity : 0,
                        'product_image' => optional(optional($item->product)->primaryImage)->image_url ?? '/assets/images/no-image.png'
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
                    return this.items.reduce((sum, item) => Number(sum) + Number(item?.subtotal ?? 0), 0);
                },

                get productNamesText() {
                    const parts = this.items
                        .map(i => {
                            const rawName = (i?.product_name || '');
                            const name = rawName.includes('(') ? rawName.split('(')[0].trim() : rawName.trim();
                            if (!name) return '';
                            const qty = Number(i?.quantity || 0);
                            // إذا الكمية >1: الاسم ثم "عدد N" ثم علامة + ، وإلا: الاسم ثم + فقط
                            return qty > 1 ? `${name}عدد ${qty}+` : `${name}+`;
                        })
                        .filter(Boolean);
                    const joined = parts.join('');
                    const prefix = 'فتح وتصوير الطلب امام الزبون قبل التسليم عند القياس';

                    // الإجماليات
                    const totalQty = this.items.reduce((s, it) => s + Number(it?.quantity || 0), 0);
                    // حساب مباشر: كمية × سعر للوحدة لكل عنصر لضمان الدقة حتى مع تأخير تحديث subtotal
                    const totalAmount = this.items.reduce((s, it) => s + (Number(it?.quantity || 0) * Number(it?.unit_price || 0)), 0);
                    const totalAmountWithFee = totalAmount + 5000; // إضافة 5000 تلقائياً
                    const totalAmountTxt = new Intl.NumberFormat('en-US').format(totalAmountWithFee);
                    // إزالة الأقواس وعلامة الـ pipe واستبدالها بمسافة
                    const totals = ` اجمالي العدد ${totalQty} اجمالي المبلغ ${totalAmountTxt}`;

                    // استبدال السطر الجديد بمسافة وإزالة أي علامات ترقيم أخرى
                    const fullText = joined ? `${prefix} ${joined}${totals}` : `${prefix} ${totals}`;
                    // إزالة أي علامات ترقيم متبقية (فقط الأرقام والنص والمسافات)
                    return fullText.replace(/[^\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\uFB50-\uFDFF\uFE70-\uFEFF\u0020\u0030-\u0039\u0061-\u007A\u0041-\u005A]/g, ' ');
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
                    const max = Number(item?.max_quantity || 1);
                    let q = Math.floor(Number(item?.quantity || 1));
                    if (q < 1) q = 1;
                    if (q > max) q = max;
                    item.quantity = q;
                    item.subtotal = q * Number(item.unit_price || 0);
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
                        item.size_id = newSize.id;
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
                    if (!this.deliveryCode) {
                        alert('يرجى إدخال كود التوصيل');
                        return;
                    }

                    if (confirm('هل أنت متأكد من تجهيز وتقييد هذا الطلب؟ لا يمكن التراجع عن هذا الإجراء.')) {
                        document.getElementById('processForm').submit();
                    }
                },

                formatPrice(price) {
                    return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
                },

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

        // تحويل الأرقام إلى كلمات عربية (مبسطة للأعداد الصحيحة حتى المليار)
        function toArabicWords(num) {
            num = Math.floor(Number(num) || 0);
            if (num === 0) return 'صفر';
            const units = ['','واحد','اثنان','ثلاثة','أربعة','خمسة','ستة','سبعة','ثمانية','تسعة'];
            const teens = ['عشرة','أحد عشر','اثنا عشر','ثلاثة عشر','أربعة عشر','خمسة عشر','ستة عشر','سبعة عشر','ثمانية عشر','تسعة عشر'];
            const tens = ['','عشرة','عشرون','ثلاثون','أربعون','خمسون','ستون','سبعون','ثمانون','تسعون'];
            const hundreds = ['','مائة','مائتان','ثلاثمائة','أربعمائة','خمسمائة','ستمائة','سبعمائة','ثمانمائة','تسعمائة'];
            const scales = [
                { value: 1_000_000_000, singular: 'مليار', dual: 'ملياران', plural: 'مليارات' },
                { value: 1_000_000, singular: 'مليون', dual: 'مليونان', plural: 'ملايين' },
                { value: 1_000, singular: 'ألف', dual: 'ألفان', plural: 'آلاف' },
            ];

            function twoDigits(n){
                if (n === 0) return '';
                if (n < 10) return units[n];
                if (n < 20) return teens[n-10];
                const t = Math.floor(n/10), u = n%10;
                if (u === 0) return tens[t];
                // "ثلاثة وعشرون"
                return units[u] + ' و' + tens[t];
            }

            function threeDigits(n){
                const h = Math.floor(n/100), r = n%100;
                const parts = [];
                if (h) parts.push(hundreds[h]);
                if (r) parts.push(twoDigits(r));
                return parts.join(' و');
            }

            function pluralize(count, s){
                if (count === 1) return s.singular;
                if (count === 2) return s.dual;
                return s.plural;
            }

            let n = num;
            const words = [];
            for (const s of scales){
                if (n >= s.value){
                    const q = Math.floor(n / s.value);
                    n = n % s.value;
                    const qWords = q < 100 ? twoDigits(q) : threeDigits(q);
                    words.push(qWords + ' ' + pluralize(q, s));
                }
            }
            if (n > 0){
                words.push(n < 100 ? twoDigits(n) : threeDigits(n));
            }
            return words.join(' و');
        }
    </script>
</x-layout.admin>
