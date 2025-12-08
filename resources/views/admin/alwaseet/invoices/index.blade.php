<x-layout.admin>
    <div class="panel">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">الفواتير</h5>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mb-5">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- جدول الفواتير -->
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>المبلغ الإجمالي</th>
                                <th>عدد الطلبات المسلمة</th>
                                <th>طلبات الاستبدال</th>
                                <th>الحالة</th>
                                <th>تاريخ التحديث</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                                <tr>
                                    <td>
                                        <span class="font-semibold">#{{ $invoice['id'] }}</span>
                                    </td>
                                    <td>
                                        <span class="font-semibold">{{ number_format($invoice['merchant_price'], 0) }} د.ع</span>
                                    </td>
                                    <td>{{ $invoice['delivered_orders_count'] }}</td>
                                    <td>{{ $invoice['replacement_delivered_orders_count'] ?? 0 }}</td>
                                    <td>
                                        <span class="badge badge-outline-primary">{{ $invoice['status'] }}</span>
                                    </td>
                                    <td>
                                        {{ isset($invoice['updated_at']) ? \Carbon\Carbon::parse($invoice['updated_at'])->format('Y-m-d H:i') : 'غير متوفر' }}
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="{{ route('admin.alwaseet.invoices.show', $invoice['id']) }}" class="btn btn-sm btn-outline-primary">
                                                تفاصيل
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-8 text-gray-500">
                                        <div class="flex flex-col items-center gap-2">
                                            <p class="text-lg font-semibold">لا توجد فواتير</p>
                                            <p class="text-sm">لا توجد فواتير متاحة حالياً</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
    </div>
</x-layout.admin>

