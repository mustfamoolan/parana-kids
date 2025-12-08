<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الشحنة #{{ $shipment->alwaseet_order_id }}</h5>
            <a href="{{ url()->previous() }}" class="btn btn-outline-primary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- معلومات العميل -->
            <div class="panel">
                <h6 class="mb-4 text-lg font-semibold">معلومات العميل</h6>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">الاسم</label>
                        <p class="font-semibold">{{ $shipment->client_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">رقم الهاتف</label>
                        <p class="font-semibold">{{ $shipment->client_mobile }}</p>
                    </div>
                    @if($shipment->client_mobile2)
                        <div>
                            <label class="text-sm font-medium text-gray-500">رقم الهاتف الثاني</label>
                            <p class="font-semibold">{{ $shipment->client_mobile2 }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="text-sm font-medium text-gray-500">المدينة</label>
                        <p class="font-semibold">{{ $shipment->city_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">المنطقة</label>
                        <p class="font-semibold">{{ $shipment->region_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">الموقع</label>
                        <p class="font-semibold">{{ $shipment->location }}</p>
                    </div>
                </div>
            </div>

            <!-- معلومات الشحنة -->
            <div class="panel">
                <h6 class="mb-4 text-lg font-semibold">معلومات الشحنة</h6>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">الحالة</label>
                        <p>
                            <span class="badge {{ $shipment->status_badge_class }}">{{ $shipment->status }}</span>
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">السعر الإجمالي</label>
                        <p class="font-semibold text-lg">{{ number_format($shipment->price, 0) }} د.ع</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">رسوم التوصيل</label>
                        <p class="font-semibold">{{ number_format($shipment->delivery_price, 0) }} د.ع</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">حجم الطرد</label>
                        <p class="font-semibold">{{ $shipment->package_size }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">نوع البضاعة</label>
                        <p class="font-semibold">{{ $shipment->type_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">عدد القطع</label>
                        <p class="font-semibold">{{ $shipment->items_number }}</p>
                    </div>
                    @if($shipment->replacement)
                        <div>
                            <span class="badge badge-outline-warning">طلب استبدال</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- الملاحظات -->
        @if($shipment->merchant_notes || $shipment->issue_notes)
            <div class="panel mt-5">
                <h6 class="mb-4 text-lg font-semibold">الملاحظات</h6>
                @if($shipment->merchant_notes)
                    <div class="mb-3">
                        <label class="text-sm font-medium text-gray-500">ملاحظات التاجر</label>
                        <p class="mt-1">{{ $shipment->merchant_notes }}</p>
                    </div>
                @endif
                @if($shipment->issue_notes)
                    <div>
                        <label class="text-sm font-medium text-gray-500">ملاحظات المشكلة</label>
                        <p class="mt-1 text-warning">{{ $shipment->issue_notes }}</p>
                    </div>
                @endif
            </div>
        @endif

        <!-- الربط بطلب -->
        <div class="panel mt-5">
            <h6 class="mb-4 text-lg font-semibold">ربط بطلب في النظام</h6>
            @if($shipment->isLinked())
                <div class="mb-4 rounded-lg bg-success/10 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-success">مربوط بطلب</p>
                            <p class="text-sm text-gray-600">طلب رقم: {{ $shipment->order->order_number }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.alwaseet.unlink-order', $shipment->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('هل أنت متأكد من إلغاء الربط؟')">
                                إلغاء الربط
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form method="POST" action="{{ route('admin.alwaseet.link-order', $shipment->id) }}">
                    @csrf
                    <div class="mb-4">
                        <label for="order_id" class="mb-2 block text-sm font-medium">اختر الطلب</label>
                        <select name="order_id" id="order_id" class="form-select" required>
                            <option value="">-- اختر طلب --</option>
                            @foreach($availableOrders as $order)
                                <option value="{{ $order->id }}">
                                    {{ $order->order_number }} - {{ $order->customer_name }} ({{ number_format($order->total_amount, 0) }} د.ع)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">ربط بالطلب</button>
                </form>
            @endif
        </div>

        <!-- معلومات إضافية -->
        <div class="panel mt-5">
            <h6 class="mb-4 text-lg font-semibold">معلومات إضافية</h6>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm font-medium text-gray-500">تاريخ الإنشاء في الواسط</label>
                    <p class="font-semibold">{{ $shipment->alwaseet_created_at?->format('Y-m-d H:i:s') ?? 'غير متوفر' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">آخر تحديث</label>
                    <p class="font-semibold">{{ $shipment->alwaseet_updated_at?->format('Y-m-d H:i:s') ?? 'غير متوفر' }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">تاريخ المزامنة</label>
                    <p class="font-semibold">{{ $shipment->synced_at?->format('Y-m-d H:i:s') ?? 'غير متوفر' }}</p>
                </div>
                @if($shipment->qr_link)
                    <div>
                        <label class="text-sm font-medium text-gray-500">رابط QR</label>
                        <p>
                            <a href="{{ route('admin.alwaseet.receipts.download', $shipment->id) }}"
                               class="btn btn-sm btn-success"
                               download>
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                تحميل PDF
                            </a>
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout.admin>

