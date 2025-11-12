<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset='utf-8' />
    <meta http-equiv='X-UA-Compatible' content='IE=edge' />
    <title>{{ $title ?? 'المخزن' }}</title>

    <meta name='viewport' content='width=device-width, initial-scale=1' />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg" href="/assets/images/favicon.svg" />

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#4361ee" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Parana Kids" />
    <link rel="apple-touch-icon" href="/assets/images/icons/icon-192x192.png" />
    <link rel="manifest" href="/manifest.json" />

    <!-- Local Nunito Font (replaces Google Fonts to avoid ERR_CONNECTION_TIMED_OUT in Iraq) -->
    <link rel="stylesheet" href="/assets/css/fonts.css" />

    <script src="/assets/js/perfect-scrollbar.min.js"></script>
    <script defer src="/assets/js/popper.min.js"></script>
    <script defer src="/assets/js/tippy-bundle.umd.min.js"></script>
    <script defer src="/assets/js/sweetalert.min.js"></script>
    @vite(['resources/css/app.css'])
</head>

<body x-data="main" class="antialiased relative font-nunito text-sm font-normal overflow-x-hidden"
    :class="[$store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ?  'dark' : '', $store.app.menu, $store.app.layout, $store.app
        .rtlClass
    ]">

    <!-- sidebar menu overlay -->
    <div x-cloak class="fixed inset-0 bg-[black]/60 z-50 lg:hidden" :class="{ 'hidden': !$store.app.sidebar }"
        @click="$store.app.toggleSidebar()"></div>

    <!-- screen loader -->
    <div
        class="screen_loader fixed inset-0 bg-[#fafafa] dark:bg-[#060818] z-[60] grid place-content-center animate__animated">
        <svg width="64" height="64" viewBox="0 0 135 135" xmlns="http://www.w3.org/2000/svg" fill="#4361ee">
            <path
                d="M67.447 58c5.523 0 10-4.477 10-10s-4.477-10-10-10-10 4.477-10 10 4.477 10 10 10zm9.448 9.447c0 5.523 4.477 10 10 10 5.522 0 10-4.477 10-10s-4.478-10-10-10c-5.523 0-10 4.477-10 10zm-9.448 9.448c-5.523 0-10 4.477-10 10 0 5.522 4.477 10 10 10s10-4.478 10-10c0-5.523-4.477-10-10-10zM58 67.447c0-5.523-4.477-10-10-10s-10 4.477-10 10 4.477 10 10 10 10-4.477 10-10z">
                <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="-360 67 67" dur="2.5s"
                    repeatCount="indefinite" />
            </path>
            <path
                d="M28.19 40.31c6.627 0 12-5.374 12-12 0-6.628-5.373-12-12-12-6.628 0-12 5.372-12 12 0 6.626 5.372 12 12 12zm30.72-19.825c4.686 4.687 12.284 4.687 16.97 0 4.686-4.686 4.686-12.284 0-16.97-4.686-4.687-12.284-4.687-16.97 0-4.687 4.686-4.687 12.284 0 16.97zm35.74 7.705c0 6.627 5.37 12 12 12 6.626 0 12-5.373 12-12 0-6.628-5.374-12-12-12-6.63 0-12 5.372-12 12zm19.822 30.72c-4.686 4.686-4.686 12.284 0 16.97 4.687 4.686 12.285 4.686 16.97 0 4.687-4.686 4.687-12.284 0-16.97-4.685-4.687-12.283-4.687-16.97 0zm-7.704 35.74c-6.627 0-12 5.37-12 12 0 6.626 5.373 12 12 12s12-5.374 12-12c0-6.63-5.373-12-12-12zm-30.72 19.822c-4.686-4.686-12.284-4.686-16.97 0-4.686 4.687-4.686 12.285 0 16.97 4.686 4.687 12.284 4.687 16.97 0 4.687-4.685 4.687-12.283 0-16.97zm-35.74-7.704c0-6.627-5.372-12-12-12-6.626 0-12 5.373-12 12s5.374 12 12 12c6.628 0 12-5.373 12-12zm-19.823-30.72c4.687-4.686 4.687-12.284 0-16.97-4.686-4.686-12.284-4.686-16.97 0-4.687 4.686-4.687 12.284 0 16.97 4.686 4.687 12.284 4.687 16.97 0z">
                <animateTransform attributeName="transform" type="rotate" from="0 67 67" to="360 67 67" dur="8s"
                    repeatCount="indefinite" />
            </path>
        </svg>
    </div>

    <div class="fixed bottom-6 ltr:right-6 rtl:left-6 z-50" x-data="scrollToTop">
        <template x-if="showTopButton">
            <button type="button"
                class="btn btn-outline-primary rounded-full p-2 animate-pulse bg-[#fafafa] dark:bg-[#060818] dark:hover:bg-primary"
                @click="goToTop">
                <svg width="24" height="24" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.5" fill-rule="evenodd" clip-rule="evenodd"
                        d="M12 20.75C12.4142 20.75 12.75 20.4142 12.75 20L12.75 10.75L11.25 10.75L11.25 20C11.25 20.4142 11.5858 20.75 12 20.75Z"
                        fill="currentColor" />
                    <path
                        d="M6.00002 10.75C5.69667 10.75 5.4232 10.5673 5.30711 10.287C5.19103 10.0068 5.25519 9.68417 5.46969 9.46967L11.4697 3.46967C11.6103 3.32902 11.8011 3.25 12 3.25C12.1989 3.25 12.3897 3.32902 12.5304 3.46967L18.5304 9.46967C18.7449 9.68417 18.809 10.0068 18.6929 10.287C18.5768 10.5673 18.3034 10.75 18 10.75L6.00002 10.75Z"
                        fill="currentColor" />
                </svg>
            </button>
        </template>
    </div>

    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data("scrollToTop", () => ({
                showTopButton: false,
                init() {
                    window.onscroll = () => {
                        this.scrollFunction();
                    };
                },

                scrollFunction() {
                    if (document.body.scrollTop > 50 || document.documentElement.scrollTop > 50) {
                        this.showTopButton = true;
                    } else {
                        this.showTopButton = false;
                    }
                },

                goToTop() {
                    document.body.scrollTop = 0;
                    document.documentElement.scrollTop = 0;
                },
            }));
        });
    </script>

    <x-common.theme-customiser />

    <div class="main-container text-black dark:text-white-dark min-h-screen" :class="[$store.app.navbar]">

        <!-- Admin Sidebar -->
        <x-common.sidebar />

        <div class="main-content flex flex-col min-h-screen">
            <!-- Mobile Sidebar Toggle Button -->
            <button type="button"
                class="fixed top-4 ltr:left-4 rtl:right-4 z-50 lg:hidden p-2 rounded-full bg-white dark:bg-[#0e1726] shadow-lg hover:bg-white-light/90 dark:hover:bg-dark/60 dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary transition-all"
                @click="$store.app.toggleSidebar()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 7L4 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M20 17L4 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>

            <div class="dvanimation p-6 animate__animated" :class="[$store.app.animation]">
                {{ $slot }}
            </div>

            <x-common.footer />
        </div>
    </div>

    <!-- زر عائم للداشبورد (للإدمن فقط) -->
    <x-admin.floating-dashboard-button />

    <script src="/assets/js/alpine-collaspe.min.js"></script>
    <script src="/assets/js/alpine-persist.min.js"></script>
    <script defer src="/assets/js/alpine-ui.min.js"></script>
    <script defer src="/assets/js/alpine-focus.min.js"></script>
    <script defer src="/assets/js/alpine.min.js"></script>
    <script src="/assets/js/custom.js"></script>

    <!-- PWA Service Worker Registration using Workbox -->
    <script type="module">
        import { Workbox } from 'https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-window.prod.mjs';

        if ('serviceWorker' in navigator) {
            const wb = new Workbox('/service-worker.js');

            wb.register().then((registration) => {
                console.log('Service Worker registered successfully:', registration.scope);
            }).catch((error) => {
                console.log('Service Worker registration failed:', error);
            });

            // Listen for updates
            wb.addEventListener('installed', (event) => {
                if (event.isUpdate) {
                    console.log('New service worker available');
                }
            });
        }

        // Listen for appinstalled event (optional - just for logging)
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
        }, { passive: true });
    </script>

    <!-- PWA: حفظ الصفحة الحالية ومنع الرجوع إلى صفحة تسجيل الدخول -->
    <script>
        (function() {
            // صفحات تسجيل الدخول التي يجب منع الرجوع إليها
            const loginPages = ['/admin/login', '/delegate/login'];
            let currentPath = window.location.pathname;
            let isLoginPage = loginPages.some(page => currentPath.includes(page));

            // دالة للتحقق من حالة تسجيل الدخول وإعادة التوجيه إذا لزم الأمر
            function checkAndRedirect() {
                currentPath = window.location.pathname;
                isLoginPage = loginPages.some(page => currentPath.includes(page));

                if (isLoginPage) {
                    // التحقق من وجود cookies تسجيل الدخول
                    const hasRememberToken = document.cookie.includes('remember_web_');
                    const hasSessionCookie = document.cookie.includes('laravel_session');

                    if (hasRememberToken || hasSessionCookie) {
                        // المستخدم مسجل دخول، إعادة التوجيه إلى الداشبورد
                        const dashboardUrl = currentPath.includes('/admin/') || currentPath.includes('/delegate/')
                            ? (currentPath.includes('/admin/') ? '/admin/dashboard' : '/delegate/dashboard')
                            : '/admin/dashboard';
                        window.location.replace(dashboardUrl);
                        return true;
                    }
                }
                return false;
            }

            // فحص فوري عند تحميل الصفحة
            if (checkAndRedirect()) {
                return; // إيقاف تنفيذ باقي الكود إذا تم إعادة التوجيه
            }

            // حفظ الصفحة الحالية في localStorage (استثناء صفحات تسجيل الدخول)
            function saveCurrentPage() {
                if (!isLoginPage && typeof Storage !== 'undefined') {
                    // حفظ URL الحالي
                    localStorage.setItem('pwa_last_page', window.location.href);
                    localStorage.setItem('pwa_last_path', window.location.pathname);
                }
            }

            // حفظ الصفحة عند تحميل الصفحة
            saveCurrentPage();

            // حفظ الصفحة عند تغيير الصفحة (للتنقل داخل التطبيق)
            window.addEventListener('beforeunload', saveCurrentPage);

            // منع الرجوع إلى صفحة تسجيل الدخول إذا كان المستخدم مسجل دخول
            if (!isLoginPage) {
                // إزالة صفحة تسجيل الدخول من history إذا كانت موجودة
                if (window.history && window.history.replaceState) {
                    // استخدام replaceState لإزالة صفحة تسجيل الدخول من history
                    window.history.replaceState({ preventBack: true, isAuthenticated: true }, null, window.location.href);
                }

                // إضافة صفحة افتراضية في history لمنع الرجوع إلى صفحة تسجيل الدخول
                if (window.history && window.history.pushState) {
                    // إضافة صفحة افتراضية في history
                    window.history.pushState({ preventBack: true, isAuthenticated: true }, null, window.location.href);

                    // منع الرجوع إلى صفحة تسجيل الدخول
                    let isNavigating = false;

                    function handlePopState(event) {
                        if (isNavigating) return;

                        currentPath = window.location.pathname;
                        const isTryingToGoToLogin = loginPages.some(page => currentPath.includes(page));

                        if (isTryingToGoToLogin) {
                            isNavigating = true;
                            // إعادة توجيه إلى الداشبورد
                            const dashboardUrl = currentPath.includes('/admin/') || currentPath.includes('/delegate/')
                                ? (currentPath.includes('/admin/') ? '/admin/dashboard' : '/delegate/dashboard')
                                : '/admin/dashboard';
                            window.location.replace(dashboardUrl);
                            return;
                        }

                        // إعادة إضافة الصفحة في history لمنع الرجوع
                        if (event.state && event.state.preventBack) {
                            window.history.pushState({ preventBack: true, isAuthenticated: true }, null, window.location.href);
                        }
                    }

                    window.addEventListener('popstate', handlePopState);

                    // التحقق من حالة الصفحة عند العودة من back button (للموبايل)
                    window.addEventListener('pageshow', function(event) {
                        // التحقق من حالة تسجيل الدخول عند إعادة فتح الصفحة
                        if (checkAndRedirect()) {
                            return;
                        }

                        if (event.persisted) {
                            // الصفحة تم تحميلها من cache (back button)
                            currentPath = window.location.pathname;
                            const isTryingToGoToLogin = loginPages.some(page => currentPath.includes(page));

                            if (isTryingToGoToLogin) {
                                const dashboardUrl = currentPath.includes('/admin/') || currentPath.includes('/delegate/')
                                    ? (currentPath.includes('/admin/') ? '/admin/dashboard' : '/delegate/dashboard')
                                    : '/admin/dashboard';
                                window.location.replace(dashboardUrl);
                            }
                        }
                    });

                    // إضافة hashchange event listener كحل احتياطي
                    window.addEventListener('hashchange', function() {
                        if (checkAndRedirect()) {
                            return;
                        }
                    });

                    // فحص دوري للتحقق من URL الحالي (كل 2 ثانية)
                    let lastCheckedPath = currentPath;
                    setInterval(function() {
                        currentPath = window.location.pathname;
                        if (currentPath !== lastCheckedPath) {
                            lastCheckedPath = currentPath;
                            if (checkAndRedirect()) {
                                return;
                            }
                        }
                    }, 2000);
                }
            }

            // إضافة visibilitychange event listener للتحقق من حالة تسجيل الدخول عند إعادة فتح التطبيق
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    // التطبيق أصبح مرئياً (تم إعادة فتحه)
                    setTimeout(function() {
                        if (checkAndRedirect()) {
                            return;
                        }
                    }, 100);
                }
            });
        })();
    </script>

    <!-- PWA: التحقق من حالة تسجيل الدخول عند تحميل الصفحة -->
    <script>
        (function() {
            // التحقق من حالة تسجيل الدخول عند تحميل الصفحة
            // هذا يساعد في PWA لضمان بقاء المستخدم مسجل دخول
            if (typeof Storage !== 'undefined') {
                // التحقق من وجود remember token في cookies
                const hasRememberToken = document.cookie.includes('remember_web_');
                const hasSessionCookie = document.cookie.includes('laravel_session');

                // إذا كان هناك remember token أو session cookie، المستخدم يجب أن يكون مسجل دخول
                // إذا لم يكن كذلك، قد تكون هناك مشكلة في الـ cache
                if (hasRememberToken || hasSessionCookie) {
                    // محاولة تحديث الصفحة للحصول على حالة تسجيل الدخول الصحيحة
                    // لكن فقط إذا لم نكن في صفحة تسجيل الدخول
                    const currentPath = window.location.pathname;
                    const isLoginPage = currentPath.includes('/admin/login') || currentPath.includes('/delegate/login');

                    if (!isLoginPage) {
                        // التحقق من حالة تسجيل الدخول عبر AJAX
                        // هذا يساعد في تحديث حالة تسجيل الدخول في PWA
                        fetch('/api/check-auth', {
                            method: 'GET',
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            }
                        }).then(response => {
                            if (response.ok) {
                                return response.json();
                            }
                        }).then(data => {
                            // إذا كان المستخدم غير مسجل دخول رغم وجود cookies، قد تكون هناك مشكلة
                            // لكن لا نفعل شيء تلقائياً لأن Laravel middleware سيتعامل مع هذا
                            if (data && !data.authenticated && (hasRememberToken || hasSessionCookie)) {
                                // قد تكون هناك مشكلة في الـ cache، لكن لا نفعل شيء تلقائياً
                                // Laravel middleware سيتعامل مع هذا عند محاولة الوصول لصفحة محمية
                            }
                        }).catch(() => {
                            // إذا فشل الطلب، لا نفعل شيء
                        });
                    }
                }
            }
        })();
    </script>
</body>

</html>
