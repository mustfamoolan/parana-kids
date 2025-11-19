<x-layout.admin>
    <div class="container mx-auto px-4 py-6">
        <!-- العنوان -->
        <h1 class="text-2xl font-bold mb-6 text-center">مرحباً {{ auth()->user()->name }}</h1>

        <!-- الأزرار الرئيسية -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- 1. الطلبات -->
            <a href="{{ route('admin.orders.management') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">الطلبات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @php
                        $pendingOrdersQuery = \App\Models\Order::where('status', 'pending');
                        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
                        if (auth()->user()->isSupplier()) {
                            $accessibleWarehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
                            $pendingOrdersQuery->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                            });
                        }
                        $pendingOrdersCount = $pendingOrdersQuery->count();
                    @endphp
                    @if($pendingOrdersCount > 0)
                        <span class="badge bg-warning">{{ $pendingOrdersCount }}</span> قيد الانتظار
                    @else
                        إدارة الطلبات
                    @endif
                </p>
            </a>

            <!-- 1.1. الطلبات غير المقيدة -->
            <a href="{{ route('admin.orders.pending') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">الطلبات غير المقيدة</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض الطلبات غير المقيدة</p>
            </a>

            <!-- 1.2. الطلبات المقيدة -->
            <a href="{{ route('admin.orders.confirmed') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">الطلبات المقيدة</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض الطلبات المقيدة</p>
            </a>

            @if(auth()->user()->isAdmin() || auth()->user()->isSupplier())
            <!-- 2. الإرجاع الجزئي -->
            <a href="{{ route('admin.orders.partial-returns.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">الإرجاع الجزئي</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @php
                        $partialReturnsQuery = \App\Models\Order::where('status', 'confirmed')
                            ->whereHas('items', function($q) {
                                $q->where('quantity', '>', 0);
                            });
                        // للمجهز: عرض الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
                        if (auth()->user()->isSupplier()) {
                            $accessibleWarehouseIds = auth()->user()->warehouses->pluck('id')->toArray();
                            $partialReturnsQuery->whereHas('items.product', function($q) use ($accessibleWarehouseIds) {
                                $q->whereIn('warehouse_id', $accessibleWarehouseIds);
                            });
                        }
                        $partialReturnsCount = $partialReturnsQuery->count();
                    @endphp
                    @if($partialReturnsCount > 0)
                        <span class="badge bg-warning">{{ $partialReturnsCount }}</span> طلب قابل للإرجاع
                    @else
                        إرجاع منتجات من الطلبات
                    @endif
                </p>
            </a>
            @endif

            @if(auth()->user()->isAdmin())
            <!-- 3. التقارير -->
            <a href="{{ route('admin.reports') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">التقارير</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض التقارير والإحصائيات</p>
            </a>

            <!-- 3.2. كشف مبيعات -->
            <a href="{{ route('admin.sales-report') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">كشف مبيعات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض كشف المبيعات الشامل</p>
            </a>

            <!-- 3.1. المصروفات -->
            <a href="{{ route('admin.expenses.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-danger/10 to-danger/5 border-2 border-danger/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-danger/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-danger mb-2">المصروفات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @php
                        $monthExpenses = \App\Models\Expense::whereMonth('expense_date', now()->month)
                            ->whereYear('expense_date', now()->year)
                            ->sum('amount');
                    @endphp
                    @if($monthExpenses > 0)
                        <span class="badge bg-danger">{{ number_format($monthExpenses, 0) }} د.ع</span> هذا الشهر
                    @else
                        إدارة المصروفات
                    @endif
                </p>
            </a>
            @endif

            <!-- 4. المخازن -->
            <a href="{{ route('admin.warehouses.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">المخازن</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إدارة المخازن والمنتجات</p>
            </a>

            <!-- 5. كشف حركة المواد -->
            <a href="{{ route('admin.product-movements.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">كشف حركة المواد</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض حركة المواد والمنتجات</p>
            </a>

            <!-- 6. نقل المواد -->
            <a href="{{ route('admin.transfers.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-purple/10 to-purple/5 border-2 border-purple/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-purple mb-2">نقل المواد</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">نقل المواد بين المخازن</p>
            </a>

            <!-- 7. المراسلة -->
            <a href="{{ route('chat.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">المراسلة</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">المراسلة مع الفريق</p>
            </a>

            <!-- 7.1. إنشاء رابط -->
            <a href="{{ route('admin.product-links.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-danger/10 to-danger/5 border-2 border-danger/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-danger/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" fill="currentColor" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-danger mb-2">إنشاء رابط</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إنشاء رابط للمنتجات</p>
            </a>

            <!-- 8. كشف حركة الطلبات -->
            <a href="{{ route('admin.order-movements.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">كشف حركة الطلبات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض حركة الطلبات</p>
            </a>

            <!-- 9. إرجاع طلبات -->
            <a href="{{ route('admin.bulk-returns.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">إرجاع طلبات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إرجاع طلبات بالجملة</p>
            </a>

            @if(auth()->user()->isAdmin())
            <!-- 10. إدارة المستخدمين -->
            <a href="{{ route('admin.users.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">إدارة المستخدمين</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إدارة المستخدمين والمندوبين والمجهزين</p>
            </a>

            <!-- 11. الإعدادات -->
            <a href="{{ route('admin.settings.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-secondary/10 to-secondary/5 border-2 border-secondary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-secondary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path opacity="0.5" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" fill="currentColor" />
                        <path d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z" fill="currentColor" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-secondary mb-2">الإعدادات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    @php
                        $deliveryFee = \App\Models\Setting::getDeliveryFee();
                    @endphp
                    سعر التوصيل: <span class="badge bg-secondary">{{ number_format($deliveryFee, 0, '.', ',') }} د.ع</span>
                </p>
            </a>
            @endif

            @if(auth()->user()->isAdmin())
            <!-- المخازن الخاصة -->
            <a href="{{ route('admin.private-warehouses.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-secondary/10 to-secondary/5 border-2 border-secondary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-secondary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path opacity="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" fill="currentColor" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-secondary mb-2">المخازن الخاصة</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إدارة المخازن الخاصة والموردين</p>
            </a>
            @endif
        </div>
    </div>
</x-layout.admin>
