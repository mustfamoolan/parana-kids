<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة الخزن</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.treasuries.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة خزنة جديدة
                </a>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="panel mb-5">
            <form method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium mb-2">البحث</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           class="form-input" placeholder="ابحث بالاسم...">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="{{ route('admin.treasuries.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <!-- كاردات الخزن -->
        <div class="panel">
            @if($treasuries->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($treasuries as $treasury)
                        <div class="panel">
                            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-lg">{{ $treasury->name }}</div>
                                        <div class="text-sm text-gray-500">تم الإنشاء: {{ $treasury->created_at->format('Y-m-d') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">رأس المال:</span>
                                    <span class="font-semibold">{{ number_format($treasury->initial_capital, 2) }} IQD</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">الرصيد الحالي:</span>
                                    <span class="font-semibold text-success">{{ number_format($treasury->current_balance, 2) }} IQD</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">عدد المعاملات:</span>
                                    <span class="font-semibold">{{ $treasury->transactions_count }}</span>
                                </div>
                            </div>

                            <div class="mt-4 pt-4 border-t flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.treasuries.show', $treasury) }}" class="btn btn-outline-primary flex-1">
                                        عرض التفاصيل
                                    </a>
                                    <a href="{{ route('admin.treasuries.edit', $treasury) }}" class="btn btn-outline-secondary">
                                        تعديل
                                    </a>
                                </div>
                                <div class="flex gap-2">
                                    <form action="{{ route('admin.treasuries.reset-balance', $treasury) }}" method="POST" class="flex-1" onsubmit="return confirm('هل أنت متأكد من تصفير رصيد الخزنة؟ سيتم تصفير الرصيد الحالي. هذا الإجراء لا يمكن التراجع عنه.');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning w-full">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            تصفير الرصيد
                                        </button>
                                    </form>
                                    @if(!$treasury->investor_id)
                                    <form action="{{ route('admin.treasuries.destroy', $treasury) }}" method="POST" class="flex-1" onsubmit="return confirm('هل أنت متأكد من حذف الخزنة؟ سيتم حذف الخزنة وجميع معاملاتها. هذا الإجراء لا يمكن التراجع عنه.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-full">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            حذف
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-5">
                    {{ $treasuries->links() }}
                </div>
            @else
                <div class="text-center py-10">
                    <p class="text-gray-500">لا توجد خزن</p>
                    <a href="{{ route('admin.treasuries.create') }}" class="btn btn-primary mt-4">إضافة خزنة جديدة</a>
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>

