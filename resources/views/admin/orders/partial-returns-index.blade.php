<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إرجاع جزئي - الطلبات المقيدة</h5>
        </div>

        <!-- فلتر وبحث -->
        <div class="mb-5">
            <form method="GET" action="{{ route('admin.orders.partial-returns.index') }}" class="space-y-4">
                <!-- الصف الأول: البحث -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، كود الوسيط، اسم المندوب، أو اسم المجهز (مطابقة تامة)..."
                            value="{{ request('search') }}"
                        >
                    </div>
                </div>

                <!-- الصف الثاني: المندوب والمجهز -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="sm:w-48">
                        @php
                            $orderCreators = \App\Models\User::whereIn('role', ['delegate', 'admin', 'supplier'])->orderBy('role')->orderBy('name')->get();
                        @endphp
                        <select name="delegate_id" class="form-select">
                            <option value="">كل المندوبين والمديرين والمجهزين</option>
                            @foreach($orderCreators as $creator)
                                <option value="{{ $creator->id }}" {{ request('delegate_id') == $creator->id ? 'selected' : '' }}>
                                    {{ $creator->name }} ({{ $creator->code }}) - {{ $creator->role === 'admin' ? 'مدير' : ($creator->role === 'supplier' ? 'مجهز' : 'مندوب') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <select name="confirmed_by" class="form-select">
                            <option value="">كل المجهزين والمديرين</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('confirmed_by') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }}) - {{ $supplier->role === 'admin' ? 'مدير' : 'مجهز' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                        @if(request()->hasAny(['search', 'date_from', 'date_to', 'delegate_id', 'confirmed_by']))
                            <a href="{{ route('admin.orders.partial-returns.index') }}" class="btn btn-outline-secondary">
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
        @if(request()->hasAny(['search', 'date_from', 'date_to', 'delegate_id', 'confirmed_by']))
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        عرض {{ $orders->total() }} طلب مقيد
                        @if(request('search'))
                            للبحث: "{{ request('search') }}"
                        @endif
                        @if(request('date_from') || request('date_to'))
                            -
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

        <!-- كروت الطلبات -->
        @if($orders->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($orders as $index => $order)
                    <div id="order-{{ $order->id }}" class="panel border-2 border-green-500 dark:border-green-600">
                        <!-- هيدر الكارت -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="text-lg font-bold text-primary dark:text-primary-light">
                                        رقم الطلب: {{ $order->order_number }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    #{{ $orders->firstItem() + $index }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-outline-success">مقيد</span>
                            </div>
                        </div>

                        <!-- معلومات الزبون -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الزبون</span>
                                <p class="font-medium">{{ $order->customer_name }}</p>
                            </div>
                        </div>

                        <!-- المندوب -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المندوب</span>
                                @if($order->delegate)
                                <p class="font-medium">{{ $order->delegate->name }}</p>
                                @else
                                    <p class="font-medium text-gray-400">-</p>
                                @endif
                                @if($order->delivery_code)
                                <div class="flex items-center gap-2 mt-1">
                                    <p class="text-sm text-gray-500">كود الوسيط: <span class="font-medium">{{ $order->delivery_code }}</span></p>
                                    <button
                                        type="button"
                                        onclick="copyDeliveryCode('{{ $order->delivery_code }}', 'delivery')"
                                        class="btn btn-xs btn-outline-primary flex items-center gap-1"
                                        title="نسخ كود الوسيط"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        نسخ
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- المجهز -->
                        @if($order->confirmedBy)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المجهز</span>
                                    <p class="font-medium">{{ $order->confirmedBy->name }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- التاريخ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">التاريخ</span>
                                <p class="font-medium">{{ $order->created_at->format('Y-m-d') }}</p>
                                <p class="text-sm text-gray-500">{{ $order->created_at->format('H:i') }}</p>
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="flex gap-2 flex-wrap">
                            <a href="{{ route('admin.orders.partial-return', $order) }}" class="btn btn-sm btn-warning flex-1" title="إرجاع جزئي">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                إرجاع جزئي
                            </a>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary flex-1" title="عرض">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                عرض
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <x-pagination :items="$orders" />
        @else
            <div class="panel">
                <div class="flex flex-col items-center justify-center py-10">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">لا توجد طلبات مقيدة</h3>
                    <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على أي طلبات مقيدة تطابق معايير البحث</p>
                </div>
            </div>
        @endif
    </div>

    <script>
        function copyDeliveryCode(text, type = '') {
            // تحديد نوع الرسالة
            let successMessage = 'تم النسخ بنجاح!';
            let errorMessage = 'فشل في النسخ';

            if (type === 'order') {
                successMessage = 'تم نسخ رقم الطلب بنجاح!';
                errorMessage = 'فشل في نسخ رقم الطلب';
            } else if (type === 'delivery') {
                successMessage = 'تم نسخ كود الوسيط بنجاح!';
                errorMessage = 'فشل في نسخ كود الوسيط';
            }

            // إنشاء عنصر مؤقت
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            // تحديد النص ونسخه
            textarea.select();
            textarea.setSelectionRange(0, 99999); // للجوال

            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textarea);

                if (successful) {
                    // إظهار رسالة نجاح
                    if (typeof showNotification === 'function') {
                        showNotification(successMessage, 'success');
                    } else {
                        alert(successMessage);
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            } catch (err) {
                document.body.removeChild(textarea);
                // استخدام Clipboard API كبديل
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (typeof showNotification === 'function') {
                            showNotification(successMessage, 'success');
                        } else {
                            alert(successMessage);
                        }
                    }).catch(() => {
                        if (typeof showNotification === 'function') {
                            showNotification(errorMessage, 'error');
                        } else {
                            alert(errorMessage);
                        }
                    });
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            }
        }
    </script>
</x-layout.admin>

