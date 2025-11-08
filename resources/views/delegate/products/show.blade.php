<x-layout.default>
    <div class="panel" x-data="multiSizeForm()">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المنتج: {{ $product->name }}</h5>
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
                        $backUrl = route('delegate.products.all');
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمنتجات
                </a>
            </div>
        </div>

        <!-- معرض الصور -->
        @if($product->images->count() > 0)
            <div class="panel mb-5">
                <div class="swiper max-w-3xl mx-auto" id="productSliderDelegate">
                    <div class="swiper-wrapper">
                        @foreach($product->images as $image)
                        <div class="swiper-slide">
                            <img src="{{ $image->image_url }}"
                                 class="w-full h-96 object-cover rounded-lg"
                                 alt="{{ $product->name }}">
                        </div>
                        @endforeach
                    </div>

                    <!-- أزرار التنقل -->
                    <a href="javascript:;" class="swiper-button-prev-delegate-product grid place-content-center ltr:left-2 rtl:right-2 p-1 transition text-primary hover:text-white border border-primary hover:border-primary hover:bg-primary rounded-full absolute z-[999] top-1/2 -translate-y-1/2">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 rtl:rotate-180">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    <a href="javascript:;" class="swiper-button-next-delegate-product grid place-content-center ltr:right-2 rtl:left-2 p-1 transition text-primary hover:text-white border border-primary hover:border-primary hover:bg-primary rounded-full absolute z-[999] top-1/2 -translate-y-1/2">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ltr:rotate-180">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>

                    <!-- Pagination -->
                    <div class="swiper-pagination"></div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const productSwiperDelegate = new Swiper("#productSliderDelegate", {
                            slidesPerView: 1,
                            spaceBetween: 30,
                            loop: {{ $product->images->count() > 1 ? 'true' : 'false' }},
                            pagination: {
                                el: ".swiper-pagination",
                                clickable: true,
                                type: "fraction",
                            },
                            navigation: {
                                nextEl: '.swiper-button-next-delegate-product',
                                prevEl: '.swiper-button-prev-delegate-product',
                            },
                        });
                    });
                </script>
            </div>
        @else
            <div class="panel mb-5">
                <div class="w-full h-80 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <svg class="w-32 h-32 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        @endif

        <!-- معلومات المنتج -->
        <div class="panel">
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">معلومات المنتج</h6>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">اسم المنتج:</span>
                    <span class="font-medium">{{ $product->name }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">كود المنتج:</span>
                    <span class="badge badge-outline-primary">{{ $product->code }}</span>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-gray-500 dark:text-gray-400">سعر البيع:</span>
                    <span class="font-medium text-success">{{ number_format($product->selling_price, 0) }} دينار عراقي</span>
                </div>

                @if($product->description)
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الوصف:</span>
                        <div class="text-right max-w-xs">
                            <p class="font-medium">{{ $product->description }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- قسم القياسات -->
        <div class="panel mb-5">
            <div class="mb-5">
                <h5 class="font-semibold text-lg dark:text-white-light mb-4">اختر القياس:</h5>
                <div class="space-y-4">
                    <!-- شبكة القياسات -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($product->sizes as $size)
                            @if($size->available_quantity > 0)
                                <!-- قياس متوفر -->
                                <button
                                    type="button"
                                    @click="toggleSize({{ $size->id }}, '{{ $size->size_name }}', {{ $size->available_quantity }})"
                                    :class="selectedSizes.includes({{ $size->id }}) ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 hover:border-green-400'"
                                    class="relative border-2 rounded-lg p-4 transition-all cursor-pointer">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $size->size_name }}</div>
                                        <div class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">متوفر: {{ $size->available_quantity }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ number_format($product->selling_price, 0) }} د.ع</div>
                                    </div>
                                    <div x-show="selectedSizes.includes({{ $size->id }})" class="absolute top-1 left-1">
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </button>
                            @else
                                <!-- قياس غير متوفر -->
                                <div class="relative border-2 border-red-200 bg-red-50 dark:bg-red-900/10 rounded-lg p-4 opacity-60 cursor-not-allowed">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-gray-400 dark:text-gray-600">{{ $size->size_name }}</div>
                                        <div class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">غير متوفر</div>
                                        <div class="text-xs text-gray-400 mt-1">نفذت الكمية</div>
                                    </div>
                                    <div class="absolute top-1 left-1">
                                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- القياسات المختارة مع الكميات -->
                    <div x-show="selectedSizes.length > 0" class="mt-6">
                        <h6 class="font-semibold text-md mb-3">القياسات المختارة:</h6>
                        <div class="space-y-3">
                            <template x-for="(item, index) in items" :key="item.size_id">
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex-1">
                                        <span class="font-semibold" x-text="item.size_name"></span>
                                        <span class="text-xs text-gray-500 mr-2">الكمية المتوفرة: <span x-text="item.max_quantity"></span></span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="decrementQuantity(index)" class="btn btn-sm btn-outline-danger">-</button>
                                        <input
                                            type="number"
                                            x-model="item.quantity"
                                            :max="item.max_quantity"
                                            min="1"
                                            class="form-input w-20 text-center"
                                            @input="validateQuantity(index)"
                                        />
                                        <button type="button" @click="incrementQuantity(index)" class="btn btn-sm btn-outline-success">+</button>
                                    </div>
                                    <button type="button" @click="removeSize(item.size_id)" class="text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- ملخص الطلب -->
                        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold">إجمالي القطع:</span>
                                <span class="text-lg font-bold text-primary" x-text="totalQuantity"></span>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="font-semibold">الإجمالي:</span>
                                <span class="text-xl font-bold text-success" x-text="formatPrice(totalPrice)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- تنبيه إذا لم يتم اختيار قياس -->
                    <div x-show="selectedSizes.length === 0" class="text-center py-4 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <p>اختر قياس أو أكثر للمتابعة</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- إضافة للسلة -->
        @if($product->sizes->count() > 0)
            <div class="panel mt-5">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">إضافة للسلة</h6>
                </div>

                <form method="POST" action="{{ route('delegate.carts.items.store') }}" id="addToCartForm" class="space-y-4" @submit="console.log('Form submitted with items:', items)">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    <!-- اختيار السلة -->
                    <div>
                        <label for="cart_id" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            اختر السلة
                        </label>
                        <select id="cart_id" name="cart_id" class="form-select" required x-model="selectedCartId" @change="console.log('Cart changed to:', selectedCartId)">
                            <option value="">اختر سلة أو أنشئ سلة جديدة</option>
                            @foreach($activeCarts as $cart)
                                <option value="{{ $cart->id }}" {{ ($selectedCart && $selectedCart->id == $cart->id) ? 'selected' : '' }}>
                                    {{ $cart->cart_name }} ({{ $cart->total_items }} منتج)
                                </option>
                            @endforeach
                        </select>
                        @error('cart_id')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- إخفاء الحقول المخفية للقياسات المختارة -->
                    <template x-for="(item, index) in items" :key="item.size_id">
                        <div>
                            <input type="hidden" :name="`items[${index}][size_id]`" :value="item.size_id">
                            <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                        </div>
                    </template>

                    <!-- زر الإضافة -->
                    <div class="flex justify-between items-center gap-2 mt-4">
                        <button
                            type="submit"
                            :disabled="selectedSizes.length === 0 || !selectedCartId"
                            class="btn btn-primary flex-1"
                            :class="selectedSizes.length === 0 || !selectedCartId ? 'opacity-50 cursor-not-allowed' : ''"
                            @click="console.log('Submit clicked, items:', items, 'selectedCartId:', selectedCartId, 'selectedSizes:', selectedSizes)"
                        >
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            إضافة للسلة
                        </button>

                        <!-- معلومات debugging -->
                        <div class="text-xs text-gray-500">
                            <div>القياسات المختارة: <span x-text="selectedSizes.length"></span></div>
                            <div>السلة المختارة: <span x-text="selectedCartId || 'غير مختارة'"></span></div>
                            <div>حالة الزر: <span x-text="selectedSizes.length === 0 || !selectedCartId ? 'مقفل' : 'نشط'"></span></div>
                        </div>

                        <button
                            type="button"
                            @click="showNewCartForm = !showNewCartForm"
                            class="btn btn-outline-primary"
                        >
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            سلة جديدة
                        </button>
                    </div>

                    <!-- نموذج إنشاء سلة جديدة -->
                    <div x-show="showNewCartForm" x-transition class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <h6 class="text-md font-semibold dark:text-white-light mb-3">إنشاء سلة جديدة</h6>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="newCartName"
                                class="form-input flex-1"
                                placeholder="اسم السلة (مثل: سلة - زبون 1)"
                                @keyup.enter="createNewCart()"
                            >
                            <button
                                type="button"
                                @click="createNewCart()"
                                class="btn btn-success"
                                :disabled="!newCartName"
                            >
                                إنشاء
                            </button>
                            <button type="button" @click="showNewCartForm = false" class="btn btn-outline-secondary">
                                إلغاء
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        @endif
    </div>

    <script>
        // Alpine.js data for multi-size form
        document.addEventListener('alpine:init', () => {
            Alpine.data('multiSizeForm', () => ({
                selectedCartId: '{{ $selectedCart->id ?? ($activeCarts->first()->id ?? '') }}',
                selectedSizes: [],
                items: [],
                showNewCartForm: false,
                newCartName: '',
                carts: @json($activeCarts->map(function($cart) {
                    return [
                        'id' => $cart->id,
                        'name' => $cart->cart_name,
                        'total_items' => $cart->total_items
                    ];
                })),

                get totalQuantity() {
                    return this.items.reduce((sum, item) => sum + parseInt(item.quantity), 0);
                },

                get totalPrice() {
                    return this.totalQuantity * {{ $product->selling_price }};
                },

                toggleSize(sizeId, sizeName, maxQuantity) {
                    console.log('Toggling size:', sizeId, sizeName, maxQuantity);
                    if (this.selectedSizes.includes(sizeId)) {
                        this.removeSize(sizeId);
                    } else {
                        this.selectedSizes.push(sizeId);
                        this.items.push({
                            size_id: sizeId,
                            size_name: sizeName,
                            quantity: 1,
                            max_quantity: maxQuantity
                        });
                    }
                    console.log('Selected sizes:', this.selectedSizes);
                    console.log('Items:', this.items);
                },

                removeSize(sizeId) {
                    this.selectedSizes = this.selectedSizes.filter(id => id !== sizeId);
                    this.items = this.items.filter(item => item.size_id !== sizeId);
                },

                incrementQuantity(index) {
                    if (this.items[index].quantity < this.items[index].max_quantity) {
                        this.items[index].quantity++;
                    }
                },

                decrementQuantity(index) {
                    if (this.items[index].quantity > 1) {
                        this.items[index].quantity--;
                    }
                },

                validateQuantity(index) {
                    if (this.items[index].quantity > this.items[index].max_quantity) {
                        this.items[index].quantity = this.items[index].max_quantity;
                    }
                    if (this.items[index].quantity < 1) {
                        this.items[index].quantity = 1;
                    }
                },

                formatPrice(price) {
                    return new Intl.NumberFormat('ar-IQ').format(price) + ' د.ع';
                },

                updateCartSelect() {
                    // تحديث قائمة السلال في الصفحة
                    const cartSelect = document.getElementById('cart_id');
                    if (cartSelect) {
                        // إضافة السلة الجديدة للقائمة
                        const newOption = document.createElement('option');
                        newOption.value = this.selectedCartId;
                        newOption.textContent = this.carts.find(cart => cart.id == this.selectedCartId).name + ' (0 منتج)';
                        newOption.selected = true;

                        // إزالة الخيار الافتراضي إذا كان موجوداً
                        const defaultOption = cartSelect.querySelector('option[value=""]');
                        if (defaultOption) {
                            defaultOption.remove();
                        }

                        cartSelect.appendChild(newOption);
                    }
                },

                async createNewCart() {
                    if (!this.newCartName.trim()) {
                        alert('يرجى إدخال اسم السلة');
                        return;
                    }

                    try {
                        console.log('Creating cart with name:', this.newCartName);

                        const response = await fetch('{{ route("delegate.carts.store") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                cart_name: this.newCartName
                            })
                        });

                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);

                        if (!response.ok) {
                            const errorText = await response.text();
                            console.error('Error response:', errorText);
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }

                        const data = await response.json();
                        console.log('Response data:', data);

                        if (data.success) {
                            // إضافة السلة الجديدة للقائمة
                            this.carts.push({
                                id: data.cart.id,
                                name: data.cart.cart_name,
                                total_items: 0
                            });

                            // اختيار السلة الجديدة
                            this.selectedCartId = data.cart.id;

                            // إخفاء النموذج وتفريغ الحقل
                            this.showNewCartForm = false;
                            this.newCartName = '';

                            // تحديث قائمة السلال في الصفحة
                            this.updateCartSelect();
                        } else {
                            alert(data.message || 'حدث خطأ في إنشاء السلة');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('حدث خطأ في الاتصال بالخادم: ' + error.message);
                    }
                }
            }));
        });

    </script>
</x-layout.default>
