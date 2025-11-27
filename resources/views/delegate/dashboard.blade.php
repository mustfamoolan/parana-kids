<x-layout.default>
    <div class="container mx-auto px-4 py-6">
        <!-- البنر النصي المتوهج -->
        <div id="dashboard-banner-container" class="mb-6" style="display: none;">
            <div class="dashboard-banner-glow">
                <div class="panel border-2 border-primary/30 bg-gradient-to-r from-primary/20 via-secondary/20 to-primary/20 dark:from-primary/10 dark:via-secondary/10 dark:to-primary/10">
                    <p id="dashboard-banner-text" class="text-xl font-bold text-center text-primary dark:text-primary-light glow-text">
                    </p>
                </div>
            </div>
        </div>

        <!-- العنوان -->
        <h1 class="text-2xl font-bold mb-6 text-center">مرحباً {{ auth()->user()->name }}</h1>

        @if($activeOrder)
            <!-- تنبيه الطلب النشط -->
            <div class="panel mb-6 !bg-warning-light border-2 border-warning" id="activeOrderAlert">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex-1">
                        <h5 class="font-bold text-warning text-lg mb-2">لديك طلب نشط!</h5>
                        @if($customerData)
                            <p class="text-black dark:text-white">الزبون: {{ $customerData['customer_name'] }}</p>
                        @else
                            <p class="text-black dark:text-white">الزبون: غير معروف</p>
                        @endif
                        <p class="text-black dark:text-white">المنتجات: {{ $activeOrder->items->count() }}</p>
                        <p class="text-black dark:text-white">الإجمالي: {{ number_format($activeOrder->total_amount, 0) }} د.ع</p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('delegate.products.all') }}" class="btn btn-warning">إكمال الطلب</a>
                        <form method="POST" action="{{ route('delegate.orders.cancel-current') }}">
                            @csrf
                            <button type="submit" class="btn btn-danger">إلغاء</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <!-- الأزرار الرئيسية -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- 1. طلب جديد -->
            <a href="#"
               @click.prevent="startNewOrder"
               x-data="{
                   hasActiveOrder: {{ $activeOrder ? 'true' : 'false' }},
                   startNewOrder() {
                       if (this.hasActiveOrder) {
                           // عرض مودال
                           Swal.fire({
                               title: 'لديك طلب نشط!',
                               text: 'يجب إكمال أو إلغاء الطلب الحالي أولاً',
                               icon: 'warning',
                               showCancelButton: true,
                               confirmButtonText: 'إكمال الطلب',
                               cancelButtonText: 'إلغاء الطلب',
                               confirmButtonColor: '#4361ee',
                               cancelButtonColor: '#e7515a'
                           }).then((result) => {
                               if (result.isConfirmed) {
                                   window.location.href = '{{ route('delegate.products.all') }}';
                               } else if (result.dismiss === Swal.DismissReason.cancel) {
                                   cancelOrder();
                               }
                           });
                       } else {
                           window.location.href = '{{ route('delegate.orders.start') }}';
                       }
                   }
               }"
               class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">طلب جديد</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إنشاء طلب جديد</p>
            </a>

            <!-- 2. طلباتي -->
            <a href="{{ route('delegate.orders.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">طلباتي</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <span class="badge bg-warning">{{ $stats['pending_orders'] }}</span> قيد الانتظار
                </p>
            </a>

            <!-- 3. المراسلة -->
            <a href="{{ route('chat.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">المراسلة</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">المراسلة مع الفريق</p>
            </a>

            <!-- 4. المنتجات -->
            <a href="{{ route('delegate.products.all') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">المنتجات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">تصفح المنتجات</p>
            </a>

            <!-- 5. إنشاء رابط -->
            <a href="{{ route('delegate.product-links.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-purple/10 to-purple/5 border-2 border-purple/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" fill="currentColor" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-purple mb-2">إنشاء رابط</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إنشاء رابط للمنتجات</p>
            </a>

            <!-- 6. الإعدادات -->
            <a href="{{ route('delegate.settings.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-secondary/10 to-secondary/5 border-2 border-secondary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-secondary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path opacity="0.5" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z" fill="currentColor" />
                        <path d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z" fill="currentColor" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-secondary mb-2">الإعدادات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إعدادات المظهر والتخصيص</p>
            </a>

            <!-- تسجيل الخروج -->
            <div class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-danger/10 to-danger/5 border-2 border-danger/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-danger/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-danger mb-2">تسجيل الخروج</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">الخروج من النظام</p>
                <form method="POST" action="{{ route('delegate.logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="btn btn-danger w-full">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        تسجيل الخروج
                    </button>
                </form>
            </div>

        </div>
    </div>

    <style>
        /* تأثير التوهج للبنر النصي */
        .dashboard-banner-glow {
            animation: glow-pulse 2s ease-in-out infinite;
        }

        .glow-text {
            text-shadow:
                0 0 10px rgba(67, 97, 238, 0.8),
                0 0 20px rgba(67, 97, 238, 0.6),
                0 0 30px rgba(67, 97, 238, 0.4),
                0 0 40px rgba(67, 97, 238, 0.2);
            animation: text-glow 2s ease-in-out infinite;
        }

        #dashboard-banner-text {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
            max-width: 100%;
            padding: 0.75rem 1rem;
            line-height: 1.6;
        }

        /* تحسين على الموبايل */
        @media (max-width: 640px) {
            #dashboard-banner-text {
                font-size: 1rem;
                padding: 0.5rem 0.75rem;
                line-height: 1.5;
            }
        }

        @keyframes glow-pulse {
            0%, 100% {
                box-shadow:
                    0 0 10px rgba(67, 97, 238, 0.5),
                    0 0 20px rgba(67, 97, 238, 0.3),
                    0 0 30px rgba(67, 97, 238, 0.2);
            }
            50% {
                box-shadow:
                    0 0 20px rgba(67, 97, 238, 0.8),
                    0 0 30px rgba(67, 97, 238, 0.6),
                    0 0 40px rgba(67, 97, 238, 0.4),
                    0 0 50px rgba(67, 97, 238, 0.2);
            }
        }

        @keyframes text-glow {
            0%, 100% {
                text-shadow:
                    0 0 10px rgba(67, 97, 238, 0.8),
                    0 0 20px rgba(67, 97, 238, 0.6),
                    0 0 30px rgba(67, 97, 238, 0.4);
            }
            50% {
                text-shadow:
                    0 0 15px rgba(67, 97, 238, 1),
                    0 0 25px rgba(67, 97, 238, 0.8),
                    0 0 35px rgba(67, 97, 238, 0.6),
                    0 0 45px rgba(67, 97, 238, 0.4);
            }
        }

        /* تحسين التصميم في الوضع الداكن */
        .dark .glow-text {
            text-shadow:
                0 0 10px rgba(67, 97, 238, 1),
                0 0 20px rgba(67, 97, 238, 0.8),
                0 0 30px rgba(67, 97, 238, 0.6),
                0 0 40px rgba(67, 97, 238, 0.4);
        }

        .dark .dashboard-banner-glow {
            animation: glow-pulse-dark 2s ease-in-out infinite;
        }

        @keyframes glow-pulse-dark {
            0%, 100% {
                box-shadow:
                    0 0 15px rgba(67, 97, 238, 0.6),
                    0 0 25px rgba(67, 97, 238, 0.4),
                    0 0 35px rgba(67, 97, 238, 0.3);
            }
            50% {
                box-shadow:
                    0 0 25px rgba(67, 97, 238, 0.9),
                    0 0 35px rgba(67, 97, 238, 0.7),
                    0 0 45px rgba(67, 97, 238, 0.5),
                    0 0 55px rgba(67, 97, 238, 0.3);
            }
        }
    </style>

    <script>
        function cancelOrder() {
            fetch('{{ route('delegate.orders.cancel-current') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            })
            .then(() => {
                Swal.fire('تم!', 'تم إلغاء الطلب', 'info')
                    .then(() => window.location.reload());
            });
        }

        // Dashboard Banner Real-time System
        (function() {
            let bannerCheckInterval = null;
            let lastBannerText = null;
            const pollInterval = 4000; // كل 4 ثوانٍ
            const bannerContainer = document.getElementById('dashboard-banner-container');
            const bannerTextElement = document.getElementById('dashboard-banner-text');

            // جلب البنر النصي
            async function fetchDashboardBanner() {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };

                    if (csrfToken) {
                        headers['X-CSRF-TOKEN'] = csrfToken;
                    }

                    const response = await fetch('/api/banner/dashboard', {
                        method: 'GET',
                        headers: headers,
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        // لا نطبع خطأ إذا كان 401 أو 403 (مشكلة authentication)
                        if (response.status !== 401 && response.status !== 403) {
                            console.log('Dashboard Banner: API response not OK:', response.status);
                        }
                        return null;
                    }

                    const data = await response.json();

                    if (data.success && data.active && data.text) {
                        return data.text;
                    }

                    return null;
                } catch (error) {
                    // لا نطبع خطأ إذا كان network error (قد يكون السيرفر غير متاح مؤقتاً)
                    if (error.name !== 'TypeError') {
                        console.error('Dashboard Banner: Error fetching banner:', error);
                    }
                    return null;
                }
            }

            // عرض/إخفاء البنر
            function updateBanner(text) {
                try {
                    if (!bannerContainer || !bannerTextElement) {
                        console.warn('Dashboard Banner: Container or text element not found');
                        return;
                    }

                    if (text && text !== lastBannerText) {
                        // عرض البنر
                        bannerTextElement.textContent = text;
                        bannerContainer.style.display = 'block';
                        lastBannerText = text;
                        console.log('Dashboard Banner: Banner shown:', text);
                    } else if (!text && lastBannerText) {
                        // إخفاء البنر
                        bannerContainer.style.display = 'none';
                        lastBannerText = null;
                        console.log('Dashboard Banner: Banner hidden');
                    }
                } catch (error) {
                    console.error('Dashboard Banner: Error updating banner:', error);
                }
            }

            // فحص البنر
            async function checkBanner() {
                if (document.hidden) {
                    return;
                }

                const bannerText = await fetchDashboardBanner();
                updateBanner(bannerText);
            }

            // بدء فحص البنر
            function startBannerPolling() {
                console.log('Dashboard Banner: Starting polling system');

                // فحص فوري عند تحميل الصفحة
                setTimeout(checkBanner, 1000); // بعد ثانية واحدة

                // Polling دوري
                bannerCheckInterval = setInterval(() => {
                    if (!document.hidden) {
                        checkBanner();
                    }
                }, pollInterval);

                console.log('Dashboard Banner: Polling started with interval:', pollInterval, 'ms');
            }

            // بدء عند تحميل الصفحة
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    startBannerPolling();
                });
            } else {
                startBannerPolling();
            }

            // إعادة الفحص عند إعادة فتح الصفحة
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    setTimeout(checkBanner, 500);
                }
            });

            // عرض البنر الأولي إذا كان موجوداً في الصفحة (قبل API call)
            @if($dashboardBannerEnabled && !empty($dashboardBannerText))
            setTimeout(() => {
                try {
                    updateBanner({!! json_encode($dashboardBannerText, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) !!});
                } catch (error) {
                    console.error('Dashboard Banner: Error showing initial banner:', error);
                }
            }, 100);
            @endif
        })();
    </script>
</x-layout.default>
