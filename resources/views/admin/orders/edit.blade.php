<x-layout.admin>
    <div x-data="orderEditForm()">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
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

        <!-- عرض أخطاء Validation -->
        @if($errors->any())
            <div class="panel mb-5 border-l-4 border-red-500">
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h6 class="font-semibold text-red-700 dark:text-red-300 mb-2">حدث خطأ أثناء حفظ التعديلات:</h6>
                        <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
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

        <form method="POST" action="{{ route('admin.orders.update', $order) }}" id="editForm">
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
                            type="text"
                            id="customer_phone"
                            name="customer_phone"
                            class="form-input @error('customer_phone') border-red-500 @enderror"
                            value="{{ old('customer_phone', $order->customer_phone) }}"
                            required
                        >
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="copyPhoneNumber()" class="btn btn-sm btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                            <button type="button" onclick="openWhatsApp()" class="btn btn-sm btn-outline-success">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                واتساب
                            </button>
                            <button type="button" onclick="callPhoneNumber()" class="btn btn-sm btn-outline-primary">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                اتصال
                            </button>
                        </div>
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
            <div class="mb-5">
                <h6 class="text-lg font-semibold mb-4">منتجات الطلب</h6>
                <div x-show="items.length === 0" class="text-center py-8 text-gray-500">
                    <p>لا توجد منتجات في هذا الطلب.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="panel">
                            <!-- صورة المنتج والاسم -->
                            <div class="flex items-start gap-4 mb-4">
                                <div class="flex-shrink-0">
                                    <img :src="item.product_image" :alt="item.product_name" class="w-20 h-20 object-cover rounded-lg cursor-pointer hover:opacity-80 transition-opacity" @click="openImageModal(item.product_image, item.product_name)">
                                </div>
                                <div class="flex-1">
                                    <h6 class="font-semibold text-base dark:text-white-light mb-1" x-text="item.product_name"></h6>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mb-2" x-text="item.product_code"></p>
                                </div>
                                <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 flex-shrink-0" title="حذف">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- التفاصيل -->
                            <div class="space-y-3 border-t pt-3">
                                <!-- القياس -->
                                <div>
                                    <label class="form-label text-xs mb-1">القياس</label>
                                    <select @change="changeItemSize(index, $event.target.value)" class="form-select text-sm">
                                        <template x-for="size in getProductSizes(item.product_id)" :key="size.id">
                                            <option :value="size.id" :selected="size.id == item.size_id" x-text="size.size_name + ' (' + size.available_quantity + ')'"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- الكمية -->
                                <div>
                                    <label class="form-label text-xs mb-1">الكمية</label>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="decrementQuantity(index)" class="btn btn-sm btn-outline-danger">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <input type="number" x-model="item.quantity" @input="updateItemQuantity(index)" class="form-input w-20 text-center" min="1" :max="item.max_quantity">
                                        <button type="button" @click="incrementQuantity(index)" class="btn btn-sm btn-outline-success">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- السعر والمجموع -->
                                <div class="flex items-center justify-between pt-2 border-t">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">السعر:</span>
                                    <span class="text-sm font-medium" x-text="formatPrice(item.unit_price) + ' × ' + item.quantity"></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">المجموع:</span>
                                    <span class="text-xl font-bold text-success" x-text="formatPrice(item.subtotal)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
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
                        <img x-show="selectedProduct && selectedProduct.primary_image" :src="selectedProduct && selectedProduct.primary_image ? selectedProduct.primary_image : ''" :alt="selectedProduct && selectedProduct.name ? selectedProduct.name : ''" class="w-12 h-12 object-cover rounded">
                        <div>
                            <div class="font-semibold" x-text="selectedProduct && selectedProduct.name ? selectedProduct.name : ''"></div>
                            <div class="text-sm text-gray-500" x-text="selectedProduct && selectedProduct.code ? selectedProduct.code : ''"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-2">اختر القياس:</label>
                            <select x-model="selectedSize" class="form-select">
                                <option value="">اختر القياس</option>
                                <template x-for="size in (selectedProduct && selectedProduct.sizes ? selectedProduct.sizes : [])" :key="size.id">
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
                    placeholder="أدخل كود شركة التوصيل (اختياري)"
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
                <div id="hidden-items-container"></div>

                <div class="flex gap-3">
                    <button type="button" @click="submitOrder()" class="btn btn-success flex-1">
                        حفظ التعديلات
                    </button>
                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">
                        إلغاء
                    </a>
                </div>
            </div>
        </form>
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
                        'product_id' => $item->product_id,
                        'size_id' => $item->size_id,
                        'product_name' => $item->product_name,
                        'product_code' => $item->product_code,
                        'size_name' => $item->size_name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price ?? 0,
                        'subtotal' => $item->subtotal ?? ($item->quantity * ($item->unit_price ?? 0)),
                        'max_quantity' => $maxQuantity,
                        'product_image' => $item->product->primaryImage ? $item->product->primaryImage->image_url : '/assets/images/no-image.png'
                    ];
                })) !!},

                init() {
                    // تحديث الحقول المخفية عند التحميل الأولي
                    this.$nextTick(() => {
                        this.updateHiddenFields();
                    });
                },
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
                    return this.items.reduce((sum, item) => {
                        const subtotal = Number(item?.subtotal) || 0;
                        return Number(sum) + subtotal;
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
                    const qty = parseInt(item.quantity) || 1;
                    item.quantity = Math.max(1, Math.min(qty, item.max_quantity));
                    item.subtotal = item.quantity * item.unit_price;
                    // تحديث الحقول المخفية بعد تغيير الكمية
                    this.updateHiddenFields();
                },

                incrementQuantity(index) {
                    const item = this.items[index];
                    if (item.quantity < item.max_quantity) {
                        item.quantity = Math.min(item.quantity + 1, item.max_quantity);
                        item.subtotal = item.quantity * item.unit_price;
                        // تحديث الحقول المخفية بعد تغيير الكمية
                        this.updateHiddenFields();
                    }
                },

                decrementQuantity(index) {
                    const item = this.items[index];
                    if (item.quantity > 1) {
                        item.quantity = Math.max(1, item.quantity - 1);
                        item.subtotal = item.quantity * item.unit_price;
                        // تحديث الحقول المخفية بعد تغيير الكمية
                        this.updateHiddenFields();
                    }
                },

                changeItemSize(index, newSizeId) {
                    const item = this.items[index];
                    const product = this.products.find(p => p.id === item.product_id);

                    if (!product || !product.sizes) {
                        alert('حدث خطأ: المنتج غير موجود أو لا يحتوي على أحجام');
                        return;
                    }

                    const newSize = product.sizes.find(s => s.id == newSizeId);

                    if (newSize) {
                        item.size_id = newSize.id;
                        item.size_name = newSize.size_name;

                        // للطلبات المقيدة: الكمية المتاحة = الكمية في المخزن + الكمية الحالية في الطلب
                        // للطلبات pending: الكمية المتاحة = الكمية في المخزن فقط
                        const currentOrderStatus = '{{ $order->status }}';
                        let maxQuantity = newSize.available_quantity;
                        if (currentOrderStatus === 'confirmed') {
                            maxQuantity += item.quantity;
                        }
                        item.max_quantity = maxQuantity;

                        // تحديث الكمية إذا كانت أكبر من المتوفر
                        if (item.quantity > maxQuantity) {
                            item.quantity = maxQuantity;
                        }

                        // تحديث السعر والمجموع مباشرة
                        item.subtotal = item.quantity * item.unit_price;

                        // تحديث الحقول المخفية بعد تغيير القياس
                        this.updateHiddenFields();
                    }
                },

                removeItem(index) {
                    if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                        this.items.splice(index, 1);
                        // تحديث الحقول المخفية بعد الحذف
                        this.updateHiddenFields();
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
                    if (!this.selectedSize || !this.selectedProduct || !this.selectedProduct.sizes) return 1;
                    const size = this.selectedProduct.sizes.find(s => s.id == this.selectedSize);
                    return size ? size.available_quantity : 1;
                },

                addProduct() {
                    if (!this.selectedProduct || !this.selectedSize || !this.quantity) {
                        alert('يرجى اختيار المنتج والقياس والكمية');
                        return;
                    }

                    if (!this.selectedProduct.sizes) {
                        alert('حدث خطأ: لا توجد أحجام متاحة لهذا المنتج');
                        return;
                    }

                    const size = this.selectedProduct.sizes.find(s => s.id == this.selectedSize);

                    if (!size) {
                        alert('حدث خطأ: القياس غير موجود');
                        return;
                    }

                    const subtotal = parseInt(this.quantity) * parseFloat(this.selectedProduct.selling_price);

                    this.items.push({
                        product_id: this.selectedProduct.id,
                        size_id: parseInt(this.selectedSize),
                        product_name: this.selectedProduct.name,
                        product_code: this.selectedProduct.code,
                        size_name: size.size_name,
                        quantity: parseInt(this.quantity),
                        unit_price: parseFloat(this.selectedProduct.selling_price),
                        subtotal: subtotal,
                        max_quantity: size.available_quantity,
                        product_image: this.selectedProduct.primary_image
                    });

                    alert('تم إضافة المنتج بنجاح! الإجمالي: ' + this.items.length);

                    // تحديث الحقول المخفية بعد الإضافة
                    this.updateHiddenFields();

                    // إعادة تعيين
                    this.cancelProductSelection();
                },

                async submitOrder() {
                    if (this.items.length === 0) {
                        alert('يجب إضافة منتج واحد على الأقل');
                        return;
                    }

                    // التحقق من أن جميع العناصر لها البيانات المطلوبة
                    for (let i = 0; i < this.items.length; i++) {
                        const item = this.items[i];
                        if (!item.product_id || !item.size_id || !item.quantity || item.quantity < 1) {
                            alert(`يرجى التحقق من بيانات المنتج رقم ${i + 1}`);
                            return;
                        }
                    }

                    if (confirm('هل أنت متأكد من حفظ التعديلات على هذا الطلب؟')) {
                        // تحديث الحقول المخفية يدوياً قبل الإرسال
                        this.updateHiddenFields();

                        // التحقق من الحقول قبل الإرسال
                        const form = document.getElementById('editForm');
                        if (!form) {
                            alert('حدث خطأ: لم يتم العثور على النموذج');
                            return;
                        }

                        // التحقق من وجود الحقول المخفية
                        const hiddenInputs = form.querySelectorAll('#hidden-items-container input[type="hidden"]');
                        console.log('عدد الحقول المخفية قبل الإرسال:', hiddenInputs.length);

                        if (hiddenInputs.length === 0) {
                            alert('خطأ: لم يتم إنشاء الحقول المخفية. يرجى المحاولة مرة أخرى.');
                            console.error('لا توجد حقول مخفية للإرسال');
                            return;
                        }

                        // عرض البيانات المرسلة للتشخيص
                        const formData = new FormData(form);
                        const itemsData = [];
                        for (let i = 0; i < this.items.length; i++) {
                            const productId = formData.get(`items[${i}][product_id]`);
                            const sizeId = formData.get(`items[${i}][size_id]`);
                            const quantity = formData.get(`items[${i}][quantity]`);
                            if (productId && sizeId && quantity) {
                                itemsData.push({ product_id: productId, size_id: sizeId, quantity: quantity });
                            }
                        }
                        console.log('البيانات المرسلة:', itemsData);

                        // إرسال النموذج
                        form.submit();
                    }
                },

                updateHiddenFields() {
                    // حذف الحقول المخفية القديمة
                    const container = document.getElementById('hidden-items-container');
                    if (!container) {
                        console.error('لم يتم العثور على hidden-items-container');
                        return;
                    }

                    container.innerHTML = '';

                    console.log('تحديث الحقول المخفية - عدد العناصر:', this.items.length);

                    // إنشاء حقول مخفية جديدة لكل عنصر
                    this.items.forEach((item, index) => {
                        if (!item.product_id || !item.size_id || !item.quantity) {
                            console.warn(`تخطي عنصر ${index}: بيانات غير كاملة`, item);
                            return; // تخطي العناصر غير الصحيحة
                        }

                        const productIdInput = document.createElement('input');
                        productIdInput.type = 'hidden';
                        productIdInput.name = `items[${index}][product_id]`;
                        productIdInput.value = parseInt(item.product_id); // تحويل إلى رقم

                        const sizeIdInput = document.createElement('input');
                        sizeIdInput.type = 'hidden';
                        sizeIdInput.name = `items[${index}][size_id]`;
                        sizeIdInput.value = parseInt(item.size_id); // تحويل إلى رقم

                        const quantityInput = document.createElement('input');
                        quantityInput.type = 'hidden';
                        quantityInput.name = `items[${index}][quantity]`;
                        quantityInput.value = parseInt(item.quantity); // تحويل إلى رقم

                        container.appendChild(productIdInput);
                        container.appendChild(sizeIdInput);
                        container.appendChild(quantityInput);

                        console.log(`إضافة عنصر ${index}:`, {
                            product_id: item.product_id,
                            size_id: item.size_id,
                            quantity: item.quantity
                        });
                    });

                    // التحقق من الحقول المضافة
                    const addedInputs = container.querySelectorAll('input[type="hidden"]');
                    console.log(`تم إضافة ${addedInputs.length} حقلاً مخفياً`);
                },

                formatPrice(price) {
                    const numPrice = Number(price) || 0;
                    return new Intl.NumberFormat('en-US').format(numPrice) + ' د.ع';
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

        // دالة نسخ رقم الهاتف
        function copyPhoneNumber() {
            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value) {
                phoneInput.select();
                phoneInput.setSelectionRange(0, 99999);
                try {
                    document.execCommand('copy');
                    showCopyNotification('تم نسخ رقم الهاتف بنجاح!');
                } catch (err) {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(phoneInput.value).then(function() {
                            showCopyNotification('تم نسخ رقم الهاتف بنجاح!');
                        });
                    } else {
                        showCopyNotification('فشل في نسخ رقم الهاتف');
                    }
                }
            }
        }

        // دالة فتح واتساب
        function openWhatsApp() {
            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value) {
                const phone = phoneInput.value.replace(/[^0-9]/g, ''); // إزالة جميع الأحرف غير الرقمية
                const whatsappUrl = `https://wa.me/${phone}`;
                window.open(whatsappUrl, '_blank');
            }
        }

        // دالة الاتصال بالهاتف
        function callPhoneNumber() {
            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value) {
                const phone = phoneInput.value.replace(/[^0-9+]/g, ''); // الاحتفاظ بالأرقام وعلامة +
                window.location.href = `tel:${phone}`;
            }
        }

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
