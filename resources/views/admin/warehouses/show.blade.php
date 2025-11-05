<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المخزن: {{ $warehouse->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
                @can('update', $warehouse)
                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-outline-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        تعديل
                    </a>
                @endcan
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.warehouses.destroy', $warehouse) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المخزن؟ سيتم حذف جميع المنتجات والبيانات المرتبطة به')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            حذف المخزن
                        </button>
                    </form>
                @endif
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

        <!-- التخفيض العام للمخزن -->
        @if(auth()->user()->isAdmin())
        <div class="panel mb-5">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h6 class="text-lg font-semibold dark:text-white-light mb-1">تخفيض عام للمخزن</h6>
                        <p class="text-sm text-gray-500 dark:text-gray-400">تحديد سعر موحد لجميع منتجات المخزن خلال فترة زمنية</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    @if($activePromotion)
                        <div class="text-sm text-gray-600 dark:text-gray-400 rtl:text-left ltr:text-right">
                            <div class="font-medium">السعر: {{ number_format($activePromotion->promotion_price, 0) }} د.ع</div>
                            <div class="text-xs">من {{ $activePromotion->start_date->setTimezone('Asia/Baghdad')->format('Y-m-d H:i') }}</div>
                            <div class="text-xs">إلى {{ $activePromotion->end_date->setTimezone('Asia/Baghdad')->format('Y-m-d H:i') }}</div>
                        </div>
                    @endif
                    <label class="w-12 h-6 relative">
                        <input type="checkbox"
                               id="promotionToggle"
                               class="custom_switch absolute w-full h-full opacity-0 z-10 cursor-pointer peer"
                               {{ $activePromotion && $activePromotion->isActive() ? 'checked' : '' }}>
                        <span for="promotionToggle"
                              class="bg-[#ebedf2] dark:bg-dark block h-full rounded-full before:absolute rtl:before:right-1 ltr:before:left-1 before:bg-white dark:before:bg-white-dark dark:peer-checked:before:bg-white before:bottom-1 before:w-4 before:h-4 before:rounded-full peer-checked:rtl:before:right-7 peer-checked:ltr:before:left-7 peer-checked:bg-primary before:transition-all before:duration-300"></span>
                    </label>
                    <button type="button" id="editPromotionBtn"
                            class="btn btn-sm btn-outline-primary {{ $activePromotion ? '' : 'hidden' }}">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        تعديل
                    </button>
                </div>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <!-- معلومات المخزن -->
            <div class="lg:col-span-2">
                <div class="panel">
                    <div class="mb-5">
                        <h6 class="text-lg font-semibold dark:text-white-light">معلومات المخزن</h6>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">اسم المخزن:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">الموقع:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->location }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المنشئ:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->creator->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->created_at->setTimezone('Asia/Baghdad')->format('Y-m-d H:i') }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">آخر تحديث:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->updated_at->setTimezone('Asia/Baghdad')->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الإحصائيات -->
            <div>
                <div class="panel">
                    <div class="mb-5">
                        <h6 class="text-lg font-semibold dark:text-white-light">الإحصائيات</h6>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->products->count() }} منتج</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">إجمالي القطع:</span>
                            <span class="font-medium text-black dark:text-white">{{ number_format($totalPieces) }} قطعة</span>
                        </div>

                        @if(auth()->user()->isAdmin())
                            <div class="border-t pt-4 mt-4">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">السعر الكلي للبيع:</span>
                                    </div>
                                    <div class="text-2xl font-bold text-black dark:text-white">
                                        {{ number_format($totalSellingPrice, 0) }}
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">دينار عراقي</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">السعر الكلي للشراء:</span>
                                    </div>
                                    <div class="text-2xl font-bold text-black dark:text-white">
                                        {{ number_format($totalPurchasePrice, 0) }}
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">دينار عراقي</span>
                                    </div>
                                </div>

                                @if($totalSellingPrice > 0 && $totalPurchasePrice > 0)
                                    <div class="mt-4 pt-4 border-t">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-gray-500 dark:text-gray-400">الربح المتوقع:</span>
                                        </div>
                                        <div class="text-2xl font-bold text-black dark:text-white">
                                            {{ number_format($totalSellingPrice - $totalPurchasePrice, 0) }}
                                            <span class="text-sm font-normal text-gray-600 dark:text-gray-400">دينار عراقي</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- المنتجات -->
        <div class="panel mt-5">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h6 class="text-lg font-semibold dark:text-white-light">منتجات المخزن</h6>
                @can('create', App\Models\Product::class)
                    <a href="{{ route('admin.warehouses.products.create', $warehouse) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة منتج جديد
                    </a>
                @endcan
            </div>

            <!-- البحث والفلترة -->
            <form method="GET" action="{{ route('admin.warehouses.show', $warehouse) }}" class="mb-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- حقل البحث -->
                    <div>
                        <label for="search" class="block text-sm font-medium mb-2">البحث</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            class="form-input w-full"
                            placeholder="ابحث بكود المنتج أو القياس أو اسم المنتج..."
                            value="{{ $searchTerm ?? '' }}"
                        >
                    </div>

                    <!-- فلتر النوع -->
                    <div>
                        <label for="gender_type" class="block text-sm font-medium mb-2">نوع المنتج</label>
                        <select
                            id="gender_type"
                            name="gender_type"
                            class="form-select w-full"
                        >
                            <option value="">كل الأنواع</option>
                            <option value="boys" {{ ($genderTypeFilter ?? '') == 'boys' ? 'selected' : '' }}>ولادي</option>
                            <option value="girls" {{ ($genderTypeFilter ?? '') == 'girls' ? 'selected' : '' }}>بناتي</option>
                            <option value="boys_girls" {{ ($genderTypeFilter ?? '') == 'boys_girls' ? 'selected' : '' }}>ولادي بناتي</option>
                            <option value="accessories" {{ ($genderTypeFilter ?? '') == 'accessories' ? 'selected' : '' }}>اكسسوار</option>
                        </select>
                    </div>

                    <!-- أزرار البحث والمسح -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            بحث
                        </button>
                        @if($searchTerm || $genderTypeFilter)
                            <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                مسح
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            @if($warehouse->products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($warehouse->products as $product)
                        <div class="panel">
                            <div class="flex items-center gap-3 mb-3">
                                @if($product->primaryImage)
                                    <button type="button" onclick="openImageModal('{{ $product->primaryImage->image_url }}', '{{ $product->name }}')" class="w-16 h-16 flex-shrink-0 rounded overflow-hidden">
                                        <img src="{{ $product->primaryImage->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover hover:opacity-90 cursor-pointer">
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
                                    <div class="font-medium text-sm">
                                        @if($activePromotion && $activePromotion->is_active && now()->between($activePromotion->start_date, $activePromotion->end_date))
                                            <span class="text-success">{{ number_format($product->effective_price, 0) }} د.ع</span>
                                            <span class="text-xs text-gray-400 line-through rtl:mr-2 ltr:ml-2">{{ number_format($product->selling_price, 0) }}</span>
                                        @else
                                            {{ number_format($product->effective_price, 0) }} د.ع
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">الكمية الإجمالية:</span>
                                    <div><span class="badge badge-outline-success">{{ $product->total_quantity }}</span></div>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">المنشئ:</span>
                                    <div class="text-sm">{{ $product->creator->name }}</div>
                                </div>
                            </div>
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
                    @if($searchTerm || $genderTypeFilter)
                        <p class="text-lg font-medium text-gray-500">لا توجد منتجات تطابق معايير البحث والفلترة المحددة</p>
                        <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-outline-primary mt-4">
                            عرض جميع المنتجات
                        </a>
                    @else
                        <p class="text-lg font-medium text-gray-500">لا توجد منتجات في هذا المخزن</p>
                    @endif
                </div>
            @endif
        </div>
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

    <!-- Modal للتخفيض -->
    @if(auth()->user()->isAdmin())
    <div id="promotionModal" class="fixed inset-0 bg-black/80 z-[9999] hidden items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="promotionModalTitle" class="text-lg font-semibold dark:text-white-light">تخفيض عام للمخزن</h3>
                <button onclick="closePromotionModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="promotionForm" class="p-4">
                <div class="space-y-4">
                    <div>
                        <label for="promotion_price" class="block text-sm font-medium mb-2">السعر الموحد (دينار عراقي)</label>
                        <input type="number" id="promotion_price" name="promotion_price"
                               class="form-input w-full" min="0" step="1" required
                               placeholder="مثال: 1000">
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium mb-2">تاريخ البداية</label>
                        <input type="datetime-local" id="start_date" name="start_date"
                               class="form-input w-full" required>
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium mb-2">تاريخ النهاية</label>
                        <input type="datetime-local" id="end_date" name="end_date"
                               class="form-input w-full" required>
                    </div>
                </div>

                <div class="flex gap-2 mt-6">
                    <button type="submit" class="btn btn-primary flex-1">حفظ</button>
                    <button type="button" onclick="closePromotionModal()" class="btn btn-outline-secondary">إلغاء</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <script>
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

        @if(auth()->user()->isAdmin())
        // Promotion Management
        const promotionToggle = document.getElementById('promotionToggle');
        const editPromotionBtn = document.getElementById('editPromotionBtn');
        const promotionModal = document.getElementById('promotionModal');
        const promotionForm = document.getElementById('promotionForm');
        const promotionModalTitle = document.getElementById('promotionModalTitle');
        @php
            $promotionData = null;
            if ($activePromotion && $activePromotion->isActive()) {
                $promotionData = [
                    'id' => $activePromotion->id,
                    'promotion_price' => $activePromotion->promotion_price,
                    'start_date' => $activePromotion->start_date->setTimezone('Asia/Baghdad')->format('Y-m-d\TH:i'),
                    'end_date' => $activePromotion->end_date->setTimezone('Asia/Baghdad')->format('Y-m-d\TH:i'),
                    'is_active' => $activePromotion->is_active,
                ];
            }
        @endphp
        let currentPromotion = @json($promotionData);

        // التأكد من أن toggle يطابق حالة التخفيض
        if (promotionToggle && !currentPromotion) {
            promotionToggle.checked = false;
        }

        function openPromotionModal(isEdit = false) {
            if (isEdit && currentPromotion) {
                promotionModalTitle.textContent = 'تعديل التخفيض';
                document.getElementById('promotion_price').value = currentPromotion.promotion_price;
                document.getElementById('start_date').value = currentPromotion.start_date;
                document.getElementById('end_date').value = currentPromotion.end_date;
            } else {
                promotionModalTitle.textContent = 'تخفيض عام للمخزن';
                promotionForm.reset();
                // تعيين القيم الافتراضية
                const now = new Date();
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('start_date').value = now.toISOString().slice(0, 16);
                document.getElementById('end_date').value = tomorrow.toISOString().slice(0, 16);
            }
            promotionModal.classList.remove('hidden');
            promotionModal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closePromotionModal() {
            promotionModal.classList.add('hidden');
            promotionModal.classList.remove('flex');
            document.body.style.overflow = 'auto';
            promotionForm.reset();
        }

        // Toggle Promotion
        if (promotionToggle) {
            promotionToggle.addEventListener('change', function() {
                if (this.checked) {
                    if (!currentPromotion) {
                        // إذا كان التخفيض غير موجود، افتح modal لإنشاء واحد
                        openPromotionModal(false);
                    } else if (!currentPromotion.is_active) {
                        // إذا كان التخفيض موجود لكن غير نشط، قم بتفعيله
                        togglePromotion();
                    }
                } else {
                    // إذا تم إيقاف toggle، قم بإيقاف التخفيض
                    if (currentPromotion && currentPromotion.is_active) {
                        togglePromotion();
                    }
                }
            });
        }

        // Edit Promotion Button
        if (editPromotionBtn) {
            editPromotionBtn.addEventListener('click', function() {
                openPromotionModal(true);
            });
        }

        // Toggle Promotion (activate/deactivate)
        function togglePromotion() {
            fetch('{{ route("admin.warehouses.promotion.toggle", $warehouse) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'حدث خطأ أثناء التبديل');
                    promotionToggle.checked = !promotionToggle.checked;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('حدث خطأ أثناء التبديل');
                promotionToggle.checked = !promotionToggle.checked;
            });
        }

        // Submit Promotion Form
        if (promotionForm) {
            promotionForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = {
                    promotion_price: document.getElementById('promotion_price').value,
                    start_date: document.getElementById('start_date').value,
                    end_date: document.getElementById('end_date').value,
                };

                const isEdit = currentPromotion && currentPromotion.id;
                let url;
                if (isEdit) {
                    const baseUrl = '{{ route("admin.warehouses.promotion.update", [$warehouse, 0]) }}';
                    url = baseUrl.replace('/0', '/' + currentPromotion.id);
                } else {
                    url = '{{ route("admin.warehouses.promotion.store", $warehouse) }}';
                }
                const method = isEdit ? 'PUT' : 'POST';

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closePromotionModal();
                        window.location.reload();
                    } else {
                        alert(data.message || 'حدث خطأ أثناء الحفظ');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء الحفظ');
                });
            });
        }

        // Close modal on outside click
        if (promotionModal) {
            promotionModal.addEventListener('click', function(e) {
                if (e.target === promotionModal) {
                    closePromotionModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !promotionModal.classList.contains('hidden')) {
                    closePromotionModal();
                }
            });
        }
        @endif
    </script>
</x-layout.admin>
