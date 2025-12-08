<x-layout.default>
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="/assets/css/swiper-bundle.min.css" />

    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Banner السلة النشطة -->
        @if($activeCart && $activeCart->items->count() > 0)
        <div id="activeCartBanner" class="panel mb-5 !bg-primary-light dark:!bg-primary/20 border-2 border-primary">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex-1">
                    <h5 class="font-bold text-primary text-lg mb-2">
                        <svg class="w-5 h-5 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        السلة النشطة
                    </h5>
                    <div class="space-y-1 text-sm">
                        <p class="text-gray-700 dark:text-gray-300">
                            <strong>المنتجات في السلة:</strong>
                            <span class="badge bg-primary">{{ $activeCart->items->count() }} منتج</span>
                            <strong class="ltr:ml-3 rtl:mr-3">الإجمالي:</strong>
                            <span class="font-bold text-primary">{{ number_format($activeCart->total_amount, 0) }} د.ع</span>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 flex-shrink-0 flex-wrap">
                    <a href="{{ route('shop.cart.view') }}" class="btn btn-primary">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        عرض السلة
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Header -->
        <div class="sticky top-0 bg-white dark:bg-gray-900 z-10 pb-4">
            <h1 class="text-2xl font-bold mb-4 text-center">المتجر الإلكتروني</h1>

            <!-- الفلاتر -->
            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- فلتر النوع -->
                <div>
                    <label for="gender_type_filter" class="block text-sm font-medium mb-2">النوع</label>
                    <select id="gender_type_filter" class="form-select" onchange="applyFilters()">
                        <option value="">كل الأنواع</option>
                        <option value="boys" {{ request('gender_type') == 'boys' ? 'selected' : '' }}>ولادي</option>
                        <option value="girls" {{ request('gender_type') == 'girls' ? 'selected' : '' }}>بناتي</option>
                        <option value="boys_girls" {{ request('gender_type') == 'boys_girls' ? 'selected' : '' }}>ولادي بناتي</option>
                        <option value="accessories" {{ request('gender_type') == 'accessories' ? 'selected' : '' }}>اكسسوار</option>
                    </select>
                </div>

                <!-- فلتر التخفيض -->
                <div>
                    <label for="has_discount_filter" class="block text-sm font-medium mb-2">التخفيض</label>
                    <select id="has_discount_filter" class="form-select" onchange="applyFilters()">
                        <option value="">كل المنتجات</option>
                        <option value="1" {{ request('has_discount') == '1' ? 'selected' : '' }}>المنتجات المخفضة فقط</option>
                    </select>
                </div>

                <!-- البحث -->
                <div>
                    <label for="searchInput" class="block text-sm font-medium mb-2">البحث</label>
                    <div class="relative">
                        <input type="text" id="searchInput" class="form-input w-full ltr:pr-10 rtl:pl-10" placeholder="ابحث بكود المنتج أو القياس أو النوع..." value="{{ request('search') }}" autocomplete="off" />
                        <div class="absolute ltr:right-3 rtl:left-3 top-1/2 -translate-y-1/2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        @if(request('search'))
                            <button onclick="clearSearch()" class="absolute ltr:left-3 rtl:right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-500 mt-1 text-center mb-2">
                اكتب كود المنتج أو القياس أو النوع (ولادي/بناتي/اكسسوار) للبحث الفوري
            </p>

            <!-- عدد النتائج -->
            <div class="text-sm text-gray-500 mb-2">
                <span id="resultCount">{{ $products->total() }} منتج</span>
            </div>

            <!-- معلومات البحث -->
            @if(request('search'))
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 mb-4">
                    <div class="text-sm">
                        <span class="text-gray-700 dark:text-gray-300">نتائج البحث عن "</span>
                        <span class="font-semibold">{{ request('search') }}</span>
                        <span class="text-gray-700 dark:text-gray-300">": </span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $products->total() }} منتج</span>
                        @if($products->total() > 0)
                            <span class="text-gray-500 text-xs block mt-1">💡 اضغط على المنتج لاختيار القياسات المتوفرة</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- شبكة المنتجات -->
        <div id="productsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 justify-items-center">
            @include('shop.partials.product-cards', ['products' => $products])
        </div>

        <!-- زر تحميل المزيد -->
        <div id="loadMoreContainer" class="text-center py-6">
            <button id="loadMoreBtn" class="btn btn-primary btn-lg">
                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                تحميل المزيد
            </button>
        </div>

        <!-- مؤشر التحميل -->
        <div id="loadingIndicator" class="hidden text-center py-4">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            <p class="mt-2 text-sm text-gray-500">جاري التحميل...</p>
        </div>

        <!-- رسالة عدم وجود منتجات -->
        <div id="noProducts" class="{{ $products->total() == 0 ? '' : 'hidden' }} text-center py-8">
            @if(request('search'))
                <div class="text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold mb-2">لا توجد نتائج</h3>
                    <p class="text-sm mb-4">لم نجد أي منتجات تطابق "<strong>{{ request('search') }}</strong>"</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-primary btn-sm">مسح البحث</a>
                </div>
            @else
                <p class="text-gray-400">ابدأ البحث عن منتج باستخدام الكود أو الاسم</p>
            @endif
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black/60 z-[999] hidden overflow-y-auto" onclick="if(event.target === this) closeProductModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="panel max-w-2xl w-full mx-auto" onclick="event.stopPropagation()">
                <!-- Header -->
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-bold text-lg" id="modalProductName">اختر القياسات</h5>
                    <button type="button" onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Product Info -->
                <div class="flex gap-4 mb-5 pb-5 border-b border-gray-200 dark:border-gray-700">
                    <img id="modalProductImage" src="" alt="" class="w-24 h-24 object-cover rounded">
                    <div class="flex-1">
                        <p class="font-bold text-lg" id="modalProductCode"></p>
                        <p class="text-gray-500 text-sm">السعر: <span class="font-bold text-primary" id="modalProductPrice"></span> د.ع</p>
                    </div>
                </div>

                <!-- Sizes List -->
                <div id="modalSizesList" class="space-y-2 mb-5"></div>

                <!-- Add Button -->
                <div class="flex gap-3 justify-end pt-4 border-t">
                    <button type="button" onclick="closeProductModal()" class="btn btn-outline-secondary">إلغاء</button>
                    <button type="button" onclick="addSelectedSizesToCart()" class="btn btn-primary">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        إضافة للسلة
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Swiper JS -->
    <script src="/assets/js/swiper-bundle.min.js"></script>

    <script>
        let page = 1;
        let loading = false;
        let hasMore = {{ $products->hasMorePages() ? 'true' : 'false' }};
        let currentSearch = '{{ request('search') }}';

        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const loadMoreContainer = document.getElementById('loadMoreContainer');

        if (!hasMore) {
            loadMoreContainer.classList.add('hidden');
        }

        loadMoreBtn.addEventListener('click', function() {
            if (hasMore) {
                page++;
                loadProducts(false);
            }
        });

        const searchInput = document.getElementById('searchInput');
        let searchDebounceTimeout;

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();
            clearTimeout(searchDebounceTimeout);
            searchDebounceTimeout = setTimeout(() => {
                page = 1;
                currentSearch = searchTerm;
                loadProducts(true);
            }, 500);
        });

        window.clearSearch = function() {
            searchInput.value = '';
            currentSearch = '';
            page = 1;
            loadProducts(true);
        };

        window.applyFilters = function() {
            page = 1;
            loadProducts(true);
        };

        window.addEventListener('scroll', function() {
            if (loading || !hasMore) return;
            const scrollPosition = window.innerHeight + window.scrollY;
            const threshold = document.body.offsetHeight - 200;
            if (scrollPosition >= threshold) {
                page++;
                loadProducts(false);
            }
        });

        function loadProducts(reset = false) {
            if (loading) return;
            loading = true;

            const genderTypeFilter = document.getElementById('gender_type_filter')?.value || '';
            const hasDiscountFilter = document.getElementById('has_discount_filter')?.value || '';

            if (reset) {
                document.getElementById('productsContainer').innerHTML = '';
                page = 1;
            }

            document.getElementById('loadingIndicator').classList.remove('hidden');
            if (loadMoreBtn) loadMoreBtn.disabled = true;

            let url = `{{ route('shop.index') }}?page=${page}&search=${encodeURIComponent(currentSearch)}`;
            if (genderTypeFilter) url += `&gender_type=${encodeURIComponent(genderTypeFilter)}`;
            if (hasDiscountFilter) url += `&has_discount=${encodeURIComponent(hasDiscountFilter)}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (reset) {
                    document.getElementById('productsContainer').innerHTML = data.products;
                } else {
                    document.getElementById('productsContainer').insertAdjacentHTML('beforeend', data.products);
                }

                hasMore = data.has_more;
                loading = false;
                if (loadMoreBtn) loadMoreBtn.disabled = false;

                if (!hasMore) {
                    if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                } else {
                    if (loadMoreContainer) loadMoreContainer.classList.remove('hidden');
                }

                document.getElementById('loadingIndicator').classList.add('hidden');

                if (document.getElementById('productsContainer').children.length === 0) {
                    document.getElementById('noProducts').classList.remove('hidden');
                    if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                } else {
                    document.getElementById('noProducts').classList.add('hidden');
                }

                const resultCount = document.getElementById('resultCount');
                if (resultCount) {
                    resultCount.textContent = `${data.total} منتج`;
                }

                initializeProductSwipers();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ في تحميل المنتجات. يرجى المحاولة مرة أخرى.');
                loading = false;
                if (loadMoreBtn) loadMoreBtn.disabled = false;
                document.getElementById('loadingIndicator').classList.add('hidden');
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('productsContainer').children.length === 0) {
                document.getElementById('noProducts').classList.remove('hidden');
            }
        });

        // Product Modal Functions
        let currentProductData = null;
        let selectedSizes = {};

        function openProductModal(productId) {
            loadProductData(productId);
        }

        function loadProductData(productId) {
            fetch(`/shop/api/products/${productId}`)
                .then(res => res.json())
                .then(data => {
                    currentProductData = data;
                    selectedSizes = {};

                    document.getElementById('modalProductName').textContent = data.name;
                    document.getElementById('modalProductCode').textContent = data.code;
                    document.getElementById('modalProductPrice').textContent = number_format(data.selling_price, 0);
                    document.getElementById('modalProductImage').src = data.image || '/assets/images/no-image.png';
                    document.getElementById('modalProductImage').alt = data.name;

                    const sizesList = document.getElementById('modalSizesList');
                    sizesList.innerHTML = '';

                    data.sizes.forEach(size => {
                        const isAvailable = size.available_quantity > 0;
                        const sizeHtml = `
                            <div class="flex items-center gap-3 p-3 rounded border ${isAvailable ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50'}">
                                <label class="flex items-center flex-1 cursor-pointer ${!isAvailable ? 'opacity-50 cursor-not-allowed' : ''}">
                                    <input type="checkbox" ${!isAvailable ? 'disabled' : ''} class="form-checkbox" onchange="toggleSizeSelection(${size.id}, ${size.available_quantity}, this.checked)">
                                    <span class="ltr:ml-3 rtl:mr-3 font-medium">
                                        ${size.size_name}
                                        ${isAvailable ? `<span class="text-xs text-green-600">(متوفر: ${size.available_quantity})</span>` : `<span class="text-xs text-red-500">(غير متوفر)</span>`}
                                    </span>
                                </label>
                                <div class="flex items-center gap-2 ${!isAvailable ? 'hidden' : ''}">
                                    <button type="button" onclick="decrementQuantity(${size.id}, ${size.available_quantity})" class="btn btn-sm btn-outline-danger">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                        </svg>
                                    </button>
                                    <input type="number" id="qty-${size.id}" min="1" max="${size.available_quantity}" value="1" class="form-input w-20 text-center" onchange="updateSizeQuantity(${size.id}, this.value, ${size.available_quantity})">
                                    <button type="button" onclick="incrementQuantity(${size.id}, ${size.available_quantity})" class="btn btn-sm btn-outline-success">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                        sizesList.insertAdjacentHTML('beforeend', sizeHtml);
                    });

                    document.getElementById('productModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    Swal.fire('خطأ', 'حدث خطأ أثناء تحميل المنتج', 'error');
                });
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.body.style.overflow = '';
            currentProductData = null;
            selectedSizes = {};
        }

        function toggleSizeSelection(sizeId, maxQty, checked) {
            if (checked) {
                const qtyInput = document.getElementById(`qty-${sizeId}`);
                let qty = Math.floor(Number(qtyInput.value) || 1);
                if (qty < 1) qty = 1;
                if (qty > maxQty) qty = maxQty;
                qtyInput.value = qty;
                selectedSizes[sizeId] = { quantity: qty, max_quantity: maxQty };
            } else {
                delete selectedSizes[sizeId];
            }
        }

        function updateSizeQuantity(sizeId, quantity, maxQty) {
            const qtyInput = document.getElementById(`qty-${sizeId}`);
            let qty = Math.floor(Number(quantity) || 1);
            if (qty < 1) qty = 1;
            if (qty > maxQty) qty = maxQty;
            qtyInput.value = qty;
            if (selectedSizes[sizeId]) {
                selectedSizes[sizeId].quantity = qty;
            }
        }

        function incrementQuantity(sizeId, maxQty) {
            const qtyInput = document.getElementById(`qty-${sizeId}`);
            let currentQty = Math.floor(Number(qtyInput.value) || 1);
            if (currentQty < maxQty) {
                currentQty++;
                updateSizeQuantity(sizeId, currentQty, maxQty);
            }
        }

        function decrementQuantity(sizeId, maxQty) {
            const qtyInput = document.getElementById(`qty-${sizeId}`);
            let currentQty = Math.floor(Number(qtyInput.value) || 1);
            if (currentQty > 1) {
                currentQty--;
                updateSizeQuantity(sizeId, currentQty, maxQty);
            }
        }

        function addSelectedSizesToCart() {
            const selectedSizesArray = Object.keys(selectedSizes);
            if (selectedSizesArray.length === 0) {
                Swal.fire('تنبيه', 'يرجى اختيار قياس واحد على الأقل', 'warning');
                return;
            }

            const items = selectedSizesArray.map(sizeId => ({
                size_id: parseInt(sizeId),
                quantity: selectedSizes[sizeId].quantity
            }));

            fetch('{{ route('shop.cart.items.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_id: currentProductData.id,
                    items: items
                })
            })
            .then(async res => {
                const contentType = res.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        closeProductModal();
                        Swal.fire({
                            title: 'تم!',
                            text: 'تم إضافة المنتج للسلة بنجاح',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        Swal.fire('خطأ', data.message || 'حدث خطأ أثناء الإضافة', 'error');
                    }
                } else {
                    if (res.ok || res.redirected) {
                        closeProductModal();
                        Swal.fire({
                            title: 'تم!',
                            text: 'تم إضافة المنتج للسلة بنجاح',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(() => {
                            window.location.reload();
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

        function number_format(number, decimals = 0) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeProductSwipers();
        });

        function initializeProductSwipers() {
            document.querySelectorAll('[class*="product-swiper-"]').forEach(swiperElement => {
                if (swiperElement.swiper) return;
                const productId = swiperElement.className.match(/product-swiper-(\d+)/)[1];
                new Swiper(`.product-swiper-${productId}`, {
                    loop: false,
                    slidesPerView: 1,
                    spaceBetween: 0,
                    navigation: {
                        nextEl: `.swiper-button-next-${productId}`,
                        prevEl: `.swiper-button-prev-${productId}`,
                    },
                    pagination: {
                        el: `.swiper-pagination-${productId}`,
                        clickable: true,
                        type: 'bullets',
                    },
                });
            });
        }
    </script>
</x-layout.default>

