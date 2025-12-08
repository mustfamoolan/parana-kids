@foreach($products as $product)
    @php
        // إذا كان البحث بالقياس، تحقق من توفر القياس المبحوث
        $shouldShowProduct = true;
        if (isset($searchedSize) && !empty($searchedSize)) {
            // فلترة القياسات التي تطابق البحث
            $matchingSizes = $product->sizes->filter(function($size) use ($searchedSize) {
                return stripos($size->size_name, $searchedSize) !== false;
            });

            // تحقق من وجود قياس متوفر
            $hasAvailableSize = false;
            foreach ($matchingSizes as $size) {
                if ($size->available_quantity > 0) {
                    $hasAvailableSize = true;
                    break;
                }
            }

            $shouldShowProduct = $hasAvailableSize;
        }
    @endphp

    @if($shouldShowProduct)
        <div data-product-id="{{ $product->id }}"
           class="max-w-[22rem] w-full bg-white shadow-[4px_6px_10px_-3px_#bfc9d4] rounded border border-[#e0e6ed] dark:border-[#1b2e4b] dark:bg-[#191e3a] dark:shadow-none product-card transition-transform hover:scale-[1.02]">
            <div class="py-7 px-6">
                    <!-- الصور مع Swiper -->
                    <div class="-mt-7 mb-7 -mx-6 rounded-tl rounded-tr h-[260px] overflow-hidden relative">
                        @if($product->images->count() > 0)
                            <div class="swiper product-swiper-{{ $product->id }} h-full">
                                <div class="swiper-wrapper">
                                    @foreach($product->images as $image)
                                        <div class="swiper-slide">
                                            <img src="{{ $image->image_url }}"
                                                 alt="{{ $product->name }}"
                                                 class="w-full h-full object-cover"
                                                 loading="lazy">
                                        </div>
                                    @endforeach
                                </div>
                                @if($product->images->count() > 1)
                                    <!-- Navigation buttons -->
                                    <div class="swiper-button-next swiper-button-next-{{ $product->id }}"></div>
                                    <div class="swiper-button-prev swiper-button-prev-{{ $product->id }}"></div>
                                    <!-- Pagination -->
                                    <div class="swiper-pagination swiper-pagination-{{ $product->id }}"></div>
                                @endif
                            </div>
                        @else
                            <div class="w-full h-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- الكود -->
                    <p class="text-primary text-xs mb-1.5 font-bold">{{ $product->code }}</p>

                    <!-- النوع والتخفيض -->
                    <div class="mb-2 flex items-center gap-2 flex-wrap">
                        @if($product->gender_type)
                            <span class="badge {{ $product->gender_type == 'boys' ? 'badge-outline-info' : ($product->gender_type == 'girls' ? 'badge-outline-pink' : ($product->gender_type == 'boys_girls' ? 'badge-outline-primary' : 'badge-outline-warning')) }} text-xs">
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
                        @endif
                        @if($product->hasActiveDiscount())
                            <span class="badge badge-warning text-xs">مخفض</span>
                        @endif
                    </div>

                    <!-- الاسم -->
                    <h5 class="text-[#3b3f5c] text-[15px] font-bold mb-4 dark:text-white-light line-clamp-2">{{ $product->name }}</h5>

                    <!-- السعر -->
                    <p class="text-white-dark mb-4">
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
                            <div class="relative bg-gradient-to-br from-warning/10 to-warning/5 dark:from-warning/20 dark:to-warning/10 rounded-lg p-3 border-2 border-warning/30">
                                <!-- Badge التخفيض في الأعلى -->
                                <div class="absolute -top-2 -right-2 bg-warning text-white rounded-full px-3 py-1 text-xs font-bold shadow-lg z-10">
                                    @if($discountInfo['type'] === 'percentage')
                                        -{{ number_format($discountInfo['percentage'], 0) }}%
                                    @else
                                        تخفيض
                                    @endif
                                </div>

                                <div class="flex flex-col gap-2 mt-2">
                                    <!-- السعر الجديد -->
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-3xl font-bold text-success">{{ number_format($product->effective_price, 0) }}</span>
                                        <span class="text-sm text-gray-500">د.ع</span>
                                    </div>

                                    <!-- السعر القديم -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg text-gray-400 line-through">{{ number_format($product->selling_price, 0) }} د.ع</span>
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
                                    <span class="text-2xl font-bold text-success">{{ number_format($product->effective_price, 0) }}</span>
                                    <span class="text-xs text-gray-400 line-through rtl:mr-2 ltr:ml-2">{{ number_format($product->selling_price, 0) }}</span>
                                </div>
                                <span class="text-sm text-gray-500">دينار عراقي</span>
                            </div>
                        @else
                            <div class="flex items-baseline gap-2">
                                <span class="text-2xl font-bold text-primary">{{ number_format($product->effective_price, 0) }}</span>
                                <span class="text-sm text-gray-500">دينار عراقي</span>
                            </div>
                        @endif
                    </p>

                    <!-- القياسات -->
                    <div class="flex flex-wrap gap-1 mb-4">
                        @php
                            // إذا كان البحث بالقياس، أظهر فقط القياس المطلوب
                            if (isset($searchedSize) && !empty($searchedSize)) {
                                $filteredSizes = $product->sizes->filter(function($size) use ($searchedSize) {
                                    return stripos($size->size_name, $searchedSize) !== false;
                                });

                                $availableSizes = $filteredSizes->filter(function($size) {
                                    return $size->available_quantity > 0;
                                });
                                $unavailableSizes = $filteredSizes->filter(function($size) {
                                    return $size->available_quantity <= 0;
                                });
                            } else {
                                // إذا كان البحث بالكود أو بدون بحث، أظهر كل القياسات
                                $availableSizes = $product->sizes->filter(function($size) {
                                    return $size->available_quantity > 0;
                                });
                                $unavailableSizes = $product->sizes->filter(function($size) {
                                    return $size->available_quantity <= 0;
                                });
                            }
                        @endphp

                        @if($availableSizes->count() > 0)
                            @foreach($availableSizes as $size)
                                <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded font-semibold">
                                    {{ $size->size_name }} ({{ $size->available_quantity }})
                                </span>
                            @endforeach
                        @endif

                        @if($unavailableSizes->count() > 0)
                            @if(isset($searchedSize) && !empty($searchedSize))
                                <!-- إذا كان البحث بالقياس وغير متوفر، أظهر رسالة واضحة -->
                                @foreach($unavailableSizes as $size)
                                    <span class="text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 px-2 py-1 rounded font-semibold">
                                        {{ $size->size_name }} - غير متوفر
                                    </span>
                                @endforeach
                            @else
                                <span class="text-xs text-red-500 font-medium">{{ $unavailableSizes->count() }} غير متوفر</span>
                            @endif
                        @endif

                        @if($availableSizes->count() == 0 && $unavailableSizes->count() == 0 && isset($searchedSize))
                            <span class="text-xs bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 px-2 py-1 rounded">
                                القياس غير موجود في هذا المنتج
                            </span>
                        @endif
                    </div>

                    <!-- زر إضافة -->
                    <button type="button"
                            onclick="openProductModal({{ $product->id }})"
                            class="btn btn-primary w-full mt-4">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        إضافة للسلة
                    </button>
                </div>
            </div>
        @endif
    @endforeach

