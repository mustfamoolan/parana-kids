<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav x-data="sidebar"
        class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex justify-between items-center px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="main-logo flex items-center shrink-0">
                    <img class="w-8 ml-[5px] flex-none" src="/assets/images/ParanaKids.png"
                        alt="image" />
                    <span
                        class="text-xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle dark:text-white-light">لوحة المستثمر</span>
                </div>
                <a href="javascript:;"
                    class="collapse-icon w-8 h-8 rounded-full flex items-center hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light transition duration-300 rtl:rotate-180"
                    @click="$store.app.toggleSidebar()">
                    <svg class="w-5 h-5 m-auto" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </div>
            <ul class="perfect-scrollbar relative font-semibold space-y-0.5 h-[calc(100vh-140px)] overflow-y-auto overflow-x-hidden p-4 py-0">
                <li class="menu nav-item">
                    <a href="{{ route('investor.dashboard') }}" 
                       class="nav-link group {{ request()->routeIs('investor.dashboard') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M2 12.2039C2 9.91549 2 8.77128 2.5192 7.82274C3.0384 6.87421 3.98695 6.28551 5.88403 5.10813L7.88403 3.86687C9.88939 2.62229 10.8921 2 12 2C13.1079 2 14.1106 2.62229 16.116 3.86687L18.116 5.10812C20.0131 6.28551 20.9616 6.87421 21.4808 7.82274C22 8.77128 22 9.91549 22 12.2039V13.725C22 17.6258 22 19.5763 20.8284 20.7881C19.6569 22 17.7712 22 14 22H10C6.22876 22 4.34315 22 3.17157 20.7881C2 19.5763 2 17.6258 2 13.725V12.2039Z"
                                    fill="currentColor" />
                                <path
                                    d="M9 17.25C8.58579 17.25 8.25 17.5858 8.25 18C8.25 18.4142 8.58579 18.75 9 18.75H15C15.4142 18.75 15.75 18.4142 15.75 18C15.75 17.5858 15.4142 17.25 15 17.25H9Z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الرئيسية</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('investor.investments') }}" 
                       class="nav-link group {{ request()->routeIs('investor.investments') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M3 13.2501V17.7501C3 19.8567 3 20.9101 3.58579 21.4959C4.17157 22.0817 5.22503 22.0817 7.33194 22.0817H16.6681C18.775 22.0817 19.8284 22.0817 20.4142 21.4959C21 20.9101 21 19.8567 21 17.7501V13.2501C21 11.1435 21 10.0901 20.4142 9.50431C19.8284 8.91853 18.775 8.91853 16.6681 8.91853H7.33194C5.22503 8.91853 4.17157 8.91853 3.58579 9.50431C3 10.0901 3 11.1435 3 13.2501Z"
                                    fill="currentColor" />
                                <path d="M3 6.91852L12 2L21 6.91852M3 6.91852L12 11.0815M3 6.91852V8.91852M21 6.91852L12 11.0815M21 6.91852V8.91852M12 11.0815V22.0815"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الاستثمارات</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('investor.transactions') }}" 
                       class="nav-link group {{ request()->routeIs('investor.transactions') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الحركات المالية</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('investor.profile') }}" 
                       class="nav-link group {{ request()->routeIs('investor.profile') ? 'active' : '' }}">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z"
                                    fill="currentColor" />
                                <path
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الملف الشخصي</span>
                        </div>
                    </a>
                </li>
            </ul>

            <!-- تسجيل الخروج -->
            <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-[#0e1726]">
                <div class="mb-3 px-3 py-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ session('investor_name') ?? 'مستثمر' }}
                </div>
                <form method="POST" action="{{ route('investor.logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full nav-link group text-danger">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-danger shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M17 16L21 12M21 12L17 8M21 12H7M13 16V17C13 19.2091 11.2091 21 9 21H5C2.79086 21 1 19.2091 1 17V7C1 4.79086 2.79086 3 5 3H9C11.2091 3 13 4.79086 13 7V8"
                                    stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3">تسجيل الخروج</span>
                        </div>
                    </button>
                </form>
            </div>
        </div>
    </nav>
</div>
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("sidebar", () => ({
            init() {
                const selector = document.querySelector('.sidebar ul a[href="' + window.location.pathname + '"]');
                if (selector) {
                    selector.classList.add('active');
                }
            },
        }));
    });
</script>

