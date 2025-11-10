<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h5 class="text-lg font-semibold dark:text-white-light">فواتير المورد: {{ $supplier->name }}</h5>
                @if($supplier->privateWarehouse)
                    <p class="text-sm text-gray-500 mt-1">المخزن الخاص: {{ $supplier->privateWarehouse->name }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمستخدمين
                </a>
            </div>
        </div>

        @if($invoices->count() > 0)
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>عدد العناصر</th>
                            <th>المبلغ الإجمالي</th>
                            <th>المنشئ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td>
                                    <span class="font-semibold">{{ $invoice->invoice_number }}</span>
                                </td>
                                <td>{{ $invoice->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span class="badge badge-outline-primary">{{ $invoice->items->count() }}</span>
                                </td>
                                <td>
                                    <span class="font-semibold">{{ number_format($invoice->total_amount, 2) }} يوان</span>
                                </td>
                                <td>{{ $invoice->creator->name }}</td>
                                <td>
                                    <a href="{{ route('admin.invoices.pdf', $invoice->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        تحميل PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-lg font-medium text-gray-500">لا توجد فواتير لهذا المورد</p>
            </div>
        @endif
    </div>
</x-layout.admin>

