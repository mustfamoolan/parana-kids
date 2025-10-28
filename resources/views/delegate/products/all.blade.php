<x-layout.default>
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Banner الطلب النشط -->
        @if(session('current_cart_id'))
            @php
                $currentCart = \App\Models\Cart::with('items')->find(session('current_cart_id'));
                $customerData = session('customer_data');
            @endphp
            @if($currentCart && $customerData)
                <div class="panel mb-5 !bg-success-light dark:!bg-success/20 border-2 border-success">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <h5 class="font-bold text-success text-lg mb-2">
                                <svg class="w-5 h-5 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                طلب جاري: {{ $customerData['customer_name'] }}
                            </h5>
                            <div class="space-y-1 text-sm">
                                <p class="text-gray-700 dark:text-gray-300">
                                    <strong>الهاتف:</strong> {{ $customerData['customer_phone'] }}
                                </p>
                                <p class="text-gray-700 dark:text-gray-300">
                                    <strong>العنوان:</strong> {{ $customerData['customer_address'] }}
                                </p>
                                <p class="text-gray-700 dark:text-gray-300">
                                    <strong>المنتجات في السلة:</strong>
                                    <span class="badge bg-success">{{ $currentCart->items->count() }} منتج</span>
                                    <strong class="ltr:ml-3 rtl:mr-3">الإجمالي:</strong>
                                    <span class="font-bold text-success">{{ number_format($currentCart->total_amount, 0) }} د.ع</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-2 flex-shrink-0 flex-wrap">
                            <button type="button" onclick="openCartModal()" class="btn btn-info">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                عرض الطلب
                            </button>
                            @if($currentCart->items->count() > 0)
                                <button type="button" onclick="openConfirmModal()" class="btn btn-success">
                                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    إرسال الطلب
                                </button>
                            @endif
                            <button type="button" onclick="archiveCurrentOrder()" class="btn btn-secondary">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                                أرشفة
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Header ثابت -->
        <div class="sticky top-0 bg-white dark:bg-gray-900 z-10 pb-4">
            <h1 class="text-2xl font-bold mb-4 text-center">المنتجات</h1>

            <!-- نموذج بحث فوري -->
            <div class="mb-4">
                <div class="relative">
                    <input
                        type="text"
                        id="searchInput"
                        class="form-input w-full ltr:pr-10 rtl:pl-10"
                        placeholder="ابحث بكود المنتج أو القياس..."
                        value="{{ request('search') }}"
                        autocomplete="off"
                    />
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
                <p class="text-xs text-gray-500 mt-1 text-center">
                    اكتب كود المنتج أو القياس للبحث الفوري
                </p>
            </div>

            <!-- عدد النتائج -->
            <div class="text-sm text-gray-500 mb-2">
                <span id="resultCount">{{ $products->total() }} منتج</span>
            </div>

            <!-- معلومات البحث البسيطة -->
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
            @include('delegate.products.partials.product-cards', ['products' => $products])
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
                    <a href="{{ route('delegate.products.all') }}" class="btn btn-primary btn-sm">
                        مسح البحث
                    </a>
                </div>
            @else
                <p class="text-gray-400">ابدأ البحث عن منتج باستخدام الكود أو الاسم</p>
            @endif
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal"
         class="fixed inset-0 bg-black/60 z-[999] hidden overflow-y-auto"
         onclick="if(event.target === this) closeProductModal()">
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
                    <img id="modalProductImage"
                         src=""
                         alt=""
                         class="w-24 h-24 object-cover rounded">
                    <div class="flex-1">
                        <p class="text-primary text-sm font-bold mb-1" id="modalProductCode"></p>
                        <p class="text-2xl font-bold text-primary mb-2">
                            <span id="modalProductPrice"></span>
                            <span class="text-sm text-gray-500">د.ع</span>
                        </p>
                    </div>
                </div>

                <!-- Sizes Selection -->
                <div class="space-y-3 mb-5 max-h-[400px] overflow-y-auto" id="modalSizesList">
                    <!-- Sizes will be loaded here -->
                </div>

                <!-- Footer Buttons -->
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" onclick="closeProductModal()" class="btn btn-outline-danger">
                        إلغاء
                    </button>
                    <button type="button" onclick="addSelectedSizesToCart()" class="btn btn-success">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        إضافة للطلب
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Confirmation Modal -->
    @if(session('current_cart_id') && isset($currentCart) && isset($customerData))
        <div id="confirmOrderModal"
             class="fixed inset-0 bg-black/60 z-[999] hidden overflow-y-auto"
             onclick="if(event.target === this) closeConfirmModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="panel max-w-3xl w-full mx-auto" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-5 pb-4 border-b">
                        <h5 class="font-bold text-xl">تأكيد إرسال الطلب</h5>
                        <button type="button" onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Customer Info -->
                    <div class="mb-5">
                        <h6 class="font-bold text-lg mb-3">معلومات الزبون</h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <span class="text-gray-500 text-sm">الاسم:</span>
                                <p class="font-medium">{{ $customerData['customer_name'] }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">الهاتف:</span>
                                <p class="font-medium">{{ $customerData['customer_phone'] }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-gray-500 text-sm">العنوان:</span>
                                <p class="font-medium">{{ $customerData['customer_address'] }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-gray-500 text-sm">رابط التواصل:</span>
                                <p class="font-medium">{{ $customerData['customer_social_link'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Products List -->
                    <div class="mb-5">
                        <h6 class="font-bold text-lg mb-3">المنتجات</h6>
                        <div class="table-responsive">
                            <table class="table-hover">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>القياس</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($currentCart->items as $item)
                                        <tr>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <img src="{{ $item->product->primaryImage->image_url ?? '/assets/images/no-image.png' }}"
                                                         class="w-10 h-10 object-cover rounded">
                                                    <div>
                                                        <p class="font-medium">{{ $item->product->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ $item->product->code }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $item->size->size_name }}</td>
                                            <td><span class="badge badge-outline-primary">{{ $item->quantity }}</span></td>
                                            <td>{{ number_format($item->price, 0) }} د.ع</td>
                                            <td class="font-bold">{{ number_format($item->subtotal, 0) }} د.ع</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right font-bold">المجموع الكلي:</td>
                                        <td class="font-bold text-success text-xl">{{ number_format($currentCart->total_amount, 0) }} د.ع</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if(!empty($customerData['notes']))
                        <div class="mb-5">
                            <h6 class="font-bold text-lg mb-2">ملاحظات:</h6>
                            <p class="text-gray-700 dark:text-gray-300">{{ $customerData['notes'] }}</p>
                        </div>
                    @endif

                    <!-- Footer Buttons -->
                    <div class="flex gap-3 justify-end mt-6 pt-4 border-t">
                        <button type="button" onclick="closeConfirmModal()" class="btn btn-outline-secondary">
                            رجوع
                        </button>
                        <form method="POST" action="{{ route('delegate.orders.submit') }}" id="confirmOrderForm">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                تأكيد وإرسال
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Cart View/Edit Modal -->
    @if(session('current_cart_id') && isset($currentCart) && isset($customerData))
        <div id="cartModal"
             class="fixed inset-0 bg-black/60 z-[999] hidden overflow-y-auto"
             onclick="if(event.target === this) closeCartModal()">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="panel max-w-5xl w-full mx-auto" onclick="event.stopPropagation()">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-5 pb-4 border-b">
                        <h5 class="font-bold text-xl">عرض وتعديل الطلب</h5>
                        <button type="button" onclick="closeCartModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Customer Info -->
                    <div class="mb-5">
                        <h6 class="font-bold text-lg mb-3">معلومات الزبون</h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 bg-gray-50 dark:bg-gray-800 p-4 rounded">
                            <div>
                                <span class="text-gray-500 text-sm">الاسم:</span>
                                <p class="font-medium">{{ $customerData['customer_name'] }}</p>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">الهاتف:</span>
                                <p class="font-medium">{{ $customerData['customer_phone'] }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <span class="text-gray-500 text-sm">العنوان:</span>
                                <p class="font-medium">{{ $customerData['customer_address'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Products List with Edit capabilities -->
                    <div class="mb-5">
                        <h6 class="font-bold text-lg mb-3">المنتجات في السلة</h6>
                        <div class="table-responsive">
                            <table class="table-hover">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>القياس</th>
                                        <th>الكمية</th>
                                        <th>السعر</th>
                                        <th>الإجمالي</th>
                                        <th class="text-center">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="cartModalItems">
                                    @foreach($currentCart->items as $item)
                                        <tr data-item-id="{{ $item->id }}">
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <img src="{{ $item->product->primaryImage->image_url ?? '/assets/images/no-image.png' }}"
                                                         class="w-10 h-10 object-cover rounded">
                                                    <div>
                                                        <p class="font-medium text-sm">{{ $item->product->name }}</p>
                                                        <p class="text-xs text-gray-500">{{ $item->product->code }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $item->size->size_name }}</td>
                                            <td>
                                                <input type="number"
                                                       value="{{ $item->quantity }}"
                                                       min="1"
                                                       max="{{ $item->size->available_quantity + $item->quantity }}"
                                                       class="form-input w-20"
                                                       onchange="updateCartItemQuantity({{ $item->id }}, this.value)"
                                                       data-item-price="{{ $item->price }}">
                                            </td>
                                            <td>{{ number_format($item->price, 0) }} د.ع</td>
                                            <td class="font-bold item-subtotal">{{ number_format($item->subtotal, 0) }} د.ع</td>
                                            <td class="text-center">
                                                <button type="button"
                                                        onclick="deleteCartItem({{ $item->id }})"
                                                        class="btn btn-sm btn-outline-danger">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right font-bold">المجموع الكلي:</td>
                                        <td colspan="2" class="font-bold text-success text-xl" id="cartModalTotal">
                                            {{ number_format($currentCart->total_amount, 0) }} د.ع
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="flex gap-3 justify-end mt-6 pt-4 border-t">
                        <button type="button" onclick="closeCartModal()" class="btn btn-outline-secondary">
                            إغلاق
                        </button>
                        <button type="button" onclick="closeCartModal(); window.scrollTo(0,0);" class="btn btn-outline-primary">
                            <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            إضافة منتجات
                        </button>
                        <button type="button" onclick="closeCartModal(); openConfirmModal();" class="btn btn-success">
                            <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            إرسال الطلب
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script>
        let page = 1;
        let loading = false;
        let hasMore = {{ $products->hasMorePages() ? 'true' : 'false' }};
        let searchTimeout;
        let currentSearch = '{{ request('search') }}';

        // زر تحميل المزيد
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        const loadMoreContainer = document.getElementById('loadMoreContainer');

        // إخفاء الزر إذا لم يكن هناك المزيد
        if (!hasMore) {
            loadMoreContainer.classList.add('hidden');
        }

        loadMoreBtn.addEventListener('click', function() {
            if (hasMore) {
                page++;
                loadProducts(false);
            }
        });

        // البحث الفوري
        const searchInput = document.getElementById('searchInput');
        let searchDebounceTimeout;

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.trim();

            // إلغاء المؤقت السابق
            clearTimeout(searchDebounceTimeout);

            // انتظر 500ms بعد توقف الكتابة
            searchDebounceTimeout = setTimeout(() => {
                page = 1; // إعادة تعيين الصفحة
                currentSearch = searchTerm; // حفظ مصطلح البحث الحالي
                loadProducts(true); // true = replace content
            }, 500);
        });

        // دالة مسح البحث
        window.clearSearch = function() {
            searchInput.value = '';
            currentSearch = '';
            page = 1;
            loadProducts(true);
        };

        // Infinite scroll
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

            console.log('Loading products:', { page, search: currentSearch, reset });

            if (reset) {
                document.getElementById('productsContainer').innerHTML = '';
                page = 1;
            }

            document.getElementById('loadingIndicator').classList.remove('hidden');
            if (loadMoreBtn) loadMoreBtn.disabled = true;

            const url = `{{ route('delegate.products.all') }}?page=${page}&search=${encodeURIComponent(currentSearch)}`;
            console.log('Fetching:', url);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Data received:', data);

                if (reset) {
                    document.getElementById('productsContainer').innerHTML = data.products;
                } else {
                    document.getElementById('productsContainer').insertAdjacentHTML('beforeend', data.products);
                }

                hasMore = data.has_more;
                loading = false;
                if (loadMoreBtn) loadMoreBtn.disabled = false;

                // إخفاء أو إظهار الزر
                if (!hasMore) {
                    if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                } else {
                    if (loadMoreContainer) loadMoreContainer.classList.remove('hidden');
                }

                document.getElementById('loadingIndicator').classList.add('hidden');

                // إظهار رسالة عدم وجود منتجات
                if (document.getElementById('productsContainer').children.length === 0) {
                    document.getElementById('noProducts').classList.remove('hidden');
                    if (loadMoreContainer) loadMoreContainer.classList.add('hidden');
                } else {
                    document.getElementById('noProducts').classList.add('hidden');
                }

                // تحديث عدد النتائج
                const resultCount = document.getElementById('resultCount');
                if (resultCount) {
                    resultCount.textContent = `${data.total} منتج`;
                }

                // تهيئة Swiper للمنتجات الجديدة
                initializeProductSwipers();
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('حدث خطأ في تحميل المنتجات. يرجى المحاولة مرة أخرى.');
                loading = false;
                if (loadMoreBtn) loadMoreBtn.disabled = false;
                document.getElementById('loadingIndicator').classList.add('hidden');
            });
        }

        // إظهار رسالة عدم وجود منتجات عند التحميل الأولي
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('productsContainer').children.length === 0) {
                document.getElementById('noProducts').classList.remove('hidden');
            }
        });

        // دالة أرشفة الطلب الحالي
        function archiveCurrentOrder() {
            Swal.fire({
                title: 'أرشفة الطلب؟',
                text: 'سيتم حفظ الطلب ويمكنك استرجاعه لاحقاً',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'نعم، أرشف',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#4361ee',
                cancelButtonColor: '#e7515a'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route('delegate.orders.archive-current') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                        },
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('تم!', 'تم أرشفة الطلب بنجاح', 'success')
                                .then(() => window.location.reload());
                        } else {
                            Swal.fire('خطأ!', data.error || 'حدث خطأ أثناء الأرشفة', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('خطأ!', 'حدث خطأ أثناء الأرشفة', 'error');
                    });
                }
            });
        }

        // ===== Product Modal Functions =====
        let currentProductData = null;
        let selectedSizes = {};

        // فتح المودال
        function openProductModal(productId) {
            // التحقق من وجود طلب نشط
            @if(!session('current_cart_id'))
                Swal.fire({
                    title: 'لا يوجد طلب نشط',
                    text: 'يجب إنشاء طلب جديد أولاً',
                    icon: 'warning',
                    confirmButtonText: 'إنشاء طلب',
                    showCancelButton: true,
                    cancelButtonText: 'إلغاء'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ route('delegate.orders.start') }}';
                    }
                });
                return;
            @endif

            // جلب بيانات المنتج
            fetch(`/delegate/api/products/${productId}`)
                .then(res => res.json())
                .then(data => {
                    currentProductData = data;
                    selectedSizes = {};

                    // تعبئة معلومات المنتج
                    document.getElementById('modalProductName').textContent = data.name;
                    document.getElementById('modalProductCode').textContent = data.code;
                    document.getElementById('modalProductPrice').textContent = number_format(data.selling_price, 0);
                    document.getElementById('modalProductImage').src = data.image || '/assets/images/no-image.png';
                    document.getElementById('modalProductImage').alt = data.name;

                    // تعبئة القياسات
                    const sizesList = document.getElementById('modalSizesList');
                    sizesList.innerHTML = '';

                    data.sizes.forEach(size => {
                        const isAvailable = size.available_quantity > 0;
                        const sizeHtml = `
                            <div class="flex items-center gap-3 p-3 rounded border ${isAvailable ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/50'}">
                                <label class="flex items-center flex-1 cursor-pointer ${!isAvailable ? 'opacity-50 cursor-not-allowed' : ''}">
                                    <input type="checkbox"
                                           ${!isAvailable ? 'disabled' : ''}
                                           class="form-checkbox"
                                           onchange="toggleSizeSelection(${size.id}, ${size.available_quantity}, this.checked)">
                                    <span class="ltr:ml-3 rtl:mr-3 font-medium">
                                        ${size.size_name}
                                        ${isAvailable
                                            ? `<span class="text-xs text-green-600">(متوفر: ${size.available_quantity})</span>`
                                            : `<span class="text-xs text-red-500">(غير متوفر)</span>`
                                        }
                                    </span>
                                </label>
                                <input type="number"
                                       id="qty-${size.id}"
                                       min="1"
                                       max="${size.available_quantity}"
                                       value="1"
                                       class="form-input w-20 ${!isAvailable ? 'hidden' : ''}"
                                       ${!isAvailable ? 'disabled' : ''}
                                       onchange="updateSizeQuantity(${size.id}, this.value)">
                            </div>
                        `;
                        sizesList.insertAdjacentHTML('beforeend', sizeHtml);
                    });

                    // عرض المودال
                    document.getElementById('productModal').classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    Swal.fire('خطأ', 'حدث خطأ أثناء تحميل المنتج', 'error');
                });
        }

        // إغلاق المودال
        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.body.style.overflow = '';
            currentProductData = null;
            selectedSizes = {};
        }

        // تفعيل/إلغاء تفعيل قياس
        function toggleSizeSelection(sizeId, maxQty, checked) {
            if (checked) {
                const qtyInput = document.getElementById(`qty-${sizeId}`);
                selectedSizes[sizeId] = {
                    quantity: parseInt(qtyInput.value) || 1,
                    max_quantity: maxQty
                };
            } else {
                delete selectedSizes[sizeId];
            }
        }

        // تحديث كمية القياس
        function updateSizeQuantity(sizeId, quantity) {
            if (selectedSizes[sizeId]) {
                selectedSizes[sizeId].quantity = parseInt(quantity);
            }
        }

        // إضافة القياسات المختارة للسلة
        function addSelectedSizesToCart() {
            const selectedSizesArray = Object.keys(selectedSizes);

            if (selectedSizesArray.length === 0) {
                Swal.fire('تنبيه', 'يرجى اختيار قياس واحد على الأقل', 'warning');
                return;
            }

            // تحضير البيانات
            const items = selectedSizesArray.map(sizeId => ({
                size_id: parseInt(sizeId),
                quantity: selectedSizes[sizeId].quantity
            }));

            // إرسال للسيرفر
            fetch('{{ route('delegate.carts.items.store') }}', {
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

                // إذا كان الـ response JSON
                if (contentType && contentType.includes('application/json')) {
                    const data = await res.json();

                    if (res.ok && data.success) {
                        // إغلاق المودال
                        closeProductModal();

                        // إشعار نجاح
                        Swal.fire({
                            title: 'تم!',
                            text: 'تم إضافة المنتج للطلب بنجاح',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // تحديث الصفحة لتحديث البانر
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        Swal.fire('خطأ', data.message || 'حدث خطأ أثناء الإضافة', 'error');
                    }
                } else {
                    // إذا كان الـ response redirect أو HTML (يعني نجح لكن Laravel عمل redirect)
                    if (res.ok || res.redirected) {
                        // إغلاق المودال
                        closeProductModal();

                        // إشعار نجاح
                        Swal.fire({
                            title: 'تم!',
                            text: 'تم إضافة المنتج للطلب بنجاح',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // تحديث الصفحة
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

        // دالة تنسيق الأرقام
        function number_format(number, decimals = 0) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        }

        // ===== Initialize Swiper for Product Cards =====
        document.addEventListener('DOMContentLoaded', function() {
            initializeProductSwipers();
        });

        function initializeProductSwipers() {
            // Find all product swipers that haven't been initialized yet
            document.querySelectorAll('[class*="product-swiper-"]').forEach(swiperElement => {
                // Skip if already initialized
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

        // ===== Confirm Order Modal Functions =====

        // فتح مودال التأكيد
        function openConfirmModal() {
            document.getElementById('confirmOrderModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        // إغلاق مودال التأكيد
        function closeConfirmModal() {
            document.getElementById('confirmOrderModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // ===== Cart Modal Functions =====

        // فتح مودال السلة
        function openCartModal() {
            document.getElementById('cartModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        // إغلاق مودال السلة
        function closeCartModal() {
            document.getElementById('cartModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // تحديث كمية منتج في المودال
        function updateCartItemQuantity(itemId, newQuantity) {
            if (newQuantity < 1) {
                Swal.fire('خطأ', 'الكمية يجب أن تكون 1 على الأقل', 'error');
                return;
            }

            fetch(`/delegate/cart-items/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ quantity: parseInt(newQuantity) })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // تحديث الإجمالي الفرعي في الجدول
                    const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                    const price = parseFloat(row.querySelector('input[data-item-price]').dataset.itemPrice);
                    const subtotal = price * newQuantity;
                    row.querySelector('.item-subtotal').textContent = number_format(subtotal, 0) + ' د.ع';

                    // تحديث الإجمالي الكلي
                    updateCartModalTotal();

                    Swal.fire({
                        icon: 'success',
                        title: 'تم!',
                        text: 'تم تحديث الكمية',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('خطأ', data.message || 'حدث خطأ أثناء التحديث', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('خطأ', 'حدث خطأ أثناء التحديث', 'error');
            });
        }

        // حذف منتج من السلة في المودال
        function deleteCartItem(itemId) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف هذا المنتج من السلة',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء',
                confirmButtonColor: '#e7515a',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`/delegate/cart-items/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // حذف الصف من الجدول
                            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                            row.remove();

                            // تحديث الإجمالي
                            updateCartModalTotal();

                            Swal.fire('تم!', 'تم حذف المنتج', 'success');

                            // إذا أصبحت السلة فارغة، أعد تحميل الصفحة
                            const remainingItems = document.querySelectorAll('#cartModalItems tr').length;
                            if (remainingItems === 0) {
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        } else {
                            Swal.fire('خطأ', data.message || 'حدث خطأ', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('خطأ', 'حدث خطأ أثناء الحذف', 'error');
                    });
                }
            });
        }

        // تحديث الإجمالي الكلي في المودال
        function updateCartModalTotal() {
            let total = 0;
            document.querySelectorAll('#cartModalItems tr').forEach(row => {
                const input = row.querySelector('input[data-item-price]');
                if (input) {
                    const price = parseFloat(input.dataset.itemPrice);
                    const quantity = parseInt(input.value);
                    total += price * quantity;
                }
            });
            document.getElementById('cartModalTotal').textContent = number_format(total, 0) + ' د.ع';
        }
    </script>
</x-layout.default>

