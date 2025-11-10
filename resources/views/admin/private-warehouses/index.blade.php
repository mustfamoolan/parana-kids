<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">قائمة المخازن الخاصة</h5>
            @can('create', App\Models\PrivateWarehouse::class)
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <a href="{{ route('admin.private-warehouses.create') }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة مخزن خاص جديد
                    </a>
                </div>
            @endcan
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- كروت الإحصائيات -->
        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $warehouses->count() }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي المخازن الخاصة</div>
                    </div>
                    <div class="rounded-full bg-primary/10 p-3">
                        <svg class="h-8 w-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $warehouses->sum('users_count') }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي الموردين</div>
                    </div>
                    <div class="rounded-full bg-success/10 p-3">
                        <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $warehouses->sum('invoices_count') }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي الفواتير</div>
                    </div>
                    <div class="rounded-full bg-info/10 p-3">
                        <svg class="h-8 w-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- كاردات المخازن الخاصة -->
        @if($warehouses->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($warehouses as $warehouse)
                    <div class="panel">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4 pb-4 border-b">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-semibold text-lg">{{ $warehouse->name }}</div>
                                    <div class="text-xs text-gray-500">#{{ $warehouse->id }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="space-y-3">
                            @if($warehouse->description)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">الوصف:</span>
                                    <div class="font-medium">{{ Str::limit($warehouse->description, 50) }}</div>
                                </div>
                            @endif

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">المنشئ:</span>
                                <div class="font-medium">{{ $warehouse->creator->name }}</div>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">عدد الموردين:</span>
                                <div><span class="badge badge-outline-primary">{{ $warehouse->users_count ?? $warehouse->users->count() }}</span></div>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                                <div><span class="badge badge-outline-success">{{ $warehouse->invoice_products_count ?? $warehouse->invoiceProducts->count() }}</span></div>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">عدد الفواتير:</span>
                                <div><span class="badge badge-outline-info">{{ $warehouse->invoices_count ?? $warehouse->invoices->count() }}</span></div>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                                <div class="text-sm">{{ $warehouse->created_at->format('Y-m-d') }}</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t">
                            @can('view', $warehouse)
                                <a href="{{ route('admin.private-warehouses.show', $warehouse) }}" class="btn btn-sm btn-outline-primary flex-1" title="عرض التفاصيل">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            @endcan

                            @can('update', $warehouse)
                                <a href="{{ route('admin.private-warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-warning flex-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                            @endcan

                            @can('delete', $warehouse)
                                <form method="POST" action="{{ route('admin.private-warehouses.destroy', $warehouse) }}" class="flex-1" onsubmit="return confirm('هل أنت متأكد من حذف هذا المخزن الخاص؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-full">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p class="text-lg font-medium text-gray-500">لا توجد مخازن خاصة متاحة</p>
            </div>
        @endif
    </div>
</x-layout.admin>

