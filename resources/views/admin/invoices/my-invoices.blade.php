<x-layout.admin>
    @if(session('success'))
        <div class="alert alert-success mb-5">
            {{ session('success') }}
        </div>
    @endif

    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                @if(isset($privateWarehouse) && $privateWarehouse)
                    <h5 class="text-lg font-semibold dark:text-white-light">فواتير المخزن الخاص: {{ $privateWarehouse->name }}</h5>
                    @if($privateWarehouse->description)
                        <p class="text-sm text-gray-500 mt-1">{{ $privateWarehouse->description }}</p>
                    @endif
                @else
                    <h5 class="text-lg font-semibold dark:text-white-light">الفواتير المحفوظة</h5>
                    <p class="text-sm text-gray-500 mt-1">جميع الفواتير التي قمت بإنشائها</p>
                @endif
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if(isset($privateWarehouse) && $privateWarehouse)
                    <a href="{{ route('admin.invoices.index', ['private_warehouse_id' => $privateWarehouse->id]) }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة للمخزن
                    </a>
                @else
                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة لإنشاء فاتورة
                    </a>
                @endif
            </div>
        </div>

        @if($invoices->count() > 0)
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>رقم الفاتورة</th>
                            <th>تاريخ الإنشاء</th>
                            <th>منشئ الفاتورة</th>
                            <th>عدد العناصر</th>
                            <th>المبلغ الإجمالي</th>
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
                                    @if($invoice->creator)
                                        <span class="font-medium">{{ $invoice->creator->name }}</span>
                                    @else
                                        <span class="text-gray-400">غير معروف</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-outline-primary">{{ $invoice->items->count() }}</span>
                                </td>
                                <td>
                                    <span class="font-semibold">{{ number_format($invoice->total_amount, 2) }} يوان</span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="{{ route('admin.invoices.pdf', $invoice->id) }}" class="btn btn-sm btn-primary" target="_blank">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            تحميل PDF
                                        </a>
                                        @if(auth()->check() && auth()->user()->isAdmin())
                                        <a href="{{ route('admin.invoices.edit', $invoice->id) }}" class="btn btn-sm btn-warning">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            تعديل
                                        </a>
                                        <form method="POST" action="{{ route('admin.invoices.destroy', $invoice->id) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الفاتورة؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                                حذف
                                            </button>
                                        </form>
                                        @endif
                                    </div>
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
                <p class="text-lg font-medium text-gray-500">لا توجد فواتير محفوظة</p>
                <p class="text-sm text-gray-400 mt-2">ابدأ بإنشاء فاتورة جديدة من صفحة إنشاء الفاتورة</p>
            </div>
        @endif
    </div>
</x-layout.admin>

