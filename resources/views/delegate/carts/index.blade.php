<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">سلال التسوق</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمنتجات
                </a>
                <button type="button" id="createNewCartBtn" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    سلة جديدة
                </button>
            </div>
        </div>

        <!-- نموذج إنشاء سلة جديدة -->
        <div id="newCartForm" class="mb-5 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hidden">
            <h6 class="text-md font-semibold dark:text-white-light mb-3">إنشاء سلة جديدة</h6>
            <form method="POST" action="{{ route('delegate.carts.store') }}" class="flex gap-2">
                @csrf
                <input
                    type="text"
                    name="cart_name"
                    class="form-input flex-1"
                    placeholder="اسم السلة (مثل: سلة - زبون 1)"
                    required
                >
                <button type="submit" class="btn btn-success">
                    إنشاء
                </button>
            </form>
        </div>

        @if($carts->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($carts as $cart)
                    <div class="panel">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h6 class="text-lg font-semibold dark:text-white-light">{{ $cart->cart_name }}</h6>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $cart->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                            <span class="badge badge-outline-primary">{{ $cart->total_items }} منتج</span>
                        </div>

                        <!-- معلومات السلة -->
                        <div class="mb-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                                <span class="font-medium">{{ $cart->total_items }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500 dark:text-gray-400">الإجمالي:</span>
                                <span class="font-bold text-primary">{{ number_format($cart->total_amount, 0) }} دينار عراقي</span>
                            </div>
                        </div>

                        <!-- منتجات السلة -->
                        @if($cart->items->count() > 0)
                            <div class="mb-4">
                                <h6 class="text-sm font-semibold dark:text-white-light mb-2">منتجات السلة</h6>
                                <div class="space-y-1">
                                    @foreach($cart->items->take(3) as $item)
                                        <div class="flex items-center justify-between text-xs bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                            <span>{{ $item->product->name }}</span>
                                            <span class="text-gray-500">{{ $item->quantity }}x</span>
                                        </div>
                                    @endforeach
                                    @if($cart->items->count() > 3)
                                        <div class="text-center text-xs text-gray-500">
                                            +{{ $cart->items->count() - 3 }} منتج آخر
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- أزرار العمل -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('delegate.carts.show', $cart) }}" class="btn btn-primary btn-sm flex-1">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                عرض السلة
                            </a>

                            @if($cart->items->count() > 0)
                                <a href="{{ route('delegate.orders.create', $cart) }}" class="btn btn-success btn-sm flex-1">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    إتمام الطلب
                                </a>
                            @endif
                        </div>

                        <!-- زر الحذف -->
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <form method="POST" action="{{ route('delegate.carts.destroy', $cart) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذه السلة؟ سيتم إرجاع المنتجات للمخزون.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm w-full">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    حذف السلة
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                </svg>
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">لا توجد سلال</h6>
                <p class="text-gray-500 dark:text-gray-400 mb-4">لم تقم بإنشاء أي سلة تسوق بعد</p>
                <button type="button" id="createFirstCartBtn" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إنشاء أول سلة
                </button>
            </div>
        @endif
    </div>

    <script>
        // إظهار/إخفاء نموذج إنشاء سلة جديدة
        document.getElementById('createNewCartBtn').addEventListener('click', function() {
            const form = document.getElementById('newCartForm');
            form.classList.toggle('hidden');
        });

        // إظهار نموذج إنشاء أول سلة
        document.getElementById('createFirstCartBtn').addEventListener('click', function() {
            const form = document.getElementById('newCartForm');
            form.classList.remove('hidden');
            form.scrollIntoView({ behavior: 'smooth' });
        });
    </script>

</x-layout.default>
