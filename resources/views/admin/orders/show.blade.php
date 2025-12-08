<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    // التحقق من back_route أولاً (الأفضل - يعمل على أي بيئة)
                    $backRoute = request()->query('back_route');
                    $backParams = request()->query('back_params');
                    $backUrl = null;

                    if ($backRoute && \Illuminate\Support\Facades\Route::has($backRoute)) {
                        $params = $backParams ? json_decode(urldecode($backParams), true) : [];
                        if (!is_array($params)) {
                            $params = [];
                        }
                        $backUrl = route($backRoute, $params);
                    } else {
                        // إذا لم يكن back_route موجوداً، نستخدم back_url القديم
                        $backUrl = request()->query('back_url');
                        if ($backUrl) {
                            $backUrl = urldecode($backUrl);
                            // Security check: ensure the URL is from the same domain
                            $parsed = parse_url($backUrl);
                            $currentHost = request()->getHost();
                            if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                                $backUrl = null;
                            }
                        }
                    }

                    if (!$backUrl) {
                        if ($order->trashed()) {
                            $backUrl = route('admin.orders.management', ['status' => 'deleted']) . '#order-' . $order->id;
                        } else {
                            $backUrl = route('admin.orders.management') . '#order-' . $order->id;
                        }
                    }
                @endphp
                @if($order->trashed())
                    <!-- للطلبات المحذوفة: زر العودة فقط -->
                    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة للطلبات المحذوفة
                    </a>
                @else
                    <!-- للطلبات غير المحذوفة: جميع الأزرار -->
                    <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة للطلبات
                    </a>
                    @php
                        $backUrlParam = request()->query('back_url') ? '&back_url=' . urlencode(request()->query('back_url')) : '';
                    @endphp
                    @if($order->status === 'confirmed')
                        <a href="{{ route('admin.orders.management', ['status' => 'confirmed']) }}{{ $backUrlParam }}" class="btn btn-outline-success">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            الطلبات المقيدة
                        </a>
                    @elseif($order->status === 'cancelled')
                        <a href="{{ route('admin.orders.management', ['status' => 'cancelled']) }}{{ $backUrlParam }}" class="btn btn-outline-danger">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            الطلبات الملغية
                        </a>
                    @elseif($order->status === 'returned')
                        <a href="{{ route('admin.orders.management', ['status' => 'returned']) }}{{ $backUrlParam }}" class="btn btn-outline-warning">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                            </svg>
                            الطلبات المسترجعة
                        </a>
                    @elseif($order->status === 'exchanged')
                        <a href="{{ route('admin.orders.management', ['status' => 'exchanged']) }}{{ $backUrlParam }}" class="btn btn-outline-info">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            الطلبات المستبدلة
                        </a>
                    @endif
                @endif
            </div>
        </div>

        <!-- حالة التدقيق والتأكيد للطلبات غير المقيدة -->
        @if($order->status === 'pending' && auth()->user()->isAdminOrSupplier())
            <div class="panel mb-5">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">حالة التدقيق والتأكيد</h6>
                </div>

                <!-- حالة التدقيق -->
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تدقيق القياس</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400" id="sizeReviewStatusText">{{ $order->size_review_status_text }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <select id="sizeReviewSelect" class="form-select" onchange="updateReviewStatus({{ $order->id }}, 'size_reviewed', this.value)">
                            <option value="not_reviewed" {{ $order->size_reviewed === 'not_reviewed' ? 'selected' : '' }}>لم يتم التدقيق</option>
                            <option value="reviewed" {{ $order->size_reviewed === 'reviewed' ? 'selected' : '' }}>تم تدقيق القياس</option>
                        </select>
                    </div>
                </div>

                <!-- حالة الرسالة -->
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">حالة الرسالة</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400" id="messageConfirmStatusText">{{ $order->message_confirmation_status_text }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <select id="messageConfirmSelect" class="form-select" onchange="updateReviewStatus({{ $order->id }}, 'message_confirmed', this.value)">
                            <option value="not_sent" {{ $order->message_confirmed === 'not_sent' ? 'selected' : '' }}>لم يرسل الرسالة</option>
                            <option value="waiting_response" {{ $order->message_confirmed === 'waiting_response' ? 'selected' : '' }}>تم الارسال رسالة وبالانتضار الرد</option>
                            <option value="not_confirmed" {{ $order->message_confirmed === 'not_confirmed' ? 'selected' : '' }}>لم يتم التاكيد الرسالة</option>
                            <option value="confirmed" {{ $order->message_confirmed === 'confirmed' ? 'selected' : '' }}>تم تاكيد الرسالة</option>
                        </select>
                    </div>
                </div>
            </div>
        @elseif($order->status === 'pending')
            <div class="panel mb-5">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">حالة التدقيق والتأكيد</h6>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">تدقيق القياس:</span>
                        <span class="badge {{ $order->size_review_status_badge_class }}">{{ $order->size_review_status_text }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">حالة الرسالة:</span>
                        <span class="badge {{ $order->message_confirmation_status_badge_class }}">{{ $order->message_confirmation_status_text }}</span>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- معلومات الطلب -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الطلب</h6>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رقم الطلب:</span>
                        <span class="font-medium">{{ $order->order_number }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">تاريخ الطلب:</span>
                        <span class="font-medium">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الحالة:</span>
                        @if($order->status === 'pending')
                            <span class="badge badge-warning">غير مقيد</span>
                        @else
                            <span class="badge badge-success">مقيد</span>
                        @endif
                    </div>
                    @if($order->status === 'confirmed')
                        <div class="flex items-start justify-between">
                            <span class="text-gray-500 dark:text-gray-400">كود الوسيط:</span>
                            <div class="flex flex-col gap-2">
                                <span class="font-medium font-mono text-success">{{ $order->delivery_code }}</span>
                                <button onclick="copyToClipboard('{{ $order->delivery_code }}')" class="btn btn-sm btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    نسخ
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ التقييد:</span>
                            <span class="font-medium">{{ $order->confirmed_at->format('Y-m-d H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المقيد بواسطة:</span>
                            <span class="font-medium">{{ $order->confirmedBy->name ?? 'غير محدد' }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">المندوب:</span>
                        <span class="font-medium">{{ $order->delegate->name }} ({{ $order->delegate->code }})</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الإجمالي الكلي:</span>
                        <span class="font-bold text-primary">{{ number_format($order->total_amount, 0) }} دينار عراقي</span>
                    </div>

                    @if($order->status === 'confirmed')
                        <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-semibold text-green-800 dark:text-green-200">الطلب مقيد</span>
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">
                                <p>تم تقييد الطلب في: {{ $order->confirmed_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->confirmedBy->name ?? 'غير محدد' }}</p>
                                @if($order->delivery_code)
                                    <p>كود الوسيط: {{ $order->delivery_code }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($order->status === 'cancelled')
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span class="font-semibold text-red-800 dark:text-red-200">الطلب ملغي</span>
                            </div>
                            <div class="text-sm text-red-700 dark:text-red-300">
                                <p>تم إلغاء الطلب في: {{ $order->cancelled_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->processedBy->name ?? 'غير محدد' }}</p>
                                @if($order->cancellation_reason)
                                    <p>سبب الإلغاء: {{ $order->cancellation_reason }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($order->status === 'returned')
                        <div class="mt-4 p-3 bg-warning/20 border border-warning rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                <span class="font-semibold text-warning">الطلب مسترجع</span>
                            </div>
                            <div class="text-sm text-warning">
                                <p>تم إرجاع الطلب في: {{ $order->returned_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->processedBy->name ?? 'غير محدد' }}</p>
                                <p>نوع الإرجاع: {{ $order->is_partial_return ? 'جزئي' : 'كلي' }}</p>
                                @if($order->return_notes)
                                    <p>ملاحظات: {{ $order->return_notes }}</p>
                                @endif
                            </div>
                        </div>
                    @elseif($order->status === 'exchanged')
                        <div class="mt-4 p-3 bg-info/20 border border-info rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                </svg>
                                <span class="font-semibold text-info">الطلب مستبدل</span>
                            </div>
                            <div class="text-sm text-info">
                                <p>تم استبدال الطلب في: {{ $order->exchanged_at->format('Y-m-d H:i') }}</p>
                                <p>بواسطة: {{ $order->processedBy->name ?? 'غير محدد' }}</p>
                                <p>نوع الاستبدال: {{ $order->is_partial_exchange ? 'جزئي' : 'كلي' }}</p>
                            </div>
                        </div>
                    @endif

                    @if($order->trashed() && $order->deletion_reason)
                        <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span class="font-semibold text-red-800 dark:text-red-200">الطلب محذوف</span>
                            </div>
                            <div class="text-sm text-red-700 dark:text-red-300">
                                <p>تم حذف الطلب في: {{ $order->deleted_at->format('Y-m-d H:i') }}</p>
                                @if($order->deletedBy)
                                    <p>بواسطة: {{ $order->deletedBy->name }}</p>
                                @endif
                                <p class="mt-2 font-medium">سبب الحذف:</p>
                                <p class="mt-1">{{ $order->deletion_reason }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- معلومات الزبون -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات الزبون</h6>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">الاسم:</span>
                        <div class="flex flex-col gap-2">
                            <span class="font-medium">{{ $order->customer_name }}</span>
                            <button onclick="copyToClipboard('{{ $order->customer_name }}')" class="btn btn-sm btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                        </div>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                        <div class="flex flex-col gap-2">
                            <span class="font-medium">{{ $order->customer_phone }}</span>
                            <div class="flex flex-wrap gap-2">
                                <button onclick="makePhoneCall('{{ $order->customer_phone }}')" class="btn btn-sm btn-outline-primary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    اتصال
                                </button>
                                <button onclick="openWhatsAppForOrder()" class="btn btn-sm btn-outline-success">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.570-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    واتساب
                                </button>
                                <button onclick="copyToClipboard('{{ $order->customer_phone }}')" class="btn btn-sm btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    نسخ
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($order->customer_phone2)
                    <div class="flex items-start justify-between mt-3">
                        <span class="text-gray-500 dark:text-gray-400">رقم الهاتف الثاني:</span>
                        <div class="flex flex-col gap-2">
                            <span class="font-medium">{{ $order->customer_phone2 }}</span>
                            <div class="flex flex-wrap gap-2">
                                <button onclick="makePhoneCall('{{ $order->customer_phone2 }}')" class="btn btn-sm btn-outline-primary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    اتصال
                                </button>
                                <button onclick="openWhatsAppForOrder2()" class="btn btn-sm btn-outline-success">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.570-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    واتساب
                                </button>
                                <button onclick="copyToClipboard('{{ $order->customer_phone2 }}')" class="btn btn-sm btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    نسخ
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">العنوان:</span>
                        <div class="flex flex-col gap-2">
                            <span class="font-medium text-right max-w-xs">{{ $order->customer_address }}</span>
                            <button onclick="copyToClipboard('{{ $order->customer_address }}')" class="btn btn-sm btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                نسخ
                            </button>
                        </div>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-gray-500 dark:text-gray-400">رابط السوشل ميديا:</span>
                        <div class="flex flex-col gap-2">
                            <a href="{{ $order->customer_social_link }}" target="_blank" class="font-medium text-primary hover:underline text-right max-w-xs">
                                {{ Str::limit($order->customer_social_link, 30) }}
                            </a>
                            <div class="flex flex-wrap gap-2">
                                <button onclick="openLink('{{ $order->customer_social_link }}')" class="btn btn-sm btn-outline-info">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    فتح
                                </button>
                                <button onclick="copyToClipboard('{{ $order->customer_social_link }}')" class="btn btn-sm btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    نسخ
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($order->notes)
                        <div class="flex items-start justify-between">
                            <span class="text-gray-500 dark:text-gray-400">ملاحظات:</span>
                            <div class="flex flex-col gap-2">
                                <span class="font-medium text-right max-w-xs">{{ $order->notes }}</span>
                                <button onclick="copyToClipboard('{{ $order->notes }}')" class="btn btn-sm btn-outline-secondary">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    نسخ
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- منتجات الطلب -->
        <div class="panel mt-5">
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">منتجات الطلب ({{ $order->items->count() }} منتج)</h6>
            </div>
            <div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($order->items as $item)
                        <div class="panel">
                            <div class="flex items-center gap-3 mb-3">
                                @if(optional($item->product)->primaryImage)
                                    <button type="button" class="w-20 h-20 rounded overflow-hidden" onclick="openImageZoomModal('{{ optional($item->product->primaryImage)->image_url }}','{{ optional($item->product)->name ?? $item->product_name }}')">
                                        <img src="{{ optional($item->product->primaryImage)->image_url }}" class="w-full h-full object-cover hover:opacity-90" alt="{{ optional($item->product)->name ?? $item->product_name }}">
                                    </button>
                                @else
                                    <div class="w-20 h-20 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <div class="font-semibold">{{ optional($item->product)->name ?? $item->product_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ optional($item->product)->code ?? $item->product_code }}</div>
                                </div>
                            </div>

                            <!-- القياس والعدد بجانب بعض في مربعات -->
                            <div class="mb-4 flex items-center justify-center gap-4">
                                <span class="badge badge-outline-primary text-2xl font-bold w-20 h-20 flex items-center justify-center rounded-lg border-2">{{ optional($item->size)->size_name ?? $item->size_name }}</span>
                                <span class="badge badge-outline-success text-2xl font-bold w-20 h-20 flex items-center justify-center rounded-lg border-2">{{ $item->quantity }}</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2 text-sm border-t pt-3">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">سعر الوحدة (مباشر)</span>
                                    <div class="font-medium">{{ number_format(optional($item->product)->selling_price ?? $item->unit_price, 0) }} دينار</div>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">الإجمالي (مباشر)</span>
                                    @php $liveSubtotal = (optional($item->product)->selling_price ?? $item->unit_price) * $item->quantity; @endphp
                                    <div class="font-bold text-success">{{ number_format($liveSubtotal, 0) }} دينار</div>
                                </div>
                                <div class="col-span-2 bg-gray-50 dark:bg-gray-800/50 p-2 rounded">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">المخزن</span>
                                    <div class="text-gray-700 dark:text-gray-300">{{ optional(optional($item->product)->warehouse)->name ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 panel flex items-center justify-between">
                    <div class="font-semibold">الإجمالي الكلي:</div>
                    <div class="text-lg font-bold">{{ number_format($order->total_amount, 0) }} دينار عراقي</div>
                </div>
            </div>
        </div>

        <!-- زر الحذف -->
        @if(!$order->trashed() && in_array($order->status, ['pending', 'confirmed']))
            @can('delete', $order)
                <div class="mt-6 flex justify-center">
                    <button onclick="deleteOrder({{ $order->id }})" class="btn btn-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        حذف الطلب
                    </button>
                </div>
            @endcan
        @endif
    </div>


    <script>
        // بيانات الطلب للواتساب
        const orderWhatsAppData = {
            phone: '{{ $order->customer_phone }}',
            orderNumber: '{{ $order->order_number }}',
            customerPhone: '{{ $order->customer_phone }}',
            pageName: '{{ optional($order->delegate)->page_name ?? '' }}',
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

        // دالة فتح واتساب للطلب (الرقم الثاني)
        function openWhatsAppForOrder2() {
            const phone2 = '{{ $order->customer_phone2 }}';
            if (!phone2) {
                alert('لا يوجد رقم هاتف ثاني');
                return;
            }
            openWhatsApp(phone2, orderWhatsAppData.items, orderWhatsAppData.totalAmount, orderWhatsAppData.orderNumber, phone2, orderWhatsAppData.pageName, orderWhatsAppData.deliveryFee);
        }

        // دالة نسخ النص إلى الحافظة
        function copyToClipboard(text) {
            try {
                navigator.clipboard.writeText(text).then(function() {
                    showCopyNotification('تم نسخ النص بنجاح!');
                });
            } catch (err) {
                // استخدام الطريقة القديمة إذا فشلت الطريقة الحديثة
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopyNotification('تم نسخ النص بنجاح!');
            }
        }

        // دالة فتح الرابط
        function openLink(url) {
            if (url) {
                let link = url;
                // إضافة http:// إذا لم يكن موجوداً
                if (!link.match(/^https?:\/\//)) {
                    link = 'http://' + link;
                }
                window.open(link, '_blank');
            }
        }

        // دالة الاتصال الهاتفي
        function makePhoneCall(phone) {
            // تنظيف رقم الهاتف (إزالة المسافات والرموز غير الضرورية)
            let cleanPhone = phone.replace(/[^\d+]/g, '');

            // فتح تطبيق الهاتف مع الرقم
            window.location.href = `tel:${cleanPhone}`;
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

        // دالة حذف الطلب
        function deleteOrder(orderId) {
            // إظهار modal لطلب سبب الحذف
            const modal = document.getElementById('deleteOrderModal');
            const form = document.getElementById('deleteOrderForm');
            const reasonInput = document.getElementById('deletion_reason');
            const orderIdInput = document.getElementById('delete_order_id');

            if (modal && form && reasonInput && orderIdInput) {
                orderIdInput.value = orderId;
                reasonInput.value = '';
                form.action = `/admin/orders/${orderId}`;
                modal.classList.remove('hidden');
            }
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteOrderModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function submitDeleteOrder() {
            const form = document.getElementById('deleteOrderForm');
            const reasonInput = document.getElementById('deletion_reason');

            if (!reasonInput || !reasonInput.value || reasonInput.value.trim().length < 3) {
                alert('يجب كتابة سبب الحذف (3 أحرف على الأقل)');
                return;
            }

            if (form) {
                form.submit();
            }
        }
    </script>

    <!-- Modal لتكبير الصورة -->
    <div id="imgZoomModal" class="fixed inset-0 z-[100] hidden bg-black/60 p-4">
        <div class="h-full w-full flex items-center justify-center">
            <img id="imgZoomEl" class="max-h-full max-w-full rounded-lg shadow-2xl" src="" alt="">
        </div>
    </div>

    <script>
        function openImageZoomModal(src, altText) {
            const modal = document.getElementById('imgZoomModal');
            const imgEl = document.getElementById('imgZoomEl');
            if (!src) return;
            imgEl.src = src;
            imgEl.alt = altText || '';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        (function initImageZoomModal(){
            const modal = document.getElementById('imgZoomModal');
            const imgEl = document.getElementById('imgZoomEl');
            if (!modal || !imgEl) return;
            const close = () => {
                modal.classList.add('hidden');
                imgEl.src = '';
                document.body.style.overflow = 'auto';
            };
            modal.addEventListener('click', (e)=>{ if(e.target === modal) close(); });
            document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') close(); });
        })();
    </script>

    <!-- Modal لطلب سبب الحذف -->
    <div id="deleteOrderModal" class="fixed inset-0 z-[9999] hidden bg-black/60 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold dark:text-white-light">حذف الطلب</h3>
                <button type="button" onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form id="deleteOrderForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <input type="hidden" id="delete_order_id" name="order_id" value="">

                <div class="mb-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                        سيتم إرجاع جميع المنتجات للمخزن. هل أنت متأكد من حذف هذا الطلب؟
                    </p>

                    <label for="deletion_reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        سبب الحذف <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="deletion_reason"
                        name="deletion_reason"
                        rows="4"
                        class="form-textarea w-full"
                        placeholder="اكتب سبب حذف الطلب (3 أحرف على الأقل)"
                        required
                        minlength="3"
                        maxlength="1000"
                    ></textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">يجب كتابة سبب الحذف (3-1000 حرف)</p>
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-outline-secondary">
                        إلغاء
                    </button>
                    <button type="button" onclick="submitDeleteOrder()" class="btn btn-danger">
                        حذف الطلب
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function updateReviewStatus(orderId, field, value) {
        fetch(`/admin/orders/${orderId}/review-status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ field: field, value: value })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحديث النص والـ badge حسب الحقل
                if (field === 'size_reviewed') {
                    // تحديث نص التدقيق
                    const sizeText = document.getElementById('sizeReviewStatusText');
                    if (sizeText) {
                        sizeText.textContent = data.size_review_status_text;
                    }
                } else {
                    // تحديث نص حالة الرسالة
                    const messageText = document.getElementById('messageConfirmStatusText');
                    if (messageText) {
                        messageText.textContent = data.message_confirmation_status_text;
                    }
                }
                if (typeof showCopyNotification === 'function') {
                    showCopyNotification(data.message);
                } else {
                    alert(data.message);
                }
            } else {
                if (typeof showCopyNotification === 'function') {
                    showCopyNotification('فشل في تحديث الحالة', 'error');
                } else {
                    alert('فشل في تحديث الحالة');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showCopyNotification === 'function') {
                showCopyNotification('حدث خطأ أثناء تحديث الحالة', 'error');
            } else {
                alert('حدث خطأ أثناء تحديث الحالة');
            }
        });
    }
    </script>
</x-layout.admin>
