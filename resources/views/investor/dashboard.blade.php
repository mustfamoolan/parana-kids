<x-layout.investor>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold dark:text-white-light">مرحباً {{ $investor->name }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">لوحة تحكم المستثمر</p>
        </div>

        <!-- الفلاتر -->
        <div class="panel mb-6">
            <form method="GET" action="{{ route('investor.dashboard') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- فلتر من تاريخ -->
                    <div>
                        <label class="form-label dark:text-white-light">من تاريخ</label>
                        <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
                    </div>

                    <!-- فلتر إلى تاريخ -->
                    <div>
                        <label class="form-label dark:text-white-light">إلى تاريخ</label>
                        <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
                    </div>

                    <!-- فلتر نوع الحركة -->
                    <div>
                        <label class="form-label dark:text-white-light">نوع الحركة</label>
                        <select name="transaction_type" class="form-select">
                            <option value="">جميع الأنواع</option>
                            <option value="deposit" {{ request('transaction_type') === 'deposit' ? 'selected' : '' }}>إيداع</option>
                            <option value="withdrawal" {{ request('transaction_type') === 'withdrawal' ? 'selected' : '' }}>سحب</option>
                        </select>
                    </div>

                    <!-- فلتر نوع المرجع -->
                    <div>
                        <label class="form-label dark:text-white-light">نوع المرجع</label>
                        <select name="reference_type" class="form-select">
                            <option value="">جميع الأنواع</option>
                            <option value="manual" {{ request('reference_type') === 'manual' ? 'selected' : '' }}>يدوي</option>
                            <option value="profit" {{ request('reference_type') === 'profit' ? 'selected' : '' }}>ربح</option>
                            <option value="expense" {{ request('reference_type') === 'expense' ? 'selected' : '' }}>مصروف</option>
                            <option value="order" {{ request('reference_type') === 'order' ? 'selected' : '' }}>طلب</option>
                            <option value="cost_return" {{ request('reference_type') === 'cost_return' ? 'selected' : '' }}>إرجاع تكلفة</option>
                            <option value="partial_return" {{ request('reference_type') === 'partial_return' ? 'selected' : '' }}>إرجاع جزئي</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">تطبيق الفلتر</button>
                    <a href="{{ route('investor.dashboard') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- كاردات الإحصائيات -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <!-- الرصيد الحالي -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">الرصيد الحالي</h6>
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold {{ ($treasury->current_balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($treasury->current_balance ?? 0, 2) }} دينار</div>
            </div>

            <!-- مبلغ الاستثمار -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">مبلغ الاستثمار</h6>
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold text-info">{{ number_format($totalInvestmentAmount ?? 0, 2) }} دينار</div>
            </div>

            <!-- قيمة المخزن الحالية -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">قيمة المخزن الحالية</h6>
                    <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold text-info">{{ number_format($currentWarehouseValue ?? 0, 2) }} دينار</div>
            </div>

            <!-- إجمالي الأرباح -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">إجمالي الأرباح</h6>
                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold text-primary">{{ number_format($totalProfit ?? 0, 2) }} دينار</div>
            </div>

            <!-- الأرباح المعلقة -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">الأرباح المعلقة</h6>
                    <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold text-warning">{{ number_format($pendingProfits ?? 0, 2) }} دينار</div>
            </div>

            <!-- الأرباح المدفوعة -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">الأرباح المدفوعة</h6>
                    <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold text-success">{{ number_format($paidProfits ?? 0, 2) }} دينار</div>
            </div>

            <!-- إجمالي المصروفات -->
            <div class="panel">
                <div class="flex items-center justify-between mb-2">
                    <h6 class="text-sm font-medium text-gray-600 dark:text-gray-400">إجمالي المصروفات</h6>
                    <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="text-2xl font-bold text-danger">{{ number_format($totalExpenses ?? 0, 2) }} دينار</div>
            </div>
        </div>

        <!-- الاستثمارات -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h6 class="text-lg font-semibold dark:text-white-light">آخر الاستثمارات</h6>
                <a href="{{ route('investor.investments') }}" class="btn btn-outline-primary btn-sm">عرض الكل</a>
            </div>
            @if($allInvestments->count() > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($allInvestments->take(10) as $item)
                        <div class="panel">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h6 class="font-semibold text-lg dark:text-white-light mb-2">
                                        @if($item['type'] === 'new' && $item['project'])
                                            {{ $item['project']->project_name }}
                                        @elseif($item['type'] === 'old')
                                            @if($item['investment']->product)
                                                {{ $item['investment']->product->name }}
                                            @elseif($item['investment']->warehouse)
                                                {{ $item['investment']->warehouse->name }}
                                            @else
                                                -
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </h6>
                                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <div>
                                            <span class="font-medium dark:text-white-light">النوع:</span>
                                            @if($item['type'] === 'new')
                                                {{ $item['investment']->investment_type === 'product' ? 'منتج' : 'مخزن' }}
                                            @else
                                                {{ $item['investment']->investment_type === 'product' ? 'منتج' : ($item['investment']->investment_type === 'warehouse' ? 'مخزن' : 'مخزن خاص') }}
                                            @endif
                                        </div>
                                        <div>
                                            <span class="font-medium dark:text-white-light">المبلغ:</span>
                                            <span class="text-primary dark:text-primary">
                                                @if($item['type'] === 'new' && $item['investmentInvestor'])
                                                    {{ number_format($item['investmentInvestor']->investment_amount, 2) }} دينار
                                                @else
                                                    {{ number_format($item['investment']->investment_amount ?? 0, 2) }} دينار
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    @php
                                        $status = $item['type'] === 'new' ? $item['investment']->status : $item['investment']->status;
                                    @endphp
                                    @if($status === 'active')
                                        <span class="badge badge-success">نشط</span>
                                    @else
                                        <span class="badge badge-danger">غير نشط</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="panel text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>لا توجد استثمارات</p>
                </div>
            @endif
        </div>

        <!-- الحركات -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h6 class="text-lg font-semibold dark:text-white-light">آخر الحركات</h6>
                <a href="{{ route('investor.transactions') }}" class="btn btn-outline-primary btn-sm">عرض الكل</a>
            </div>
            @if($transactions && $transactions->count() > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($transactions->take(10) as $transaction)
                        <div class="panel">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        @if($transaction->transaction_type === 'deposit')
                                            <span class="badge badge-success dark:bg-success dark:text-white">إيداع</span>
                                        @else
                                            <span class="badge badge-danger dark:bg-danger dark:text-white">سحب</span>
                                        @endif
                                        @php
                                            $referenceTypeLabels = [
                                                'manual' => 'يدوي',
                                                'profit' => 'ربح',
                                                'expense' => 'مصروف',
                                                'order' => 'طلب',
                                                'cost_return' => 'إرجاع تكلفة',
                                                'expense_return' => 'إرجاع مصروف',
                                                'partial_return' => 'إرجاع جزئي',
                                            ];
                                            $label = $referenceTypeLabels[$transaction->reference_type] ?? $transaction->reference_type;
                                        @endphp
                                        <span class="badge badge-outline-primary dark:border-primary dark:text-primary">{{ $label }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $transaction->created_at->format('Y-m-d H:i') }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                        {{ $transaction->description ?? '-' }}
                                    </div>
                                </div>
                                <div class="text-lg font-bold {{ $transaction->transaction_type === 'deposit' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} دينار
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="panel text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>لا توجد حركات</p>
                </div>
            @endif
        </div>
    </div>
</x-layout.investor>

