<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">طلباتي</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمنتجات
                </a>
            </div>
        </div>

        @if($orders->count() > 0)
            <!-- إحصائيات سريعة - محسنة للجوال -->
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">إجمالي الطلبات</h6>
                            <p class="text-xl font-bold text-primary">{{ auth()->user()->orders()->count() }}</p>
                        </div>
                        <div class="p-2 bg-primary/10 rounded-lg">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">غير مقيدة</h6>
                            <p class="text-xl font-bold text-warning">{{ auth()->user()->orders()->where('status', 'pending')->count() }}</p>
                        </div>
                        <div class="p-2 bg-warning/10 rounded-lg">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">مقيدة</h6>
                            <p class="text-xl font-bold text-success">{{ auth()->user()->orders()->where('status', 'confirmed')->count() }}</p>
                        </div>
                        <div class="p-2 bg-success/10 rounded-lg">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- روابط سريعة - محسنة للجوال -->
            <div class="mb-5">
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-2">
                    <a href="{{ route('delegate.orders.index', ['status' => 'pending']) }}"
                       class="btn btn-sm {{ request('status') == 'pending' ? 'btn-warning' : 'btn-outline-warning' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات غير المقيدة</span>
                        <span class="sm:hidden">غير مقيدة</span>
                    </a>
                    <a href="{{ route('delegate.orders.index', ['status' => 'confirmed']) }}"
                       class="btn btn-sm {{ request('status') == 'confirmed' ? 'btn-success' : 'btn-outline-success' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات المقيدة</span>
                        <span class="sm:hidden">مقيدة</span>
                    </a>
                    <a href="{{ route('delegate.orders.index', ['date_from' => now()->format('Y-m-d')]) }}"
                       class="btn btn-sm {{ request('date_from') == now()->format('Y-m-d') ? 'btn-info' : 'btn-outline-info' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">طلبات اليوم</span>
                        <span class="sm:hidden">اليوم</span>
                    </a>
                    <a href="{{ route('delegate.orders.index', ['date_from' => now()->subDays(7)->format('Y-m-d')]) }}"
                       class="btn btn-sm {{ request('date_from') == now()->subDays(7)->format('Y-m-d') ? 'btn-primary' : 'btn-outline-primary' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">آخر 7 أيام</span>
                        <span class="sm:hidden">أسبوع</span>
                    </a>
                </div>
            </div>

            <!-- فلتر وبحث -->
            <div class="mb-5">
                <form method="GET" action="{{ route('delegate.orders.index') }}" class="space-y-4">
                    <!-- الصف الأول: البحث والحالة -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                type="text"
                                name="search"
                                class="form-input"
                                placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، العنوان، رابط السوشل ميديا، الملاحظات، أو اسم/كود المنتج..."
                                value="{{ request('search') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <select name="status" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>غير مقيد</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>مقيد</option>
                            </select>
                        </div>
                    </div>

                    <!-- الصف الثاني: التاريخ -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_from"
                                class="form-input"
                                placeholder="من تاريخ"
                                value="{{ request('date_from') }}"
                            >
                        </div>
                        <div class="sm:w-48">
                            <input
                                type="date"
                                name="date_to"
                                class="form-input"
                                placeholder="إلى تاريخ"
                                value="{{ request('date_to') }}"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            @if(request('search') || request('status') || request('date_from') || request('date_to'))
                                <a href="{{ route('delegate.orders.index') }}" class="btn btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    مسح
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            <!-- نتائج البحث -->
            @if(request('search') || request('status') || request('date_from') || request('date_to'))
                <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                            عرض {{ $orders->count() }} طلب
                            @if(request('search'))
                                للبحث: "{{ request('search') }}"
                            @endif
                            @if(request('status'))
                                في حالة: {{ request('status') == 'pending' ? 'غير مقيد' : 'مقيد' }}
                            @endif
                            @if(request('date_from') || request('date_to'))
                                @if(request('date_from') && request('date_to'))
                                    من {{ request('date_from') }} إلى {{ request('date_to') }}
                                @elseif(request('date_from'))
                                    من {{ request('date_from') }}
                                @elseif(request('date_to'))
                                    حتى {{ request('date_to') }}
                                @endif
                            @endif
                        </span>
                    </div>
                </div>
            @endif

            <!-- قائمة الطلبات -->
            <div class="space-y-4">
                @foreach($orders as $order)
                    <div class="panel">
                        <!-- Header: رقم الطلب والحالة -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
                            <div class="flex items-center justify-between sm:justify-start gap-3">
                                <div>
                                    <h6 class="text-lg font-semibold dark:text-white-light">{{ $order->order_number }}</h6>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->customer_name }}</p>
                                </div>
                                <span class="badge {{ $order->status === 'pending' ? 'badge-outline-warning' : 'badge-outline-success' }} shrink-0">
                                    {{ $order->status === 'pending' ? 'غير مقيد' : 'مقيد' }}
                                </span>
                            </div>
                            <div class="text-left sm:text-right">
                                <div class="text-lg font-semibold text-success">{{ number_format($order->total_amount, 0) }} دينار عراقي</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $order->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                        </div>

                        <!-- معلومات الزبون - محسنة للجوال -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">الهاتف</span>
                                <p class="font-medium text-sm">{{ $order->customer_phone }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">العنوان</span>
                                <p class="font-medium text-sm">{{ Str::limit($order->customer_address, 25) }}</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">السوشل ميديا</span>
                                <p class="font-medium text-sm">
                                    <a href="{{ $order->customer_social_link }}" target="_blank" class="text-primary hover:underline">
                                        {{ Str::limit($order->customer_social_link, 20) }}
                                    </a>
                                </p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">تاريخ الطلب</span>
                                <p class="font-medium text-sm">{{ $order->created_at->format('Y-m-d H:i') }}</p>
                            </div>
                        </div>


                        <!-- منتجات الطلب - محسنة للجوال -->
                        <div class="mb-4">
                            <h6 class="text-sm font-semibold dark:text-white-light mb-3">منتجات الطلب ({{ $order->items->count() }} منتج)</h6>
                            <div class="space-y-2">
                                @foreach($order->items->take(3) as $item)
                                    <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 p-2 rounded-lg">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium">{{ $item->product_name }}</p>
                                            <p class="text-xs text-gray-500">{{ $item->product_code }} - {{ $item->size_name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold">{{ $item->quantity }} قطعة</p>
                                            <p class="text-xs text-gray-500">{{ number_format($item->subtotal, 0) }} دينار</p>
                                        </div>
                                    </div>
                                @endforeach
                                @if($order->items->count() > 3)
                                    <div class="text-center">
                                        <span class="text-xs text-gray-500">+{{ $order->items->count() - 3 }} منتج آخر</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- ملاحظات -->
                        @if($order->notes)
                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">ملاحظات:</span>
                                <p class="text-sm mt-1">{{ $order->notes }}</p>
                            </div>
                        @endif

                        <!-- أزرار العمل - محسنة للجوال -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            <a href="{{ route('delegate.orders.show', $order) }}" class="btn btn-primary btn-sm flex-1 sm:flex-none">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                عرض التفاصيل
                            </a>

                            @if($order->status === 'pending')
                                <a href="{{ route('delegate.orders.edit', $order) }}" class="btn btn-warning btn-sm flex-1 sm:flex-none">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    تعديل
                                </a>

                                <form method="POST" action="{{ route('delegate.orders.cancel', $order) }}" class="flex-1 sm:flex-none" onsubmit="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm w-full">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        إلغاء
                                    </button>
                                </form>
                            @endif

                            @if(in_array($order->status, ['pending', 'confirmed']))
                                <button onclick="deleteOrder({{ $order->id }})" class="btn btn-outline-danger btn-sm flex-1 sm:flex-none">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    حذف
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $orders->links() }}
            </div>
        @else
            <!-- لا توجد طلبات -->
            <div class="text-center py-12">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h6 class="text-lg font-semibold dark:text-white-light mb-2">
                    @if(request('search') || request('status') || request('date_from') || request('date_to'))
                        لا توجد نتائج للبحث
                    @else
                        لا توجد طلبات
                    @endif
                </h6>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    @if(request('search') || request('status') || request('date_from') || request('date_to'))
                        لم يتم العثور على طلبات تطابق معايير البحث
                    @else
                        لم تقم بإرسال أي طلبات بعد
                    @endif
                </p>
                <div class="flex gap-2 justify-center">
                    @if(request('search') || request('status') || request('date_from') || request('date_to'))
                        <a href="{{ route('delegate.orders.index') }}" class="btn btn-outline-secondary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            مسح البحث
                        </a>
                    @endif
                    <a href="{{ route('delegate.products.all') }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        تصفح المنتجات
                    </a>
                </div>
            </div>
        @endif
    </div>

    <script>
        function deleteOrder(orderId) {
            if (confirm('هل أنت متأكد من حذف هذا الطلب؟ سيتم إرجاع جميع المنتجات للمخزن.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/delegate/orders/${orderId}`;

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                form.appendChild(methodField);
                form.appendChild(csrfToken);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</x-layout.default>
