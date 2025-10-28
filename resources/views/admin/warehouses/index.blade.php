<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">قائمة المخازن</h5>
            @can('create', App\Models\Warehouse::class)
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة مخزن جديد
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

        <!-- كروت الإحصائيات -->
        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $totalWarehouses }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي المخازن</div>
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
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalProducts) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي المنتجات</div>
                    </div>
                    <div class="rounded-full bg-success/10 p-3">
                        <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ number_format($totalPieces) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">إجمالي القطع</div>
                    </div>
                    <div class="rounded-full bg-info/10 p-3">
                        <svg class="h-8 w-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- فلتر البحث -->
        <div class="panel mb-5">
            <form method="GET" action="{{ route('admin.warehouses.index') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="warehouse_id" class="mb-2 block text-sm font-medium">فلترة حسب المخزن</label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select">
                        <option value="">جميع المخازن</option>
                        @foreach($warehousesList as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-1">
                    <label for="product_search" class="mb-2 block text-sm font-medium">بحث عن منتج</label>
                    <input type="text" name="product_search" id="product_search" value="{{ request('product_search') }}" placeholder="اسم أو كود المنتج" class="form-input">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <svg class="h-4 w-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        بحث
                    </button>

                    @if(request()->hasAny(['warehouse_id', 'product_search']))
                        <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                            إعادة تعيين
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم المخزن</th>
                        <th>الموقع</th>
                        <th>المنشئ</th>
                        <th>عدد المنتجات</th>
                        <th>تاريخ الإنشاء</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $warehouse)
                        <tr>
                            <td>{{ $warehouse->id }}</td>
                            <td>
                                <div class="whitespace-nowrap font-medium">{{ $warehouse->name }}</div>
                            </td>
                            <td>
                                <div class="whitespace-nowrap">{{ $warehouse->location }}</div>
                            </td>
                            <td>
                                <div class="whitespace-nowrap">{{ $warehouse->creator->name }}</div>
                            </td>
                            <td>
                                <span class="badge badge-outline-primary">{{ $warehouse->products_count ?? $warehouse->products->count() }}</span>
                            </td>
                            <td>
                                <div class="whitespace-nowrap">{{ $warehouse->created_at->format('Y-m-d') }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @can('view', $warehouse)
                                        <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                    <a href="{{ route('admin.warehouses.products.index', $warehouse) }}" class="btn btn-sm btn-outline-info" title="عرض المنتجات">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                    </a>

                                    @can('update', $warehouse)
                                        <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-warning">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('manage', $warehouse)
                                        <a href="{{ route('admin.warehouses.assign-users', $warehouse) }}" class="btn btn-sm btn-outline-info">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('delete', $warehouse)
                                        <form method="POST" action="{{ route('admin.warehouses.destroy', $warehouse) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المخزن؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                لا توجد مخازن متاحة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <x-pagination :items="$warehouses" />
    </div>
</x-layout.admin>
