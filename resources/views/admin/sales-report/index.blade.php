<x-layout.admin>
    <script defer src="/assets/js/apexcharts.js"></script>

    <div x-data="salesReport">
        <ul class="flex space-x-2 rtl:space-x-reverse mb-6">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="text-primary hover:underline">لوحة التحكم</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>كشف مبيعات</span>
            </li>
        </ul>

        <!-- الفلاتر -->
        <div class="panel mb-6">
            <form method="GET" action="{{ route('admin.sales-report') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- فلتر المندوب -->
                    <div>
                        <label class="form-label">المندوب</label>
                        <select name="delegate_id" class="form-select">
                            <option value="">كل المندوبين</option>
                            @foreach($delegates as $delegate)
                                <option value="{{ $delegate->id }}" {{ request('delegate_id') == $delegate->id ? 'selected' : '' }}>
                                    {{ $delegate->name }} ({{ $delegate->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- فلتر المجهز -->
                    <div>
                        <label class="form-label">المجهز</label>
                        <select name="confirmed_by" class="form-select">
                            <option value="">كل المجهزين والمديرين</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('confirmed_by') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }}) - {{ $supplier->role === 'admin' ? 'مدير' : 'مجهز' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- فلتر المخزن -->
                    <div>
                        <label class="form-label">المخزن</label>
                        <select name="warehouse_id" class="form-select">
                            <option value="">كل المخازن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- فلتر المنتج -->
                    <div>
                        <label class="form-label">المنتج</label>
                        <div class="relative">
                            <input
                                type="text"
                                id="product_search"
                                class="form-input"
                                placeholder="ابحث بكود المنتج أو اسم المنتج..."
                                autocomplete="off"
                                value="@if(request('product_id'))@php $selectedProduct = $products->firstWhere('id', request('product_id')); @endphp{{ $selectedProduct ? $selectedProduct->name . ' (' . $selectedProduct->code . ')' : '' }}@endif"
                            >
                            <input type="hidden" name="product_id" id="product_id" value="{{ request('product_id') }}">
                            <div id="product_results" class="absolute z-10 w-full mt-1 bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                                <!-- نتائج البحث ستظهر هنا -->
                            </div>
                        </div>
                    </div>

                    <!-- البحث الذكي -->
                    <div>
                        <label class="form-label">بحث ذكي</label>
                        <input type="text" name="search" class="form-input" value="{{ request('search') }}" placeholder="رقم الطلب، اسم العميل، رقم الهاتف، كود الوسيط">
                    </div>

                    <!-- فلتر من تاريخ -->
                    <div>
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-input" value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}">
                    </div>

                    <!-- فلتر إلى تاريخ -->
                    <div>
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-input" value="{{ request('date_to', now()->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
                    <a href="{{ route('admin.sales-report') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- الكاردات الإحصائية -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-6">
            <!-- المبلغ الكلي -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الكلي</h6>
                        <p class="text-xl font-bold text-primary">{{ number_format($statistics['total_amount_without_delivery'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- الأرباح -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الأرباح</h6>
                        <p class="text-xl font-bold text-warning">{{ number_format($statistics['total_profit_without_margin'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-warning/10 rounded-lg">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- عدد الطلبات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد الطلبات</h6>
                        <p class="text-xl font-bold text-primary">{{ number_format($statistics['orders_count'], 0, '.', ',') }} طلب</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- عدد القطع -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد القطع</h6>
                        <p class="text-xl font-bold text-success">{{ number_format($statistics['items_count'], 0, '.', ',') }} قطعة</p>
                    </div>
                    <div class="p-2 bg-success/10 rounded-lg">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- الشارتات -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Line Chart: المبيعات حسب التاريخ -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">المبيعات حسب التاريخ</h5>
                </div>
                <div x-ref="salesChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
            </div>

            <!-- Line Chart: الأرباح حسب التاريخ -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">الأرباح حسب التاريخ</h5>
                </div>
                <div x-ref="profitsChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data("salesReport", () => ({
                salesChart: null,
                profitsChart: null,

                init() {
                    isDark = this.$store.app.theme === "dark" || this.$store.app.isDarkMode ? true : false;
                    isRtl = this.$store.app.rtlClass === "rtl" ? true : false;

                    setTimeout(() => {
                        // Sales Chart
                        this.salesChart = new ApexCharts(this.$refs.salesChart, this.salesChartOptions);
                        this.salesChart.render();

                        // Profits Chart
                        this.profitsChart = new ApexCharts(this.$refs.profitsChart, this.profitsChartOptions);
                        this.profitsChart.render();
                    }, 300);

                    this.$watch('$store.app.theme', () => {
                        isDark = this.$store.app.theme === "dark" || this.$store.app.isDarkMode ? true : false;
                        this.refreshOptions();
                    });

                    this.$watch('$store.app.rtlClass', () => {
                        isRtl = this.$store.app.rtlClass === "rtl" ? true : false;
                        this.refreshOptions();
                    });
                },

                refreshOptions() {
                    isDark = this.$store.app.theme === "dark" || this.$store.app.isDarkMode ? true : false;
                    isRtl = this.$store.app.rtlClass === "rtl" ? true : false;
                    if (this.salesChart) this.salesChart.updateOptions(this.salesChartOptions);
                    if (this.profitsChart) this.profitsChart.updateOptions(this.profitsChartOptions);
                },

                get salesChartOptions() {
                    return {
                        series: [{
                            name: 'المبيعات',
                            data: @json($chartData['sales_by_date']['values'] ?? [])
                        }],
                        chart: {
                            height: 300,
                            type: 'line',
                            toolbar: false
                        },
                        colors: ['#4361ee'],
                        stroke: {
                            width: 2,
                            curve: 'smooth'
                        },
                        xaxis: {
                            categories: @json($chartData['sales_by_date']['categories'] ?? []),
                            axisBorder: {
                                color: isDark ? '#191e3a' : '#e0e6ed'
                            }
                        },
                        yaxis: {
                            opposite: isRtl ? true : false,
                            labels: {
                                offsetX: isRtl ? -20 : 0,
                                formatter: function(val) {
                                    return new Intl.NumberFormat('en-US', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(val) + ' دينار';
                                }
                            }
                        },
                        grid: {
                            borderColor: isDark ? '#191e3a' : '#e0e6ed'
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: {
                                formatter: function(val) {
                                    return new Intl.NumberFormat('en-US').format(val) + ' دينار';
                                }
                            }
                        }
                    };
                },

                get profitsChartOptions() {
                    return {
                        series: [{
                            name: 'الأرباح بدون فروقات',
                            data: @json($chartData['profits_by_date']['values'] ?? [])
                        }, {
                            name: 'الأرباح مع الفروقات',
                            data: @json($chartData['profits_with_margin_by_date']['values'] ?? [])
                        }],
                        chart: {
                            height: 300,
                            type: 'line',
                            toolbar: false
                        },
                        colors: ['#00ab55', '#f59e0b'],
                        stroke: {
                            width: 2,
                            curve: 'smooth'
                        },
                        xaxis: {
                            categories: @json($chartData['profits_by_date']['categories'] ?? []),
                            axisBorder: {
                                color: isDark ? '#191e3a' : '#e0e6ed'
                            }
                        },
                        yaxis: {
                            opposite: isRtl ? true : false,
                            labels: {
                                offsetX: isRtl ? -20 : 0,
                                formatter: function(val) {
                                    return new Intl.NumberFormat('en-US', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    }).format(val) + ' دينار';
                                }
                            }
                        },
                        grid: {
                            borderColor: isDark ? '#191e3a' : '#e0e6ed'
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: {
                                formatter: function(val) {
                                    return new Intl.NumberFormat('en-US').format(val) + ' دينار';
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    };
                }
            }));
        });

        // البحث عن المنتجات
        let searchTimeout;
        const productSearchInput = document.getElementById('product_search');
        const productResultsDiv = document.getElementById('product_results');
        const productIdInput = document.getElementById('product_id');

        if (productSearchInput) {
            productSearchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchProducts(this.value);
                }, 300);
            });

            // إخفاء نتائج البحث عند النقر خارجها
            document.addEventListener('click', function(event) {
                if (!productSearchInput.contains(event.target) && !productResultsDiv.contains(event.target)) {
                    productResultsDiv.classList.add('hidden');
                }
            });
        }

        function searchProducts(query) {
            if (!query || query.length < 1) {
                productResultsDiv.classList.add('hidden');
                return;
            }

            const url = `{{ route('admin.sales-report.search-products') }}?search=${encodeURIComponent(query)}`;

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(products => {
                productResultsDiv.innerHTML = '';

                if (!products || products.length === 0) {
                    productResultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">لا توجد نتائج</div>';
                } else {
                    products.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition-colors';
                        item.innerHTML = `
                            <div class="font-medium text-black dark:text-white">${escapeHtml(product.name)}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">كود: ${escapeHtml(product.code)}</div>
                        `;
                        item.addEventListener('click', function() {
                            selectProduct(product.id, product.name, product.code);
                        });
                        productResultsDiv.appendChild(item);
                    });
                }
                productResultsDiv.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error searching products:', error);
                productResultsDiv.innerHTML = '<div class="p-3 text-sm text-danger text-center">حدث خطأ في البحث</div>';
                productResultsDiv.classList.remove('hidden');
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function selectProduct(productId, productName, productCode) {
            productIdInput.value = productId;
            productSearchInput.value = `${productName} (${productCode})`;
            productResultsDiv.classList.add('hidden');
        }
    </script>
</x-layout.admin>

