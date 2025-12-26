<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset='utf-8' />
    <meta http-equiv='X-UA-Compatible' content='IE=edge' />
    <title>{{ $title ?? 'لوحة المستثمر' }}</title>

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

    <!-- Local Nunito Font -->
    <link rel="stylesheet" href="/assets/css/fonts.css" />

    <script src="/assets/js/perfect-scrollbar.min.js"></script>
    <script defer src="/assets/js/popper.min.js"></script>
    <script defer src="/assets/js/tippy-bundle.umd.min.js"></script>
    <script defer src="/assets/js/sweetalert.min.js"></script>

    @vite(['resources/css/app.css'])

    <style>
        :root {
            --custom-text-primary: #3b3f5c;
            --custom-text-secondary: #888ea8;
        }

        .dark {
            --custom-text-primary: #e0e6ed;
            --custom-text-secondary: #888ea8;
        }

    </style>
</head>

<body x-data="main" class="antialiased relative font-nunito text-sm font-normal overflow-x-hidden vertical"
    :class="[$store.app.sidebar ? 'toggle-sidebar' : '', $store.app.theme === 'dark' || $store.app.isDarkMode ? 'dark' : '']">

    <!-- sidebar menu overlay -->
    <div x-cloak class="fixed inset-0 bg-[black]/60 z-40 lg:hidden" :class="{ 'hidden': !$store.app.sidebar }"
        @click="$store.app.toggleSidebar()"></div>

    <div class="main-container text-black dark:text-white-dark min-h-screen">

        <!-- Investor Sidebar -->
        <x-investor.sidebar />

        <div class="main-content flex flex-col min-h-screen">
            <!-- Mobile Sidebar Toggle Button -->
            <button type="button"
                class="fixed top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 z-50 lg:hidden p-2 rounded-full bg-primary text-white shadow-lg hover:bg-primary/90 dark:hover:bg-primary/80 transition-all"
                @click="$store.app.toggleSidebar()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 7L4 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M20 17L4 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>

            <!-- Desktop Sidebar Show Button (يظهر فقط عندما يكون السايد بار مخفياً) -->
            <button type="button"
                class="fixed top-1/2 -translate-y-1/2 ltr:left-4 rtl:right-4 z-50 hidden lg:block p-2 rounded-full bg-primary text-white shadow-lg hover:bg-primary/90 dark:hover:bg-primary/80 transition-all"
                :class="{ '!hidden': !$store.app.sidebar }"
                @click="$store.app.toggleSidebar()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 7L20 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path opacity="0.5" d="M4 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    <path d="M4 17L20 17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
            </button>

            <!-- Header -->
            <header class="bg-white dark:bg-[#0e1726] shadow-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-30">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <a href="{{ route('investor.dashboard') }}" class="flex items-center gap-2">
                            <img class="w-8" src="/assets/images/ParanaKids.png" alt="Logo" />
                            <span class="hidden sm:inline text-lg sm:text-xl font-semibold dark:text-white-light">لوحة المستثمر</span>
                        </a>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4">
                        <span class="hidden sm:inline text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ session('investor_name') ?? 'مستثمر' }}
                        </span>
                        <form method="POST" action="{{ route('investor.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1 hidden sm:inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                <span class="hidden sm:inline">تسجيل الخروج</span>
                                <span class="sm:hidden">خروج</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>

            <!-- Footer -->
            <footer class="bg-white dark:bg-[#0e1726] border-t border-gray-200 dark:border-gray-700 py-3 sm:py-4 mt-auto">
                <div class="px-4 sm:px-6 text-center text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                    <p>&copy; {{ date('Y') }} Parana Kids. جميع الحقوق محفوظة.</p>
                </div>
            </footer>
        </div>
    </div>

    <script src="/assets/js/alpine-collaspe.min.js"></script>
    <script src="/assets/js/alpine-persist.min.js"></script>
    <script defer src="/assets/js/alpine-ui.min.js"></script>
    <script defer src="/assets/js/alpine-focus.min.js"></script>
    <script defer src="/assets/js/alpine.min.js"></script>
    <script src="/assets/js/custom.js"></script>
    
</body>

</html>

