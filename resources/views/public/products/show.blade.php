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
            <!-- Products Grouped by Size -->
            @if(count($groupedProducts) > 0)
                @foreach($groupedProducts as $sizeName => $products)
                    @if($products->count() > 0)
                        <!-- فواصل القياسات ككاردات مميزة -->
                        <div class="mb-8 mt-12 first:mt-0">
                            <div class="bg-gradient-to-r from-primary to-primary-light dark:from-[#1b2e4b] dark:to-[#0e1726] rounded-xl shadow-lg p-5 border-l-8 border-primary relative overflow-hidden">
                                <div class="relative z-10 flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="bg-white dark:bg-primary rounded-lg p-3 shadow-sm">
                                            <svg class="w-6 h-6 text-primary dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-2xl font-black text-white dark:text-white-light tracking-wide">القياس: {{ $sizeName }}</h2>
                                            <p class="text-white/80 dark:text-white-light/60 text-sm mt-1">يوجد {{ $products->count() }} منتجات متوفرة بهذا القياس</p>
                                        </div>
                                    </div>
                                    <div class="hidden sm:block">
                                        <span class="bg-white/20 backdrop-blur-md text-white px-4 py-1.5 rounded-full text-xs font-bold border border-white/30">
                                            Paraná Kids Collection
                                        </span>
                                    </div>
                                </div>
                                <!-- زخرفة خلفية -->
                                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                            @foreach($products as $product)
                                @php
                                    $hasProductDiscount = $product->hasActiveDiscount();
                                    $discountInfo = $hasProductDiscount ? $product->getDiscountInfo() : null;
                                @endphp
                                <div class="relative bg-white dark:bg-[#0e1726] rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-100 dark:border-gray-800">
                                    <!-- Badge التخفيض -->
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
                                            <button type="button" onclick="openImageModal('{{ $product->primaryImage->image_url }}', '{{ $product->name }}')" class="w-full h-full">
                                                <img src="{{ $product->primaryImage->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover hover:opacity-90 cursor-pointer">
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
                                        <h3 class="font-semibold text-base dark:text-white-light mb-3 line-clamp-2">{{ $product->name }}</h3>

                                        @php
                                            $activePromotion = $product->warehouse->getCurrentActivePromotion();
                                            $hasPromotion = $activePromotion && $activePromotion->isActive();
                                        @endphp
                                        @if($hasProductDiscount)
                                            <div class="bg-gradient-to-br from-warning/10 to-warning/5 dark:from-warning/20 dark:to-warning/10 rounded-lg p-3 border-2 border-warning/30">
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex items-baseline gap-2">
                                                        <span class="text-2xl font-bold text-success">{{ number_format($product->effective_price, 0) }}</span>
                                                        <span class="text-sm text-gray-500">د.ع</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-base text-gray-400 line-through">{{ number_format($product->selling_price, 0) }} د.ع</span>
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
                    @endif
                @endforeach
            @else
                <div class="text-center py-24 bg-white dark:bg-[#0e1726] rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800">
                    <div class="bg-gray-50 dark:bg-gray-800/50 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white-light mb-2">عذراً، لا توجد منتجات</h3>
                    <p class="text-gray-500 dark:text-gray-400">لا توجد منتجات متوفرة حالياً بناءً على الفلاتر المختارة في هذا الرابط.</p>
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

