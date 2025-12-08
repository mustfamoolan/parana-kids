<x-layout.default>
    <link rel="stylesheet" href="/assets/css/swiper-bundle.min.css" />

    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المنتج: {{ $product->name }}</h5>
            <a href="{{ route('shop.index') }}" class="btn btn-outline-secondary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة للمتجر
            </a>
        </div>

        <!-- معرض الصور -->
        @if($product->images->count() > 0)
            <div class="panel mb-5">
                <div class="swiper max-w-3xl mx-auto" id="productSlider">
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
                    <a href="javascript:;" class="swiper-button-prev grid place-content-center ltr:left-2 rtl:right-2 p-1 transition text-primary hover:text-white border border-primary hover:border-primary hover:bg-primary rounded-full absolute z-[999] top-1/2 -translate-y-1/2">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 rtl:rotate-180">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                    <a href="javascript:;" class="swiper-button-next grid place-content-center ltr:right-2 rtl:left-2 p-1 transition text-primary hover:text-white border border-primary hover:border-primary hover:bg-primary rounded-full absolute z-[999] top-1/2 -translate-y-1/2">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ltr:rotate-180">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>

                    <!-- Pagination -->
                    <div class="swiper-pagination"></div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        new Swiper("#productSlider", {
                            slidesPerView: 1,
                            spaceBetween: 30,
                            loop: {{ $product->images->count() > 1 ? 'true' : 'false' }},
                            pagination: {
                                el: ".swiper-pagination",
                                clickable: true,
                                type: "fraction",
                            },
                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
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
        <div class="panel mb-5">
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
                    <span class="text-gray-500 dark:text-gray-400">السعر:</span>
                    <span class="font-medium text-primary text-xl">{{ number_format($product->effective_price, 0) }} دينار عراقي</span>
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
        <div class="panel mb-5" x-data="multiSizeForm()">
            <div class="mb-5">
                <h5 class="font-semibold text-lg dark:text-white-light mb-4">اختر القياس:</h5>
                <div class="space-y-4">
                    <!-- شبكة القياسات -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        @foreach($product->sizes as $size)
                            @if($size->available_quantity > 0)
                                <button
                                    type="button"
                                    @click="toggleSize({{ $size->id }}, '{{ $size->size_name }}', {{ $size->available_quantity }})"
                                    :class="selectedSizes.includes({{ $size->id }}) ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 hover:border-green-400'"
                                    class="relative border-2 rounded-lg p-4 transition-all cursor-pointer">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $size->size_name }}</div>
                                        <div class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1">متوفر: {{ $size->available_quantity }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ number_format($product->effective_price, 0) }} د.ع</div>
                                    </div>
                                    <div x-show="selectedSizes.includes({{ $size->id }})" class="absolute top-1 left-1">
                                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </button>
                            @else
                                <div class="relative border-2 border-red-200 bg-red-50 dark:bg-red-900/10 rounded-lg p-4 opacity-60 cursor-not-allowed">
                                    <div class="text-center">
                                        <div class="text-lg font-bold text-gray-500">{{ $size->size_name }}</div>
                                        <div class="text-xs text-red-600 dark:text-red-400 font-semibold mt-1">غير متوفر</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- القياسات المختارة -->
                    <div x-show="selectedSizes.length > 0" class="mt-4 space-y-2">
                        <h6 class="font-semibold">القياسات المختارة:</h6>
                        <template x-for="(sizeId, index) in selectedSizes" :key="sizeId">
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="font-medium" x-text="getSizeName(sizeId)"></span>
                                    <span class="text-xs text-gray-500" x-text="'متوفر: ' + getSizeQuantity(sizeId)"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="decrementQty(sizeId)" class="btn btn-sm btn-outline-danger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <input type="number"
                                           :id="'qty-' + sizeId"
                                           x-model="items.find(i => i.size_id === sizeId)?.quantity || 1"
                                           @change="updateQty(sizeId, $event.target.value)"
                                           min="1"
                                           :max="getSizeQuantity(sizeId)"
                                           class="form-input w-20 text-center">
                                    <button type="button" @click="incrementQty(sizeId)" class="btn btn-sm btn-outline-success">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                    <button type="button" @click="removeSize(sizeId)" class="btn btn-sm btn-outline-danger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- زر الإضافة -->
            <div class="mt-6">
                <button type="button"
                        @click="addToCart()"
                        :disabled="selectedSizes.length === 0"
                        class="btn btn-primary w-full btn-lg"
                        :class="selectedSizes.length === 0 ? 'opacity-50 cursor-not-allowed' : ''">
                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    إضافة للسلة
                </button>
            </div>
        </div>
    </div>

    <script src="/assets/js/swiper-bundle.min.js"></script>
    <script>
        function multiSizeForm() {
            return {
                selectedSizes: [],
                items: [],
                sizes: @json($product->sizes->map(function($size) {
                    return [
                        'id' => $size->id,
                        'name' => $size->size_name,
                        'quantity' => $size->available_quantity
                    ];
                })->keyBy('id')->toArray()),

                toggleSize(sizeId, sizeName, maxQty) {
                    const index = this.selectedSizes.indexOf(sizeId);
                    if (index > -1) {
                        this.selectedSizes.splice(index, 1);
                        this.items = this.items.filter(i => i.size_id !== sizeId);
                    } else {
                        this.selectedSizes.push(sizeId);
                        this.items.push({
                            size_id: sizeId,
                            quantity: 1
                        });
                    }
                },

                removeSize(sizeId) {
                    const index = this.selectedSizes.indexOf(sizeId);
                    if (index > -1) {
                        this.selectedSizes.splice(index, 1);
                        this.items = this.items.filter(i => i.size_id !== sizeId);
                    }
                },

                getSizeName(sizeId) {
                    return this.sizes[sizeId]?.name || '';
                },

                getSizeQuantity(sizeId) {
                    return this.sizes[sizeId]?.quantity || 0;
                },

                incrementQty(sizeId) {
                    const item = this.items.find(i => i.size_id === sizeId);
                    if (item) {
                        const maxQty = this.getSizeQuantity(sizeId);
                        if (item.quantity < maxQty) {
                            item.quantity++;
                        }
                    }
                },

                decrementQty(sizeId) {
                    const item = this.items.find(i => i.size_id === sizeId);
                    if (item && item.quantity > 1) {
                        item.quantity--;
                    }
                },

                updateQty(sizeId, value) {
                    const item = this.items.find(i => i.size_id === sizeId);
                    if (item) {
                        const maxQty = this.getSizeQuantity(sizeId);
                        let qty = parseInt(value) || 1;
                        if (qty < 1) qty = 1;
                        if (qty > maxQty) qty = maxQty;
                        item.quantity = qty;
                    }
                },

                addToCart() {
                    if (this.selectedSizes.length === 0) {
                        Swal.fire('تنبيه', 'يرجى اختيار قياس واحد على الأقل', 'warning');
                        return;
                    }

                    const items = this.items.map(item => ({
                        size_id: item.size_id,
                        quantity: item.quantity
                    }));

                    fetch('{{ route('shop.cart.items.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            product_id: {{ $product->id }},
                            items: items
                        })
                    })
                    .then(async res => {
                        const contentType = res.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            const data = await res.json();
                            if (res.ok && data.success) {
                                Swal.fire({
                                    title: 'تم!',
                                    text: 'تم إضافة المنتج للسلة بنجاح',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                setTimeout(() => {
                                    window.location.href = '{{ route('shop.cart.view') }}';
                                }, 2000);
                            } else {
                                Swal.fire('خطأ', data.message || 'حدث خطأ أثناء الإضافة', 'error');
                            }
                        } else {
                            if (res.ok || res.redirected) {
                                Swal.fire({
                                    title: 'تم!',
                                    text: 'تم إضافة المنتج للسلة بنجاح',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                setTimeout(() => {
                                    window.location.href = '{{ route('shop.cart.view') }}';
                                }, 2000);
                            } else {
                                Swal.fire('خطأ', 'حدث خطأ أثناء الإضافة', 'error');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('خطأ', 'حدث خطأ أثناء الإضافة: ' + error.message, 'error');
                    });
                }
            }
        }
    </script>
</x-layout.default>

