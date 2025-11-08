<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">سلة التسوق: {{ $cart->cart_name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $backUrl = request()->query('back_url');
                    if ($backUrl) {
                        $backUrl = urldecode($backUrl);
                        $parsed = parse_url($backUrl);
                        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
                        if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                            $backUrl = null;
                        }
                    }
                    if (!$backUrl) {
                        $backUrl = route('delegate.carts.index');
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للسلال
                </a>
                <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة منتجات
                </a>
            </div>
        </div>

        @if($cart->items->count() > 0)
            <!-- ملخص السلة - محسن للجوال -->
            <div class="panel mb-5">
                <div class="grid grid-cols-3 gap-3">
                    <div class="text-center p-3 bg-primary/10 rounded-lg">
                        <div class="text-xl font-bold text-primary">{{ $cart->total_items }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">إجمالي القطع</div>
                    </div>
                    <div class="text-center p-3 bg-success/10 rounded-lg">
                        <div class="text-xl font-bold text-success">{{ number_format($cart->total_amount, 0) }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">دينار عراقي</div>
                    </div>
                    <div class="text-center p-3 bg-info/10 rounded-lg">
                        <div class="text-xl font-bold text-info">{{ $cart->items->count() }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">منتج مختلف</div>
                    </div>
                </div>
            </div>

            <!-- منتجات السلة -->
            <div class="space-y-4">
                @foreach($cart->items as $item)
                    <div class="panel">
                        <div class="flex items-center gap-4">
                            <!-- صورة المنتج -->
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden flex-shrink-0">
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
                                <h6 class="font-semibold dark:text-white-light">{{ $item->product->name }}</h6>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->product->code }}</p>
                                <div class="flex items-center gap-4 mt-2">
                                    <span class="badge badge-outline-primary">{{ $item->size->size_name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        متوفر: {{ $item->size->available_quantity + $item->quantity }} قطعة
                                    </span>
                                </div>
                            </div>

                            <!-- السعر والكمية -->
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-success">{{ number_format($item->price, 0) }} دينار</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">للقطعة الواحدة</div>
                                </div>

                                <!-- تعديل الكمية -->
                                <div class="flex items-center gap-2">
                                    <form method="POST" action="{{ route('delegate.cart-items.update', $item) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input
                                            type="number"
                                            name="quantity"
                                            value="{{ $item->quantity }}"
                                            min="1"
                                            max="{{ $item->size->available_quantity + $item->quantity }}"
                                            class="form-input w-20 text-center"
                                            onchange="this.form.submit()"
                                        >
                                    </form>
                                </div>

                                <!-- المجموع الفرعي -->
                                <div class="text-right">
                                    <div class="text-lg font-semibold text-primary">{{ number_format($item->subtotal, 0) }} دينار</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">المجموع الفرعي</div>
                                </div>

                                <!-- حذف المنتج -->
                                <form method="POST" action="{{ route('delegate.cart-items.destroy', $item) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج من السلة؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- إجمالي السلة -->
            <div class="panel mt-5">
                <div class="flex items-center justify-between">
                    <h6 class="text-xl font-semibold dark:text-white-light">إجمالي السلة</h6>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-success">{{ number_format($cart->total_amount, 0) }} دينار عراقي</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $cart->total_items }} قطعة</div>
                    </div>
                </div>
            </div>

            <!-- أزرار العمل -->
            <div class="flex gap-4 justify-center mt-6">
                <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة منتجات أخرى
                </a>

                <a href="{{ route('delegate.orders.create', $cart) }}" class="btn btn-success btn-lg">
                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    إتمام الطلب
                </a>
            </div>
        @else
            <!-- السلة فارغة -->
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                </svg>
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">السلة فارغة</h6>
                <p class="text-gray-500 dark:text-gray-400 mb-4">لم تقم بإضافة أي منتجات إلى هذه السلة بعد</p>
                <a href="{{ route('delegate.products.all') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    تصفح المنتجات
                </a>
            </div>
        @endif
    </div>

</x-layout.default>
