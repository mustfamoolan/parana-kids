<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav x-data="sidebar"
        class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex justify-between items-center px-4 py-3">
                <div class="main-logo flex items-center shrink-0">
                    <img class="w-8 ml-[5px] flex-none" src="/assets/images/ParanaKids.png"
                        alt="image" />
                    <span
                        class="text-2xl ltr:ml-1.5 rtl:mr-1.5  font-semibold  align-middle lg:inline dark:text-white-light">المخزن</span>
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
            <ul class="perfect-scrollbar relative font-semibold space-y-0.5 h-[calc(100vh-80px)] overflow-y-auto overflow-x-hidden  p-4 py-0"
                x-data="{ activeDropdown: null }">
                {{-- القائمة للمورد - فقط صفحتين --}}
                @if(auth()->check() && auth()->user()->isPrivateSupplier())
                <li class="menu nav-item">
                    <a href="{{ route('admin.invoices.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إنشاء فاتورة</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.invoices.my-invoices') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الفواتير المحفوظة</span>
                        </div>
                    </a>
                </li>
                @endif

                {{-- القائمة للمدير والمجهز (ليس للمورد) --}}
                @if(auth()->check() && auth()->user()->isAdminOrSupplier() && !auth()->user()->isPrivateSupplier())
                <li class="menu nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link group">
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
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">لوحة التحكم</span>
                        </div>
                    </a>
                </li>

                @if(auth()->user()->isAdmin())
                <li class="menu nav-item">
                    <a href="{{ route('admin.users.index') }}" class="nav-link group">
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
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إدارة المستخدمين</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.reports') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">التقارير</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.sales-report') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">كشف مبيعات</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.expenses.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">المصروفات</span>
                        </div>
                    </a>
                </li>
                @endif

                {{-- زر المراسلة للمدير والمجهز --}}
                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSupplier() || auth()->user()->isPrivateSupplier()))
                <li class="menu nav-item">
                    <a href="{{ route('chat.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5" d="M17.4975 18.4851L20.6281 9.09373C21.8764 5.34874 22.5006 3.47624 21.5122 2.48782C20.5237 1.49939 18.6511 2.12356 14.906 3.37189L5.57477 6.48218C3.49295 7.1761 2.45203 7.52305 2.13608 8.28637C2.06182 8.46577 2.01692 8.65596 2.00311 8.84963C1.94433 9.67365 2.72018 10.4495 4.27188 12.0011L4.55451 12.2837C4.80921 12.5384 4.93655 12.6658 5.03282 12.8075C5.22269 13.0871 5.33046 13.4143 5.34393 13.7519C5.35076 13.9232 5.32403 14.1013 5.27057 14.4574C5.07488 15.7612 4.97703 16.4131 5.0923 16.9147C5.32205 17.9146 6.09599 18.6995 7.09257 18.9433C7.59255 19.0656 8.24576 18.977 9.5522 18.7997L9.62363 18.79C9.99191 18.74 10.1761 18.715 10.3529 18.7257C10.6738 18.745 10.9838 18.8496 11.251 19.0285C11.3981 19.1271 11.5295 19.2585 11.7923 19.5213L12.0436 19.7725C13.5539 21.2828 14.309 22.0379 15.1101 21.9985C15.3309 21.9877 15.5479 21.9365 15.7503 21.8474C16.4844 21.5244 16.8221 20.5113 17.4975 18.4851Z" stroke="currentColor" stroke-width="1.5"/>
                                <path opacity="0.5" d="M6 18L21 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">المراسلة</span>
                        </div>
                    </a>
                </li>
                @endif

                @endif

                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSupplier()))
                @if(auth()->user()->isAdmin())
                <li class="menu nav-item">
                    <a href="{{ route('admin.private-warehouses.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">المخازن الخاصة</span>
                        </div>
                    </a>
                </li>
                @endif

                {{-- المخازن العادية - فقط للمدير والمجهز (ليس للمورد) --}}
                @if(!auth()->user()->isPrivateSupplier())
                <li class="menu nav-item">
                    <a href="{{ route('admin.warehouses.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M2 12C2 8.229 2 6.343 3.172 5.172C4.343 4 6.229 4 10 4H14C17.771 4 19.657 4 20.828 5.172C22 6.343 22 8.229 22 12C22 15.771 22 17.657 20.828 18.828C19.657 20 17.771 20 14 20H10C6.229 20 4.343 20 3.172 18.828C2 17.657 2 15.771 2 12Z"
                                    fill="currentColor" />
                                <path
                                    d="M6 8C6 7.44772 6.44772 7 7 7H17C17.5523 7 18 7.44772 18 8C18 8.55228 17.5523 9 17 9H7C6.44772 9 6 8.55228 6 8Z"
                                    fill="currentColor" />
                                <path
                                    d="M6 12C6 11.4477 6.44772 11 7 11H17C17.5523 11 18 11.4477 18 12C18 12.5523 17.5523 13 17 13H7C6.44772 13 6 12.5523 6 12Z"
                                    fill="currentColor" />
                                <path
                                    d="M7 15C6.44772 15 6 15.4477 6 16C6 16.5523 6.44772 17 7 17H12C12.5523 17 13 16.5523 13 16C13 15.4477 12.5523 15 12 15H7Z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">المخازن</span>
                        </div>
                    </a>
                </li>
                @endif

                <li class="menu nav-item">
                    <a href="{{ route('admin.product-movements.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">كشف حركة المواد</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.transfers.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">نقل المواد</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.product-links.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" fill="currentColor" />
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إنشاء رابط</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.orders.management') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.order-movements.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">كشف حركة الطلبات</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.bulk-returns.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إرجاع طلبات</span>
                        </div>
                    </a>
                </li>

                <li class="menu nav-item">
                    <a href="{{ route('admin.orders.partial-returns.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إرجاع جزئي</span>
                        </div>
                    </a>
                </li>
                @endif

