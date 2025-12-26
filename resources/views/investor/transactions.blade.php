<x-layout.investor>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold dark:text-white-light">الحركات المالية</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">جميع حركاتك المالية</p>
            </div>
            <div>
                <a href="{{ route('investor.dashboard') }}" class="btn btn-outline-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للوحة التحكم
                </a>
            </div>
        </div>

        <!-- فلاتر -->
        <div class="panel mb-6">
            <form method="GET" action="{{ route('investor.transactions') }}" class="space-y-4">
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
                    <a href="{{ route('investor.transactions') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        @if($transactions && $transactions->count() > 0)
            <div class="grid grid-cols-1 gap-4 mb-6">
                @foreach($transactions as $transaction)
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
                                @if($transaction->creator)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        من: {{ $transaction->creator->name }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-lg font-bold {{ $transaction->transaction_type === 'deposit' ? 'text-success' : 'text-danger' }}">
                                {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }} دينار
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $transactions->appends(request()->query())->links() }}
            </div>
        @else
            <div class="panel text-center py-12">
                <div class="text-gray-500 dark:text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-lg font-medium">لا توجد حركات</p>
                    <p class="text-sm">لم يتم تسجيل أي حركات مالية بعد</p>
                </div>
            </div>
        @endif
    </div>
</x-layout.investor>

