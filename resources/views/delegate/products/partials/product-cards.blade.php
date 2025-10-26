@foreach($products as $product)
            <a href="{{ route('delegate.warehouses.products.show', [$product->warehouse_id, $product->id]) }}"
               class="max-w-[22rem] w-full bg-white shadow-[4px_6px_10px_-3px_#bfc9d4] rounded border border-[#e0e6ed] dark:border-[#1b2e4b] dark:bg-[#191e3a] dark:shadow-none delegate-product-card block transition-transform hover:scale-[1.02]">
                <div class="py-7 px-6">
                    <!-- الصورة المربعة -->
                    <div class="-mt-7 mb-7 -mx-6 rounded-tl rounded-tr h-[260px] overflow-hidden">
                        @if($product->primaryImage)
                            <img src="{{ $product->primaryImage->image_url }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover"
                                 loading="lazy">
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

                    <!-- الاسم -->
                    <h5 class="text-[#3b3f5c] text-[15px] font-bold mb-4 dark:text-white-light line-clamp-2">{{ $product->name }}</h5>

                    <!-- السعر -->
                    <p class="text-white-dark mb-4">
                        <span class="text-2xl font-bold text-primary">{{ number_format($product->selling_price, 0) }}</span>
                        <span class="text-sm text-gray-500">دينار عراقي</span>
                    </p>

                    <!-- القياسات -->
                    <div class="flex flex-wrap gap-1 mb-4">
                        @php
                            $availableSizes = $product->sizes->filter(function($size) {
                                return $size->available_quantity > 0;
                            });
                            $unavailableSizes = $product->sizes->filter(function($size) {
                                return $size->available_quantity <= 0;
                            });
                        @endphp

                        @foreach($availableSizes->take(5) as $size)
                            <span class="text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 px-2 py-1 rounded font-semibold">
                                {{ $size->size_name }} ({{ $size->available_quantity }})
                            </span>
                        @endforeach

                        @if($availableSizes->count() > 5)
                            <span class="text-xs text-green-600 font-medium">+{{ $availableSizes->count() - 5 }} قياس</span>
                        @endif

                        @if($unavailableSizes->count() > 0)
                            <span class="text-xs text-red-500 font-medium">{{ $unavailableSizes->count() }} غير متوفر</span>
                        @endif
                    </div>

                    <!-- إحصائيات -->
                    <div class="relative flex justify-between mt-6 pt-4 before:w-[250px] before:h-[1px] before:bg-[#e0e6ed] before:inset-x-0 before:top-0 before:absolute before:mx-auto dark:before:bg-[#1b2e4b]">
                        <div class="flex items-center font-semibold">
                            <div class="text-[#515365] dark:text-white-dark text-xs">عرض التفاصيل</div>
                        </div>
                        <div class="flex font-semibold">
                            <div class="text-primary flex items-center ltr:mr-3 rtl:ml-3 text-xs">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                {{ $availableSizes->count() }}
                            </div>
                            <div class="text-primary flex items-center text-xs">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                                {{ $product->total_quantity }}
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
