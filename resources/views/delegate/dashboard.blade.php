<x-layout.default>
    <div class="container mx-auto px-4 py-6">
        <!-- العنوان -->
        <h1 class="text-2xl font-bold mb-6 text-center">مرحباً {{ auth()->user()->name }}</h1>

        @if($activeOrder)
            <!-- تنبيه الطلب النشط -->
            <div class="panel mb-6 !bg-warning-light border-2 border-warning">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex-1">
                        <h5 class="font-bold text-warning text-lg mb-2">لديك طلب نشط!</h5>
                        <p class="text-black dark:text-white">الزبون: {{ session('customer_data.customer_name') }}</p>
                        <p class="text-black dark:text-white">المنتجات: {{ $activeOrder->items->count() }}</p>
                        <p class="text-black dark:text-white">الإجمالي: {{ number_format($activeOrder->total_amount, 0) }} د.ع</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('delegate.products.all') }}" class="btn btn-warning">إكمال الطلب</a>
                        <form method="POST" action="{{ route('delegate.orders.cancel-current') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">إلغاء</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- الأزرار الرئيسية -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- 1. طلب جديد -->
            <a href="#"
               @click.prevent="startNewOrder"
               x-data="{
                   hasActiveOrder: {{ $activeOrder ? 'true' : 'false' }},
                   startNewOrder() {
                       if (this.hasActiveOrder) {
                           // عرض مودال
                           Swal.fire({
                               title: 'لديك طلب نشط!',
                               text: 'يجب إكمال أو إلغاء الطلب الحالي أولاً',
                               icon: 'warning',
                               showCancelButton: true,
                               confirmButtonText: 'إكمال الطلب',
                               cancelButtonText: 'إلغاء الطلب',
                               confirmButtonColor: '#4361ee',
                               cancelButtonColor: '#e7515a'
                           }).then((result) => {
                               if (result.isConfirmed) {
                                   window.location.href = '{{ route('delegate.products.all') }}';
                               } else if (result.dismiss === Swal.DismissReason.cancel) {
                                   cancelOrder();
                               }
                           });
                       } else {
                           window.location.href = '{{ route('delegate.orders.start') }}';
                       }
                   }
               }"
               class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">طلب جديد</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إنشاء طلب جديد</p>
            </a>

            <!-- 2. طلباتي -->
            <a href="{{ route('delegate.orders.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">طلباتي</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="badge bg-warning">{{ $stats['pending_orders'] }}</span> قيد الانتظار
                </p>
            </a>

            <!-- 3. الرسائل (placeholder) -->
            <a href="#" onclick="alert('قريباً'); return false;" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">الرسائل</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">الإشعارات والرسائل</p>
            </a>

            <!-- 4. المنتجات -->
            <a href="{{ route('delegate.products.all') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">المنتجات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">تصفح المنتجات</p>
            </a>

            <!-- 5. إنشاء رابط -->
            <a href="{{ route('delegate.product-links.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-purple/10 to-purple/5 border-2 border-purple/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" fill="currentColor" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-purple mb-2">إنشاء رابط</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إنشاء رابط للمنتجات</p>
            </a>

        </div>
    </div>

    <script>
        function cancelOrder() {
            fetch('{{ route('delegate.orders.cancel-current') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            })
            .then(() => {
                Swal.fire('تم!', 'تم إلغاء الطلب', 'info')
                    .then(() => window.location.reload());
            });
        }
    </script>
</x-layout.default>
