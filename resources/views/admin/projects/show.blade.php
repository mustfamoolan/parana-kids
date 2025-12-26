<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المشروع: {{ $project->name }}</h5>
            <div class="flex gap-2">
                <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-warning">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    تعديل المشروع
                </a>
                <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" id="deleteProjectForm" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="confirmDelete()" class="btn btn-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        حذف المشروع
                    </button>
                </form>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-outline-secondary">العودة</a>
            </div>
        </div>

        <!-- معلومات المشروع -->
        <div class="panel mb-5">
            <h6 class="text-lg font-semibold mb-4 dark:text-white-light">معلومات المشروع</h6>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">اسم المشروع</div>
                    <div class="font-semibold text-lg dark:text-white-light">{{ $project->name }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">نوع المشروع</div>
                    <div>
                        @if($project->project_type === 'investors')
                            <span class="badge badge-info">مستثمرين</span>
                        @else
                            <span class="badge badge-warning">شريك</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">الحالة</div>
                    <div>
                        @if($project->status === 'active')
                            <span class="badge badge-success">نشط</span>
                        @elseif($project->status === 'completed')
                            <span class="badge badge-info">مكتمل</span>
                        @else
                            <span class="badge badge-danger">{{ $project->status }}</span>
                        @endif
                    </div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">تاريخ الإنشاء</div>
                    <div class="dark:text-white-light">{{ $project->created_at->format('Y-m-d H:i') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">تم الإنشاء بواسطة</div>
                    <div class="dark:text-white-light">{{ $project->creator->name ?? '-' }}</div>
                </div>
            </div>
        </div>

        <!-- الفلاتر -->
        <div class="panel mb-6">
            <form method="GET" action="{{ route('admin.projects.show', $project) }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- فلتر من تاريخ -->
                    <div>
                        <label class="form-label">من تاريخ التقييد</label>
                        <input type="date" name="date_from" class="form-input" value="{{ request('date_from') }}">
                    </div>

                    <!-- فلتر إلى تاريخ -->
                    <div>
                        <label class="form-label">إلى تاريخ التقييد</label>
                        <input type="date" name="date_to" class="form-input" value="{{ request('date_to') }}">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">تطبيق الفلتر</button>
                    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- المستثمرين -->
        <div class="panel mb-5">
            <h6 class="text-lg font-semibold mb-4 dark:text-white-light">المستثمرين ({{ count($investorsData) }})</h6>
            @if(count($investorsData) > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th class="dark:text-white-light">الاسم</th>
                                <th class="dark:text-white-light">رقم الهاتف</th>
                                <th class="dark:text-white-light">اسم الخزنة</th>
                                <th class="dark:text-white-light">رأس المال</th>
                                <th class="dark:text-white-light">الرصيد الحالي</th>
                                <th class="dark:text-white-light">إجمالي الأرباح</th>
                                <th class="dark:text-white-light">إجمالي الإيداعات</th>
                                <th class="dark:text-white-light">إجمالي السحوبات</th>
                                <th class="dark:text-white-light">عدد الاستثمارات</th>
                                <th class="dark:text-white-light">إجمالي مبلغ الاستثمار</th>
                                <th class="dark:text-white-light">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($investorsData as $data)
                                <tr>
                                    <td class="dark:text-white-light">{{ $data['investor']->name }}</td>
                                    <td class="dark:text-white-light">{{ $data['investor']->phone }}</td>
                                    <td class="dark:text-white-light">{{ $data['treasury']->name ?? 'غير محدد' }}</td>
                                    <td class="dark:text-white-light">{{ number_format($data['treasury']->initial_capital ?? 0, 2) }} IQD</td>
                                    <td class="dark:text-white-light">{{ number_format($data['treasury']->current_balance ?? 0, 2) }} IQD</td>
                                    <td class="dark:text-white-light">{{ number_format($data['total_profit'] ?? 0, 2) }} IQD</td>
                                    <td class="dark:text-white-light">{{ number_format($data['total_deposits'] ?? 0, 2) }} IQD</td>
                                    <td class="dark:text-white-light">{{ number_format($data['total_withdrawals'] ?? 0, 2) }} IQD</td>
                                    <td class="dark:text-white-light">{{ $data['investments_count'] }}</td>
                                    <td class="dark:text-white-light">{{ number_format($data['total_investment'], 2) }} IQD</td>
                                    <td>
                                        <a href="{{ route('admin.investors.show', $data['investor']->id) }}" class="btn btn-primary btn-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            عرض التفاصيل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">لا يوجد مستثمرين</p>
                </div>
            @endif
        </div>

        <!-- الاستثمار المشترك -->
        @if($project->investments()->count() > 0)
            @foreach($project->investments as $investment)
                @php
                    $targets = $investment->targets;
                    $targetNames = [];
                    foreach ($targets as $target) {
                        if ($target->target_type === 'product') {
                            $product = \App\Models\Product::find($target->target_id);
                            if ($product) {
                                $targetNames[] = $product->name;
                            }
                        } elseif ($target->target_type === 'warehouse') {
                            $warehouse = \App\Models\Warehouse::find($target->target_id);
                            if ($warehouse) {
                                $targetNames[] = $warehouse->name;
                            }
                        }
                    }
                    
                    // حساب إجمالي مبلغ الاستثمار من cost_percentage و total_value مباشرة لضمان الدقة 100%
                    $totalInvestmentAmount = 0;
                    foreach ($investment->investors as $invInvestor) {
                        $costPercentage = $invInvestor->cost_percentage ?? 0;
                        $totalInvestmentAmount += ($costPercentage / 100) * $investment->total_value;
                    }
                    
                    $totalInvestorPercentage = $investment->investors()->sum('profit_percentage');
                    $adminPercentage = $investment->admin_profit_percentage ?? 0;
                    $remainingPercentage = 100 - ($adminPercentage + $totalInvestorPercentage);
                @endphp
                
                <div class="panel mb-5">
                    <h6 class="text-lg font-semibold mb-4 dark:text-white-light">الاستثمار المشترك</h6>
                    
                    <!-- معلومات الاستثمار -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <!-- كارد نوع الاستثمار -->
                        <div class="border border-info rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">نوع الاستثمار</h6>
                                <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="mb-2">
                                @if($investment->investment_type === 'product')
                                    <span class="badge badge-info">منتج</span>
                                @elseif($investment->investment_type === 'warehouse')
                                    <span class="badge badge-primary">مخزن</span>
                                @else
                                    <span class="badge badge-success">مخزن خاص</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">نوع الاستثمار في المشروع</div>
                        </div>

                        <!-- كارد المنتجات/المخازن -->
                        <div class="border border-primary rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">المنتجات/المخازن</h6>
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                            <div class="font-semibold dark:text-white-light mb-2">
                                @if(count($targetNames) > 0)
                                    {{ implode(', ', $targetNames) }}
                                @else
                                    -
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">قائمة المنتجات أو المخازن</div>
                        </div>

                        <!-- كارد القيمة الإجمالية -->
                        <div class="border border-primary rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">القيمة الإجمالية</h6>
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-3xl font-bold text-primary dark:text-primary">{{ number_format($investment->total_value, 2) }} IQD</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">القيمة الإجمالية للاستثمار</div>
                        </div>

                        <!-- كارد إجمالي مبلغ الاستثمار -->
                        <div class="border border-info rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">إجمالي مبلغ الاستثمار</h6>
                                <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="text-3xl font-bold text-info dark:text-info">{{ number_format($totalInvestmentAmount, 2) }} IQD</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">إجمالي مبلغ الاستثمار من المستثمرين</div>
                        </div>
                    </div>

                    <!-- توزيع النسب -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <!-- كارد نسبة المستثمرين -->
                        <div class="border border-success rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">نسبة المستثمرين</h6>
                                <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="text-3xl font-bold text-success dark:text-success">{{ number_format($totalInvestorPercentage, 2) }}%</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">نسبة المستثمرين من الأرباح</div>
                        </div>

                        <!-- كارد نسبة المدير -->
                        <div class="border border-warning rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">نسبة المدير</h6>
                                <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div class="text-3xl font-bold text-warning dark:text-warning">{{ number_format($adminPercentage, 2) }}%</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">نسبة المدير من الأرباح</div>
                        </div>

                        <!-- كارد النسبة المتبقية -->
                        <div class="border {{ $remainingPercentage > 20 ? 'border-success' : ($remainingPercentage > 10 ? 'border-warning' : 'border-danger') }} rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="text-lg font-semibold dark:text-white-light">النسبة المتبقية</h6>
                                <svg class="w-8 h-8 {{ $remainingPercentage > 20 ? 'text-success' : ($remainingPercentage > 10 ? 'text-warning' : 'text-danger') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="text-3xl font-bold {{ $remainingPercentage > 20 ? 'text-success dark:text-success' : ($remainingPercentage > 10 ? 'text-warning dark:text-warning' : 'text-danger dark:text-danger') }}">
                                {{ number_format($remainingPercentage, 2) }}%
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">النسبة المتبقية غير موزعة</div>
                        </div>
                    </div>

                    <!-- جدول المستثمرين في هذا الاستثمار -->
                    <div class="mt-4">
                        <h6 class="text-md font-semibold mb-3 dark:text-white-light">المستثمرين في هذا الاستثمار ({{ $investment->investors()->count() }})</h6>
                        <div class="table-responsive">
                            <table class="table-hover">
                                <thead>
                                    <tr>
                                        <th class="dark:text-white-light">الاسم</th>
                                        <th class="dark:text-white-light">رقم الهاتف</th>
                                        <th class="dark:text-white-light">مبلغ الاستثمار</th>
                                        <th class="dark:text-white-light">نسبة التكلفة</th>
                                        <th class="dark:text-white-light">نسبة الربح</th>
                                        <th class="dark:text-white-light">الأرباح المتوقعة</th>
                                        <th class="dark:text-white-light">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($investment->investors as $investmentInvestor)
                                        @php
                                            $investor = $investmentInvestor->investor;
                                            $investorCostPercentage = $investmentInvestor->cost_percentage ?? 0;
                                            // حساب مبلغ الاستثمار من cost_percentage و total_value مباشرة لضمان الدقة 100%
                                            $investorInvestmentAmount = ($investorCostPercentage / 100) * $investment->total_value;
                                            // حساب الربح المتوقع من الربح الفعلي المتوقع (effective_price - purchase_price) وليس من total_value
                                            $investmentExpectedProfit = $investment->getExpectedProfit();
                                            $investorExpectedProfit = ($investmentInvestor->profit_percentage / 100) * $investmentExpectedProfit;
                                        @endphp
                                        <tr>
                                            <td class="dark:text-white-light">{{ $investor->name }}</td>
                                            <td class="dark:text-white-light">{{ $investor->phone }}</td>
                                            <td class="dark:text-white-light">{{ number_format($investorInvestmentAmount, 2) }} IQD</td>
                                            <td class="dark:text-white-light">{{ number_format($investorCostPercentage, 2) }}%</td>
                                            <td class="dark:text-white-light">{{ number_format($investmentInvestor->profit_percentage, 2) }}%</td>
                                            <td class="dark:text-white-light">{{ number_format($investorExpectedProfit, 2) }} IQD</td>
                                            <td>
                                                <a href="{{ route('admin.investors.show', $investor->id) }}" class="btn btn-primary btn-sm">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    عرض التفاصيل
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="panel mb-5">
                <div class="text-center py-8">
                    <p class="text-gray-500">لا توجد استثمارات</p>
                </div>
            </div>
        @endif

        <!-- ملخص إجمالي -->
        <div class="panel">
            <h6 class="text-lg font-semibold mb-4 dark:text-white-light">ملخص إجمالي</h6>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- كارد إجمالي القيمة -->
                <div class="border border-primary rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="text-lg font-semibold dark:text-white-light">إجمالي القيمة</h6>
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-primary dark:text-primary">{{ number_format($totalValue, 2) }} IQD</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">القيمة الإجمالية للمشروع</div>
                </div>

                <!-- كارد إجمالي مبلغ الاستثمار -->
                <div class="border border-info rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="text-lg font-semibold dark:text-white-light">إجمالي مبلغ الاستثمار</h6>
                        <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-info dark:text-info">{{ number_format($totalInvestment, 2) }} IQD</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">إجمالي مبلغ الاستثمار من المستثمرين</div>
                </div>

                <!-- كارد إجمالي الأرباح المتوقعة -->
                <div class="border border-success rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="text-lg font-semibold dark:text-white-light">الأرباح المتوقعة للمستثمرين</h6>
                        <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-success dark:text-success">{{ number_format($totalExpectedProfit, 2) }} IQD</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">إجمالي الأرباح المتوقعة للمستثمرين</div>
                </div>

                <!-- كارد ربح المدير المتوقع -->
                <div class="border border-warning rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h6 class="text-lg font-semibold dark:text-white-light">ربح المدير المتوقع</h6>
                        <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="text-3xl font-bold text-warning dark:text-warning">{{ number_format($totalAdminProfit, 2) }} IQD</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">ربح المدير المتوقع من المشروع</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            if (confirm('هل أنت متأكد من حذف هذا المشروع؟\n\nسيتم حذف:\n- المشروع\n- جميع الاستثمارات المرتبطة به\n- خزنة المشروع الفرعية\n- جميع المستثمرين المرتبطين به\n- خزنات المستثمرين\n\n⚠️ تحذير: هذا الإجراء لا يمكن التراجع عنه!')) {
                document.getElementById('deleteProjectForm').submit();
            }
        }
    </script>
</x-layout.admin>

