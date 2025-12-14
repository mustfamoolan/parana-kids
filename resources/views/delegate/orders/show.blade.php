<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $backUrl = request()->query('back_url');
                    if ($backUrl) {
                        $backUrl = urldecode($backUrl);
                        // Security check: ensure the URL is from the same domain
                        $parsed = parse_url($backUrl);
                        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
                        if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                            $backUrl = null;
                        }
                    }
                    if (!$backUrl) {
                        if ($order->trashed()) {
                            $backUrl = route('delegate.orders.index', ['status' => 'deleted']) . '#order-' . $order->id;
                        } else {
                            $backUrl = route('delegate.orders.index') . '#order-' . $order->id;
                        }
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلبات
                </a>
            </div>
        </div>

        <!-- كارد سبب الحذف - للطلبات المحذوفة فقط -->
        @if($order->trashed() && $order->deletion_reason)
            <div class="panel mb-5 border-2 border-red-500 dark:border-red-600">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h6 class="text-xl font-bold text-red-600 dark:text-red-400 mb-3">سبب الحذف</h6>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 mb-3">
                            <p class="text-lg text-gray-800 dark:text-gray-200 leading-relaxed">{{ $order->deletion_reason }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">تاريخ الحذف:</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200 rtl:mr-2 ltr:ml-2">
                                    {{ $order->deleted_at->locale('ar')->translatedFormat('l') }}،
                                    {{ $order->deleted_at->format('d/m/Y') }}
                                    <span class="rtl:mr-1 ltr:ml-1">{{ $order->deleted_at->format('g:i A') }}</span>
                                </span>
                            </div>
                            @if($order->deletedByUser)
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">حذف بواسطة:</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200 rtl:mr-2 ltr:ml-2">
                                        {{ $order->deletedByUser->name }}
                                        <span class="text-xs text-gray-500">({{ $order->deletedByUser->role === 'admin' ? 'مدير' : 'مجهز' }})</span>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- معلومات الطلب الأساسية -->
        <div class="panel mb-5">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-primary">{{ $order->order_number }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">رقم الطلب</div>
                </div>
                <div class="text-center">
                    @if($order->trashed())
                        <div class="text-2xl font-bold text-danger">
                            محذوف
                        </div>
                    @else
                        <div class="text-2xl font-bold {{ $order->status === 'pending' ? 'text-warning' : 'text-success' }}">
                            {{ $order->status === 'pending' ? 'غير مقيد' : 'مقيد' }}
                        </div>
                    @endif
                    <div class="text-sm text-gray-500 dark:text-gray-400">حالة الطلب</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-success">{{ number_format($order->total_amount, 0) }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">دينار عراقي</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-info">{{ $order->items->sum('quantity') }}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي القطع</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- معلومات الزبون -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الزبون</h6>
                </div>

                <div class="space-y-4">
                    <!-- الاسم -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 dark:text-gray-400">الاسم:</span>
                            <span class="font-medium">{{ $order->customer_name }}</span>
                        </div>
                        <button type="button" onclick="copyToClipboard('{{ $order->customer_name }}')" class="btn btn-sm btn-outline-secondary w-full">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ الاسم
                        </button>
                    </div>

                    <!-- رقم الهاتف -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                            <span class="font-medium">{{ $order->customer_phone }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="copyToClipboard('{{ $order->customer_phone }}')" class="btn btn-sm btn-outline-secondary flex-1">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                            <button type="button"
                                    onclick="openWhatsAppForOrder()"
                                    class="btn btn-sm btn-success flex-1">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"></path>
                                </svg>
                                واتساب
                            </button>
                            <a href="tel:{{ $order->customer_phone }}" class="btn btn-sm btn-info flex-1">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                اتصال
                            </a>
                        </div>
                    </div>

                    <!-- العنوان -->
                    <div>
                        <div class="flex items-start justify-between mb-2">
                            <span class="text-gray-500 dark:text-gray-400">العنوان:</span>
                            <div class="text-right max-w-xs">
                                <span class="font-medium">{{ $order->customer_address }}</span>
                            </div>
                        </div>
                        <button type="button" onclick="copyToClipboard('{{ $order->customer_address }}')" class="btn btn-sm btn-outline-secondary w-full">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            نسخ العنوان
                        </button>
                    </div>

                    <!-- السوشل ميديا -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-500 dark:text-gray-400">السوشل ميديا:</span>
                            <span class="font-medium text-primary">{{ Str::limit($order->customer_social_link, 20) }}</span>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="copyToClipboard('{{ $order->customer_social_link }}')" class="btn btn-sm btn-outline-secondary flex-1">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                            <a href="{{ $order->customer_social_link }}" target="_blank" class="btn btn-sm btn-primary flex-1">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                فتح
                            </a>
                        </div>
                    </div>

                    @if($order->notes)
                        <div class="flex items-start justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ملاحظات:</span>
                            <div class="text-right max-w-xs">
                                <span class="font-medium">{{ $order->notes }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">تاريخ الطلب:</span>
                        <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                </div>
            </div>

            <!-- إحصائيات الطلب -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">إحصائيات الطلب</h6>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                        <span class="font-medium">{{ $order->items->count() }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">إجمالي القطع:</span>
                        <span class="font-medium">{{ $order->items->sum('quantity') }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">المبلغ الإجمالي:</span>
                        <span class="font-medium text-success">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">المندوب:</span>
                        @if($order->delegate)
                            <span class="font-medium">{{ $order->delegate->name }}</span>
                        @else
                            <span class="font-medium text-gray-400">-</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">السلة الأصلية:</span>
                        <span class="font-medium">{{ $order->cart->cart_name }}</span>
                    </div>

                    @if($order->status === 'confirmed' && $order->delivery_code)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-500 dark:text-gray-400">كود الوسيط:</span>
                                <span class="font-medium text-primary text-lg">{{ $order->delivery_code }}</span>
                            </div>
                            <button type="button" onclick="copyToClipboard('{{ $order->delivery_code }}')" class="btn btn-sm btn-outline-primary w-full">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ كود الوسيط
                            </button>
                        </div>
                    @endif

                    @if($order->status === 'confirmed' && $order->confirmed_at)
                        <div class="flex items-center justify-between mt-4">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ التقييد:</span>
                            <span class="font-medium">
                                {{ $order->confirmed_at->locale('ar')->translatedFormat('l') }}،
                                {{ $order->confirmed_at->format('d/m/Y') }}
                                <span class="rtl:mr-1 ltr:ml-1">{{ $order->confirmed_at->format('g:i A') }}</span>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- منتجات الطلب -->
        <div class="panel mt-6">
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">منتجات الطلب</h6>
            </div>

            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                        <!-- كارت لكل منتج -->
                        <div class="flex gap-4">
                            <!-- صورة المنتج -->
                            <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded overflow-hidden flex-shrink-0">
                                @if($item->product && $item->product->primaryImage)
                                    <img src="{{ $item->product->primaryImage->image_url }}"
                                         alt="{{ $item->product_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <div class="flex-1">
                                <!-- اسم المنتج + الكود -->
                                <h3 class="font-semibold dark:text-white-light text-lg">{{ $item->product_name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $item->product_code }}</p>

                                <!-- معلومات المنتج في grid -->
                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">القياس</span>
                                        <span class="badge badge-outline-primary">{{ $item->size_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">الكمية</span>
                                        <p class="font-medium text-lg">{{ $item->quantity }}</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">سعر الوحدة</span>
                                        <p class="font-medium">{{ number_format($item->unit_price, 0) }} د.ع</p>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المجموع</span>
                                        <p class="font-medium text-success text-lg">{{ number_format($item->subtotal, 0) }} د.ع</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- كارت الإجمالي -->
                <div class="bg-primary/10 dark:bg-primary/20 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold dark:text-white-light">الإجمالي:</span>
                        <span class="text-2xl font-bold text-success">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ملاحظة مهمة -->
        @if(!$order->trashed())
            <div class="panel mt-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-blue-500 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h6 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-2">معلومات مهمة</h6>
                        <div class="space-y-2 text-sm text-blue-600 dark:text-blue-400">
                            <p>• تم خصم جميع المنتجات من المخزون عند إرسال الطلب</p>
                            <p>• الطلب في حالة "{{ $order->status === 'pending' ? 'غير مقيد' : 'مقيد' }}" حالياً</p>
                            <p>• يمكنك التواصل مع الإدارة لتأكيد أو تعديل حالة الطلب</p>
                            <p>• جميع المعلومات محفوظة ويمكن الرجوع إليها في أي وقت</p>
                        </div>
                    </div>
                </div>

                @if($order->status === 'pending')
                    <!-- أزرار العمل للطلبات غير المقيدة -->
                    <div class="panel mt-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('delegate.orders.edit', $order) }}" class="btn btn-warning btn-lg flex-1 sm:flex-none">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                تعديل الطلب
                            </a>

                        </div>
                    </div>
                @endif

                @if($order->status === 'pending')
                    <!-- زر حذف الطلب -->
                    <div class="panel mt-6">
                        <div class="flex justify-center">
                            <button onclick="deleteOrder({{ $order->id }})" class="btn btn-outline-danger btn-lg">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                حذف الطلب
                            </button>
                        </div>
                    </div>
                @endif
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

    <script>
        // بيانات الطلب للواتساب
        const orderWhatsAppData = {
            phone: '{{ $order->customer_phone }}',
            orderNumber: '{{ $order->order_number }}',
            customerPhone: '{{ $order->customer_phone }}',
            pageName: '{{ auth()->user()->page_name ?? '' }}',
            deliveryFee: {{ \App\Models\Setting::getDeliveryFee() }},
            items: @json($order->items->map(function($item) {
                return [
                    'product_name' => $item->product_name ?? optional($item->product)->name ?? $item->product_code,
                    'product_code' => $item->product_code,
                    'unit_price' => $item->unit_price
                ];
            })),
            totalAmount: {{ $order->total_amount }}
        };

        // دالة فتح واتساب للطلب
        function openWhatsAppForOrder() {
            openWhatsApp(orderWhatsAppData.phone, orderWhatsAppData.items, orderWhatsAppData.totalAmount, orderWhatsAppData.orderNumber, orderWhatsAppData.customerPhone, orderWhatsAppData.pageName, orderWhatsAppData.deliveryFee);
        }

        // دالة نسخ النص إلى الحافظة
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
                showCopyNotification('تم نسخ النص بنجاح!');
            } catch (err) {
                // استخدام Clipboard API إذا كان متاحاً
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        showCopyNotification('تم نسخ النص بنجاح!');
                    }).catch(function() {
                        showCopyNotification('فشل في نسخ النص');
                    });
                } else {
                    showCopyNotification('فشل في نسخ النص');
                }
            }

            // إزالة العنصر المؤقت
            document.body.removeChild(textarea);
        }

        // دالة إظهار إشعار النسخ
        function showCopyNotification(message) {
            // إنشاء عنصر الإشعار
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
            notification.textContent = message;

            // إضافة الإشعار للصفحة
            document.body.appendChild(notification);

            // إزالة الإشعار بعد 3 ثوان
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // دالة بناء رسالة الواتساب
        function generateWhatsAppMessage(orderItems, totalAmount, orderNumber, customerPhone, pageName, deliveryFee) {
            let message = '📦 أهلاً وسهلاً بيكم ❤️\n';
            // استخدام اسم البيج للمندوب أو "برنا كدز" كقيمة افتراضية
            const pageNameText = pageName || 'برنا كدز';
            message += `معكم مجهز ${pageNameText} 👗\n\n`;

            // إضافة رقم الزبون
            if (customerPhone) {
                message += `رقم الهاتف: ${customerPhone}\n\n`;
            }

            // إضافة قائمة المنتجات (باسم المنتج بدلاً من الكود)
            message += 'المنتجات:\n';
            orderItems.forEach(function(item) {
                const price = new Intl.NumberFormat('en-US').format(item.unit_price);
                const productName = item.product_name || item.product_code;
                message += `- ${productName} - ${price} د.ع\n`;
            });

            // حساب المجموع الكلي مع سعر التوصيل
            const totalWithDelivery = totalAmount + deliveryFee;
            const totalFormatted = new Intl.NumberFormat('en-US').format(totalAmount);
            const totalWithDeliveryFormatted = new Intl.NumberFormat('en-US').format(totalWithDelivery);
            message += `\nالمجموع الكلي: ${totalFormatted} د.ع\n`;
            message += `سعر التوصيل: ${new Intl.NumberFormat('en-US').format(deliveryFee)} د.ع\n`;
            message += `المجموع الكلي (مع التوصيل): ${totalWithDeliveryFormatted} د.ع\n\n`;

            // إضافة طلب التأكيد
            message += 'نرجو تأكيد الطلب من خلال الرد على هذه الرسالة بكلمة "تأكيد" حتى نبدأ بتجهيز الطلب وإرساله لكم 💨\n\n';
            message += 'التوصيل خلال 24 ساعه الى 36 ساعه بعد تاكيد الطلب من خلال الوتساب\n\n';
            message += 'في حال عدم الرد خلال فترة قصيرة، سيتم إلغاء الطلب تلقائيًا.\n';
            message += 'نشكر تعاونكم ويانا 🌸';

            return message;
        }

        // دالة فتح واتساب
        function openWhatsApp(phone, orderItems, totalAmount, orderNumber, customerPhone, pageName, deliveryFee) {
            // تنظيف رقم الهاتف (إزالة المسافات والرموز)
            let cleanPhone = phone.replace(/[^\d]/g, '');

            // إضافة كود الدولة 964 للعراق إذا لم يكن موجوداً
            if (!cleanPhone.startsWith('964')) {
                // إذا بدأ الرقم بـ 0، استبدله بـ 964
                if (cleanPhone.startsWith('0')) {
                    cleanPhone = '964' + cleanPhone.substring(1);
                } else if (cleanPhone.length < 12) {
                    cleanPhone = '964' + cleanPhone;
                }
            }

            // بناء الرسالة
            const message = generateWhatsAppMessage(orderItems, totalAmount, orderNumber, customerPhone, pageName, deliveryFee);
            const whatsappUrl = `https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`;

            // فتح واتساب في نافذة جديدة
            window.open(whatsappUrl, '_blank');
        }
    </script>
</x-layout.default>