@if(auth()->check() && auth()->user()->isDelegate())
<li class="menu nav-item">
    <a href="{{ route('delegate.products.all') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">المنتجات</span>
        </div>
    </a>
</li>

<li class="menu nav-item">
    <a href="{{ route('delegate.orders.start') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" opacity="0.5"/>
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">طلب جديد</span>
            <span class="badge bg-success ltr:ml-auto rtl:mr-auto" id="activeCartBadge" style="display: none;">نشط</span>
            <script>
                // عرض badge "نشط" من localStorage
                if (window.cartStorage && window.cartStorage.hasActiveCart()) {
                    const badge = document.getElementById('activeCartBadge');
                    if (badge) {
                        badge.style.display = 'inline-block';
                    }
                }
            </script>
        </div>
    </a>
</li>

<li class="menu nav-item">
    <a href="{{ route('delegate.orders.index') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات</span>
                        @php
                            $pendingOrdersCount = auth()->check() ? \App\Models\Order::where('status', 'pending')->where('delegate_id', auth()->id())->count() : 0;
                        @endphp
            @if($pendingOrdersCount > 0)
                <span class="badge badge-warning ltr:ml-auto rtl:mr-auto">{{ $pendingOrdersCount }}</span>
            @endif
        </div>
    </a>
</li>

