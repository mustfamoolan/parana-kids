<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة المستثمرين</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.investors.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة مستثمر جديد
                </a>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="panel mb-5">
            <form method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium mb-2">البحث</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           class="form-input" placeholder="ابحث بالاسم أو الرقم...">
                </div>
                <div class="sm:w-48">
                    <label for="status" class="block text-sm font-medium mb-2">الحالة</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="{{ route('admin.investors.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- كاردات المستثمرين -->
        <div class="panel">
            @if($investors->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($investors as $investor)
                        <div class="panel">
                            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-success/20 to-success/10 flex items-center justify-center">
                                        <span class="text-xl font-bold text-success">{{ strtoupper(substr($investor->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-lg">{{ $investor->name }}</div>
                                        @if($investor->status === 'active')
                                            <span class="badge badge-success text-xs">نشط</span>
                                        @else
                                            <span class="badge badge-danger text-xs">غير نشط</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                                    <div><span class="badge badge-outline-primary">{{ $investor->phone }}</span></div>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">رأس المال:</span>
                                    <div class="text-lg font-bold text-info">{{ number_format($investor->treasury->initial_capital ?? 0, 2) }} دينار</div>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">الرصيد الحالي:</span>
                                    <div class="text-lg font-bold text-success">{{ number_format($investor->treasury->current_balance ?? 0, 2) }} دينار</div>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">إجمالي الربح:</span>
                                    <div class="text-sm font-semibold text-primary">{{ number_format($investor->total_profit, 2) }} دينار</div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">الاستثمارات:</span>
                                        <div class="font-semibold">{{ $investor->investments_count }}</div>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">الأرباح:</span>
                                        <div class="font-semibold">{{ $investor->profits_count }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 mt-4 pt-4 border-t">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.investors.show', $investor) }}" class="btn btn-sm btn-info flex-1">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        عرض
                                    </a>
                                    <a href="{{ route('admin.investors.edit', $investor) }}" class="btn btn-sm btn-warning flex-1">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        تعديل
                                    </a>
                                </div>
                                @if(!$investor->is_admin)
                                <div class="flex gap-2">
                                    <form action="{{ route('admin.investors.reset-accounts', $investor) }}" method="POST" class="flex-1" onsubmit="return confirm('هل أنت متأكد من تصفير حسابات المستثمر؟ سيتم حذف جميع المعاملات وتصفير جميع الأرقام. هذا الإجراء لا يمكن التراجع عنه.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning w-full">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            تصفير الحسابات
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.investors.destroy', $investor) }}" method="POST" class="flex-1" onsubmit="return confirm('هل أنت متأكد من حذف المستثمر؟ سيتم حذف المستثمر وخزنته وجميع معاملاتها. هذا الإجراء لا يمكن التراجع عنه.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-full">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            حذف
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($investors->hasPages())
                    <div class="mt-6">
                        {{ $investors->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <p class="text-lg font-medium">لا يوجد مستثمرين</p>
                        <p class="text-sm">ابدأ بإضافة مستثمر جديد</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>

