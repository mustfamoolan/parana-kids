<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إتمام الطلب - {{ $cart->cart_name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('delegate.carts.show', $cart) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للسلة
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- نموذج معلومات الزبون -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الزبون</h6>
                    <p class="text-sm text-gray-500 dark:text-gray-400">يرجى ملء جميع المعلومات المطلوبة</p>
                </div>

                <form method="POST" action="{{ route('delegate.orders.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="cart_id" value="{{ $cart->id }}">

                    <!-- اسم الزبون -->
                    <div>
                        <label for="customer_name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            اسم الزبون <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            class="form-input"
                            placeholder="أدخل اسم الزبون الكامل"
                            value="{{ old('customer_name') }}"
                            required
                        >
                        @error('customer_name')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- رقم الهاتف -->
                    <div>
                        <label for="customer_phone" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            رقم الهاتف <span class="text-danger">*</span>
                        </label>
                        <input
                            type="tel"
                            id="customer_phone"
                            name="customer_phone"
                            class="form-input"
                            placeholder="07XX XXX XXXX"
                            value="{{ old('customer_phone') }}"
                            required
                        >
                        @error('customer_phone')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- العنوان -->
                    <div>
                        <label for="customer_address" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            العنوان <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="customer_address"
                            name="customer_address"
                            class="form-textarea"
                            rows="3"
                            placeholder="أدخل العنوان الكامل للزبون"
                            required
                        >{{ old('customer_address') }}</textarea>
                        @error('customer_address')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- رابط السوشل ميديا -->
                    <div>
                        <label for="customer_social_link" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            رابط السوشل ميديا <span class="text-danger">*</span>
                        </label>
                        <input
                            type="url"
                            id="customer_social_link"
                            name="customer_social_link"
                            class="form-input"
                            placeholder="https://facebook.com/username أو https://instagram.com/username"
                            value="{{ old('customer_social_link') }}"
                            required
                        >
                        @error('customer_social_link')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ملاحظات -->
                    <div>
                        <label for="notes" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            ملاحظات (اختيارية)
                        </label>
                        <textarea
                            id="notes"
                            name="notes"
                            class="form-textarea"
                            rows="3"
                            placeholder="أي ملاحظات إضافية حول الطلب..."
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- زر الإرسال -->
                    <div class="pt-4">
                        <button type="submit" class="btn btn-success btn-lg w-full">
                            <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            إرسال الطلب
                        </button>
                    </div>
                </form>
            </div>

            <!-- ملخص الطلب -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">ملخص الطلب</h6>
                </div>

                <!-- إحصائيات الطلب -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary">{{ $cart->total_items }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي القطع</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-success">{{ number_format($cart->total_amount, 0) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">دينار عراقي</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-info">{{ $cart->items->count() }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">منتج مختلف</div>
                    </div>
                </div>

                <!-- منتجات الطلب -->
                <div class="space-y-3">
                    <h6 class="text-md font-semibold dark:text-white-light">منتجات الطلب</h6>
                    @foreach($cart->items as $item)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <!-- صورة المنتج -->
                            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                @if($item->product->primaryImage)
                                    <img src="{{ $item->product->primaryImage->image_url }}"
                                         alt="{{ $item->product->name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- معلومات المنتج -->
                            <div class="flex-1">
                                <h6 class="text-sm font-semibold dark:text-white-light">{{ $item->product->name }}</h6>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->product->code }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="badge badge-outline-primary text-xs">{{ $item->size->size_name }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">× {{ $item->quantity }}</span>
                                </div>
                            </div>

                            <!-- السعر -->
                            <div class="text-right">
                                <div class="text-sm font-semibold text-success">{{ number_format($item->subtotal, 0) }} دينار</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($item->price, 0) }} للقطعة</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- إجمالي الطلب -->
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h6 class="text-lg font-semibold dark:text-white-light">إجمالي الطلب</h6>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-success">{{ number_format($cart->total_amount, 0) }} دينار عراقي</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $cart->total_items }} قطعة</div>
                        </div>
                    </div>
                </div>

                <!-- ملاحظة مهمة -->
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h6 class="text-sm font-semibold text-blue-700 dark:text-blue-300">ملاحظة مهمة</h6>
                            <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                                بعد إرسال الطلب، سيتم خصم المنتجات من المخزون وسيصبح الطلب في حالة "غير مقيد" حتى يتم تأكيده من قبل الإدارة.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layout.default>
