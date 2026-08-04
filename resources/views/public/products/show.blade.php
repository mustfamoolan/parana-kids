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
            --bg-light: #f0f2f8;
            --bg-dark: #060818;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Tajawal', 'Nunito', sans-serif;
            background: var(--bg-light);
            color: #1f2937;
            overflow-x: hidden;
        }

        /* Glassmorphism Effect */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        /* Product Card */
        .product-card {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 40px -8px rgba(67, 97, 238, 0.18);
        }

        /* ======== IMAGE SLIDER ======== */
        .slider-wrap {
            position: relative;
            width: 100%;
            /* 1:1 or 4:5 aspect ratio for clear large display on mobile & desktop */
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: #f1f5f9;
            border-radius: 1.5rem 1.5rem 0 0;
        }
        @media (min-width: 640px) {
            .slider-wrap {
                aspect-ratio: 4 / 3;
            }
        }
        .slider-track {
            display: flex;
            height: 100%;
            transition: transform 0.55s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }
        .slide {
            min-width: 100%;
            height: 100%;
            flex-shrink: 0;
            position: relative;
        }
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
            cursor: zoom-in;
            transition: transform 0.5s ease;
        }
        .product-card:hover .slide img {
            transform: scale(1.04);
        }

        /* Video Slide container inside carousel */
        .slide-video-wrap {
            width: 100%;
            height: 100%;
            background: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            cursor: pointer;
        }
        .slide-video-wrap iframe {
            width: 100%;
            height: 100%;
            border: none;
            object-fit: cover;
        }

        /* Dots navigation */
        .slider-dots {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 5px;
            z-index: 10;
        }
        .slider-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.8);
            cursor: pointer;
            transition: all 0.3s;
        }
        .slider-dot.active {
            background: #fff;
            width: 18px;
            border-radius: 4px;
        }

        /* Prev/Next arrows */
        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            z-index: 15;
            background: rgba(255,255,255,0.85);
            backdrop-filter: blur(6px);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .product-card:hover .slider-arrow { opacity: 1; }
        .slider-arrow.prev { right: 8px; }
        .slider-arrow.next { left: 8px; }
        .slider-arrow svg { width: 14px; height: 14px; color: #1f2937; }

        /* Single image (no slider needed) */
        .single-img-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background: #f1f5f9;
            border-radius: 1.5rem 1.5rem 0 0;
        }
        .single-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
            cursor: zoom-in;
            transition: transform 0.5s ease;
        }
        .product-card:hover .single-img-wrap img {
            transform: scale(1.06);
        }

        /* Gradient on image bottom */
        .img-gradient {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 55%;
            background: linear-gradient(to top, rgba(0,0,0,0.38) 0%, transparent 100%);
            pointer-events: none;
            z-index: 5;
        }

        /* Discount badge */
        .badge-discount {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 20;
            background: #ef4444;
            color: #fff;
            font-weight: 900;
            font-size: 0.78rem;
            padding: 4px 10px;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(239,68,68,0.4);
            transform: rotate(2deg);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.55s ease-out both;
        }
        .delay-1 { animation-delay: 0.08s; }
        .delay-2 { animation-delay: 0.16s; }
        .delay-3 { animation-delay: 0.24s; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--primary-light); border-radius: 10px; }

        /* Gradient bg - premium */
        .bg-gradient-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        /* Image Modal overlay */
        #imageModal {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.92);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(12px);
        }
        #imageModal.open { display: flex; }
        #imageModal .modal-img-wrap {
            max-width: min(90vw, 640px);
            width: 100%;
            position: relative;
        }
        #imageModal img {
            width: 100%;
            height: auto;
            border-radius: 1.5rem;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.08);
            display: block;
        }
        #imageModal .modal-title {
            color: #fff;
            text-align: center;
            font-weight: 900;
            font-size: 1.2rem;
            margin-top: 1rem;
        }
        #imageModal .modal-close {
            position: absolute;
            top: -44px; left: 0;
            width: 36px; height: 36px;
            background: rgba(255,255,255,0.1);
            border: none; border-radius: 50%;
            color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        #imageModal .modal-close:hover { background: rgba(255,255,255,0.2); }

        /* Grid responsive: Single Column on Mobile for Maximum Clarity */
        .products-grid {
            display: grid !important;
            grid-template-columns: 1fr !important;
            gap: 24px !important;
        }
        @media (min-width: 640px) {
            .products-grid { 
                grid-template-columns: repeat(2, 1fr) !important; 
                gap: 20px !important; 
            }
        }
        @media (min-width: 900px) {
            .products-grid { 
                grid-template-columns: repeat(3, 1fr) !important; 
                gap: 24px !important; 
            }
        }
        @media (min-width: 1200px) {
            .products-grid { 
                grid-template-columns: repeat(4, 1fr) !important; 
                gap: 28px !important; 
            }
        }

        /* Video Modal Overlay - Shorts (9:16 Vertical Ratio) */
        #videoModal {
            position: fixed; inset: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 0.75rem;
            backdrop-filter: blur(16px);
        }
        #videoModal.open { display: flex; }
        #videoModal .modal-video-wrap {
            max-width: min(90vw, 420px);
            width: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #videoModal iframe {
            width: 100%;
            aspect-ratio: 9 / 16;
            max-height: 80vh;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7), 0 0 30px rgba(255, 0, 0, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.15);
            display: block;
            background: #000;
        }
        #videoModal .modal-title {
            color: #fff;
            text-align: center;
            font-weight: 900;
            font-size: 1.1rem;
            margin-top: 1rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        #videoModal .modal-close {
            position: absolute;
            top: -48px; left: 50%;
            transform: translateX(-50%);
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            color: #fff; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
        }
        #videoModal .modal-close:hover { 
            background: rgba(255,0,0,0.8);
            transform: translateX(-50%) scale(1.1);
        }
    </style>
