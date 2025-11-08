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
            <div class="mb-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
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
                        <div class="flex-1">
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">غير مقيدة</h6>
                            <p class="text-xl font-bold text-warning">{{ auth()->user()->orders()->where('status', 'pending')->count() }}</p>
                            @php
                                $lastPendingOrder = auth()->user()->orders()->where('status', 'pending')->latest('created_at')->first();
                            @endphp
                            @if($lastPendingOrder)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $lastPendingOrder->created_at->locale('ar')->translatedFormat('l') }}، {{ $lastPendingOrder->created_at->format('d/m/Y') }}
                                    <span class="rtl:mr-1 ltr:ml-1">{{ $lastPendingOrder->created_at->format('g:i A') }}</span>
                                </p>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">لا يوجد</p>
                            @endif
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
                        <div class="flex-1">
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">مقيدة</h6>
                            <p class="text-xl font-bold text-success">{{ auth()->user()->orders()->where('status', 'confirmed')->count() }}</p>
                            @php
                                $lastConfirmedOrder = auth()->user()->orders()->where('status', 'confirmed')->whereNotNull('confirmed_at')->latest('confirmed_at')->first();
                            @endphp
                            @if($lastConfirmedOrder && $lastConfirmedOrder->confirmed_at)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $lastConfirmedOrder->confirmed_at->locale('ar')->translatedFormat('l') }}، {{ $lastConfirmedOrder->confirmed_at->format('d/m/Y') }}
                                    <span class="rtl:mr-1 ltr:ml-1">{{ $lastConfirmedOrder->confirmed_at->format('g:i A') }}</span>
                                </p>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">لا يوجد</p>
                            @endif
                        </div>
                        <div class="p-2 bg-success/10 rounded-lg">
                            <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="panel p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h6 class="text-xs font-semibold dark:text-white-light text-gray-500">محذوفة</h6>
                            <p class="text-xl font-bold text-danger">{{ auth()->user()->orders()->onlyTrashed()->whereNotNull('deleted_by')->whereNotNull('deletion_reason')->count() }}</p>
                            @php
                                $lastDeletedOrder = auth()->user()->orders()->onlyTrashed()->whereNotNull('deleted_by')->whereNotNull('deletion_reason')->latest('deleted_at')->first();
                            @endphp
                            @if($lastDeletedOrder && $lastDeletedOrder->deleted_at)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $lastDeletedOrder->deleted_at->locale('ar')->translatedFormat('l') }}، {{ $lastDeletedOrder->deleted_at->format('d/m/Y') }}
                                    <span class="rtl:mr-1 ltr:ml-1">{{ $lastDeletedOrder->deleted_at->format('g:i A') }}</span>
                                </p>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">لا يوجد</p>
                            @endif
                        </div>
                        <div class="p-2 bg-danger/10 rounded-lg">
                            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
                                <option value="deleted" {{ request('status') == 'deleted' ? 'selected' : '' }}>محذوف</option>
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
                        @if($order->trashed())
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
                                        {{ $order->order_number }}
                                    </h6>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $order->customer_name }}</p>
                                </div>
                                @if($order->trashed())
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
                                    {{ $order->created_at->locale('ar')->translatedFormat('l') }}، {{ $order->created_at->format('d/m/Y') }}
                                    <span class="rtl:mr-1 ltr:ml-1">{{ $order->created_at->format('g:i A') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- رابط السوشل ميديا -->
                        @if($order->customer_social_link)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-2">رابط السوشل ميديا</span>
                                    <a href="{{ $order->customer_social_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary w-full flex items-center justify-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        فتح الرابط
                                    </a>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 truncate">{{ Str::limit($order->customer_social_link, 30) }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- معلومات التقييد للطلبات المقيدة -->
                        @if($order->status === 'confirmed' && $order->confirmed_at)
                            <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <span class="text-xs font-semibold text-green-700 dark:text-green-400 block mb-2">
                                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    تاريخ التقييد
                                </span>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                    {{ $order->confirmed_at->locale('ar')->translatedFormat('l') }}، {{ $order->confirmed_at->format('d/m/Y') }}
                                    <span class="rtl:mr-1 ltr:ml-1">{{ $order->confirmed_at->format('g:i A') }}</span>
                                </p>

                                @if($order->delivery_code)
                                    <div class="pt-3 border-t border-green-200 dark:border-green-700">
                                        <span class="text-xs text-green-600 dark:text-green-400 block mb-1">كود الوسيط:</span>
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="font-bold text-lg text-green-700 dark:text-green-300">{{ $order->delivery_code }}</span>
                                            <button
                                                type="button"
                                                onclick="copyToClipboard('{{ $order->delivery_code }}')"
                                                class="btn btn-xs btn-outline-primary flex items-center gap-1"
                                                title="نسخ كود الوسيط"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                نسخ
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- ملاحظات -->
                        @if($order->notes)
                            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-medium">ملاحظات:</span>
                                <p class="text-sm mt-1">{{ $order->notes }}</p>
                            </div>
                        @endif

                        <!-- أزرار العمل - محسنة للجوال -->
                        <div class="flex flex-col sm:flex-row gap-2">
                            @if($order->trashed())
                                <!-- معلومات الطلبات المحذوفة -->
                                <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg w-full">
                                    <p class="text-xs text-red-600 dark:text-red-400 mb-2">
                                        <strong>تم الحذف:</strong> {{ $order->deleted_at->locale('ar')->translatedFormat('l') }}، {{ $order->deleted_at->format('d/m/Y') }}
                                        <span class="rtl:mr-1 ltr:ml-1">{{ $order->deleted_at->format('g:i A') }}</span>
                                    </p>
                                    @if($order->deletedByUser)
                                        <p class="text-xs text-red-600 dark:text-red-400 mb-1">
                                            <strong>المجهز الذي حذف الطلب:</strong> {{ $order->deletedByUser->name }} ({{ $order->deletedByUser->code }})
                                        </p>
                                    @endif
                                    @if($order->deletion_reason)
                                        <div class="mt-2 pt-2 border-t border-red-300 dark:border-red-700">
                                            <p class="text-xs text-red-700 dark:text-red-300 font-medium mb-1">سبب الحذف:</p>
                                            <p class="text-xs text-red-600 dark:text-red-400">{{ $order->deletion_reason }}</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- فقط زر عرض التفاصيل للطلبات المحذوفة -->
                                <a href="{{ route('delegate.orders.show', $order) }}?back_url={{ urlencode(request()->fullUrl()) }}" class="btn btn-primary btn-sm flex-1 sm:flex-none">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    عرض التفاصيل
                                </a>
                            @else
                                <!-- أزرار الطلبات العادية -->
                                <a href="{{ route('delegate.orders.show', $order) }}?back_url={{ urlencode(request()->fullUrl()) }}" class="btn btn-primary btn-sm flex-1 sm:flex-none">
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
    // دالة نسخ إلى الحافظة
    function copyToClipboard(text) {
        // إنشاء عنصر مؤقت
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);

        // تحديد ونسخ النص
        textarea.select();
        textarea.setSelectionRange(0, 99999); // للهواتف المحمولة

        try {
            document.execCommand('copy');
            showNotification('تم نسخ كود الوسيط بنجاح!');
        } catch (err) {
            showNotification('حدث خطأ أثناء النسخ', 'error');
        }

        // إزالة العنصر المؤقت
        document.body.removeChild(textarea);
    }

    // دالة عرض الإشعار
    function showNotification(message, type = 'success') {
        // إنشاء عنصر الإشعار
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300`;
        notification.textContent = message;

        // إضافة الإشعار للصفحة
        document.body.appendChild(notification);

        // إزالة الإشعار بعد 3 ثوان
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (document.body.contains(notification)) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

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
