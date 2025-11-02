<x-layout.admin>
    <script defer src="/assets/js/apexcharts.js"></script>

    <div x-data="reports">
        <ul class="flex space-x-2 rtl:space-x-reverse mb-6">
            <li>
                <a href="{{ route('admin.dashboard') }}" class="text-primary hover:underline">لوحة التحكم</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>التقارير</span>
            </li>
        </ul>

        <!-- الفلاتر -->
        <div class="panel mb-6">
            <form method="GET" action="{{ route('admin.reports') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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

                    <!-- فلتر من تاريخ -->
                    <div>
                        <label class="form-label">من تاريخ</label>
                        <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
                    </div>

                    <!-- فلتر إلى تاريخ -->
                    <div>
                        <label class="form-label">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
                    </div>

                    <!-- فلتر حالة الطلب -->
                    <div>
                        <label class="form-label">حالة الطلب</label>
                        <select name="status" class="form-select">
                            <option value="">كل الحالات</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>غير مقيد</option>
                            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>مقيد</option>
                            <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>مسترجعة</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ملغاة</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
                    <a href="{{ route('admin.reports') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- الكاردات الإحصائية -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
            <!-- الربح الحالي -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الربح الحالي</h6>
                        <p class="text-xl font-bold text-success">{{ number_format($totalActualProfit, 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-success/10 rounded-lg">
                        <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- الربح المتوقع -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">الربح المتوقع</h6>
                        <p class="text-xl font-bold text-primary">{{ number_format($totalExpectedProfit, 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- قيمة المخازن -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">قيمة المخازن</h6>
                        <p class="text-xl font-bold text-info">{{ number_format($totalWarehouseValue, 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-info/10 rounded-lg">
                        <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- قيمة المنتجات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">قيمة المنتجات</h6>
                        <p class="text-xl font-bold text-warning">{{ number_format($totalProductValue, 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-warning/10 rounded-lg">
                        <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المبلغ الإجمالي -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">المبلغ الإجمالي</h6>
                        <p class="text-xl font-bold text-secondary">{{ number_format($totalAmount, 0, '.', ',') }} دينار</p>
                    </div>
                    <div class="p-2 bg-secondary/10 rounded-lg">
                        <svg class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- عدد القطع المبيعة -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">عدد القطع المبيعة</h6>
                        <p class="text-xl font-bold text-primary">{{ number_format($totalSoldItems ?? 0, 0, '.', ',') }} قطعة</p>
                    </div>
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($soldItemsByWarehouse) && $soldItemsByWarehouse->count() > 0)
        <!-- القطع المبيعة لكل مخزن -->
        <div class="panel mb-5">
            <h5 class="font-semibold text-lg dark:text-white-light mb-4">عدد القطع المبيعة لكل مخزن</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($soldItemsByWarehouse as $item)
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-sm font-semibold text-gray-600 dark:text-gray-400">{{ $item['warehouse_name'] }}</h6>
                            <p class="text-lg font-bold text-primary">{{ number_format($item['total_quantity'], 0, '.', ',') }} قطعة</p>
                        </div>
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- الشارتات -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Line Chart: اتجاه الأرباح حسب التاريخ -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">اتجاه الأرباح حسب التاريخ</h5>
                </div>
                <div x-ref="lineChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
            </div>

            <!-- Column Chart: مقارنة الربح الحالي vs المتوقع -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">مقارنة الربح الحالي والمتوقع</h5>
                </div>
                <div x-ref="columnChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Pie Chart: توزيع الأرباح حسب المخازن -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">توزيع الأرباح حسب المخازن</h5>
                </div>
                <div x-ref="pieChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
            </div>

            <!-- Bar Chart: الأرباح حسب المندوبين -->
            <div class="panel">
                <div class="flex items-center justify-between mb-5">
                    <h5 class="font-semibold text-lg dark:text-white-light">الأرباح حسب المندوبين</h5>
                </div>
                <div x-ref="barChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
            </div>
        </div>

        <!-- Area Chart: قيمة المخازن عبر الزمن -->
        <div class="panel mb-6">
            <div class="flex items-center justify-between mb-5">
                <h5 class="font-semibold text-lg dark:text-white-light">قيمة المخازن عبر الزمن</h5>
            </div>
            <div x-ref="areaChart" class="bg-white dark:bg-black rounded-lg overflow-hidden"></div>
        </div>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data("reports", () => ({
                lineChart: null,
                columnChart: null,
                pieChart: null,
                barChart: null,
                areaChart: null,

                init() {
                    isDark = this.$store.app.theme === "dark" || this.$store.app.isDarkMode ? true : false;
                    isRtl = this.$store.app.rtlClass === "rtl" ? true : false;

                    setTimeout(() => {
                        // Line Chart
                        this.lineChart = new ApexCharts(this.$refs.lineChart, this.lineChartOptions);
                        this.lineChart.render();

                        // Column Chart
                        this.columnChart = new ApexCharts(this.$refs.columnChart, this.columnChartOptions);
                        this.columnChart.render();

                        // Pie Chart
                        this.pieChart = new ApexCharts(this.$refs.pieChart, this.pieChartOptions);
                        this.pieChart.render();

                        // Bar Chart
                        this.barChart = new ApexCharts(this.$refs.barChart, this.barChartOptions);
                        this.barChart.render();

                        // Area Chart
                        this.areaChart = new ApexCharts(this.$refs.areaChart, this.areaChartOptions);
                        this.areaChart.render();
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
                    if (this.lineChart) this.lineChart.updateOptions(this.lineChartOptions);
                    if (this.columnChart) this.columnChart.updateOptions(this.columnChartOptions);
                    if (this.pieChart) this.pieChart.updateOptions(this.pieChartOptions);
                    if (this.barChart) this.barChart.updateOptions(this.barChartOptions);
                    if (this.areaChart) this.areaChart.updateOptions(this.areaChartOptions);
                },

                get lineChartOptions() {
                    return {
                        series: [{
                            name: 'الربح الحالي',
                            data: @json($lineChartData['actual'] ?? [])
                        }, {
                            name: 'الربح المتوقع',
                            data: @json($lineChartData['expected'] ?? [])
                        }],
                        chart: {
                            height: 300,
                            type: 'line',
                            toolbar: false
                        },
                        colors: ['#00ab55', '#4361ee'],
                        stroke: {
                            width: 2,
                            curve: 'smooth'
                        },
                        xaxis: {
                            categories: @json($lineChartData['categories'] ?? []),
                            axisBorder: {
                                color: isDark ? '#191e3a' : '#e0e6ed'
                            }
                        },
                        yaxis: {
                            opposite: isRtl ? true : false,
                            labels: {
                                offsetX: isRtl ? -20 : 0,
                                formatter: function(val) {
                                    return new Intl.NumberFormat('ar-IQ', {
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
                                    return new Intl.NumberFormat('ar-IQ').format(val) + ' دينار';
                                }
                            }
                        },
                        legend: {
                            position: 'top'
                        }
                    };
                },

                get columnChartOptions() {
                    const actualValue = @json($columnChartData['actual'] ?? 0);
                    const expectedValue = @json($columnChartData['expected'] ?? 0);
                    return {
                        series: [{
                            name: 'الربح الحالي',
                            data: [actualValue]
                        }, {
                            name: 'الربح المتوقع',
                            data: [expectedValue]
                        }],
                        chart: {
                            height: 300,
                            type: 'bar',
                            toolbar: false
                        },
                        colors: ['#00ab55', '#4361ee'],
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '55%',
                                endingShape: 'rounded'
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            show: true,
                            width: 2,
                            colors: ['transparent']
                        },
                        xaxis: {
                            categories: ['المقارنة'],
                            axisBorder: {
                                color: isDark ? '#191e3a' : '#e0e6ed'
                            }
                        },
                        yaxis: {
                            opposite: isRtl ? true : false,
                            labels: {
                                offsetX: isRtl ? -20 : 0,
                                formatter: function(val) {
                                    return new Intl.NumberFormat('ar-IQ', {
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
                                    return new Intl.NumberFormat('ar-IQ').format(val) + ' دينار';
                                }
                            }
                        }
                    };
                },

                get pieChartOptions() {
                    return {
                        series: @json($pieChartData['values'] ?? []),
                        chart: {
                            height: 300,
                            type: 'pie',
                            toolbar: false
                        },
                        labels: @json($pieChartData['labels'] ?? []),
                        colors: ['#4361ee', '#805dca', '#00ab55', '#e7515a', '#e2a03f', '#2196f3', '#3b3f5c'],
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            theme: isDark ? 'dark' : 'light',
                            y: {
                                formatter: function(val) {
                                    return new Intl.NumberFormat('ar-IQ').format(val) + ' دينار';
                                }
                            }
                        }
                    };
                },

                get barChartOptions() {
                    return {
                        series: [{
                            name: 'الربح',
                            data: @json($barChartData['values'] ?? [])
                        }],
                        chart: {
                            height: 300,
                            type: 'bar',
                            toolbar: false
                        },
                        colors: ['#4361ee'],
                        plotOptions: {
                            bar: {
                                horizontal: true
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: {
                            categories: @json($barChartData['labels'] ?? []),
                            axisBorder: {
                                color: isDark ? '#191e3a' : '#e0e6ed'
                            }
                        },
                        yaxis: {
                            opposite: isRtl ? true : false,
                            labels: {
                                offsetX: isRtl ? -20 : 0,
                                formatter: function(val) {
                                    return new Intl.NumberFormat('ar-IQ', {
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
                                    return new Intl.NumberFormat('ar-IQ').format(val) + ' دينار';
                                }
                            }
                        }
                    };
                },

                get areaChartOptions() {
                    return {
                        series: [{
                            name: 'قيمة المخازن',
                            data: @json($areaChartData['values'] ?? [])
                        }],
                        chart: {
                            height: 300,
                            type: 'area',
                            toolbar: false
                        },
                        colors: ['#805dca'],
                        stroke: {
                            width: 2,
                            curve: 'smooth'
                        },
                        xaxis: {
                            categories: @json($areaChartData['categories'] ?? []),
                            axisBorder: {
                                color: isDark ? '#191e3a' : '#e0e6ed'
                            }
                        },
                        yaxis: {
                            opposite: isRtl ? true : false,
                            labels: {
                                offsetX: isRtl ? -40 : 0,
                                formatter: function(val) {
                                    return new Intl.NumberFormat('ar-IQ', {
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
                                    return new Intl.NumberFormat('ar-IQ').format(val) + ' دينار';
                                }
                            }
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.7,
                                opacityTo: 0.3
                            }
                        }
                    };
                }
            }));
        });
    </script>
</x-layout.admin>
