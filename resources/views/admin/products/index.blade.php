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

        <!-- كاردات المنتجات -->
        @if($products->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($products as $product)
                    <div class="panel">
                        <!-- Header -->
                        <div class="flex items-center gap-3 mb-3 relative">
                            @if($product->images->count() > 0)
                                <button type="button" onclick="openImageModal('{{ $product->images->first()->image_url }}', '{{ $product->name }}')" class="w-16 h-16 flex-shrink-0 rounded overflow-hidden relative">
                                    <img src="{{ $product->images->first()->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover hover:opacity-90 cursor-pointer">
                                    @if($product->images->count() > 1)
                                        <div class="absolute top-0 left-0 bg-info text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold">
                                            +{{ $product->images->count() - 1 }}
                                        </div>
                                    @endif
                                </button>
                            @else
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm truncate">{{ $product->name }}</div>
                                <div class="text-xs text-gray-500">#{{ $product->id }}</div>
                                <div class="mt-1"><span class="badge badge-outline-primary text-xs">{{ $product->code }}</span></div>
                            </div>
                        </div>

                        @if($product->description)
                            <div class="mb-3 pb-3 border-b">
                                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2">{{ Str::limit($product->description, 80) }}</p>
                            </div>
                        @endif

                        <!-- Content -->
                        <div class="space-y-2">
                            @if(auth()->user()->isAdmin())
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">سعر الشراء:</span>
                                    <div>
                                        @if($product->purchase_price)
                                            <span class="font-medium text-info text-sm">{{ number_format($product->purchase_price, 0) }} د.ع</span>
                                        @else
                                            <span class="text-gray-400 text-sm">غير محدد</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">سعر البيع:</span>
                                <div class="font-medium text-success text-sm">{{ number_format($product->selling_price, 0) }} د.ع</div>
                            </div>

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">الكمية الإجمالية:</span>
                                <div><span class="badge badge-outline-success">{{ $product->total_quantity }}</span></div>
                            </div>

                            @if($product->sizes->count() > 0)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">القياسات:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($product->sizes as $size)
                                            <span class="badge badge-outline-secondary text-xs">
                                                {{ $size->size_name }} ({{ $size->quantity }})
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($product->link_1688)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">رابط 1688:</span>
                                    <div class="flex gap-1 mt-1">
                                        <button onclick="copyLink('{{ $product->link_1688 }}')" class="btn btn-xs btn-outline-secondary" title="نسخ الرابط">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                        <a href="{{ $product->link_1688 }}" target="_blank" class="btn btn-xs btn-primary" title="فتح الرابط">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">المنشئ:</span>
                                <div class="text-sm">{{ $product->creator->name }}</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 mt-3 pt-3 border-t">
                            @can('view', $product)
                                <a href="{{ route('admin.warehouses.products.show', [$warehouse, $product]) }}" class="btn btn-sm btn-outline-primary flex-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                            @endcan

                            @can('update', $product)
                                <a href="{{ route('admin.warehouses.products.edit', [$warehouse, $product]) }}" class="btn btn-sm btn-outline-warning flex-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
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
                <p class="text-lg font-medium text-gray-500 mb-2">لا توجد منتجات في هذا المخزن</p>
                @can('create', App\Models\Product::class)
                    <a href="{{ route('admin.warehouses.products.create', $warehouse) }}" class="btn btn-primary mt-4">
                        إضافة أول منتج
                    </a>
                @endcan
            </div>
        @endif

        <!-- Pagination -->
        <x-pagination :items="$products" />
    </div>

    <!-- Modal لتكبير الصورة -->
    <div id="imageModal" class="fixed inset-0 bg-black/80 z-[9999] hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-4xl max-h-full overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="modalTitle" class="text-lg font-semibold dark:text-white-light">صورة المنتج</h3>
                <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <img id="modalImage" src="" alt="" class="max-w-full max-h-[70vh] mx-auto object-contain rounded">
            </div>
        </div>
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

        function openImageModal(imageUrl, productName) {
            const modal = document.getElementById('imageModal');
            if (!modal) return;

            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalImage').alt = productName || 'صورة المنتج';
            document.getElementById('modalTitle').textContent = productName || 'صورة المنتج';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            if (!modal) return;

            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('imageModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeImageModal();
                    }
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeImageModal();
                    }
                });
            }
        });
    </script>
</x-layout.admin>
