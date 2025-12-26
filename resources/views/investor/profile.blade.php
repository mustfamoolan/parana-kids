<x-layout.investor>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold dark:text-white-light">الملف الشخصي</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">معلوماتك الشخصية</p>
        </div>

        <div class="panel">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- المعلومات الشخصية -->
                <div>
                    <h6 class="text-lg font-semibold mb-4 dark:text-white-light">المعلومات الشخصية</h6>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">الاسم</label>
                            <div class="text-lg font-semibold dark:text-white-light">{{ $investor->name }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">رقم الهاتف</label>
                            <div class="text-lg font-semibold dark:text-white-light">{{ $investor->phone }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">الحالة</label>
                            <div>
                                @if($investor->status === 'active')
                                    <span class="badge badge-success">نشط</span>
                                @else
                                    <span class="badge badge-danger">غير نشط</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات الخزنة -->
                @if($investor->treasury)
                    <div>
                        <h6 class="text-lg font-semibold mb-4 dark:text-white-light">معلومات الخزنة</h6>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">اسم الخزنة</label>
                                <div class="text-lg font-semibold dark:text-white-light">{{ $investor->treasury->name }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">رأس المال</label>
                                <div class="text-lg font-semibold text-info">{{ number_format($investor->treasury->initial_capital, 2) }} دينار</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">الرصيد الحالي</label>
                                <div class="text-lg font-semibold text-success">{{ number_format($investor->treasury->current_balance, 2) }} دينار</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if($investor->notes)
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">ملاحظات</label>
                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ $investor->notes }}</div>
                </div>
            @endif
        </div>
    </div>
</x-layout.investor>

