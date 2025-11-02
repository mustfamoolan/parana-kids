<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عرض المنتجات - Paraná Kids</title>
    <link rel="icon" type="image/svg" href="/assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                        <div class="bg-white dark:bg-[#0e1726] rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
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
                                <div class="text-lg font-bold text-success mt-3">
                                    {{ number_format($product->selling_price, 0) }} د.ع
                                </div>
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

