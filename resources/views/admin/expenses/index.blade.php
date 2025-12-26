<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة المصروفات</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة مصروف جديد
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- كاردات الإحصائيات -->
        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <!-- إجمالي المصروفات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalExpenses, 0) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي المصروفات</div>
                    </div>
                    <div class="rounded-full bg-primary/10 p-3">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- إجمالي الإيجار -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalRent, 0) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي الإيجار</div>
                    </div>
                    <div class="rounded-full bg-warning/10 p-3">
                        <svg class="h-8 w-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- إجمالي الرواتب -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalSalary, 0) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي الرواتب</div>
                    </div>
                    <div class="rounded-full bg-info/10 p-3">
                        <svg class="h-8 w-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- إجمالي الصرفيات الأخرى -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalOther, 0) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">صرفيات أخرى</div>
                    </div>
                    <div class="rounded-full bg-success/10 p-3">
                        <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- إجمالي الترويج -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalPromotion, 0) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي الترويج</div>
                    </div>
                    <div class="rounded-full bg-danger/10 p-3">
                        <svg class="h-8 w-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- عدد المصروفات -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $expensesCount }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">عدد المصروفات</div>
                    </div>
                    <div class="rounded-full bg-secondary/10 p-3">
                        <svg class="h-8 w-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- الفلاتر -->
        <div class="panel mb-5">
            <form method="GET" action="{{ route('admin.expenses.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- فلتر المخزن -->
                    <div>
                        <label for="warehouse_id" class="block text-sm font-medium mb-2 dark:text-white-light">المخزن</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select">
                            <option value="">كل المخازن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- فلتر نوع المصروف -->
                    <div>
                        <label for="expense_type" class="block text-sm font-medium mb-2 dark:text-white-light">نوع المصروف</label>
                        <select name="expense_type" id="expense_type" class="form-select">
                            <option value="">كل الأنواع</option>
                            <option value="rent" {{ request('expense_type') == 'rent' ? 'selected' : '' }}>إيجار</option>
                            <option value="salary" {{ request('expense_type') == 'salary' ? 'selected' : '' }}>رواتب</option>
                            <option value="other" {{ request('expense_type') == 'other' ? 'selected' : '' }}>صرفيات أخرى</option>
                            <option value="promotion" {{ request('expense_type') == 'promotion' ? 'selected' : '' }}>ترويج</option>
                        </select>
                    </div>

                    <!-- فلتر من تاريخ -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium mb-2 dark:text-white-light">من تاريخ</label>
                        <input type="date" name="date_from" id="date_from" class="form-input" value="{{ request('date_from') }}">
                    </div>

                    <!-- فلتر إلى تاريخ -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium mb-2 dark:text-white-light">إلى تاريخ</label>
                        <input type="date" name="date_to" id="date_to" class="form-input" value="{{ request('date_to') }}">
                    </div>

                    <!-- فلتر اسم الشخص -->
                    <div>
                        <label for="person_name" class="block text-sm font-medium mb-2 dark:text-white-light">اسم الشخص</label>
                        <input type="text" name="person_name" id="person_name" class="form-input" placeholder="ابحث بالاسم..." value="{{ request('person_name') }}">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        بحث
                    </button>
                    @if(request()->hasAny(['warehouse_id', 'expense_type', 'date_from', 'date_to', 'person_name']))
                        <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            إعادة تعيين
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- كاردات المصروفات -->
        @if($expenses->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($expenses as $index => $expense)
                    <div id="expense-{{ $expense->id }}" class="panel border-2
                        @if($expense->expense_type == 'rent') border-warning dark:border-warning
                        @elseif($expense->expense_type == 'salary') border-info dark:border-info
                        @elseif($expense->expense_type == 'promotion') border-danger dark:border-danger
                        @else border-success dark:border-success
                        @endif
                    ">
                        <!-- هيدر الكارد -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="text-lg font-bold text-primary dark:text-primary-light">
                                        #{{ $expense->id }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    #{{ $expenses->firstItem() + $index }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge
                                    @if($expense->expense_type == 'rent') badge-warning
                                    @elseif($expense->expense_type == 'salary') badge-info
                                    @elseif($expense->expense_type == 'promotion') badge-danger
                                    @else badge-success
                                    @endif">
                                    {{ $expense->expense_type_name }}
                                </span>
                            </div>
                        </div>

                        <!-- المبلغ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المبلغ</span>
                                <p class="text-xl font-bold text-primary dark:text-primary-light">{{ number_format($expense->amount, 0) }} د.ع</p>
                            </div>
                        </div>

                        <!-- التاريخ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">التاريخ</span>
                                <p class="font-medium">{{ $expense->expense_date->format('Y-m-d') }}</p>
                                <p class="text-sm text-gray-500">{{ $expense->expense_date->format('H:i') }}</p>
                            </div>
                        </div>

                        <!-- المخزن -->
                        @if($expense->warehouse)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المخزن</span>
                                    <p class="font-medium dark:text-white-light">{{ $expense->warehouse->name }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- الراتب (للرواتب فقط) -->
                        @if($expense->expense_type == 'salary' && $expense->salary_amount)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">الراتب</span>
                                    <p class="font-medium">{{ number_format($expense->salary_amount, 0) }} د.ع</p>
                                </div>
                            </div>
                        @endif

                        <!-- اسم الشخص (للرواتب فقط) -->
                        @if($expense->expense_type == 'salary')
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الشخص</span>
                                    <p class="font-medium">{{ $expense->person_display_name }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- المنتج (للترويج فقط) -->
                        @if($expense->expense_type == 'promotion' && $expense->product)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المنتج</span>
                                    <p class="font-medium">{{ $expense->product->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $expense->product->code }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- المنشئ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المنشئ</span>
                                <p class="font-medium">{{ $expense->creator->name }}</p>
                            </div>
                        </div>

                        <!-- الملاحظات -->
                        @if($expense->notes)
                            <div class="mb-4">
                                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 rounded-lg">
                                    <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 block mb-1">
                                        <svg class="w-4 h-4 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"/>
                                        </svg>
                                        ملاحظة
                                    </span>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $expense->notes }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- أزرار الإجراءات -->
                        <div class="flex gap-2 flex-wrap">
                            <a href="{{ route('admin.expenses.edit', $expense) }}" class="btn btn-sm btn-warning flex-1" title="تعديل">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                تعديل
                            </a>
                            <form method="POST" action="{{ route('admin.expenses.destroy', $expense) }}" class="flex-1" onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger w-full" title="حذف">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    حذف
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $expenses->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">لا توجد مصروفات</h6>
                <p class="text-gray-500 dark:text-gray-400 mb-4">لم يتم إضافة أي مصروفات بعد</p>
                <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة أول مصروف
                </a>
            </div>
        @endif
    </div>
</x-layout.admin>

