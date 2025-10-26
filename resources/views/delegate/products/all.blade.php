<x-layout.default>
    <div class="container mx-auto px-4 py-6 max-w-7xl">
        <!-- Header ثابت -->
        <div class="sticky top-0 bg-white dark:bg-gray-900 z-10 pb-4">
            <h1 class="text-2xl font-bold mb-4 text-center">المنتجات</h1>

            <!-- نموذج بحث بسيط -->
            <div class="mb-4">
                <form method="GET" action="{{ route('delegate.products.all') }}" id="searchForm">
                    <div class="flex gap-2">
                        <input
                            type="text"
                            id="searchInput"
                            name="search"
                            class="form-input flex-1"
                            placeholder="ابحث بالكود أو الاسم..."
                            value="{{ request('search') }}"
                        />
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            بحث
                        </button>
                        @if(request('search'))
                            <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-secondary">
                                مسح
                            </a>
                        @endif
                    </div>
                </form>
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

    @push('scripts')
    <script>
        let page = 1;
        let loading = false;
        let hasMore = {{ $products->hasMorePages() ? 'true' : 'false' }};
        let searchTimeout;

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

        // البحث المباشر - تم استبداله بـ form submit
        // البحث الآن يعمل عبر form submission بدلاً من AJAX

        // فلتر القياس - تم استبداله بـ form submit
        // الفلتر الآن يعمل عبر form submission بدلاً من AJAX

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

            const search = document.getElementById('searchInput').value || '{{ request('search') }}';

            console.log('Loading products:', { page, search, reset });

            if (reset) {
                document.getElementById('productsContainer').innerHTML = '';
                page = 1;
            }

            document.getElementById('loadingIndicator').classList.remove('hidden');
            if (loadMoreBtn) loadMoreBtn.disabled = true;

            const url = `{{ route('delegate.products.all') }}?page=${page}&search=${encodeURIComponent(search)}`;
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
            })
            .catch(error => {
                console.error('Error details:', error);
                alert('حدث خطأ في تحميل المنتجات. يرجى المحاولة مرة أخرى.');
                loading = false;
                loadMoreBtn.disabled = false;
                document.getElementById('loadingIndicator').classList.add('hidden');
            });
        }

        // البحث التلقائي
        const searchInput = document.getElementById('searchInput');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadProducts(true);
                }, 500);
            });
        }

        // إظهار رسالة عدم وجود منتجات عند التحميل الأولي
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('productsContainer').children.length === 0) {
                document.getElementById('noProducts').classList.remove('hidden');
            }
        });
    </script>
    @endpush
</x-layout.default>

