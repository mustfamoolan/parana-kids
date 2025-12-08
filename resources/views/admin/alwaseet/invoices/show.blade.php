<x-layout.admin>
    <div class="panel">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل الفاتورة #{{ $invoice['id'] }}</h5>
                    <div class="flex gap-2">
                        @if(str_contains($invoice['status'] ?? '', 'تم الاستلام') === false)
                            <form method="POST" action="{{ route('admin.alwaseet.invoices.receive', $invoice['id']) }}" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-success" onclick="return confirm('هل أنت متأكد من تأكيد استلام هذه الفاتورة؟')">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    تأكيد الاستلام
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('admin.alwaseet.invoices.index') }}" class="btn btn-outline-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            العودة
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success mb-5">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                <!-- معلومات الفاتورة -->
                <div class="panel mb-5">
                    <h6 class="mb-4 text-lg font-semibold">معلومات الفاتورة</h6>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">رقم الفاتورة</label>
                            <p class="font-semibold">#{{ $invoice['id'] }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">المبلغ الإجمالي</label>
                            <p class="font-semibold text-lg">{{ number_format($invoice['merchant_price'], 0) }} د.ع</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">عدد الطلبات المسلمة</label>
                            <p class="font-semibold">{{ $invoice['delivered_orders_count'] }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">طلبات الاستبدال</label>
                            <p class="font-semibold">{{ $invoice['replacement_delivered_orders_count'] ?? 0 }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">الحالة</label>
                            <p>
                                <span class="badge badge-outline-primary">{{ $invoice['status'] }}</span>
                            </p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">تاريخ التحديث</label>
                            <p class="font-semibold">{{ isset($invoice['updated_at']) ? \Carbon\Carbon::parse($invoice['updated_at'])->format('Y-m-d H:i:s') : 'غير متوفر' }}</p>
                        </div>
                    </div>
                </div>

                <!-- طلبات الفاتورة -->
                <div class="panel">
                    <h6 class="mb-4 text-lg font-semibold">طلبات الفاتورة</h6>
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>اسم العميل</th>
                                    <th>رقم الهاتف</th>
                                    <th>المدينة</th>
                                    <th>المنطقة</th>
                                    <th>السعر</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <span class="font-semibold">{{ $order['id'] }}</span>
                                        </td>
                                        <td>{{ $order['client_name'] }}</td>
                                        <td>{{ $order['client_mobile'] }}</td>
                                        <td>{{ $order['city_name'] }}</td>
                                        <td>{{ $order['region_name'] }}</td>
                                        <td>
                                            <span class="font-semibold">{{ number_format($order['price'], 0) }} د.ع</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-outline-primary">{{ $order['status'] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-8 text-gray-500">
                                            لا توجد طلبات في هذه الفاتورة
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
    </div>
</x-layout.admin>

