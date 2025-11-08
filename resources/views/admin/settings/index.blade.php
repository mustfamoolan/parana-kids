<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الإعدادات</h5>
        </div>

        @if(session('success'))
            <div class="panel mb-5 border-l-4 border-green-500">
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20">
                    <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="panel mb-5 border-l-4 border-red-500">
                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20">
                    <svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <h6 class="font-semibold text-red-700 dark:text-red-300 mb-2">حدث خطأ:</h6>
                        <ul class="list-disc list-inside text-sm text-red-600 dark:text-red-400 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4">إعدادات النظام</h6>

                    <!-- سعر التوصيل -->
                    <div class="mb-5">
                        <label for="delivery_fee" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            سعر التوصيل (دينار) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="delivery_fee"
                            name="delivery_fee"
                            value="{{ old('delivery_fee', $deliveryFee) }}"
                            class="form-input"
                            min="0"
                            step="1"
                            required
                            placeholder="أدخل سعر التوصيل"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            هذا السعر سيظهر في صفحة الإرجاع الجزئي وصفحة تجهيز الطلب (في مربع أسماء المنتجات)
                        </p>
                    </div>

                    <!-- ربح فروقات -->
                    <div class="mb-5">
                        <label for="profit_margin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            ربح فروقات (دينار)
                        </label>
                        <input
                            type="number"
                            id="profit_margin"
                            name="profit_margin"
                            value="{{ old('profit_margin', $profitMargin) }}"
                            class="form-input"
                            min="0"
                            step="1"
                            placeholder="أدخل ربح الفروقات"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            هذا المبلغ سيُستخدم لاحقاً في النظام
                        </p>
                        @error('profit_margin')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="flex gap-3 justify-end">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        حفظ الإعدادات
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layout.admin>

