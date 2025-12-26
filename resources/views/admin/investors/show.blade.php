<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المستثمر: {{ $investor->name }}</h5>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.investors.edit', $investor) }}" class="btn btn-warning">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    تعديل
                </a>
                <form action="{{ route('admin.investors.reset-accounts', $investor) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من تصفير حسابات المستثمر؟ سيتم حذف جميع المعاملات وتصفير جميع الأرقام. هذا الإجراء لا يمكن التراجع عنه.');">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        تصفير الحسابات
                    </button>
                </form>
                <form action="{{ route('admin.investors.destroy', $investor) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف المستثمر؟ سيتم حذف المستثمر وخزنته وجميع معاملاتها. هذا الإجراء لا يمكن التراجع عنه.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        حذف
                    </button>
                </form>
                <a href="{{ route('admin.investors.index') }}" class="btn btn-outline-secondary">العودة</a>
            </div>
        </div>

        <!-- خزنة المستثمر -->
        @if($investorTreasury)
            <div class="panel mb-5">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="text-lg font-semibold dark:text-white-light">خزنة المستثمر: {{ $investorTreasury->name }}</h6>
                    <a href="{{ route('admin.treasuries.show', $investorTreasury) }}" class="btn btn-primary btn-sm">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        عرض تفاصيل الخزنة
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">الرصيد الحالي</div>
                        <div class="text-2xl font-bold {{ $investorTreasury->current_balance >= 0 ? 'text-success dark:text-success' : 'text-danger dark:text-danger' }}">{{ formatCurrency($investorTreasury->current_balance) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">إجمالي الإيداعات</div>
                        <div class="text-2xl font-bold text-info dark:text-info">{{ formatCurrency($treasuryDeposits) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">إجمالي السحوبات</div>
                        <div class="text-2xl font-bold text-warning dark:text-warning">{{ formatCurrency($treasuryWithdrawals) }}</div>
                    </div>
                </div>
            </div>
        @endif

        <!-- إضافة إيداع/سحب في خزنة المستثمر -->
        @if($investorTreasury)
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4 dark:text-white-light">إضافة إيداع/سحب في خزنة المستثمر</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- إيداع -->
                    <div class="border-r dark:border-gray-700 pr-4">
                        <h6 class="text-md font-semibold mb-3 dark:text-white-light">إيداع</h6>
                        <form method="POST" action="{{ route('admin.treasuries.deposit', $investorTreasury) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="deposit_amount" class="block text-sm font-medium mb-2 dark:text-white-light">المبلغ <span class="text-red-500">*</span></label>
                                <input type="number" id="deposit_amount" name="amount" step="0.01" min="0.01" class="form-input" required>
                            </div>
                            <div>
                                <label for="deposit_description" class="block text-sm font-medium mb-2 dark:text-white-light">الوصف</label>
                                <input type="text" id="deposit_description" name="description" class="form-input" placeholder="وصف الإيداع">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-success w-full">إيداع</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- سحب -->
                    <div class="pl-4">
                        <h6 class="text-md font-semibold mb-3 dark:text-white-light">سحب</h6>
                        <form method="POST" action="{{ route('admin.treasuries.withdraw', $investorTreasury) }}" class="space-y-3">
                            @csrf
                            <div>
                                <label for="withdraw_amount" class="block text-sm font-medium mb-2 dark:text-white-light">المبلغ <span class="text-red-500">*</span></label>
                                <input type="number" id="withdraw_amount" name="amount" step="0.01" min="0.01" max="{{ $investorTreasury->current_balance }}" class="form-input" required>
                            </div>
                            <div>
                                <label for="withdraw_description" class="block text-sm font-medium mb-2 dark:text-white-light">الوصف</label>
                                <input type="text" id="withdraw_description" name="description" class="form-input" placeholder="وصف السحب">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-warning w-full">سحب</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- الفلاتر -->
        <div class="panel mb-6">
            <form method="GET" action="{{ route('admin.investors.show', $investor) }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- فلتر من تاريخ -->
                    <div>
                        <label class="form-label dark:text-white-light">من تاريخ التقييد</label>
                        <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
                    </div>

                    <!-- فلتر إلى تاريخ -->
                    <div>
                        <label class="form-label dark:text-white-light">إلى تاريخ التقييد</label>
                        <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">تطبيق الفلتر</button>
                    <a href="{{ route('admin.investors.show', $investor) }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- الأرباح المعلقة -->
        @if($investorTreasury)
            <div class="panel mb-5">
                <h6 class="text-lg font-semibold mb-4 dark:text-white-light">الأرباح والمخزن</h6>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <!-- كارد الأرباح المعلقة -->
                    <div class="border border-warning rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h6 class="text-lg font-semibold dark:text-white-light">الأرباح المعلقة</h6>
                            <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-warning dark:text-warning">{{ formatCurrency($pendingProfits ?? 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">أرباح معلقة لم يتم رفعها للرصيد</div>
                    </div>

                    <!-- كارد الأرباح المدفوعة -->
                    <div class="border border-success rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h6 class="text-lg font-semibold dark:text-white-light">الأرباح المدفوعة</h6>
                            <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-success dark:text-success">{{ formatCurrency($paidProfits ?? 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">أرباح تم رفعها للرصيد الحالي</div>
                    </div>

                    <!-- كارد قيمة المخزن الحالية -->
                    <div class="border border-info rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h6 class="text-lg font-semibold dark:text-white-light">قيمة المخزن الحالية</h6>
                            <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-info dark:text-info">{{ formatCurrency($currentWarehouseValue ?? 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">قيمة حصة المستثمر في المخزن (متغيرة)</div>
                    </div>
                </div>

                <!-- Form لرفع الأرباح -->
                @if(($pendingProfits ?? 0) > 0)
                    <form action="{{ route('admin.investors.deposit-profits', $investor) }}" method="POST" class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-white-light">المبلغ المراد رفعه</label>
                                <input type="number" 
                                       name="amount" 
                                       step="250" 
                                       min="250" 
                                       max="{{ $pendingProfits }}" 
                                       value="{{ old('amount', $pendingProfits) }}" 
                                       class="form-input" 
                                       required>
                                @error('amount')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2 dark:text-white-light">ملاحظات (اختياري)</label>
                                <input type="text" 
                                       name="notes" 
                                       value="{{ old('notes') }}" 
                                       class="form-input" 
                                       placeholder="ملاحظات حول رفع الأرباح">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" 
                                        class="btn btn-primary w-full"
                                        onclick="return confirm('هل أنت متأكد من رفع هذا المبلغ للرصيد الحالي؟')">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    رفع للرصيد الحالي
                                </button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                        لا توجد أرباح معلقة
                    </div>
                @endif
            </div>
        @endif

        <!-- المصروفات والربح الصافي -->
        @if($investorTreasury)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                <!-- كارد المصروفات -->
                <div class="panel">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="text-lg font-semibold dark:text-white-light">المصروفات</h6>
                        <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-warning dark:text-warning">{{ formatCurrency($totalInvestorExpenses ?? 0) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">مجموع المصروفات المخصومة من الخزنة</div>
                </div>

                <!-- كارد الربح الصافي -->
                <div class="panel">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="text-lg font-semibold dark:text-white-light">الربح الصافي</h6>
                        <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-success dark:text-success">
                        {{ formatCurrency($netProfit ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">الأرباح المدفوعة فقط (المصروفات تؤثر على الرصيد الحالي)</div>
                </div>
            </div>
        @endif

        <!-- الاستثمارات -->
        <div class="panel mb-5">
            <div class="flex items-center justify-between mb-4">
                <h6 class="text-lg font-semibold dark:text-white-light">الاستثمارات ({{ $totalInvestments }})</h6>
                <a href="{{ route('admin.projects.create') }}" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة مشروع جديد
                </a>
            </div>
            @if($allInvestments->count() > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="dark:text-white-light">النوع</th>
                                <th class="dark:text-white-light">المشروع</th>
                                <th class="dark:text-white-light">الهدف</th>
                                <th class="dark:text-white-light">نسبة الربح</th>
                                <th class="dark:text-white-light">قيمة الحصة في المخزن</th>
                                <th class="dark:text-white-light">المبلغ المخصوم من البضاعة</th>
                                <th class="dark:text-white-light">الحالة</th>
                                <th class="dark:text-white-light">تاريخ البدء</th>
                                <th class="dark:text-white-light">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($allInvestments as $item)
                                @php
                                    $investment = $item['investment'];
                                    $investmentInvestor = $item['investmentInvestor'];
                                    $project = $item['project'];
                                    
                                    // تحديد الهدف
                                    $targetName = '-';
                                    if ($item['type'] === 'new') {
                                        // البنية الجديدة: من InvestmentTarget
                                        $targets = $investment->targets;
                                        $targetNames = [];
                                        foreach ($targets as $target) {
                                            if ($target->target_type === 'product') {
                                                $product = \App\Models\Product::find($target->target_id);
                                                if ($product) $targetNames[] = $product->name;
                                            } elseif ($target->target_type === 'warehouse') {
                                                $warehouse = \App\Models\Warehouse::find($target->target_id);
                                                if ($warehouse) $targetNames[] = $warehouse->name;
                                            }
                                        }
                                        $targetName = !empty($targetNames) ? implode(', ', $targetNames) : '-';
                                    } else {
                                        // البنية القديمة
                                        if ($investment->product) {
                                            $targetName = $investment->product->name;
                                        } elseif ($investment->warehouse) {
                                            $targetName = $investment->warehouse->name;
                                        } elseif ($investment->privateWarehouse) {
                                            $targetName = $investment->privateWarehouse->name;
                                        }
                                    }
                                    
                                    $profitPercentage = $item['type'] === 'new' ? ($investmentInvestor->profit_percentage ?? 0) : ($investment->profit_percentage ?? 0);
                                    
                                    // حساب قيمة الحصة في المخزن والمبلغ المخصوم
                                    if ($item['type'] === 'new') {
                                        // البنية الجديدة: حساب من cost_percentage و total_value
                                        $costPercentage = $investmentInvestor->cost_percentage ?? 0;
                                        $shareValue = ($costPercentage / 100) * ($investment->total_value ?? 0);
                                        $deductedAmount = $investmentInvestor->investment_amount ?? 0;
                                    } else {
                                        // البنية القديمة
                                        $shareValue = $investment->investment_amount ?? 0;
                                        $deductedAmount = $investment->investment_amount ?? 0;
                                    }
                                @endphp
                                <tr>
                                    <td class="dark:text-white-light">
                                        @if($investment->investment_type === 'product')
                                            <span class="badge badge-info">منتج</span>
                                        @elseif($investment->investment_type === 'warehouse')
                                            <span class="badge badge-primary">مخزن</span>
                                        @else
                                            <span class="badge badge-success">مخزن خاص</span>
                                        @endif
                                    </td>
                                    <td class="dark:text-white-light">
                                        @if($project)
                                            <a href="{{ route('admin.projects.show', $project) }}" class="text-primary hover:underline">
                                                {{ $project->name }}
                                            </a>
                                        @else
                                            <span class="text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="dark:text-white-light">{{ $targetName }}</td>
                                    <td class="dark:text-white-light">{{ number_format($profitPercentage, 2) }}%</td>
                                    <td class="dark:text-white-light">{{ number_format($shareValue, 2) }} IQD</td>
                                    <td class="dark:text-white-light">{{ number_format($deductedAmount, 2) }} IQD</td>
                                    <td class="dark:text-white-light">
                                        @if($investment->status === 'active')
                                            <span class="badge badge-success">نشط</span>
                                        @else
                                            <span class="badge badge-danger">{{ $investment->status }}</span>
                                        @endif
                                    </td>
                                    <td class="dark:text-white-light">{{ $investment->start_date ? $investment->start_date->format('Y-m-d') : '-' }}</td>
                                    <td>
                                        @if($project)
                                            <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-primary btn-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                عرض المشروع
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400 mb-4">لا توجد استثمارات</p>
                    <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة مشروع جديد
                    </a>
                </div>
            @endif
        </div>

        <!-- الأرباح -->
        <div class="panel mb-5">
            <h6 class="text-lg font-semibold mb-4 dark:text-white-light">الأرباح</h6>
            @if($profits->count() > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="dark:text-white-light">التاريخ</th>
                                <th class="dark:text-white-light">الربح</th>
                                <th class="dark:text-white-light">النسبة</th>
                                <th class="dark:text-white-light">الحالة</th>
                                <th class="dark:text-white-light">الطلب</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profits as $profit)
                                <tr>
                                    <td class="dark:text-white-light">{{ $profit->profit_date->format('Y-m-d') }}</td>
                                    <td class="dark:text-white-light">{{ formatCurrency($profit->profit_amount) }}</td>
                                    <td class="dark:text-white-light">{{ number_format($profit->profit_percentage, 2) }}%</td>
                                    <td>
                                        @if($profit->status === 'paid')
                                            <span class="badge badge-success">مدفوع</span>
                                        @else
                                            <span class="badge badge-warning">معلق</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($profit->order)
                                            <a href="{{ route('admin.orders.show', $profit->order) }}" class="text-primary hover:underline">
                                                {{ $profit->order->order_number }}
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $profits->links() }}
            @else
                <p class="text-center text-gray-500 dark:text-gray-400 py-8">لا توجد أرباح</p>
            @endif
        </div>


        <!-- معاملات خزنة المستثمر -->
        @if($investorTreasury && $treasuryTransactions)
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4 dark:text-white-light">معاملات خزنة المستثمر</h6>
                
                <!-- فلتر الحركات -->
                <div class="mb-4">
                    <form method="GET" action="{{ route('admin.investors.show', $investor) }}" class="space-y-4">
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="form-label dark:text-white-light">نوع الحركة</label>
                                <select name="transaction_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">جميع الأنواع</option>
                                    <option value="deposit" {{ request('transaction_type') === 'deposit' ? 'selected' : '' }}>إيداع</option>
                                    <option value="withdrawal" {{ request('transaction_type') === 'withdrawal' ? 'selected' : '' }}>سحب</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label dark:text-white-light">نوع المرجع</label>
                                <select name="reference_type" class="form-select" onchange="this.form.submit()">
                                    <option value="">جميع الأنواع</option>
                                    <option value="manual" {{ request('reference_type') === 'manual' ? 'selected' : '' }}>يدوي</option>
                                    <option value="profit" {{ request('reference_type') === 'profit' ? 'selected' : '' }}>ربح</option>
                                    <option value="expense" {{ request('reference_type') === 'expense' ? 'selected' : '' }}>مصروف</option>
                                    <option value="order" {{ request('reference_type') === 'order' ? 'selected' : '' }}>طلب</option>
                                    <option value="cost_return" {{ request('reference_type') === 'cost_return' ? 'selected' : '' }}>إرجاع تكلفة</option>
                                    <option value="partial_return" {{ request('reference_type') === 'partial_return' ? 'selected' : '' }}>إرجاع جزئي</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <a href="{{ route('admin.investors.show', $investor) }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                @if($treasuryTransactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th class="dark:text-white-light">التاريخ</th>
                                    <th class="dark:text-white-light">النوع</th>
                                    <th class="dark:text-white-light">المبلغ</th>
                                    <th class="dark:text-white-light">الوصف</th>
                                    <th class="dark:text-white-light">المرجع</th>
                                    <th class="dark:text-white-light">تم بواسطة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($treasuryTransactions as $transaction)
                                    <tr>
                                        <td class="dark:text-white-light">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($transaction->transaction_type === 'deposit')
                                                <span class="badge badge-success">إيداع</span>
                                            @else
                                                <span class="badge badge-warning">سحب</span>
                                            @endif
                                        </td>
                                        <td class="{{ $transaction->transaction_type === 'deposit' ? 'text-success dark:text-success' : 'text-warning dark:text-warning' }}">
                                            {{ $transaction->transaction_type === 'deposit' ? '+' : '-' }}{{ formatCurrency($transaction->amount) }}
                                        </td>
                                        <td class="dark:text-white-light">{{ $transaction->description ?? '-' }}</td>
                                        <td class="dark:text-white-light">
                                            @php
                                                $referenceTypeLabels = [
                                                    'manual' => 'يدوي',
                                                    'profit' => 'ربح',
                                                    'expense' => 'مصروف',
                                                    'order' => 'طلب',
                                                    'cost_return' => 'إرجاع تكلفة',
                                                    'expense_return' => 'إرجاع مصروف',
                                                ];
                                                $label = $referenceTypeLabels[$transaction->reference_type] ?? $transaction->reference_type;
                                            @endphp
                                            @if($transaction->reference_type)
                                                <span class="badge badge-outline-primary">{{ $label }}</span>
                                                @if($transaction->reference_id)
                                                    <span class="text-xs text-gray-500">#{{ $transaction->reference_id }}</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="dark:text-white-light">{{ $transaction->creator->name ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{ $treasuryTransactions->links() }}
                @else
                    <p class="text-center text-gray-500 dark:text-gray-400 py-8">لا توجد معاملات في الخزنة</p>
                @endif
            </div>
        @endif
    </div>
</x-layout.admin>

