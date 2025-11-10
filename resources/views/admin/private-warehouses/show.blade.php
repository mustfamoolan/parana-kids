<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المخزن الخاص: {{ $privateWarehouse->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    عرض المخزن المخصص
                </a>
                <a href="{{ route('admin.private-warehouses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
                @can('update', $privateWarehouse)
                    <a href="{{ route('admin.private-warehouses.edit', $privateWarehouse) }}" class="btn btn-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        تعديل
                    </a>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- معلومات المخزن -->
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">معلومات المخزن</h6>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">الاسم:</span>
                        <div class="font-medium text-lg">{{ $privateWarehouse->name }}</div>
                    </div>

                    @if($privateWarehouse->description)
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">الوصف:</span>
                        <div class="font-medium">{{ $privateWarehouse->description }}</div>
                    </div>
                    @endif

                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">المنشئ:</span>
                        <div class="font-medium">{{ $privateWarehouse->creator->name }}</div>
                    </div>

                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                        <div class="font-medium">{{ $privateWarehouse->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- الإحصائيات -->
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">الإحصائيات</h6>
                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">عدد الموردين:</span>
                        <div class="font-medium text-lg">
                            <span class="badge badge-outline-primary">{{ $privateWarehouse->users->count() }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                        <div class="font-medium text-lg">
                            <span class="badge badge-outline-success">{{ $privateWarehouse->invoiceProducts->count() }}</span>
                        </div>
                    </div>

                    <div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">عدد الفواتير:</span>
                        <div class="font-medium text-lg">
                            <span class="badge badge-outline-info">{{ $privateWarehouse->invoices->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الموردين -->
        @if($privateWarehouse->users->count() > 0)
        <div class="panel mt-5">
            <h6 class="text-lg font-semibold mb-4">الموردين المرتبطين</h6>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الكود</th>
                            <th>رقم الهاتف</th>
                            <th>البريد الإلكتروني</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($privateWarehouse->users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td><span class="badge badge-outline-primary">{{ $user->code }}</span></td>
                                <td>{{ $user->phone }}</td>
                                <td>{{ $user->email ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.users.invoices', $user->id) }}" class="btn btn-sm btn-info">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        فواتير
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-layout.admin>

