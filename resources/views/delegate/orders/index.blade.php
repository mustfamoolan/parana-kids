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
                    <a href="{{ route('delegate.orders.index') }}"
                       class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="hidden sm:inline">جميع الطلبات</span>
                        <span class="sm:hidden">الكل</span>
                    </a>
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
                    <a href="{{ route('delegate.orders.index', ['status' => 'deleted']) }}"
                       class="btn btn-sm {{ request('status') == 'deleted' ? 'btn-danger' : 'btn-outline-danger' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات المحذوفة</span>
                        <span class="sm:hidden">محذوفة</span>
                        @php
                            $deletedCount = auth()->user()->orders()->onlyTrashed()->count();
                        @endphp
                        @if($deletedCount > 0)
                            <span class="badge bg-danger ltr:ml-1 rtl:mr-1">{{ $deletedCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('delegate.orders.index', ['status' => 'archived']) }}"
                       class="btn btn-sm {{ request('status') == 'archived' ? 'btn-secondary' : 'btn-outline-secondary' }} flex-1 sm:flex-none">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                        </svg>
                        <span class="hidden sm:inline">الطلبات المؤرشفة</span>
                        <span class="sm:hidden">مؤرشفة</span>
                        @php
                            $archivedCount = \App\Models\ArchivedOrder::where('delegate_id', auth()->id())->count();
                        @endphp
                        @if($archivedCount > 0)
                            <span class="badge bg-secondary ltr:ml-1 rtl:mr-1">{{ $archivedCount }}</span>
                        @endif
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
                        <div class="sm:w-32">
                            <input
                                type="time"
                                name="time_from"
                                class="form-input"
                                placeholder="من الساعة"
                                value="{{ request('time_from') }}"
                            >
                        </div>
                        <div class="sm:w-32">
                            <input
                                type="time"
                                name="time_to"
                                class="form-input"
                                placeholder="إلى الساعة"
                                value="{{ request('time_to') }}"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                بحث
                            </button>
                            @if(request('search') || request('status') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
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
            @if(request('search') || request('status') || request('date_from') || request('date_to') || request('time_from') || request('time_to'))
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
                    <div id="order-{{ $order->id }}" class="panel
                        @if($order instanceof \App\Models\ArchivedOrder)
                            border-2 border-gray-500 dark:border-gray-600
                        @elseif($order->trashed())
                            border-2 border-red-500 dark:border-red-600
                        @elseif($order->status === 'pending')
                            border-2 border-yellow-500 dark:border-yellow-600
                        @elseif($order->status === 'confirmed')
                            border-2 border-green-500 dark:border-green-600
                        @elseif($order->status === 'returned')
                            border-2 border-blue-500 dark:border-blue-600
                        @else
                            border-2 border-gray-300 dark:border-gray-600
                        @endif
                    ">
                        <!-- Header: رقم الطلب والحالة -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
                            <div class="flex items-center justify-between sm:justify-start gap-3">
                                <div>
                                    <h6 class="text-lg font-semibold dark:text-white-light">
                                        @if($order instanceof \App\Models\ArchivedOrder)
                                            طلب مؤرشف #{{ $order->id }}
                                        @else
                                            {{ $order->order_number }}
                                        @endif
                                    </h6>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->customer_name }}</p>
                                </div>
                                @if($order instanceof \App\Models\ArchivedOrder)
                                    <span class="badge badge-outline-secondary shrink-0">
                                        مؤرشف
                                    </span>
                                @elseif($order->trashed())
                                    <span class="badge badge-outline-danger shrink-0">
                                        محذوف
                                    </span>
                                @else
                                    <span class="badge {{ $order->status === 'pending' ? 'badge-outline-warning' : 'badge-outline-success' }} shrink-0">
                                        {{ $order->status === 'pending' ? 'غير مقيد' : 'مقيد' }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-left sm:text-right">
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    @if($order instanceof \App\Models\ArchivedOrder)
                                        {{ $order->archived_at->format('Y-m-d H:i') }}
                                    @else
                                        {{ $order->created_at->format('Y-m-d H:i') }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- معلومات الزبون - مبسطة -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">السوشل ميديا</span>
                                <p class="font-medium text-sm">
                                    <a href="{{ $order->customer_social_link }}" target="_blank" class="text-primary hover:underline">
                                        {{ Str::limit($order->customer_social_link, 20) }}
                                    </a>
                                </p>
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
                            @if($order instanceof \App\Models\ArchivedOrder)
                                <!-- أزرار الطلبات المؤرشفة -->
                                <div class="mb-3 p-3 bg-secondary/10 dark:bg-secondary/20 rounded-lg w-full">
                                    <p class="text-xs text-secondary dark:text-secondary mb-1">
                                        <strong>تم الأرشفة:</strong> {{ $order->archived_at->diffForHumans() }}
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('delegate.archived.restore', $order) }}" class="flex-1 sm:flex-none">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-full" onclick="return confirm('هل تريد استرجاع هذا الطلب المؤرشف؟')">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        استرجاع الطلب
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('delegate.archived.destroy', $order) }}" class="flex-1 sm:flex-none">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm w-full" onclick="return confirm('هل أنت متأكد من الحذف النهائي للطلب المؤرشف؟ لن يمكن استرجاعه!')">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        حذف نهائي
                                    </button>
                                </form>
                            @elseif($order->trashed())
                                <!-- أزرار الطلبات المحذوفة -->
                                <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg w-full">
                                    <p class="text-xs text-red-600 dark:text-red-400 mb-1">
                                        <strong>تم الحذف:</strong> {{ $order->deleted_at->diffForHumans() }}
                                    </p>
                                    @if($order->deletedByUser)
                                        <p class="text-xs text-red-600 dark:text-red-400">
                                            <strong>بواسطة:</strong> {{ $order->deletedByUser->name }}
                                        </p>
                                    @endif
                                </div>

                                <form method="POST" action="{{ route('delegate.orders.restore', $order) }}" class="flex-1 sm:flex-none">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm w-full" onclick="return confirm('هل تريد استرجاع هذا الطلب؟')">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        استرجاع
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('delegate.orders.forceDelete', $order->id) }}" class="flex-1 sm:flex-none">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm w-full" onclick="return confirm('هل أنت متأكد من الحذف النهائي؟ لن يمكن استرجاع هذا الطلب!')">
                                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        حذف نهائي
                                    </button>
                                </form>
                            @else
                                <!-- أزرار الطلبات العادية -->
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
                                @endif

                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <x-pagination :items="$orders" />
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // إذا كان هناك anchor في الرابط (#order-123)
        if (window.location.hash) {
            const target = document.querySelector(window.location.hash);
            if (target) {
                setTimeout(() => {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 100);
            }
        }
        // وإلا إذا كان هناك موضع محفوظ
        else if (sessionStorage.getItem('delegateOrdersScroll')) {
            const scrollPos = sessionStorage.getItem('delegateOrdersScroll');
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem('delegateOrdersScroll');
        }

        // حفظ موضع التمرير قبل الانتقال لصفحة أخرى
        const orderLinks = document.querySelectorAll('a[href*="/orders/"]');
        orderLinks.forEach(link => {
            link.addEventListener('click', function() {
                sessionStorage.setItem('delegateOrdersScroll', window.scrollY);
            });
        });
    });
    </script>
</x-layout.default>
