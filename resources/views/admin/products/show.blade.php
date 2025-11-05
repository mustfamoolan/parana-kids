<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المنتج: {{ $product->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.products.index', $product->warehouse) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمنتجات
                </a>
                @can('update', $product)
                    <a href="{{ route('admin.warehouses.products.edit', [$product->warehouse, $product]) }}" class="btn btn-outline-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        تعديل المنتج
                    </a>
                @endcan
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.warehouses.products.destroy', [$product->warehouse, $product]) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟ سيتم حذف جميع القياسات والصور المرتبطة به')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            حذف المنتج
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

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <!-- معلومات المنتج -->
            <div class="lg:col-span-2">
                <div class="panel">
                    <div class="mb-5">
                        <h6 class="text-lg font-semibold dark:text-white-light">معلومات المنتج</h6>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">اسم المنتج:</span>
                            <span class="font-medium text-black dark:text-white-light">{{ $product->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">كود المنتج:</span>
                            <span class="badge badge-outline-primary">{{ $product->code }}</span>
                        </div>

                        @if($product->gender_type)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">نوع المنتج:</span>
                                <span class="badge {{ $product->gender_type == 'boys' ? 'badge-outline-info' : ($product->gender_type == 'girls' ? 'badge-outline-pink' : ($product->gender_type == 'boys_girls' ? 'badge-outline-primary' : 'badge-outline-warning')) }}">
                                    @if($product->gender_type == 'boys')
                                        ولادي
                                    @elseif($product->gender_type == 'girls')
                                        بناتي
                                    @elseif($product->gender_type == 'boys_girls')
                                        ولادي بناتي
                                    @else
                                        اكسسوار
                                    @endif
                                </span>
                            </div>
                        @endif

                        @if(auth()->user()->isAdmin())
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">سعر الشراء:</span>
                                @if($product->purchase_price)
                                    <span class="font-medium text-info">{{ number_format($product->purchase_price, 0, '.', ',') }} دينار عراقي</span>
                                @else
                                    <span class="text-gray-400">غير محدد</span>
                                @endif
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">سعر البيع:</span>
                            <div>
                                @php
                                    $activePromotion = $product->warehouse->getCurrentActivePromotion();
                                    $hasPromotion = $activePromotion && $activePromotion->isActive();
                                @endphp
                                @if($hasPromotion)
                                    <span class="font-medium text-success">{{ number_format($product->effective_price, 0, '.', ',') }} دينار عراقي</span>
                                    <span class="text-xs text-gray-400 line-through rtl:mr-2 ltr:ml-2">{{ number_format($product->selling_price, 0, '.', ',') }}</span>
                                    <span class="badge badge-outline-success text-xs rtl:mr-2 ltr:ml-2">تخفيض</span>
                                @else
                                    <span class="font-medium text-success">{{ number_format($product->effective_price, 0, '.', ',') }} دينار عراقي</span>
                                @endif
                            </div>
                        </div>

                        @if($product->description)
                            <div class="flex items-start justify-between">
                                <span class="text-gray-500 dark:text-gray-400">الوصف:</span>
                                <span class="font-medium text-black dark:text-white-light text-right max-w-xs">{{ $product->description }}</span>
                            </div>
                        @endif

                        @if($product->link_1688)
                            <div class="border-t pt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">رابط 1688:</span>
                                    <div class="flex gap-2">
                                        <button onclick="copyToClipboard('{{ $product->link_1688 }}')"
                                                class="btn btn-sm btn-outline-secondary">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            نسخ
                                        </button>
                                        <a href="{{ $product->link_1688 }}" target="_blank"
                                           class="btn btn-sm btn-primary">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                            فتح الرابط
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المخزن:</span>
                            <span class="font-medium text-black dark:text-white-light">{{ $product->warehouse->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المنشئ:</span>
                            <span class="font-medium text-black dark:text-white-light">{{ $product->creator->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                            <span class="font-medium text-black dark:text-white-light">{{ $product->created_at->format('Y-m-d H:i') }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">آخر تحديث:</span>
                            <span class="font-medium text-black dark:text-white-light">{{ $product->updated_at->format('Y-m-d H:i') }}</span>
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
                            <span class="text-gray-500 dark:text-gray-400">الكمية الإجمالية:</span>
                            <span class="font-bold text-danger dark:text-red-400 text-lg">{{ number_format($product->total_quantity, 0, '.', ',') }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">عدد القياسات:</span>
                            <span class="font-bold text-danger dark:text-red-400 text-lg">{{ $product->sizes->count() }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">عدد الصور:</span>
                            <span class="font-bold text-danger dark:text-red-400 text-lg">{{ $product->images->count() }}</span>
                        </div>

                        @if(auth()->user()->isAdmin())
                            <div class="border-t pt-4 mt-4">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">السعر الكلي للبيع:</span>
                                    </div>
                                    <div class="text-xl font-bold text-success dark:text-white-light">
                                        {{ number_format($totalSellingPrice, 0, '.', ',') }}
                                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">دينار عراقي</span>
                                    </div>
                                </div>

                                @if($totalPurchasePrice > 0)
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-gray-500 dark:text-gray-400">السعر الكلي للشراء:</span>
                                        </div>
                                        <div class="text-xl font-bold text-info dark:text-white-light">
                                            {{ number_format($totalPurchasePrice, 0, '.', ',') }}
                                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">دينار عراقي</span>
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-gray-500 dark:text-gray-400">الربح المتوقع:</span>
                                        </div>
                                        <div class="text-xl font-bold text-warning dark:text-white-light">
                                            {{ number_format($totalSellingPrice - $totalPurchasePrice, 0, '.', ',') }}
                                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">دينار عراقي</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if(auth()->user()->isAdmin() && $product->purchase_price)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">هامش الربح:</span>
                                <span class="badge badge-warning">{{ number_format($product->effective_price - $product->purchase_price, 0, '.', ',') }} دينار عراقي</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- صور المنتج -->
        <div class="panel mt-5">
            <div class="mb-5">
                <h6 class="text-lg font-semibold dark:text-white-light">صور المنتج ({{ $product->images->count() }})</h6>
            </div>

            @if($product->images->count() > 0)
                <div class="swiper max-w-3xl mx-auto relative" id="productSlider">
                    <div class="swiper-wrapper">
                        @foreach($product->images as $image)
                        <div class="swiper-slide">
                            <img src="{{ $image->image_url }}"
                                 class="w-full h-96 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
                                 alt="{{ $product->name }}"
                                 onclick="openImageModal('{{ $image->image_url }}', '{{ $product->name }}')">
                        </div>
                        @endforeach
                    </div>

                    <!-- أزرار التنقل -->
                    <div class="swiper-button-prev-product grid place-content-center ltr:left-2 rtl:right-2 p-1 transition text-primary hover:text-white border border-primary hover:border-primary hover:bg-primary rounded-full absolute z-[10] top-1/2 -translate-y-1/2 cursor-pointer">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 rtl:rotate-180">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="swiper-button-next-product grid place-content-center ltr:right-2 rtl:left-2 p-1 transition text-primary hover:text-white border border-primary hover:border-primary hover:bg-primary rounded-full absolute z-[10] top-1/2 -translate-y-1/2 cursor-pointer">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ltr:rotate-180">
                            <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>

                    <!-- Pagination -->
                    <div class="swiper-pagination"></div>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const productSwiper = new Swiper("#productSlider", {
                            slidesPerView: 1,
                            spaceBetween: 30,
                            loop: {{ $product->images->count() > 1 ? 'true' : 'false' }},
                            pagination: {
                                el: ".swiper-pagination",
                                clickable: true,
                                type: "fraction",
                            },
                            navigation: {
                                nextEl: '.swiper-button-next-product',
                                prevEl: '.swiper-button-prev-product',
                            },
                        });
                    });
                </script>
            @else
                <div class="w-full h-64 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            @endif
        </div>

        <!-- القياسات -->
        @if($product->sizes->count() > 0)
            <div class="mt-5">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">القياسات والكميات</h6>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($product->sizes as $size)
                        <div class="panel">
                            <div class="flex items-center justify-between mb-3">
                                <h6 class="font-semibold text-base dark:text-white-light">القياس: {{ $size->size_name }}</h6>
                                @if($size->quantity > 10)
                                    <span class="badge badge-success">متوفر</span>
                                @elseif($size->quantity > 0)
                                    <span class="badge badge-warning">كمية قليلة</span>
                                @else
                                    <span class="badge badge-danger">نفد المخزون</span>
                                @endif
                            </div>

                            <div class="space-y-2 border-t pt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">الكمية المتوفرة:</span>
                                    <span class="text-xl font-bold text-primary">{{ number_format($size->quantity, 0, '.', ',') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">آخر تحديث:</span>
                                    <span class="text-xs text-gray-500">{{ $size->updated_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-t">
                                <a href="{{ route('admin.products.movements', [$product->warehouse, $product, 'size' => $size->id]) }}"
                                   class="btn btn-sm btn-outline-primary w-full">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    كشف الحركات
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
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
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('تم نسخ الرابط بنجاح!');
            });
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-success text-white px-6 py-3 rounded-lg shadow-lg z-50';
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
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
