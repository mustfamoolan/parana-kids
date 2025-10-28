<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">منتجات مخزن: {{ $warehouse->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمخازن
                </a>
                @can('create', App\Models\Product::class)
                    <a href="{{ route('admin.warehouses.products.create', $warehouse) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة منتج جديد
                    </a>
                @endcan
            </div>
        </div>

        <!-- ملاحظة العملة العراقية -->
        <div class="mb-5">
            <div class="alert alert-info">
                <div class="flex items-start">
                    <svg class="w-5 h-5 ltr:mr-3 rtl:ml-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h6 class="font-semibold">ملاحظة مهمة حول العملة</h6>
                        <p class="text-sm">نحن في العراق وعملتنا هي الدينار العراقي. لا توجد فاصلة عشرية في العملة العراقية، لذلك المبالغ تظهر كأرقام صحيحة (مثل: 1000 دينار عراقي بدلاً من 1000.00).</p>
                    </div>
                </div>
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

        <!-- معلومات المخزن -->
        <div class="mb-5 panel">
            <div class="flex items-center justify-between">
                <div>
                    <h6 class="text-base font-semibold dark:text-white-light">{{ $warehouse->name }}</h6>
                    <p class="text-gray-500 dark:text-gray-400">{{ $warehouse->location }}</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">إجمالي المنتجات</div>
                    <div class="text-2xl font-bold text-primary">{{ $products->total() }}</div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم المنتج</th>
                        <th>الكود</th>
                        @if(auth()->user()->isAdmin())
                            <th>سعر الشراء</th>
                        @endif
                        <th>سعر البيع</th>
                        <th>الكمية الإجمالية</th>
                        <th>القياسات</th>
                        <th>الصور</th>
                        <th>رابط 1688</th>
                        <th>المنشئ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>
                                <div class="whitespace-nowrap font-medium">{{ $product->name }}</div>
                                @if($product->description)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($product->description, 50) }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-outline-primary">{{ $product->code }}</span>
                            </td>
                            @if(auth()->user()->isAdmin())
                                <td>
                                    @if($product->purchase_price)
                                        <span class="font-medium">{{ number_format($product->purchase_price, 0) }} دينار عراقي</span>
                                    @else
                                        <span class="text-gray-400">غير محدد</span>
                                    @endif
                                </td>
                            @endif
                            <td>
                                <span class="font-medium text-success">{{ number_format($product->selling_price, 0) }} دينار عراقي</span>
                            </td>
                            <td>
                                <span class="badge badge-outline-success">{{ $product->total_quantity }}</span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($product->sizes as $size)
                                        <span class="badge badge-outline-secondary text-xs">
                                            {{ $size->size_name }} ({{ $size->quantity }})
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                @if($product->images->count() > 0)
                                    <div class="flex items-center">
                                        <img
                                            src="{{ $product->images->first()->image_url }}"
                                            alt="{{ $product->name }}"
                                            class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-700"
                                        >
                                        @if($product->images->count() > 1)
                                            <span class="badge badge-outline-info text-xs mr-1">+{{ $product->images->count() - 1 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">لا توجد صور</span>
                                @endif
                            </td>
                            <td>
                                @if($product->link_1688)
                                    <div class="flex gap-1">
                                        <button onclick="copyLink('{{ $product->link_1688 }}')"
                                                class="btn btn-xs btn-outline-secondary" title="نسخ الرابط">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                        <a href="{{ $product->link_1688 }}" target="_blank"
                                           class="btn btn-xs btn-primary" title="فتح الرابط">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="whitespace-nowrap">{{ $product->creator->name }}</div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    @can('view', $product)
                                        <a href="{{ route('admin.warehouses.products.show', [$warehouse, $product]) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                            @can('update', $product)
                                                <a href="{{ route('admin.warehouses.products.edit', [$warehouse, $product]) }}" class="btn btn-sm btn-outline-warning" title="تعديل">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                    @endcan

                                    @can('delete', $product)
                                        <form method="POST" action="{{ route('admin.warehouses.products.destroy', [$warehouse, $product]) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف">
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
                            <td colspan="{{ auth()->user()->isAdmin() ? '11' : '10' }}" class="text-center py-8 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                لا توجد منتجات في هذا المخزن
                                @can('create', App\Models\Product::class)
                                    <div class="mt-4">
                                        <a href="{{ route('admin.warehouses.products.create', $warehouse) }}" class="btn btn-primary">
                                            إضافة أول منتج
                                        </a>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <x-pagination :items="$products" />
    </div>

    <script>
        function copyLink(url) {
            navigator.clipboard.writeText(url).then(() => {
                showToast('تم نسخ الرابط!');
            });
        }

        function showToast(message) {
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-success text-white px-4 py-2 rounded shadow-lg z-50 text-sm';
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 2000);
        }
    </script>
</x-layout.admin>
