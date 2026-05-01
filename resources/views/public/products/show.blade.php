<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>عرض المنتجات - Paraná Kids</title>
    <link rel="icon" type="image/svg" href="/assets/images/favicon.svg">

    <!-- Local Nunito Font -->
    <link rel="stylesheet" href="/assets/css/fonts.css">

    @vite(['resources/css/app.css'])
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap');

        :root {
            --primary: #4361ee;
            --primary-light: #4895ef;
            --secondary: #3f37c9;
            --warning: #f72585;
            --success: #4cc9f0;
            --bg-light: #f8f9fc;
            --bg-dark: #060818;
        }

        body {
            font-family: 'Tajawal', 'Nunito', sans-serif;
            background: var(--bg-light);
            color: #1f2937;
            overflow-x: hidden;
        }

        .dark body {
            background: var(--bg-dark);
            color: #e5e7eb;
        }

        /* Glassmorphism Effect */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dark .glass {
            background: rgba(14, 23, 38, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Product Card Hover */
        .product-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        /* Gradient Backgrounds */
        .bg-gradient-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body class="antialiased">
    <!-- Header -->
    <header class="sticky top-0 z-50 glass shadow-sm py-4">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary/30">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">Paraná Kids</h1>
                    <p class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Premium Collection</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="hidden sm:flex flex-col items-end">
                    <span class="text-[10px] text-gray-400 font-bold">الرابط متاح لـ</span>
                    <span class="text-xs font-bold text-primary">{{ $productLink->expires_at->diffForHumans() }}</span>
                </div>
                <div class="w-px h-8 bg-gray-200 dark:bg-gray-800 mx-2 hidden sm:block"></div>
                <button onclick="window.location.reload()" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-screen py-10 px-4">
        <div class="max-w-7xl mx-auto">
            
            @if(count($groupedProducts) > 0)
                @foreach($groupedProducts as $sizeName => $products)
                    @if($products->count() > 0)
                        <!-- Size Section Header -->
                        <section class="mb-12 animate-fade-in">
                            <div class="relative mb-8">
                                <div class="bg-gradient-to-r from-primary to-secondary rounded-2xl shadow-xl p-6 overflow-hidden">
                                    <div class="absolute top-0 left-0 w-full h-full opacity-10">
                                        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                                            <path d="M0 100 C 20 0 50 0 100 100 Z" fill="white"></path>
                                        </svg>
                                    </div>
                                    
                                    <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                                        <div class="flex items-center gap-5">
                                            <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center border border-white/30 shadow-inner">
                                                <span class="text-3xl font-black text-white">{{ $sizeName }}</span>
                                            </div>
                                            <div>
                                                <h2 class="text-2xl font-black text-white">القياس: {{ $sizeName }}</h2>
                                                <p class="text-white/70 text-sm font-medium">مجموعة مختارة من {{ $products->count() }} قطع مميزة</p>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            <span class="px-4 py-2 bg-black/20 backdrop-blur-md rounded-full text-[10px] font-bold text-white border border-white/10">
                                                متوفر الآن
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Products Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                                @foreach($products as $product)
                                    @php
                                        $hasDiscount = $product->hasActiveDiscount();
                                        $discountInfo = $hasDiscount ? $product->getDiscountInfo() : null;
                                    @endphp
                                    <div class="product-card group relative bg-white dark:bg-[#0e1726] rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-800/50 overflow-hidden">
                                        <!-- Image Container -->
                                        <div class="relative aspect-[4/5] overflow-hidden bg-gray-50 dark:bg-gray-900">
                                            @if($product->primaryImage)
                                                <img 
                                                    src="{{ $product->primaryImage->image_url }}" 
                                                    alt="{{ $product->name }}" 
                                                    class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-in-out cursor-pointer"
                                                    onclick="openImageModal('{{ $product->primaryImage->image_url }}', '{{ $product->name }}')"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center opacity-20">
                                                    <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </div>
                                            @endif

                                            <!-- Discount Badge -->
                                            @if($hasDiscount)
                                                <div class="absolute top-5 right-5 z-10">
                                                    <div class="bg-warning text-white font-black text-sm px-4 py-2 rounded-2xl shadow-xl transform rotate-3">
                                                        @if($discountInfo['type'] === 'percentage')
                                                            -{{ number_format($discountInfo['percentage'], 0) }}%
                                                        @else
                                                            OFFER
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Floating Actions -->
                                            <div class="absolute bottom-5 right-5 flex flex-col gap-2 translate-y-12 group-hover:translate-y-0 opacity-0 group-hover:opacity-100 transition-all duration-300">
                                                <button onclick="openImageModal('{{ $product->primaryImage->image_url ?? '' }}', '{{ $product->name }}')" class="w-10 h-10 bg-white/90 dark:bg-black/90 backdrop-blur-md rounded-full flex items-center justify-center shadow-lg hover:bg-primary hover:text-white transition-colors">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Product Content -->
                                        <div class="p-6">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="text-[10px] font-bold text-primary px-3 py-1 bg-primary/10 rounded-full border border-primary/20 uppercase tracking-tighter">
                                                    Code: {{ $product->code }}
                                                </span>
                                                <div class="flex gap-1">
                                                    <div class="w-2 h-2 rounded-full bg-success"></div>
                                                    <div class="w-2 h-2 rounded-full bg-gray-200 dark:bg-gray-700"></div>
                                                </div>
                                            </div>
                                            
                                            <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4 line-clamp-1 group-hover:text-primary transition-colors">{{ $product->name }}</h3>

                                            <!-- Price Section -->
                                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-[1.5rem] p-4 border border-gray-100 dark:border-gray-800/50 transition-all group-hover:border-primary/30">
                                                <div class="flex flex-col">
                                                    @if($hasDiscount)
                                                        <span class="text-xs text-gray-400 line-through mb-1">{{ number_format($product->selling_price, 0) }} د.ع</span>
                                                        <div class="flex items-baseline gap-2">
                                                            <span class="text-2xl font-black text-warning">{{ number_format($product->effective_price, 0) }}</span>
                                                            <span class="text-xs font-bold text-gray-500">د.ع</span>
                                                        </div>
                                                    @else
                                                        <span class="text-[10px] text-gray-400 font-bold uppercase mb-1">السعر الحالي</span>
                                                        <div class="flex items-baseline gap-2">
                                                            <span class="text-2xl font-black text-primary">{{ number_format($product->effective_price, 0) }}</span>
                                                            <span class="text-xs font-bold text-gray-500">د.ع</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            @else
                <!-- Empty State -->
                <div class="flex flex-col items-center justify-center py-32 text-center">
                    <div class="w-32 h-32 bg-gray-100 dark:bg-gray-800 rounded-[3rem] flex items-center justify-center mb-8 relative">
                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <div class="absolute -top-2 -right-2 w-8 h-8 bg-warning rounded-full border-4 border-white dark:border-bg-dark"></div>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 dark:text-white mb-3">لا توجد منتجات حالياً</h3>
                    <p class="text-gray-500 max-w-sm mx-auto font-medium">عذراً، لا تتوفر أي منتجات تطابق المعايير المختارة في هذا الرابط في الوقت الحالي.</p>
                </div>
            @endif

            <!-- Footer Info -->
            <footer class="mt-20 py-10 border-t border-gray-200 dark:border-gray-800 text-center">
                <p class="text-gray-400 text-sm font-bold tracking-widest uppercase">Paraná Kids &copy; {{ date('Y') }}</p>
                <div class="mt-4 flex justify-center gap-6">
                    <a href="#" class="text-gray-400 hover:text-primary transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg></a>
                    <a href="#" class="text-gray-400 hover:text-primary transition-colors"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4s1.791-4 4-4 4 1.791 4 4-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
                </div>
            </footer>
        </div>
    </main>

    <!-- Image Zoom Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black/90 z-[9999] hidden flex items-center justify-center p-4 backdrop-blur-md" onclick="closeImageModal()">
        <div class="max-w-4xl w-full relative animate-fade-in" onclick="event.stopPropagation()">
            <button onclick="closeImageModal()" class="absolute -top-12 left-0 w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
            <img id="modalImage" src="" alt="" class="w-full h-auto rounded-[2.5rem] shadow-2xl border border-white/10">
            <div class="mt-6 text-center">
                <h3 id="modalTitle" class="text-white text-2xl font-black mb-2"></h3>
                <div class="w-20 h-1 bg-primary mx-auto rounded-full"></div>
            </div>
        </div>
    </div>

    <script>
        function openImageModal(imageUrl, title) {
            if (!imageUrl) return;
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeImageModal();
        });

        // Add intersection observer for scroll animations
        const observerOptions = { threshold: 0.1 };
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('section').forEach(el => observer.observe(el));
    </script>
</body>
</html>

