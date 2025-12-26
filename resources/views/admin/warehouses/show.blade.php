<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المخزن: {{ $warehouse->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $backUrl = request()->query('back_url');
                    if ($backUrl) {
                        $backUrl = urldecode($backUrl);
                        $parsed = parse_url($backUrl);
                        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
                        if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                            $backUrl = null;
                        }
                    }
                    if (!$backUrl) {
                        $backUrl = route('admin.warehouses.index');
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
                @can('update', $warehouse)
                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}?back_url={{ urlencode(request()->fullUrl()) }}" class="btn btn-outline-warning">
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
                            @if($activePromotion->discount_type === 'percentage')
                                <div class="font-medium">التخفيض: {{ number_format($activePromotion->discount_percentage, 2) }}%</div>
                            @else
                                <div class="font-medium">السعر: {{ number_format($activePromotion->promotion_price, 0) }} د.ع</div>
                            @endif
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
                    <div class="flex gap-2">
                        <button type="button" onclick="openPromotionAmountModal(false)" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            مبلغ ثابت
                        </button>
                        <button type="button" onclick="openPromotionPercentageModal(false)" class="btn btn-success btn-sm">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            نسبة مئوية
                        </button>
                    </div>
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

        <!-- إحصائيات المنتجات -->
        <div class="mb-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- المنتجات المخفضة -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $productsWithDiscount }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">المنتجات المخفضة</div>
                    </div>
                    <div class="rounded-full bg-warning/10 p-3">
                        <svg class="h-8 w-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المنتجات المحجوبة -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $productsHidden }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">المنتجات المحجوبة</div>
                    </div>
                    <div class="rounded-full bg-danger/10 p-3">
                        <svg class="h-8 w-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المنتجات غير المحجوبة -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $productsNotHidden }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">المنتجات غير المحجوبة</div>
                    </div>
                    <div class="rounded-full bg-success/10 p-3">
                        <svg class="h-8 w-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- المنتجات غير المخفضة -->
            <div class="panel">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xl font-bold text-black dark:text-white">{{ $productsWithoutDiscount }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">المنتجات غير المخفضة</div>
                    </div>
                    <div class="rounded-full bg-info/10 p-3">
                        <svg class="h-8 w-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
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
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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

                    <!-- فلتر المنتجات المحجوبة -->
                    <div>
                        <label for="is_hidden" class="block text-sm font-medium mb-2">حالة الحجب</label>
                        <select
                            id="is_hidden"
                            name="is_hidden"
                            class="form-select w-full"
                        >
                            <option value="">الكل</option>
                            <option value="0" {{ ($isHiddenFilter ?? '') === '0' ? 'selected' : '' }}>غير محجوبة</option>
                            <option value="1" {{ ($isHiddenFilter ?? '') === '1' ? 'selected' : '' }}>محجوبة</option>
                        </select>
                    </div>

                    <!-- فلتر المنتجات المخفضة -->
                    <div>
                        <label for="has_discount" class="block text-sm font-medium mb-2">التخفيض</label>
                        <select
                            id="has_discount"
                            name="has_discount"
                            class="form-select w-full"
                        >
                            <option value="">الكل</option>
                            <option value="0" {{ ($hasDiscountFilter ?? '') === '0' ? 'selected' : '' }}>بدون تخفيض</option>
                            <option value="1" {{ ($hasDiscountFilter ?? '') === '1' ? 'selected' : '' }}>مخفضة</option>
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
                        @if($searchTerm || $genderTypeFilter || $isHiddenFilter || $hasDiscountFilter)
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

            @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($products as $product)
                        <div class="panel" id="product-{{ $product->id }}">
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
                                    <div class="mt-1 flex items-center gap-2 flex-wrap">
                                        <span class="badge badge-outline-primary text-xs">{{ $product->code }}</span>
                                        @if($product->is_hidden)
                                            <span class="badge badge-danger text-xs">محجوب</span>
                                        @endif
                                        @if($product->hasActiveDiscount())
                                            @php
                                                $discountInfo = $product->getDiscountInfo();
                                            @endphp
                                            <span class="badge badge-warning text-xs" title="@if($discountInfo['type'] === 'percentage') تخفيض {{ number_format($discountInfo['percentage'], 1) }}% @else تخفيض {{ number_format($discountInfo['discount_amount'], 0) }} د.ع @endif">
                                                مخفض
                                            </span>
                                        @endif
                                    </div>
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
                                        @if($product->hasActiveDiscount())
                                            @php
                                                $discountInfo = $product->getDiscountInfo();
                                            @endphp
                                            <div class="flex flex-col gap-1">
                                                <div>
                                                    <span class="text-success">{{ number_format($product->effective_price, 0) }} د.ع</span>
                                                    <span class="text-xs text-gray-400 line-through rtl:mr-2 ltr:ml-2">{{ number_format($product->selling_price, 0) }}</span>
                                                </div>
                                                <div class="text-xs text-warning">
                                                    @if($discountInfo['type'] === 'percentage')
                                                        تخفيض {{ number_format($discountInfo['percentage'], 1) }}%
                                                    @else
                                                        تخفيض {{ number_format($discountInfo['discount_amount'], 0) }} د.ع
                                                    @endif
                                                </div>
                                            </div>
                                        @elseif($activePromotion && $activePromotion->is_active && now()->between($activePromotion->start_date, $activePromotion->end_date))
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
                                    <a href="{{ route('admin.warehouses.products.show', [$warehouse, $product]) }}?back_url={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-outline-primary flex-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                @endcan
                                @can('update', $product)
                                    <a href="{{ route('admin.warehouses.products.edit', [$warehouse, $product]) }}?back_url={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-outline-warning flex-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                @endcan
                            </div>
                            @if(auth()->user()->isAdmin())
                            <div class="flex flex-col gap-2 mt-2">
                                <button type="button" onclick="toggleProductHidden({{ $product->id }}, {{ $product->is_hidden ? 'true' : 'false' }})"
                                        class="btn btn-sm {{ $product->is_hidden ? 'btn-success' : 'btn-outline-danger' }} w-full">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.736m0 0L21 21"></path>
                                    </svg>
                                    {{ $product->is_hidden ? 'إلغاء الحجب' : 'حجب' }}
                                </button>
                                @if($product->hasActiveDiscount())
                                    <div class="flex gap-2">
                                        <button type="button" onclick="openProductDiscount{{ $product->discount_type === 'percentage' ? 'Percentage' : 'Amount' }}Modal({{ $product->id }})"
                                                class="btn btn-sm btn-outline-warning flex-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            تعديل
                                        </button>
                                        <button type="button" onclick="removeProductDiscount({{ $product->id }})"
                                                class="btn btn-sm btn-outline-danger flex-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            إلغاء
                                        </button>
                                    </div>
                                @else
                                    <div class="flex gap-2">
                                        <button type="button" onclick="openProductDiscountAmountModal({{ $product->id }})"
                                                class="btn btn-sm btn-outline-warning flex-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            مبلغ
                                        </button>
                                        <button type="button" onclick="openProductDiscountPercentageModal({{ $product->id }})"
                                                class="btn btn-sm btn-outline-success flex-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                            نسبة
                                        </button>
                                    </div>
                                @endif
                            </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $products->links() }}
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

    <!-- Modal للتخفيض - المبلغ الثابت -->
    @if(auth()->user()->isAdmin())
    <div id="promotionAmountModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] hidden items-center justify-center p-3 sm:p-4 md:p-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full max-h-[95vh] overflow-hidden shadow-2xl transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 md:p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-primary/10 to-primary/5">
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-primary/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="promotionAmountModalTitle" class="text-base sm:text-lg font-bold dark:text-white-light truncate">تخفيض عام - مبلغ ثابت</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">تطبيق سعر موحد على جميع منتجات المخزن</p>
                    </div>
                </div>
                <button onclick="closePromotionAmountModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0 rtl:mr-2 ltr:ml-2">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="promotionAmountForm" class="p-4 sm:p-5 md:p-6 overflow-y-auto max-h-[calc(95vh-120px)]" novalidate>
                <input type="hidden" name="discount_type" value="amount">
                <div class="space-y-4 sm:space-y-5">
                    <!-- السعر الموحد -->
                    <div>
                        <label for="promotion_price" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            السعر الموحد
                            <span class="text-xs font-normal text-gray-500 hidden sm:inline">(دينار عراقي)</span>
                        </label>
                        <div class="relative">
                            <input type="number" id="promotion_price" name="promotion_price"
                                   class="form-input w-full text-sm sm:text-base pl-10 rtl:pl-0 rtl:pr-10" min="0" step="1"
                                   placeholder="أدخل المبلغ..." required>
                            <span class="absolute rtl:right-3 ltr:left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">د.ع</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 rtl:mr-1 ltr:ml-1">
                            سيتم تطبيق هذا السعر على جميع منتجات المخزن
                        </p>
                    </div>

                    <!-- تاريخ البداية -->
                    <div>
                        <label for="start_date_amount" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ البداية
                        </label>
                        <input type="datetime-local" id="start_date_amount" name="start_date"
                               class="form-input w-full text-sm sm:text-base" required>
                    </div>

                    <!-- تاريخ النهاية -->
                    <div>
                        <label for="end_date_amount" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ النهاية
                        </label>
                        <input type="datetime-local" id="end_date_amount" name="end_date"
                               class="form-input w-full text-sm sm:text-base" required>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" id="promotionAmountSubmitBtn" class="btn btn-primary flex-1 gap-2 order-2 sm:order-1">
                        <svg id="promotionAmountSubmitIcon" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg id="promotionAmountSubmitSpinner" class="w-4 h-4 sm:w-5 sm:h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="promotionAmountSubmitText" class="text-sm sm:text-base">حفظ التخفيض</span>
                    </button>
                    <button type="button" onclick="closePromotionAmountModal()" class="btn btn-outline-secondary order-1 sm:order-2">
                        <span class="text-sm sm:text-base">إلغاء</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal للتخفيض - النسبة المئوية -->
    <div id="promotionPercentageModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] hidden items-center justify-center p-3 sm:p-4 md:p-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full max-h-[95vh] overflow-hidden shadow-2xl transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 md:p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-success/10 to-success/5">
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-success/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="promotionPercentageModalTitle" class="text-base sm:text-lg font-bold dark:text-white-light truncate">تخفيض عام - نسبة مئوية</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">تطبيق نسبة تخفيض على جميع منتجات المخزن</p>
                    </div>
                </div>
                <button onclick="closePromotionPercentageModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0 rtl:mr-2 ltr:ml-2">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="promotionPercentageForm" class="p-4 sm:p-5 md:p-6 overflow-y-auto max-h-[calc(95vh-120px)]" novalidate>
                <input type="hidden" name="discount_type" value="percentage">
                <div class="space-y-4 sm:space-y-5">
                    <!-- نسبة التخفيض -->
                    <div>
                        <label for="discount_percentage" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            نسبة التخفيض (%)
                        </label>
                        <div class="relative">
                            <input type="number" id="discount_percentage" name="discount_percentage"
                                   class="form-input w-full text-sm sm:text-base pr-10 rtl:pr-0 rtl:pl-10" min="0.01" max="100" step="0.01"
                                   placeholder="أدخل النسبة..." required>
                            <span class="absolute rtl:left-3 ltr:right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">%</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 rtl:mr-1 ltr:ml-1">
                            سيتم خصم هذه النسبة من السعر الأصلي لكل منتج (من 0.01% إلى 100%)
                        </p>
                    </div>

                    <!-- تاريخ البداية -->
                    <div>
                        <label for="start_date_percentage" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ البداية
                        </label>
                        <input type="datetime-local" id="start_date_percentage" name="start_date"
                               class="form-input w-full text-sm sm:text-base" required>
                    </div>

                    <!-- تاريخ النهاية -->
                    <div>
                        <label for="end_date_percentage" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ النهاية
                        </label>
                        <input type="datetime-local" id="end_date_percentage" name="end_date"
                               class="form-input w-full text-sm sm:text-base" required>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" id="promotionPercentageSubmitBtn" class="btn btn-primary flex-1 gap-2 order-2 sm:order-1">
                        <svg id="promotionPercentageSubmitIcon" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg id="promotionPercentageSubmitSpinner" class="w-4 h-4 sm:w-5 sm:h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="promotionPercentageSubmitText" class="text-sm sm:text-base">حفظ التخفيض</span>
                    </button>
                    <button type="button" onclick="closePromotionPercentageModal()" class="btn btn-outline-secondary order-1 sm:order-2">
                        <span class="text-sm sm:text-base">إلغاء</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Modal لتخفيض المنتج - المبلغ الثابت -->
    @if(auth()->user()->isAdmin())
    <div id="productDiscountAmountModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] hidden items-center justify-center p-3 sm:p-4 md:p-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full max-h-[95vh] overflow-hidden shadow-2xl transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 md:p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-warning/10 to-warning/5">
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-warning/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="productDiscountAmountModalTitle" class="text-base sm:text-lg font-bold dark:text-white-light truncate">تخفيض المنتج - مبلغ ثابت</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">تطبيق تخفيض بمبلغ ثابت على هذا المنتج</p>
                    </div>
                </div>
                <button onclick="closeProductDiscountAmountModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0 rtl:mr-2 ltr:ml-2">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="productDiscountAmountForm" class="p-4 sm:p-5 md:p-6 overflow-y-auto max-h-[calc(95vh-120px)]" novalidate>
                <input type="hidden" name="discount_type" value="amount">
                <div class="space-y-4 sm:space-y-5">
                    <!-- قيمة التخفيض -->
                    <div>
                        <label for="product_discount_amount_value" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            قيمة التخفيض (دينار عراقي)
                        </label>
                        <div class="relative">
                            <input type="number" id="product_discount_amount_value" name="discount_value"
                                   class="form-input w-full text-sm sm:text-base pl-10 rtl:pl-0 rtl:pr-10" min="0" step="1"
                                   placeholder="أدخل المبلغ..." required>
                            <span class="absolute rtl:right-3 ltr:left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">د.ع</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 rtl:mr-1 ltr:ml-1">
                            سيتم خصم هذا المبلغ من السعر الأصلي للمنتج
                        </p>
                    </div>

                    <!-- تاريخ البداية -->
                    <div>
                        <label for="product_discount_amount_start_date" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ البداية (اختياري)
                        </label>
                        <input type="datetime-local" id="product_discount_amount_start_date" name="discount_start_date"
                               class="form-input w-full text-sm sm:text-base">
                    </div>

                    <!-- تاريخ النهاية -->
                    <div>
                        <label for="product_discount_amount_end_date" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ النهاية (اختياري)
                        </label>
                        <input type="datetime-local" id="product_discount_amount_end_date" name="discount_end_date"
                               class="form-input w-full text-sm sm:text-base">
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" id="productDiscountAmountSubmitBtn" class="btn btn-primary flex-1 gap-2 order-2 sm:order-1">
                        <svg id="productDiscountAmountSubmitIcon" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg id="productDiscountAmountSubmitSpinner" class="w-4 h-4 sm:w-5 sm:h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="productDiscountAmountSubmitText" class="text-sm sm:text-base">حفظ التخفيض</span>
                    </button>
                    <button type="button" onclick="closeProductDiscountAmountModal()" class="btn btn-outline-secondary order-1 sm:order-2">
                        <span class="text-sm sm:text-base">إلغاء</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal لتخفيض المنتج - النسبة المئوية -->
    <div id="productDiscountPercentageModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[9999] hidden items-center justify-center p-3 sm:p-4 md:p-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-lg w-full max-h-[95vh] overflow-hidden shadow-2xl transform transition-all">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 sm:p-5 md:p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-success/10 to-success/5">
                <div class="flex items-center gap-2 sm:gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-success/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="productDiscountPercentageModalTitle" class="text-base sm:text-lg font-bold dark:text-white-light truncate">تخفيض المنتج - نسبة مئوية</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 hidden sm:block">تطبيق تخفيض بنسبة مئوية على هذا المنتج</p>
                    </div>
                </div>
                <button onclick="closeProductDiscountPercentageModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0 rtl:mr-2 ltr:ml-2">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form id="productDiscountPercentageForm" class="p-4 sm:p-5 md:p-6 overflow-y-auto max-h-[calc(95vh-120px)]" novalidate>
                <input type="hidden" name="discount_type" value="percentage">
                <div class="space-y-4 sm:space-y-5">
                    <!-- نسبة التخفيض -->
                    <div>
                        <label for="product_discount_percentage_value" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            نسبة التخفيض (%)
                        </label>
                        <div class="relative">
                            <input type="number" id="product_discount_percentage_value" name="discount_value"
                                   class="form-input w-full text-sm sm:text-base pr-10 rtl:pr-0 rtl:pl-10" min="0.01" max="100" step="0.01"
                                   placeholder="أدخل النسبة..." required>
                            <span class="absolute rtl:left-3 ltr:right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">%</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 rtl:mr-1 ltr:ml-1">
                            سيتم خصم هذه النسبة من السعر الأصلي للمنتج (من 0.01% إلى 100%)
                        </p>
                    </div>

                    <!-- تاريخ البداية -->
                    <div>
                        <label for="product_discount_percentage_start_date" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ البداية (اختياري)
                        </label>
                        <input type="datetime-local" id="product_discount_percentage_start_date" name="discount_start_date"
                               class="form-input w-full text-sm sm:text-base">
                    </div>

                    <!-- تاريخ النهاية -->
                    <div>
                        <label for="product_discount_percentage_end_date" class="block text-sm font-semibold mb-2 text-gray-700 dark:text-gray-300">
                            <svg class="w-4 h-4 inline-block ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            تاريخ النهاية (اختياري)
                        </label>
                        <input type="datetime-local" id="product_discount_percentage_end_date" name="discount_end_date"
                               class="form-input w-full text-sm sm:text-base">
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button type="submit" id="productDiscountPercentageSubmitBtn" class="btn btn-primary flex-1 gap-2 order-2 sm:order-1">
                        <svg id="productDiscountPercentageSubmitIcon" class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <svg id="productDiscountPercentageSubmitSpinner" class="w-4 h-4 sm:w-5 sm:h-5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="productDiscountPercentageSubmitText" class="text-sm sm:text-base">حفظ التخفيض</span>
                    </button>
                    <button type="button" onclick="closeProductDiscountPercentageModal()" class="btn btn-outline-secondary order-1 sm:order-2">
                        <span class="text-sm sm:text-base">إلغاء</span>
                    </button>
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
        const promotionAmountModal = document.getElementById('promotionAmountModal');
        const promotionPercentageModal = document.getElementById('promotionPercentageModal');
        const promotionAmountForm = document.getElementById('promotionAmountForm');
        const promotionPercentageForm = document.getElementById('promotionPercentageForm');
        @php
            $promotionData = null;
            if ($activePromotion && $activePromotion->isActive()) {
                $promotionData = [
                    'id' => $activePromotion->id,
                    'discount_type' => $activePromotion->discount_type ?? 'amount',
                    'promotion_price' => $activePromotion->promotion_price,
                    'discount_percentage' => $activePromotion->discount_percentage,
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

        // فتح Modal المبلغ الثابت
        function openPromotionAmountModal(isEdit = false) {
            const modal = promotionAmountModal;
            const form = promotionAmountForm;
            const title = document.getElementById('promotionAmountModalTitle');

            if (isEdit && currentPromotion && currentPromotion.discount_type === 'amount') {
                title.textContent = 'تعديل التخفيض - مبلغ ثابت';
                document.getElementById('promotion_price').value = currentPromotion.promotion_price || '';
                document.getElementById('start_date_amount').value = currentPromotion.start_date || '';
                document.getElementById('end_date_amount').value = currentPromotion.end_date || '';
            } else {
                title.textContent = 'تخفيض عام - مبلغ ثابت';
                form.reset();
                const now = new Date();
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('start_date_amount').value = now.toISOString().slice(0, 16);
                document.getElementById('end_date_amount').value = tomorrow.toISOString().slice(0, 16);
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
                if (modalContent) {
                    modalContent.style.opacity = '0';
                    modalContent.style.transform = 'scale(0.95)';
                    requestAnimationFrame(() => {
                        modalContent.style.transition = 'opacity 0.2s ease-out, transform 0.2s ease-out';
                        modalContent.style.opacity = '1';
                        modalContent.style.transform = 'scale(1)';
                    });
                }
            }, 10);
        }

        // إغلاق Modal المبلغ الثابت
        function closePromotionAmountModal() {
            const modal = promotionAmountModal;
            const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
            if (modalContent) {
                modalContent.style.transition = 'opacity 0.15s ease-in, transform 0.15s ease-in';
                modalContent.style.opacity = '0';
                modalContent.style.transform = 'scale(0.95)';
            }
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
                promotionAmountForm.reset();
                if (modalContent) {
                    modalContent.style.opacity = '';
                    modalContent.style.transform = '';
                    modalContent.style.transition = '';
                }
            }, 150);
        }

        // فتح Modal النسبة المئوية
        function openPromotionPercentageModal(isEdit = false) {
            const modal = promotionPercentageModal;
            const form = promotionPercentageForm;
            const title = document.getElementById('promotionPercentageModalTitle');

            if (isEdit && currentPromotion && currentPromotion.discount_type === 'percentage') {
                title.textContent = 'تعديل التخفيض - نسبة مئوية';
                document.getElementById('discount_percentage').value = currentPromotion.discount_percentage || '';
                document.getElementById('start_date_percentage').value = currentPromotion.start_date || '';
                document.getElementById('end_date_percentage').value = currentPromotion.end_date || '';
            } else {
                title.textContent = 'تخفيض عام - نسبة مئوية';
                form.reset();
                const now = new Date();
                const tomorrow = new Date(now);
                tomorrow.setDate(tomorrow.getDate() + 1);
                document.getElementById('start_date_percentage').value = now.toISOString().slice(0, 16);
                document.getElementById('end_date_percentage').value = tomorrow.toISOString().slice(0, 16);
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
            setTimeout(() => {
                const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
                if (modalContent) {
                    modalContent.style.opacity = '0';
                    modalContent.style.transform = 'scale(0.95)';
                    requestAnimationFrame(() => {
                        modalContent.style.transition = 'opacity 0.2s ease-out, transform 0.2s ease-out';
                        modalContent.style.opacity = '1';
                        modalContent.style.transform = 'scale(1)';
                    });
                }
            }, 10);
        }

        // إغلاق Modal النسبة المئوية
        function closePromotionPercentageModal() {
            const modal = promotionPercentageModal;
            const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
            if (modalContent) {
                modalContent.style.transition = 'opacity 0.15s ease-in, transform 0.15s ease-in';
                modalContent.style.opacity = '0';
                modalContent.style.transform = 'scale(0.95)';
            }
            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
                promotionPercentageForm.reset();
                if (modalContent) {
                    modalContent.style.opacity = '';
                    modalContent.style.transform = '';
                    modalContent.style.transition = '';
                }
            }, 150);
        }

        // Toggle Promotion
        if (promotionToggle) {
            promotionToggle.addEventListener('change', function() {
                if (this.checked) {
                    if (!currentPromotion) {
                        // إذا كان التخفيض غير موجود، لا نفعل شيء (المستخدم يختار من الأزرار)
                        this.checked = false;
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
                if (currentPromotion && currentPromotion.discount_type === 'percentage') {
                    openPromotionPercentageModal(true);
                } else {
                    openPromotionAmountModal(true);
                }
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

        // Submit Promotion Amount Form
        if (promotionAmountForm) {
            promotionAmountForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const promotionPrice = document.getElementById('promotion_price').value;
                const startDate = document.getElementById('start_date_amount').value;
                const endDate = document.getElementById('end_date_amount').value;

                // التحقق من الحقول المطلوبة
                let isValid = true;
                let errorMessage = '';

                // التحقق من الحقول
                if (!promotionPrice || parseFloat(promotionPrice) <= 0) {
                    isValid = false;
                    errorMessage = 'يرجى إدخال مبلغ صحيح (أكبر من 0)';
                    document.getElementById('promotion_price').focus();
                } else if (!startDate || !endDate) {
                    isValid = false;
                    errorMessage = 'يرجى إدخال تاريخ البداية والنهاية';
                    if (!startDate) {
                        document.getElementById('start_date_amount').focus();
                    } else {
                        document.getElementById('end_date_amount').focus();
                    }
                } else if (new Date(startDate) >= new Date(endDate)) {
                    isValid = false;
                    errorMessage = 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية';
                    document.getElementById('end_date_amount').focus();
                }

                if (!isValid) {
                    alert(errorMessage);
                    return;
                }

                // إظهار loading state
                const submitBtn = document.getElementById('promotionAmountSubmitBtn');
                const submitIcon = document.getElementById('promotionAmountSubmitIcon');
                const submitSpinner = document.getElementById('promotionAmountSubmitSpinner');
                const submitText = document.getElementById('promotionAmountSubmitText');

                if (submitBtn) {
                    submitBtn.disabled = true;
                    if (submitIcon) submitIcon.classList.add('hidden');
                    if (submitSpinner) submitSpinner.classList.remove('hidden');
                    if (submitText) submitText.textContent = 'جاري الحفظ...';
                }

                const formData = {
                    discount_type: 'amount',
                    promotion_price: promotionPrice,
                    discount_percentage: null,
                    start_date: startDate,
                    end_date: endDate,
                };

                const isEdit = currentPromotion && currentPromotion.id && currentPromotion.discount_type === 'amount';
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
                        showNotification(data.message || 'تم حفظ التخفيض بنجاح', 'success');
                        setTimeout(() => {
                            closePromotionAmountModal();
                            window.location.reload();
                        }, 1500);
                    } else {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitIcon) submitIcon.classList.remove('hidden');
                            if (submitSpinner) submitSpinner.classList.add('hidden');
                            if (submitText) submitText.textContent = 'حفظ التخفيض';
                        }
                        showNotification(data.message || 'حدث خطأ أثناء الحفظ', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitIcon) submitIcon.classList.remove('hidden');
                        if (submitSpinner) submitSpinner.classList.add('hidden');
                        if (submitText) submitText.textContent = 'حفظ التخفيض';
                    }
                    showNotification('حدث خطأ أثناء الحفظ', 'error');
                });
            });
        }

        // Submit Promotion Percentage Form
        if (promotionPercentageForm) {
            promotionPercentageForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const discountPercentage = document.getElementById('discount_percentage').value;
                const startDate = document.getElementById('start_date_percentage').value;
                const endDate = document.getElementById('end_date_percentage').value;

                // التحقق من الحقول
                let isValid = true;
                let errorMessage = '';

                const percentageNum = parseFloat(discountPercentage);
                if (!discountPercentage || isNaN(percentageNum) || percentageNum <= 0 || percentageNum > 100) {
                    isValid = false;
                    errorMessage = 'يرجى إدخال نسبة تخفيض صحيحة (من 0.01 إلى 100)';
                    document.getElementById('discount_percentage').focus();
                } else if (!startDate || !endDate) {
                    isValid = false;
                    errorMessage = 'يرجى إدخال تاريخ البداية والنهاية';
                    if (!startDate) {
                        document.getElementById('start_date_percentage').focus();
                    } else {
                        document.getElementById('end_date_percentage').focus();
                    }
                } else if (new Date(startDate) >= new Date(endDate)) {
                    isValid = false;
                    errorMessage = 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية';
                    document.getElementById('end_date_percentage').focus();
                }

                if (!isValid) {
                    alert(errorMessage);
                    return;
                }

                // إظهار loading state
                const submitBtn = document.getElementById('promotionPercentageSubmitBtn');
                const submitIcon = document.getElementById('promotionPercentageSubmitIcon');
                const submitSpinner = document.getElementById('promotionPercentageSubmitSpinner');
                const submitText = document.getElementById('promotionPercentageSubmitText');

                if (submitBtn) {
                    submitBtn.disabled = true;
                    if (submitIcon) submitIcon.classList.add('hidden');
                    if (submitSpinner) submitSpinner.classList.remove('hidden');
                    if (submitText) submitText.textContent = 'جاري الحفظ...';
                }

                const formData = {
                    discount_type: 'percentage',
                    promotion_price: null,
                    discount_percentage: discountPercentage,
                    start_date: startDate,
                    end_date: endDate,
                };

                const isEdit = currentPromotion && currentPromotion.id && currentPromotion.discount_type === 'percentage';
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
                        showNotification(data.message || 'تم حفظ التخفيض بنجاح', 'success');
                        setTimeout(() => {
                            closePromotionPercentageModal();
                            window.location.reload();
                        }, 1500);
                    } else {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitIcon) submitIcon.classList.remove('hidden');
                            if (submitSpinner) submitSpinner.classList.add('hidden');
                            if (submitText) submitText.textContent = 'حفظ التخفيض';
                        }
                        showNotification(data.message || 'حدث خطأ أثناء الحفظ', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        if (submitIcon) submitIcon.classList.remove('hidden');
                        if (submitSpinner) submitSpinner.classList.add('hidden');
                        if (submitText) submitText.textContent = 'حفظ التخفيض';
                    }
                    showNotification('حدث خطأ أثناء الحفظ', 'error');
                });
            });
        }

        // دالة لإظهار الإشعارات
        function showNotification(message, type = 'success') {
            // إزالة أي إشعارات سابقة
            const existingNotifications = document.querySelectorAll('.notification-toast');
            existingNotifications.forEach(n => n.remove());

            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
            notification.className = `notification-toast fixed top-24 rtl:right-4 ltr:left-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-[10000] flex items-center gap-2 min-w-[300px] max-w-[90%] sm:max-w-md`;

            // إضافة animation عند الظهور
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            notification.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';

            notification.innerHTML = `
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'success'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'
                    }
                </svg>
                <span class="flex-1">${message}</span>
            `;
            document.body.appendChild(notification);

            // Animation للظهور
            requestAnimationFrame(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            });

            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Close modals on outside click
        if (promotionAmountModal) {
            promotionAmountModal.addEventListener('click', function(e) {
                if (e.target === promotionAmountModal) {
                    closePromotionAmountModal();
                }
            });
        }

        if (promotionPercentageModal) {
            promotionPercentageModal.addEventListener('click', function(e) {
                if (e.target === promotionPercentageModal) {
                    closePromotionPercentageModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (promotionAmountModal && !promotionAmountModal.classList.contains('hidden')) {
                    closePromotionAmountModal();
                } else if (promotionPercentageModal && !promotionPercentageModal.classList.contains('hidden')) {
                    closePromotionPercentageModal();
                }
            }
        });
                    @endif

                    @if(auth()->user()->isAdmin())
                    // Product Hidden Toggle
                    function toggleProductHidden(productId, currentState) {
                        const isHidden = currentState === true;
                        const newState = !isHidden;

                        if (!confirm(`هل أنت متأكد من ${newState ? 'حجب' : 'إلغاء حجب'} هذا المنتج؟`)) {
                            return;
                        }

                        fetch(`/admin/warehouses/${@json($warehouse->id)}/products/${productId}/toggle-hidden`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ is_hidden: newState })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'حدث خطأ أثناء التحديث');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('حدث خطأ أثناء التحديث');
                        });
                    }

                    // Product Discount Modals
                    let currentProductId = null;
                    const productDiscountAmountModal = document.getElementById('productDiscountAmountModal');
                    const productDiscountPercentageModal = document.getElementById('productDiscountPercentageModal');
                    const productDiscountAmountForm = document.getElementById('productDiscountAmountForm');
                    const productDiscountPercentageForm = document.getElementById('productDiscountPercentageForm');

                    // فتح Modal المبلغ الثابت
                    function openProductDiscountAmountModal(productId) {
                        currentProductId = productId;
                        const modal = productDiscountAmountModal;
                        const form = productDiscountAmountForm;
                        const title = document.getElementById('productDiscountAmountModalTitle');

                        // يمكنك إضافة AJAX لجلب بيانات المنتج الحالية هنا
                        title.textContent = 'تخفيض المنتج - مبلغ ثابت';
                        form.reset();

                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                        setTimeout(() => {
                            const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
                            if (modalContent) {
                                modalContent.style.opacity = '0';
                                modalContent.style.transform = 'scale(0.95)';
                                requestAnimationFrame(() => {
                                    modalContent.style.transition = 'opacity 0.2s ease-out, transform 0.2s ease-out';
                                    modalContent.style.opacity = '1';
                                    modalContent.style.transform = 'scale(1)';
                                });
                            }
                        }, 10);
                    }

                    // إغلاق Modal المبلغ الثابت
                    function closeProductDiscountAmountModal() {
                        const modal = productDiscountAmountModal;
                        const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
                        if (modalContent) {
                            modalContent.style.transition = 'opacity 0.15s ease-in, transform 0.15s ease-in';
                            modalContent.style.opacity = '0';
                            modalContent.style.transform = 'scale(0.95)';
                        }
                        setTimeout(() => {
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                            document.body.style.overflow = 'auto';
                            if (productDiscountAmountForm) {
                                productDiscountAmountForm.reset();
                            }
                            currentProductId = null;
                            if (modalContent) {
                                modalContent.style.opacity = '';
                                modalContent.style.transform = '';
                                modalContent.style.transition = '';
                            }
                        }, 150);
                    }

                    // فتح Modal النسبة المئوية
                    function openProductDiscountPercentageModal(productId) {
                        currentProductId = productId;
                        const modal = productDiscountPercentageModal;
                        const form = productDiscountPercentageForm;
                        const title = document.getElementById('productDiscountPercentageModalTitle');

                        // يمكنك إضافة AJAX لجلب بيانات المنتج الحالية هنا
                        title.textContent = 'تخفيض المنتج - نسبة مئوية';
                        form.reset();

                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                        setTimeout(() => {
                            const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
                            if (modalContent) {
                                modalContent.style.opacity = '0';
                                modalContent.style.transform = 'scale(0.95)';
                                requestAnimationFrame(() => {
                                    modalContent.style.transition = 'opacity 0.2s ease-out, transform 0.2s ease-out';
                                    modalContent.style.opacity = '1';
                                    modalContent.style.transform = 'scale(1)';
                                });
                            }
                        }, 10);
                    }

                    // إغلاق Modal النسبة المئوية
                    function closeProductDiscountPercentageModal() {
                        const modal = productDiscountPercentageModal;
                        const modalContent = modal.querySelector('.bg-white, .dark\\:bg-gray-800');
                        if (modalContent) {
                            modalContent.style.transition = 'opacity 0.15s ease-in, transform 0.15s ease-in';
                            modalContent.style.opacity = '0';
                            modalContent.style.transform = 'scale(0.95)';
                        }
                        setTimeout(() => {
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                            document.body.style.overflow = 'auto';
                            if (productDiscountPercentageForm) {
                                productDiscountPercentageForm.reset();
                            }
                            currentProductId = null;
                            if (modalContent) {
                                modalContent.style.opacity = '';
                                modalContent.style.transform = '';
                                modalContent.style.transition = '';
                            }
                        }, 150);
                    }

                    // Submit Product Discount Amount Form
                    if (productDiscountAmountForm) {
                        productDiscountAmountForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const discountValue = document.getElementById('product_discount_amount_value').value;
                            const discountStartDate = document.getElementById('product_discount_amount_start_date').value;
                            const discountEndDate = document.getElementById('product_discount_amount_end_date').value;

                            // Validation
                            if (!discountValue || parseFloat(discountValue) <= 0) {
                                showNotification('يرجى إدخال مبلغ تخفيض صحيح (أكبر من 0)', 'error');
                                return;
                            }

                            if (discountStartDate && discountEndDate && new Date(discountStartDate) >= new Date(discountEndDate)) {
                                showNotification('تاريخ النهاية يجب أن يكون بعد تاريخ البداية', 'error');
                                return;
                            }

                            // إظهار loading state
                            const submitBtn = document.getElementById('productDiscountAmountSubmitBtn');
                            const submitIcon = document.getElementById('productDiscountAmountSubmitIcon');
                            const submitSpinner = document.getElementById('productDiscountAmountSubmitSpinner');
                            const submitText = document.getElementById('productDiscountAmountSubmitText');

                            if (submitBtn) {
                                submitBtn.disabled = true;
                                if (submitIcon) submitIcon.classList.add('hidden');
                                if (submitSpinner) submitSpinner.classList.remove('hidden');
                                if (submitText) submitText.textContent = 'جاري الحفظ...';
                            }

                            const formData = {
                                discount_type: 'amount',
                                discount_value: discountValue,
                                discount_start_date: discountStartDate || null,
                                discount_end_date: discountEndDate || null,
                            };

                            fetch(`/admin/warehouses/${@json($warehouse->id)}/products/${currentProductId}/discount`, {
                                method: 'POST',
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
                                    showNotification(data.message || 'تم حفظ التخفيض بنجاح', 'success');
                                    setTimeout(() => {
                                        closeProductDiscountAmountModal();
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    if (submitBtn) {
                                        submitBtn.disabled = false;
                                        if (submitIcon) submitIcon.classList.remove('hidden');
                                        if (submitSpinner) submitSpinner.classList.add('hidden');
                                        if (submitText) submitText.textContent = 'حفظ التخفيض';
                                    }
                                    showNotification(data.message || 'حدث خطأ أثناء الحفظ', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    if (submitIcon) submitIcon.classList.remove('hidden');
                                    if (submitSpinner) submitSpinner.classList.add('hidden');
                                    if (submitText) submitText.textContent = 'حفظ التخفيض';
                                }
                                showNotification('حدث خطأ أثناء الحفظ', 'error');
                            });
                        });
                    }

                    // Submit Product Discount Percentage Form
                    if (productDiscountPercentageForm) {
                        productDiscountPercentageForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const discountValue = document.getElementById('product_discount_percentage_value').value;
                            const discountStartDate = document.getElementById('product_discount_percentage_start_date').value;
                            const discountEndDate = document.getElementById('product_discount_percentage_end_date').value;

                            // Validation
                            const percentageNum = parseFloat(discountValue);
                            if (!discountValue || isNaN(percentageNum) || percentageNum <= 0 || percentageNum > 100) {
                                showNotification('يرجى إدخال نسبة تخفيض صحيحة (من 0.01 إلى 100)', 'error');
                                return;
                            }

                            if (discountStartDate && discountEndDate && new Date(discountStartDate) >= new Date(discountEndDate)) {
                                showNotification('تاريخ النهاية يجب أن يكون بعد تاريخ البداية', 'error');
                                return;
                            }

                            // إظهار loading state
                            const submitBtn = document.getElementById('productDiscountPercentageSubmitBtn');
                            const submitIcon = document.getElementById('productDiscountPercentageSubmitIcon');
                            const submitSpinner = document.getElementById('productDiscountPercentageSubmitSpinner');
                            const submitText = document.getElementById('productDiscountPercentageSubmitText');

                            if (submitBtn) {
                                submitBtn.disabled = true;
                                if (submitIcon) submitIcon.classList.add('hidden');
                                if (submitSpinner) submitSpinner.classList.remove('hidden');
                                if (submitText) submitText.textContent = 'جاري الحفظ...';
                            }

                            const formData = {
                                discount_type: 'percentage',
                                discount_value: discountValue,
                                discount_start_date: discountStartDate || null,
                                discount_end_date: discountEndDate || null,
                            };

                            fetch(`/admin/warehouses/${@json($warehouse->id)}/products/${currentProductId}/discount`, {
                                method: 'POST',
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
                                    showNotification(data.message || 'تم حفظ التخفيض بنجاح', 'success');
                                    setTimeout(() => {
                                        closeProductDiscountPercentageModal();
                                        window.location.reload();
                                    }, 1500);
                                } else {
                                    if (submitBtn) {
                                        submitBtn.disabled = false;
                                        if (submitIcon) submitIcon.classList.remove('hidden');
                                        if (submitSpinner) submitSpinner.classList.add('hidden');
                                        if (submitText) submitText.textContent = 'حفظ التخفيض';
                                    }
                                    showNotification(data.message || 'حدث خطأ أثناء الحفظ', 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                if (submitBtn) {
                                    submitBtn.disabled = false;
                                    if (submitIcon) submitIcon.classList.remove('hidden');
                                    if (submitSpinner) submitSpinner.classList.add('hidden');
                                    if (submitText) submitText.textContent = 'حفظ التخفيض';
                                }
                                showNotification('حدث خطأ أثناء الحفظ', 'error');
                            });
                        });
                    }

                    // Close modals on outside click
                    if (productDiscountAmountModal) {
                        productDiscountAmountModal.addEventListener('click', function(e) {
                            if (e.target === productDiscountAmountModal) {
                                closeProductDiscountAmountModal();
                            }
                        });
                    }

                    if (productDiscountPercentageModal) {
                        productDiscountPercentageModal.addEventListener('click', function(e) {
                            if (e.target === productDiscountPercentageModal) {
                                closeProductDiscountPercentageModal();
                            }
                        });
                    }

                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            if (productDiscountAmountModal && !productDiscountAmountModal.classList.contains('hidden')) {
                                closeProductDiscountAmountModal();
                            } else if (productDiscountPercentageModal && !productDiscountPercentageModal.classList.contains('hidden')) {
                                closeProductDiscountPercentageModal();
                            }
                        }
                    });

                    // إلغاء تخفيض المنتج
                    function removeProductDiscount(productId) {
                        if (!confirm('هل أنت متأكد من إلغاء التخفيض؟')) {
                            return;
                        }

                        const formData = {
                            discount_type: 'none',
                            discount_value: null,
                            discount_start_date: null,
                            discount_end_date: null,
                        };

                        fetch(`/admin/warehouses/${@json($warehouse->id)}/products/${productId}/discount`, {
                            method: 'POST',
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
                                showNotification(data.message || 'تم إلغاء التخفيض بنجاح', 'success');
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                showNotification(data.message || 'حدث خطأ أثناء الإلغاء', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('حدث خطأ أثناء الإلغاء', 'error');
                        });
                    }
                    @endif

                    // الانتقال التلقائي إلى المنتج عند تحميل الصفحة مع hash
                    document.addEventListener('DOMContentLoaded', function() {
                        const hash = window.location.hash;
                        if (hash && hash.startsWith('#product-')) {
                            const productId = hash.replace('#product-', '');
                            const productElement = document.getElementById('product-' + productId);
                            if (productElement) {
                                // الانتقال السلس إلى المنتج
                                setTimeout(function() {
                                    productElement.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'start'
                                    });
                                    // إضافة highlight مؤقت للمنتج
                                    productElement.classList.add('ring-2', 'ring-primary', 'ring-offset-2');
                                    setTimeout(function() {
                                        productElement.classList.remove('ring-2', 'ring-primary', 'ring-offset-2');
                                    }, 2000);
                                }, 100);
                            }
                        }
                    });
                </script>

    <!-- قسم الاستثمارات والأرباح -->
    @if(auth()->user()->isAdmin())
    <div class="panel mt-5">
        <div class="flex items-center justify-between mb-4">
            <h6 class="text-lg font-semibold">الاستثمارات والأرباح</h6>
            <a href="{{ route('admin.investments.create', ['warehouse_id' => $warehouse->id, 'back_url' => urlencode(request()->fullUrl())]) }}" class="btn btn-primary btn-sm">
                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                إضافة استثمار جديد
            </a>
        </div>

        @if($investments->count() > 0)
            <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="panel">
                    <div class="text-sm text-gray-500 mb-1">ربح المخزن الإجمالي</div>
                    <div class="text-xl font-bold text-primary">{{ number_format($totalWarehouseProfit, 2) }} دينار</div>
                </div>
                <div class="panel">
                    <div class="text-sm text-gray-500 mb-1">ربح المستثمرين</div>
                    <div class="text-xl font-bold text-info">{{ number_format($totalInvestorProfit, 2) }} دينار</div>
                </div>
                <div class="panel">
                    <div class="text-sm text-gray-500 mb-1">ربح المالك</div>
                    <div class="text-xl font-bold text-success">{{ number_format($ownerProfit, 2) }} دينار</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>المستثمر</th>
                            <th>نسبة الربح</th>
                            <th>ربح المستثمر</th>
                            <th>مبلغ الاستثمار</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($investments as $investment)
                            @php
                                $investorProfit = $warehouseProfits->where('investment_id', $investment->id)->sum('profit_amount');
                            @endphp
                            <tr>
                                <td>{{ $investment->investor->name }}</td>
                                <td>{{ $investment->profit_percentage }}%</td>
                                <td>{{ number_format($investorProfit, 2) }} دينار</td>
                                <td>{{ number_format($investment->investment_amount, 2) }} دينار</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-500 mb-4">لا توجد استثمارات لهذا المخزن</p>
                <a href="{{ route('admin.investments.create', ['warehouse_id' => $warehouse->id, 'back_url' => urlencode(request()->fullUrl())]) }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة استثمار جديد
                </a>
            </div>
        @endif
    </div>
    @endif
            </x-layout.admin>