<li class="menu nav-item">
    <a href="{{ route('chat.index') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path opacity="0.5" d="M17.4975 18.4851L20.6281 9.09373C21.8764 5.34874 22.5006 3.47624 21.5122 2.48782C20.5237 1.49939 18.6511 2.12356 14.906 3.37189L5.57477 6.48218C3.49295 7.1761 2.45203 7.52305 2.13608 8.28637C2.06182 8.46577 2.01692 8.65596 2.00311 8.84963C1.94433 9.67365 2.72018 10.4495 4.27188 12.0011L4.55451 12.2837C4.80921 12.5384 4.93655 12.6658 5.03282 12.8075C5.22269 13.0871 5.33046 13.4143 5.34393 13.7519C5.35076 13.9232 5.32403 14.1013 5.27057 14.4574C5.07488 15.7612 4.97703 16.4131 5.0923 16.9147C5.32205 17.9146 6.09599 18.6995 7.09257 18.9433C7.59255 19.0656 8.24576 18.977 9.5522 18.7997L9.62363 18.79C9.99191 18.74 10.1761 18.715 10.3529 18.7257C10.6738 18.745 10.9838 18.8496 11.251 19.0285C11.3981 19.1271 11.5295 19.2585 11.7923 19.5213L12.0436 19.7725C13.5539 21.2828 14.309 22.0379 15.1101 21.9985C15.3309 21.9877 15.5479 21.9365 15.7503 21.8474C16.4844 21.5244 16.8221 20.5113 17.4975 18.4851Z" stroke="currentColor" stroke-width="1.5"/>
                <path opacity="0.5" d="M6 18L21 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">المراسلة</span>
        </div>
    </a>
</li>

<li class="menu nav-item">
    <a href="{{ route('delegate.product-links.index') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path opacity="0.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" fill="currentColor" />
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إنشاء رابط</span>
        </div>
    </a>
</li>