</head>
<body class="antialiased">

    <!-- Header -->
    <header class="sticky top-0 z-50 glass shadow-md py-3">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gradient-premium rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-black text-gray-900 tracking-tight leading-tight">Paraná Kids</h1>
                    <p class="text-[9px] text-gray-500 uppercase tracking-widest font-bold">Premium Collection</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <div class="hidden sm:flex flex-col items-end">
                    <span class="text-[10px] text-gray-500 font-bold">الرابط متاح لـ</span>
                    <span class="text-xs font-black text-primary">{{ $productLink->expires_at->diffForHumans() }}</span>
                </div>
                <div class="w-px h-7 bg-gray-200 mx-2 hidden sm:block"></div>
                <button onclick="window.location.reload()" class="p-2 rounded-full hover:bg-gray-100 transition-colors" title="تحديث">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-screen py-8 px-4">
        <div class="max-w-7xl mx-auto">

            @if(count($groupedProducts) > 0)
                @foreach($groupedProducts as $sizeName => $products)
                    @if($products->count() > 0)
                        <section class="mb-10 animate-fade-in">

                            <!-- Size Section Header -->
                            <div class="rounded-2xl shadow-lg p-5 mb-6 overflow-hidden" style="background:#0f172a; border:2px solid #3b82f6;">
                                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 rounded-xl flex items-center justify-center shadow-inner flex-shrink-0" style="background:#1e40af; border:2px solid #60a5fa;">
                                            <span class="text-2xl font-black" style="color:#fff;">{{ $sizeName }}</span>
                                        </div>
                                        <div>
                                            <h2 class="text-xl font-black" style="color:#fff;">القياس: {{ $sizeName }}</h2>
                                            <p class="text-sm font-bold mt-0.5" style="color:#93c5fd;">{{ $products->count() }} منتج متاح</p>
                                        </div>
                                    </div>
                                    <span class="px-4 py-1.5 rounded-full text-sm font-black" style="background:#1e40af; color:#fff; border:2px solid #60a5fa;">
                                        متوفر الآن
                                    </span>
                                </div>
                            </div>

                            <!-- Products Grid -->
                            <div class="products-grid">
                                @foreach($products as $idx => $product)
                                    @php
                                        $hasDiscount = $product->hasActiveDiscount() || ($product->selling_price > 0 && $product->effective_price < $product->selling_price);
                                        $discountInfo = $product->hasActiveDiscount() ? $product->getDiscountInfo() : null;
                                        $discountPercentage = 0;
                                        if ($hasDiscount && $product->selling_price > 0) {
                                            $discountPercentage = ($discountInfo && isset($discountInfo['percentage']))
                                                ? $discountInfo['percentage']
                                                : round((($product->selling_price - $product->effective_price) / $product->selling_price) * 100);
                                        }
                                        // Collect all images: primary first, then the rest
                                        $allImages = collect();
                                        if ($product->primaryImage) {
                                            $allImages->push($product->primaryImage);
                                        }
                                        if ($product->relationLoaded('images')) {
                                            foreach ($product->images as $img) {
                                                if (!$product->primaryImage || $img->id !== $product->primaryImage->id) {
                                                    $allImages->push($img);
                                                }
                                            }
                                        }
                                        $imageCount = $allImages->count();
                                        $sliderId = 'slider-' . $product->id . '-' . $sizeName . '-' . $idx;
                                        $youtubeId = $product->getYoutubeEmbedId();
                                    @endphp

                                     <!-- ==================== 1. CARD WITH IMAGES ==================== -->
                                     <div class="product-card bg-white rounded-[1.5rem] shadow-sm border border-gray-100 overflow-hidden">
                                         <!-- IMAGE SECTION -->
                                         @if($imageCount > 1)
                                             <!-- Multi-image Carousel -->
                                             <div class="slider-wrap" id="{{ $sliderId }}-wrap">
                                                 <!-- Discount Badge -->
                                                 @if($hasDiscount)
                                                     <div class="badge-discount">
                                                         @if($discountPercentage > 0) -{{ number_format($discountPercentage, 0) }}% @else تخفيض @endif
                                                     </div>
                                                 @endif

                                                 <div class="slider-track" id="{{ $sliderId }}-track">
                                                     @foreach($allImages as $img)
                                                         <div class="slide">
                                                             <img
                                                                 src="{{ $img->image_url }}"
                                                                 alt="{{ $product->name }}"
                                                                 loading="lazy"
                                                                 onclick="openModal('{{ $img->image_url }}', '{{ addslashes($product->name) }}')"
                                                             >
                                                         </div>
                                                     @endforeach
                                                 </div>

                                                 <!-- Gradient -->
                                                 <div class="img-gradient"></div>

                                                 <!-- Dots -->
                                                 <div class="slider-dots" id="{{ $sliderId }}-dots">
                                                     @foreach($allImages as $di => $img)
                                                         <div class="slider-dot {{ $di === 0 ? 'active' : '' }}" onclick="goToSlide('{{ $sliderId }}', {{ $di }})"></div>
                                                     @endforeach
                                                 </div>

                                                 <!-- Arrows (RTL: prev=right, next=left) -->
                                                 <button class="slider-arrow prev" onclick="changeSlide('{{ $sliderId }}', -1)" aria-label="التالي">
                                                     <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                                                 </button>
                                                 <button class="slider-arrow next" onclick="changeSlide('{{ $sliderId }}', 1)" aria-label="السابق">
                                                     <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                                                 </button>
                                             </div>

                                         @elseif($imageCount === 1)
                                             <!-- Single Image -->
                                             <div class="single-img-wrap">
                                                 @if($hasDiscount)
                                                     <div class="badge-discount">
                                                         @if($discountPercentage > 0) -{{ number_format($discountPercentage, 0) }}% @else تخفيض @endif
                                                     </div>
                                                 @endif
                                                 <img
                                                     src="{{ $allImages->first()->image_url }}"
                                                     alt="{{ $product->name }}"
                                                     loading="lazy"
                                                     onclick="openModal('{{ $allImages->first()->image_url }}', '{{ addslashes($product->name) }}')"
                                                 >
                                                 <div class="img-gradient"></div>
                                             </div>

                                         @else
                                             <!-- No image placeholder -->
                                             <div class="single-img-wrap bg-gray-100 flex items-center justify-center">
                                                 @if($hasDiscount)
                                                     <div class="badge-discount">
                                                         @if($discountPercentage > 0) -{{ number_format($discountPercentage, 0) }}% @else تخفيض @endif
                                                     </div>
                                                 @endif
                                                 <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                 </svg>
                                             </div>
                                         @endif

                                         <!-- PRODUCT INFO -->
                                         <div class="p-4">
                                             <span class="inline-block text-[10px] font-bold text-primary px-2.5 py-1 bg-primary/10 rounded-full border border-primary/20 uppercase tracking-tight mb-2">
                                                 {{ $product->code }}
                                             </span>

                                             <h3 class="text-sm font-bold text-gray-800 mb-3 line-clamp-2 leading-snug">{{ $product->name }}</h3>

                                             <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                                 @if($hasDiscount)
                                                     <span class="block text-xs font-bold" style="color:#dc2626; text-decoration:line-through; text-decoration-color:#dc2626;">
                                                         {{ number_format($product->selling_price, 0) }} د.ع
                                                     </span>
                                                     <div class="flex items-baseline gap-1.5 mt-0.5">
                                                         <span class="text-xl font-black" style="color:#16a34a;">{{ number_format($product->effective_price, 0) }}</span>
                                                         <span class="text-xs font-bold text-gray-400">د.ع</span>
                                                         @if($discountPercentage > 0)
                                                             <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full" style="background:#fee2e2; color:#dc2626;">-{{ number_format($discountPercentage, 0) }}%</span>
                                                         @endif
                                                     </div>
                                                 @else
                                                     <span class="block text-[9px] text-gray-400 font-bold uppercase mb-1">السعر</span>
                                                     <div class="flex items-baseline gap-1.5">
                                                         <span class="text-xl font-black text-primary">{{ number_format($product->effective_price, 0) }}</span>
                                                         <span class="text-xs font-bold text-gray-400">د.ع</span>
                                                     </div>
                                                 @endif
                                             </div>

                                             @if($imageCount > 1)
                                                 <p class="text-[10px] text-gray-400 font-bold mt-2 text-center">
                                                     📸 {{ $imageCount }} صور · انقر للتكبير
                                                 </p>
                                             @endif
                                         </div>
                                     </div>

                                     <!-- ==================== 2. DUPLICATE CARD WITH VIDEO (IF VIDEO EXISTS) ==================== -->
                                     @if($youtubeId)
                                         <div class="product-card bg-white rounded-[1.5rem] shadow-sm border border-gray-100 overflow-hidden mt-6">
                                             <!-- VIDEO SECTION (Full Embed / Shorts) -->
                                             <div class="relative w-full overflow-hidden bg-black" style="aspect-ratio: 9 / 16; max-height: 480px;">
                                                 @if($hasDiscount)
                                                     <div class="badge-discount">
                                                         @if($discountPercentage > 0) -{{ number_format($discountPercentage, 0) }}% @else تخفيض @endif
                                                     </div>
                                                 @endif
                                                 <iframe 
                                                     src="https://www.youtube.com/embed/{{ $youtubeId }}?autoplay=0&rel=0&modestbranding=1" 
                                                     class="w-full h-full border-0" 
                                                     allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                                     allowfullscreen
                                                     loading="lazy">
                                                 </iframe>
                                             </div>

                                             <!-- PRODUCT INFO (DUPLICATED) -->
                                             <div class="p-4">
                                                 <span class="inline-block text-[10px] font-bold text-primary px-2.5 py-1 bg-primary/10 rounded-full border border-primary/20 uppercase tracking-tight mb-2">
                                                     {{ $product->code }}
                                                 </span>

                                                 <h3 class="text-sm font-bold text-gray-800 mb-3 line-clamp-2 leading-snug">{{ $product->name }}</h3>

                                                 <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                                     @if($hasDiscount)
                                                         <span class="block text-xs font-bold" style="color:#dc2626; text-decoration:line-through; text-decoration-color:#dc2626;">
                                                             {{ number_format($product->selling_price, 0) }} د.ع
                                                         </span>
                                                         <div class="flex items-baseline gap-1.5 mt-0.5">
                                                             <span class="text-xl font-black" style="color:#16a34a;">{{ number_format($product->effective_price, 0) }}</span>
                                                             <span class="text-xs font-bold text-gray-400">د.ع</span>
                                                             @if($discountPercentage > 0)
                                                                 <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full" style="background:#fee2e2; color:#dc2626;">-{{ number_format($discountPercentage, 0) }}%</span>
                                                             @endif
                                                         </div>
                                                     @else
                                                         <span class="block text-[9px] text-gray-400 font-bold uppercase mb-1">السعر</span>
                                                         <div class="flex items-baseline gap-1.5">
                                                             <span class="text-xl font-black text-primary">{{ number_format($product->effective_price, 0) }}</span>
                                                             <span class="text-xs font-bold text-gray-400">د.ع</span>
                                                         </div>
                                                     @endif
                                                 </div>
                                             </div>
                                         </div>
                                     @endif
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach

            @else
                <!-- Empty State -->
                <div class="flex flex-col items-center justify-center py-32 text-center">
                    <div class="w-28 h-28 bg-gray-100 rounded-[2.5rem] flex items-center justify-center mb-6">
                        <svg class="w-14 h-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-black text-gray-800 mb-2">لا توجد منتجات حالياً</h3>
                    <p class="text-gray-400 max-w-sm mx-auto font-medium text-sm">لا تتوفر أي منتجات تطابق المعايير المختارة في هذا الرابط.</p>
                </div>
            @endif

            <!-- Footer -->
            <footer class="mt-16 py-8 border-t border-gray-200 text-center">
                <p class="text-gray-400 text-xs font-bold tracking-widest uppercase">Paraná Kids &copy; {{ date('Y') }}</p>
            </footer>
        </div>
    </main>

    <!-- Image Modal -->
    <div id="imageModal" onclick="closeModal()">
        <div class="modal-img-wrap" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <img id="modalImg" src="" alt="">
            <p id="modalTitle" class="modal-title"></p>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" onclick="closeVideoModal()">
        <div class="modal-video-wrap" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeVideoModal()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <iframe id="modalVideoIframe" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            <p id="modalVideoTitle" class="modal-title"></p>
        </div>
    </div>

    <script>
        /* ========== IMAGE MODAL ========== */
        function openModal(url, title) {
            if (!url) return;
            document.getElementById('modalImg').src = url;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeModal() {
            document.getElementById('imageModal').classList.remove('open');
            document.body.style.overflow = '';
        }

        /* ========== VIDEO MODAL ========== */
        function openVideoModal(youtubeId, title) {
            if (!youtubeId) return;
            document.getElementById('modalVideoIframe').src = `https://www.youtube.com/embed/${youtubeId}?autoplay=1`;
            document.getElementById('modalVideoTitle').textContent = title;
            document.getElementById('videoModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeVideoModal() {
            document.getElementById('modalVideoIframe').src = '';
            document.getElementById('videoModal').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', e => { 
            if (e.key === 'Escape') {
                closeModal();
                closeVideoModal();
            }
        });

        /* ========== SLIDER LOGIC ========== */
        // Track current index for each slider
        const sliderState = {};
        const sliderTimers = {};

        function getSliderInfo(id) {
            const track = document.getElementById(id + '-track');
            if (!track) return null;
            const slides = track.querySelectorAll('.slide');
            const count = slides.length;
            return { track, count };
        }

        function updateSlider(id, idx) {
            const info = getSliderInfo(id);
            if (!info) return;
            const { track, count } = info;

            sliderState[id] = idx;
            track.style.transform = `translateX(${idx * 100}%)`;

            // Update dots
            const dots = document.querySelectorAll(`#${id}-dots .slider-dot`);
            dots.forEach((d, i) => d.classList.toggle('active', i === idx));
        }

        function changeSlide(id, dir) {
            const info = getSliderInfo(id);
            if (!info) return;
            const cur = sliderState[id] ?? 0;
            const next = (cur + dir + info.count) % info.count;
            updateSlider(id, next);
            resetAutoplay(id);
        }

        function goToSlide(id, idx) {
            updateSlider(id, idx);
            resetAutoplay(id);
        }

        function startAutoplay(id) {
            if (sliderTimers[id]) return;
            sliderTimers[id] = setInterval(() => {
                const info = getSliderInfo(id);
                if (!info) return;
                const cur = sliderState[id] ?? 0;
                updateSlider(id, (cur + 1) % info.count);
            }, 3500);
        }

        function resetAutoplay(id) {
            if (sliderTimers[id]) {
                clearInterval(sliderTimers[id]);
                sliderTimers[id] = null;
            }
            startAutoplay(id);
        }

        // Touch support
        function initTouch(id) {
            const wrap = document.getElementById(id + '-wrap');
            if (!wrap) return;
            let startX = 0;
            wrap.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
            wrap.addEventListener('touchend', e => {
                const diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40) changeSlide(id, diff > 0 ? 1 : -1);
            }, { passive: true });
        }

        // Initialize all sliders
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[id$="-track"]').forEach(track => {
                const idWithTrack = track.id;
                const id = idWithTrack.replace('-track', '');
                const slides = track.querySelectorAll('.slide');
                if (slides.length > 1) {
                    sliderState[id] = 0;
                    initTouch(id);
                    startAutoplay(id);
                }
            });
        });

        // Pause autoplay on hover
        document.querySelectorAll('.slider-wrap').forEach(wrap => {
            const id = wrap.id.replace('-wrap', '');
            wrap.addEventListener('mouseenter', () => {
                if (sliderTimers[id]) { clearInterval(sliderTimers[id]); sliderTimers[id] = null; }
            });
            wrap.addEventListener('mouseleave', () => startAutoplay(id));
        });
    </script>
</body>
</html>
