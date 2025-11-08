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
                    <!-- فلتر حالة الطلب -->
                    <div>
                        <label class="form-label">حالة الطلب</label>
                        <select name="order_status" class="form-select">
                            <option value="all" {{ request('order_status') == 'all' || !request('order_status') ? 'selected' : '' }}>الكل</option>
                            <option value="confirmed" {{ request('order_status') == 'confirmed' ? 'selected' : '' }}>مقيدة</option>
                            <option value="pending" {{ request('order_status') == 'pending' ? 'selected' : '' }}>غير مقيدة</option>
                        </select>
                    </div>

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
                        <select name="product_id" class="form-select">
                            <option value="">كل المنتجات</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }} ({{ $product->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- فلتر الطلبات المسترجعة -->
                    <div>
                        <label class="form-label">الطلبات المسترجعة</label>
                        <select name="orders_returned" class="form-select">
                            <option value="all" {{ request('orders_returned') == 'all' || !request('orders_returned') ? 'selected' : '' }}>الكل</option>
                            <option value="returned" {{ request('orders_returned') == 'returned' ? 'selected' : '' }}>مسترجعة</option>
                            <option value="not_returned" {{ request('orders_returned') == 'not_returned' ? 'selected' : '' }}>غير مسترجعة</option>
                        </select>
                    </div>

                    <!-- فلتر المواد المسترجعة -->
                    <div>
                        <label class="form-label">المواد المسترجعة</label>
                        <select name="items_returned" class="form-select">
                            <option value="all" {{ request('items_returned') == 'all' || !request('items_returned') ? 'selected' : '' }}>الكل</option>
                            <option value="returned" {{ request('items_returned') == 'returned' ? 'selected' : '' }}>مسترجعة</option>
                            <option value="not_returned" {{ request('items_returned') == 'not_returned' ? 'selected' : '' }}>غير مسترجعة</option>
                        </select>
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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <!-- المبلغ الكلي مع التوصيل -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الكلي مع التوصيل</h6>
                        <p class="text-xl font-bold text-success">{{ number_format($statistics['total_amount_with_delivery'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-success/10 rounded-lg">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المبلغ الكلي بدون توصيل -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الكلي بدون توصيل</h6>
                        <p class="text-xl font-bold text-primary">{{ number_format($statistics['total_amount_without_delivery'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- مبلغ الأرباح الكلي بدون فروقات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">مبلغ الأرباح الكلي بدون فروقات</h6>
                        <p class="text-xl font-bold text-info">{{ number_format($statistics['total_profit_without_margin'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-info/10 rounded-lg">
                        <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- مبلغ الأرباح الكلي مع الفروقات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">مبلغ الأرباح الكلي مع الفروقات</h6>
                        <p class="text-xl font-bold text-warning">{{ number_format($statistics['total_profit_with_margin'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-warning/10 rounded-lg">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- مبلغ الكلي للفروقات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">مبلغ الكلي للفروقات</h6>
                        <p class="text-xl font-bold text-secondary">{{ number_format($statistics['total_margin_amount'], 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-secondary/10 rounded-lg">
                        <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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

            <!-- عدد المواد -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد المواد</h6>
                        <p class="text-xl font-bold text-success">{{ number_format($statistics['items_count'], 0, '.', ',') }} قطعة</p>
                    </div>
                    <div class="p-2 bg-success/10 rounded-lg">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المنتج الأكثر مبيعاً -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المنتج الأكثر مبيعاً</h6>
                        @if($statistics['most_sold_product_id'])
                            @php
                                $mostSoldProduct = \App\Models\Product::find($statistics['most_sold_product_id']);
                            @endphp
                            <p class="text-lg font-bold text-info">{{ $mostSoldProduct ? $mostSoldProduct->name : 'غير محدد' }}</p>
                        @else
                            <p class="text-lg font-bold text-info">غير محدد</p>
                        @endif
                    </div>
                    <div class="p-2 bg-info/10 rounded-lg">
                        <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المنتج الأقل مبيعاً -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المنتج الأقل مبيعاً</h6>
                        @if($statistics['least_sold_product_id'])
                            @php
                                $leastSoldProduct = \App\Models\Product::find($statistics['least_sold_product_id']);
                            @endphp
                            <p class="text-lg font-bold text-warning">{{ $leastSoldProduct ? $leastSoldProduct->name : 'غير محدد' }}</p>
                        @else
                            <p class="text-lg font-bold text-warning">غير محدد</p>
                        @endif
                    </div>
                    <div class="p-2 bg-warning/10 rounded-lg">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
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
    </script>
</x-layout.admin>

