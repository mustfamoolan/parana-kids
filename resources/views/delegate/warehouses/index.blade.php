<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">المخازن المتاحة</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمنتجات
                </a>
            </div>
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
                                    <a href="{{ route('delegate.warehouses.products.index', $warehouse) }}" class="btn btn-sm btn-outline-primary">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        عرض المنتجات
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                لا توجد مخازن متاحة لك
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warehouses->hasPages())
            <div class="mt-4">
                {{ $warehouses->links() }}
            </div>
        @endif
    </div>
</x-layout.default>
