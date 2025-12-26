<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الخزنة: {{ $treasury->name }}</h5>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.treasuries.edit', $treasury) }}" class="btn btn-warning">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    تعديل
                </a>
                <form action="{{ route('admin.treasuries.reset-balance', $treasury) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من تصفير رصيد الخزنة؟ سيتم تصفير الرصيد الحالي. هذا الإجراء لا يمكن التراجع عنه.');">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        تصفير الرصيد
                    </button>
                </form>
                @if(!$treasury->investor_id)
                <form action="{{ route('admin.treasuries.destroy', $treasury) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف الخزنة؟ سيتم حذف الخزنة وجميع معاملاتها. هذا الإجراء لا يمكن التراجع عنه.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        حذف
                    </button>
                </form>
                @endif
                <a href="{{ $backUrl ?? route('admin.treasuries.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة
                </a>
            </div>
        </div>

        <!-- الإحصائيات -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
            <div class="panel">
                <div class="text-sm text-gray-500 mb-1">رأس المال</div>
                <div class="text-2xl font-bold text-primary">{{ formatCurrency($treasury->initial_capital) }}</div>
            </div>
            <div class="panel">
                <div class="text-sm text-gray-500 mb-1">الرصيد الحالي</div>
                <div class="text-2xl font-bold text-success">{{ formatCurrency($treasury->current_balance) }}</div>
            </div>
            <div class="panel">
                <div class="text-sm text-gray-500 mb-1">إجمالي الإيداعات</div>
                <div class="text-2xl font-bold text-info">{{ formatCurrency($totalDeposits) }}</div>
            </div>
            <div class="panel">
                <div class="text-sm text-gray-500 mb-1">إجمالي السحوبات</div>
                <div class="text-2xl font-bold text-warning">{{ formatCurrency($totalWithdrawals) }}</div>
            </div>
        </div>

        <!-- إيداع في الخزنة -->
        <div class="panel mb-5">
            <h6 class="text-lg font-semibold mb-4 dark:text-white-light">إيداع في الخزنة</h6>
            <form method="POST" action="{{ route('admin.treasuries.deposit', $treasury) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label for="deposit_amount" class="block text-sm font-medium mb-2 dark:text-white-light">المبلغ <span class="text-red-500">*</span></label>
                    <input type="number" id="deposit_amount" name="amount" step="0.01" min="0.01"
                           class="form-input @error('amount') border-red-500 @enderror" required>
                    @error('amount')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="deposit_description" class="block text-sm font-medium mb-2 dark:text-white-light">الوصف</label>
                    <input type="text" id="deposit_description" name="description"
                           class="form-input @error('description') border-red-500 @enderror">
                    @error('description')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-success w-full">إيداع</button>
                </div>
            </form>
        </div>

        <!-- سحب من الخزنة -->
        <div class="panel mb-5">
            <h6 class="text-lg font-semibold mb-4 dark:text-white-light">سحب من الخزنة</h6>
            <form method="POST" action="{{ route('admin.treasuries.withdraw', $treasury) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                <div>
                    <label for="withdraw_amount" class="block text-sm font-medium mb-2 dark:text-white-light">المبلغ <span class="text-red-500">*</span></label>
                    <input type="number" id="withdraw_amount" name="amount" step="0.01" min="0.01" max="{{ $treasury->current_balance }}"
                           class="form-input @error('amount') border-red-500 @enderror" required>
                    @error('amount')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <label for="withdraw_description" class="block text-sm font-medium mb-2 dark:text-white-light">الوصف</label>
                    <input type="text" id="withdraw_description" name="description"
                           class="form-input @error('description') border-red-500 @enderror">
                    @error('description')
                        <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-warning w-full">سحب</button>
                </div>
            </form>
        </div>

        <!-- المعاملات -->
        <div class="panel">
            <h6 class="text-lg font-semibold mb-4">المعاملات</h6>
            @if($transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th>النوع</th>
                                <th>المبلغ</th>
                                <th>الوصف</th>
                                <th>المرجع</th>
                                <th>تم بواسطة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($transaction->transaction_type === 'deposit')
                                            <span class="badge badge-success">إيداع</span>
                                        @else
                                            <span class="badge badge-warning">سحب</span>
                                        @endif
                                    </td>
                                    <td class="{{ $transaction->transaction_type === 'deposit' ? 'text-success' : 'text-warning' }}">
                                        {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}{{ formatCurrency($transaction->amount) }}
                                    </td>
                                    <td>{{ $transaction->description ?? '-' }}</td>
                                    <td>
                                        @if($transaction->reference_type && $transaction->reference_id)
                                            {{ $transaction->reference_type }} #{{ $transaction->reference_id }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $transaction->creator->name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-5">
                    {{ $transactions->links() }}
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500">لا توجد معاملات</p>
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>

