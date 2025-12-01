<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عرض المنتجات - Paraná Kids</title>
    <link rel="icon" type="image/svg" href="/assets/images/favicon.svg">

    <!-- Local Nunito Font (replaces Google Fonts to avoid ERR_CONNECTION_TIMED_OUT in Iraq) -->
    <link rel="stylesheet" href="/assets/css/fonts.css">

    @vite(['resources/css/app.css'])
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background: #f9fafb;
        }
        .dark body {
            background: #060818;
        }
    </style>
</head>
<body class="antialiased">
    <!-- Image Zoom Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 z-[9999] hidden flex items-center justify-center" onclick="closeImageModal()">
        <div class="max-w-4xl w-full mx-4 relative" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute top-4 left-4 bg-white rounded-full p-2 hover:bg-gray-100 z-10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <img id="modalImage" src="" alt="" class="w-full h-auto rounded-lg">
            <p id="modalTitle" class="text-white text-center mt-4 text-lg"></p>
        </div>
    </div>

    <div class="min-h-screen py-8 px-4">
        <div class="max-w-7xl mx-auto">
            <!-- القياس فوق الكاردات -->
            @if($productLink->size_name)
                <div class="mb-6 text-center">
                    <div class="inline-block bg-white dark:bg-[#0e1726] rounded-lg shadow p-4">
                        <span class="text-sm text-gray-500 dark:text-gray-400">القياس:</span>
                        <span class="font-semibold text-lg mr-2">{{ $productLink->size_name }}</span>
                    </div>
                </div>
            @endif

            <!-- Products Grid -->
            @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($products as $product)
                        @php
                            $hasProductDiscount = $product->hasActiveDiscount();
                            $discountInfo = $hasProductDiscount ? $product->getDiscountInfo() : null;
                        @endphp
                        <div class="relative bg-white dark:bg-[#0e1726] rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <!-- Badge التخفيض في الجانب الأيسر العلوي من الكارد -->
                            @if($hasProductDiscount)
                                <div class="absolute top-2 rtl:right-2 ltr:left-2 bg-warning text-white rounded-full px-3 py-1 text-xs font-bold shadow-lg z-10">
                                    @if($discountInfo['type'] === 'percentage')
                                        -{{ number_format($discountInfo['percentage'], 0) }}%
                                    @else
                                        تخفيض
                                    @endif
                                </div>
                            @endif

                            <!-- Product Image -->
                            <div class="relative aspect-square bg-gray-100 dark:bg-gray-800">
                                @if($product->primaryImage)
                                    <button
                                        type="button"
                                        onclick="openImageModal('{{ $product->primaryImage->image_url }}', '{{ $product->name }}')"
                                        class="w-full h-full"
                                    >
                                        <img
                                            src="{{ $product->primaryImage->image_url }}"
                                            alt="{{ $product->name }}"
                                            class="w-full h-full object-cover hover:opacity-90 cursor-pointer"
                                        >
                                    </button>
                                @else
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Product Info -->
                            <div class="p-4">
                                <div class="mb-2">
                                    <span class="badge badge-outline-primary text-xs">{{ $product->code }}</span>
                                </div>

                                <!-- اسم المنتج -->
                                <h3 class="font-semibold text-base dark:text-white-light mb-3 line-clamp-2">{{ $product->name }}</h3>

                                <!-- السعر -->
                                @php
                                    $activePromotion = $product->warehouse->getCurrentActivePromotion();
                                    $hasPromotion = $activePromotion && $activePromotion->isActive();
                                    $hasProductDiscount = $product->hasActiveDiscount();
                                @endphp
                                @if($hasProductDiscount)
                                    @php
                                        $discountInfo = $product->getDiscountInfo();
                                    @endphp
                                    <!-- تصميم جميل للمنتجات المخفضة -->
                                    <div class="bg-gradient-to-br from-warning/10 to-warning/5 dark:from-warning/20 dark:to-warning/10 rounded-lg p-3 border-2 border-warning/30">
                                        <div class="flex flex-col gap-2">
                                            <!-- السعر الجديد -->
                                            <div class="flex items-baseline gap-2">
                                                <span class="text-2xl font-bold text-success">{{ number_format($product->effective_price, 0) }}</span>
                                                <span class="text-sm text-gray-500">د.ع</span>
                                            </div>

                                            <!-- السعر القديم -->
                                            <div class="flex items-center gap-2">
                                                <span class="text-base text-gray-400 line-through">{{ number_format($product->selling_price, 0) }} د.ع</span>
                                                @if($discountInfo['type'] === 'amount')
                                                    <span class="text-xs text-warning font-semibold">
                                                        (وفرت {{ number_format($discountInfo['discount_amount'], 0) }} د.ع)
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- تفاصيل التخفيض -->
                                            <div class="pt-2 border-t border-warning/20">
                                                <span class="text-xs text-warning font-semibold">
                                                    @if($discountInfo['type'] === 'percentage')
                                                        تخفيض {{ number_format($discountInfo['percentage'], 1) }}% من السعر الأصلي
                                                    @else
                                                        خصم {{ number_format($discountInfo['discount_amount'], 0) }} دينار عراقي
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @elseif($hasPromotion)
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-xl font-bold text-success">{{ number_format($product->effective_price, 0) }}</span>
                                            <span class="text-xs text-gray-400 line-through rtl:mr-2 ltr:ml-2">{{ number_format($product->selling_price, 0) }}</span>
                                        </div>
                                        <span class="text-sm text-gray-500">دينار عراقي</span>
                                    </div>
                                @else
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-bold text-primary">{{ number_format($product->effective_price, 0) }}</span>
                                        <span class="text-sm text-gray-500">دينار عراقي</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 bg-white dark:bg-[#0e1726] rounded-lg shadow">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">لا توجد منتجات متاحة</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        function openImageModal(imageUrl, title) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>
</body>
</html>