@endif

                <!-- زر تثبيت PWA -->
                <li class="menu nav-item" id="pwa-install-item">
                    <a href="javascript:void(0)" onclick="window.showInstallInstructions()" class="nav-link group" id="pwa-install-button">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z"
                                    fill="currentColor" />
                                <path
                                    d="M12 16L8 12H11V8H13V12H16L12 16Z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">تثبيت التطبيق</span>
                        </div>
                    </a>
                </li>

                <!-- زر تسجيل الدخول/الخروج -->
                @if(auth()->check())
                    @if(auth()->user()->isAdmin() || auth()->user()->isSupplier() || auth()->user()->isPrivateSupplier())
                        <li class="menu nav-item">
                            <form method="POST" action="{{ route('admin.logout') }}" class="inline w-full">
                                @csrf
                                <button type="submit" class="nav-link group w-full text-left">
                                    <div class="flex items-center">
                                        <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path opacity="0.5"
                                                d="M17 9.00195C19.175 9.01406 20.3529 9.11051 21.1213 9.8789C22 10.7576 22 12.1718 22 15.0002V16.0002C22 18.8286 22 20.2429 21.1213 21.1215C20.2426 22.0002 18.8284 22.0002 16 22.0002H8C5.17157 22.0002 3.75736 22.0002 2.87868 21.1215C2 20.2429 2 18.8286 2 16.0002L2 15.0002C2 12.1718 2 10.7576 2.87868 9.87889C3.64706 9.11051 4.82497 9.01406 7 9.00195"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                            <path d="M12 15L12 2M12 2L15 5.5M12 2L9 5.5" stroke="currentColor"
                                                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span
                                            class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">تسجيل الخروج</span>
                                    </div>
                                </button>
                            </form>
                        </li>
                    @elseif(auth()->user()->isDelegate())
                        <li class="menu nav-item">
                            <form method="POST" action="{{ route('delegate.logout') }}" class="inline w-full">
                                @csrf
                                <button type="submit" class="nav-link group w-full text-left">
                                    <div class="flex items-center">
                                        <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                            fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path opacity="0.5"
                                                d="M17 9.00195C19.175 9.01406 20.3529 9.11051 21.1213 9.8789C22 10.7576 22 12.1718 22 15.0002V16.0002C22 18.8286 22 20.2429 21.1213 21.1215C20.2426 22.0002 18.8284 22.0002 16 22.0002H8C5.17157 22.0002 3.75736 22.0002 2.87868 21.1215C2 20.2429 2 18.8286 2 16.0002L2 15.0002C2 12.1718 2 10.7576 2.87868 9.87889C3.64706 9.11051 4.82497 9.01406 7 9.00195"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                            <path d="M12 15L12 2M12 2L15 5.5M12 2L9 5.5" stroke="currentColor"
                                                stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span
                                            class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">تسجيل الخروج</span>
                                    </div>
                                </button>
                            </form>
                        </li>
                    @endif
                @else
                    <li class="menu nav-item">
                        <a href="{{ request()->is('admin/*') ? route('admin.login') : route('delegate.login') }}" class="nav-link group">
                            <div class="flex items-center">
                                <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                    fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path opacity="0.5"
                                        d="M17 9.00195C19.175 9.01406 20.3529 9.11051 21.1213 9.8789C22 10.7576 22 12.1718 22 15.0002V16.0002C22 18.8286 22 20.2429 21.1213 21.1215C20.2426 22.0002 18.8284 22.0002 16 22.0002H8C5.17157 22.0002 3.75736 22.0002 2.87868 21.1215C2 20.2429 2 18.8286 2 16.0002L2 15.0002C2 12.1718 2 10.7576 2.87868 9.87889C3.64706 9.11051 4.82497 9.01406 7 9.00195"
                                        stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                    <path d="M12 15L12 2M12 2L15 5.5M12 2L9 5.5" stroke="currentColor"
                                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span
                                    class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">تسجيل الدخول</span>
                            </div>
                        </a>
                    </li>
                @endif

                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSupplier()))
                <li class="menu nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link group">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24"
                                fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.5"
                                    d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22Z"
                                    fill="currentColor" />
                                <path
                                    d="M12 15.5C13.933 15.5 15.5 13.933 15.5 12C15.5 10.067 13.933 8.5 12 8.5C10.067 8.5 8.5 10.067 8.5 12C8.5 13.933 10.067 15.5 12 15.5Z"
                                    fill="currentColor" />
                            </svg>
                            <span
                                class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الإعدادات</span>
                        </div>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </nav>

    <!-- Modal تعليمات التثبيت -->
    <div x-data="installModal" x-cloak>
        <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto" :class="open && '!block'">
            <div class="flex items-center justify-center min-h-screen px-4" @click.self="open = false">
                <div x-show="open" x-transition x-transition.duration.300 class="panel border-0 p-0 rounded-lg overflow-hidden w-full max-w-lg my-8">
                    <div class="flex bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-between px-5 py-3">
                        <h5 class="font-bold text-lg dark:text-white-light" x-text="title"></h5>
                        <button type="button" class="text-white-dark hover:text-dark" @click="toggle">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="p-5">
                        <div class="dark:text-white-dark/70 text-base font-medium text-[#1f2937]" x-html="instructions"></div>
                        <div class="flex justify-end items-center mt-8">
                            <button type="button" class="btn btn-primary" @click="toggle">فهمت</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("sidebar", () => ({
            init() {
                const selector = document.querySelector('.sidebar ul a[href="' + window.location.pathname + '"]');
                if (selector) {
                    selector.classList.add('active');
                    const ul = selector.closest('ul.sub-menu');
                    if (ul) {
                        let ele = ul.closest('li.menu').querySelectorAll('.nav-link');
                        if (ele) {
                            ele = ele[0];
                            setTimeout(() => {
                                ele.click();
                            });
                        }
                    }
                }
            },
        }));

        // PWA Install Instructions - Simple approach without beforeinstallprompt
        window.showInstallInstructions = function() {
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;

            // Detect device type
            const isIOS = /iPad|iPhone|iPod/.test(userAgent) && !window.MSStream;
            const isAndroid = /android/i.test(userAgent);
            const isDesktop = !isIOS && !isAndroid;

            // Get modal component
            const modalComponent = window.installModalComponent;
            if (!modalComponent) {
                // Wait a bit for Alpine to initialize
                setTimeout(showInstallInstructions, 100);
                return;
            }

            // Set instructions based on device type
            if (isIOS) {
                modalComponent.title = 'كيفية تثبيت التطبيق على iOS';
                modalComponent.instructions = `
                    <div class="space-y-3">
                        <p class="text-sm font-semibold mb-2">لتثبيت التطبيق على iOS:</p>
                        <ol class="list-decimal list-inside space-y-2 text-sm rtl:text-right mb-4">
                            <li>اضغط على زر <strong>المشاركة</strong> (Share) في أسفل الشاشة</li>
                            <li>اختر <strong>"إضافة إلى الشاشة الرئيسية"</strong> (Add to Home Screen)</li>
                            <li>اضغط على <strong>"إضافة"</strong> (Add) في الزاوية العلوية اليمنى</li>
                        </ol>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <p class="text-xs text-blue-700 dark:text-blue-300"><strong>ملاحظة:</strong> إذا لم يظهر خيار "إضافة إلى الشاشة الرئيسية"، تأكد من فتح الموقع في Safari وليس في متصفح آخر.</p>
                        </div>
                    </div>
                `;
            } else if (isAndroid) {
                modalComponent.title = 'كيفية تثبيت التطبيق على Android';
                modalComponent.instructions = `
                    <div class="space-y-3">
                        <p class="text-sm font-semibold mb-2">لتثبيت التطبيق على Android:</p>
                        <ol class="list-decimal list-inside space-y-2 text-sm rtl:text-right mb-4">
                            <li>اضغط على <strong>قائمة المتصفح</strong> (ثلاث نقاط ⋮ في الزاوية العلوية اليمنى)</li>
                            <li>ابحث عن <strong>"إضافة إلى الشاشة الرئيسية"</strong> أو <strong>"تثبيت التطبيق"</strong> أو <strong>"Install app"</strong></li>
                            <li>إذا لم تجد الخيار، اضغط على <strong>"إعدادات الموقع"</strong> أو <strong>"Site settings"</strong></li>
                            <li>في صفحة الإعدادات، ابحث عن <strong>"تثبيت التطبيق"</strong> أو <strong>"Install app"</strong></li>
                            <li>اضغط على <strong>"إضافة"</strong> أو <strong>"تثبيت"</strong> للتأكيد</li>
                        </ol>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <p class="text-xs text-blue-700 dark:text-blue-300"><strong>ملاحظة:</strong> إذا لم يظهر خيار التثبيت، قد يكون بسبب وجود تطبيقات أخرى مثبتة. جرب حذف بعض التطبيقات المثبتة أو استخدم Chrome بدلاً من متصفحات أخرى.</p>
                        </div>
                    </div>
                `;
            } else {
                modalComponent.title = 'كيفية تثبيت التطبيق على الكمبيوتر';
                modalComponent.instructions = `
                    <div class="space-y-3">
                        <p class="text-sm font-semibold mb-2">لتثبيت التطبيق على الكمبيوتر:</p>
                        <ol class="list-decimal list-inside space-y-2 text-sm rtl:text-right mb-4">
                            <li>ابحث عن أيقونة <strong>التثبيت</strong> (⊕) في شريط العنوان (بجانب شريط البحث)</li>
                            <li>أو اضغط على <strong>قائمة المتصفح</strong> (ثلاث نقاط ⋮) → <strong>"تثبيت التطبيق"</strong> أو <strong>"Install app"</strong></li>
                            <li>إذا لم تجد الخيار، اضغط على <strong>"إعدادات"</strong> → <strong>"التطبيقات"</strong> → <strong>"تثبيت التطبيق"</strong></li>
                            <li>اضغط على <strong>"تثبيت"</strong> في النافذة المنبثقة</li>
                        </ol>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                            <p class="text-xs text-blue-700 dark:text-blue-300"><strong>ملاحظة:</strong> إذا لم يظهر خيار التثبيت، تأكد من استخدام Chrome أو Edge. بعض المتصفحات الأخرى لا تدعم تثبيت التطبيقات.</p>
                        </div>
                    </div>
                `;
            }

            // Show modal
            modalComponent.open = true;
        }

        // Listen for appinstalled event (optional - just for logging)
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
        }, { passive: true });

        // Alpine.js Modal Component for Install Instructions
        Alpine.data("installModal", () => ({
            open: false,
            title: 'كيفية تثبيت التطبيق',
            instructions: '',

            toggle() {
                this.open = !this.open;
            },

            init() {
                // Store reference to this component globally
                window.installModalComponent = this;
            }
        }));
    });
</script>
