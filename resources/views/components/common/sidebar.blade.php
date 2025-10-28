<div :class="{ 'dark text-white-dark': $store.app.semidark }">
    <nav x-data="sidebar"
        class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
        <div class="bg-white dark:bg-[#0e1726] h-full">
            <div class="flex justify-between items-center px-4 py-3">
                <a href="/" class="main-logo flex items-center shrink-0">
                    <img class="w-8 ml-[5px] flex-none" src="/assets/images/logo.svg"
                        alt="image" />
                    <span
                        class="text-2xl ltr:ml-1.5 rtl:mr-1.5  font-semibold  align-middle lg:inline dark:text-white-light">Paraná Kids</span>
                </a>
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
                @if(auth()->check() && auth()->user()->isAdmin())
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
                @endif

                @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isSupplier()))
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
                    <button type="button" class="nav-link group w-full" @click="toggleMenu('orders')">
                        <div class="flex items-center">
                            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">تفاصيل الطلبات</span>
                        </div>
                        <div>
                            <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                    </button>
                    <ul x-show="openMenu === 'orders'" class="sub-menu">
                        <li>
                            <a href="{{ route('admin.orders.index') }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات الغير مقيدة</span>
                                    @php
                                        $pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();
                                    @endphp
                                    @if($pendingOrdersCount > 0)
                                        <span class="badge badge-warning ltr:ml-auto rtl:mr-auto">{{ $pendingOrdersCount }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.orders.confirmed') }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات المقيدة</span>
                                    @php
                                        $confirmedOrdersCount = \App\Models\Order::where('status', 'confirmed')->count();
                                    @endphp
                                    @if($confirmedOrdersCount > 0)
                                        <span class="badge badge-success ltr:ml-auto rtl:mr-auto">{{ $confirmedOrdersCount }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.orders.returned') }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات المسترجعة</span>
                                    @php
                                        $returnedOrdersCount = \App\Models\Order::where('status', 'returned')->count();
                                    @endphp
                                    @if($returnedOrdersCount > 0)
                                        <span class="badge badge-warning ltr:ml-auto rtl:mr-auto">{{ $returnedOrdersCount }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.orders.deleted') }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات المحذوفة</span>
                                    @php
                                        $deletedOrdersCount = \App\Models\Order::onlyTrashed()->count();
                                    @endphp
                                    @if($deletedOrdersCount > 0)
                                        <span class="badge badge-danger ltr:ml-auto rtl:mr-auto">{{ $deletedOrdersCount }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.order-movements.index') }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">كشف حركة الطلبات</span>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.bulk-returns.index') }}" class="nav-link group">
                                <div class="flex items-center">
                                    <svg class="group-hover:!text-primary shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                    <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">إرجاع طلبات</span>
                                </div>
                            </a>
                        </li>
                    </ul>
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
            @if(session('current_cart_id'))
                <span class="badge bg-success ltr:ml-auto rtl:mr-auto">نشط</span>
            @endif
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
    <a href="{{ route('delegate.archived.index') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الأرشفة</span>
            @php
                $archivedCount = auth()->check() ? \App\Models\ArchivedOrder::where('delegate_id', auth()->id())->count() : 0;
            @endphp
            @if($archivedCount > 0)
                <span class="badge badge-secondary ltr:ml-auto rtl:mr-auto">{{ $archivedCount }}</span>
            @endif
        </div>
    </a>
</li>

<li class="menu nav-item">
    <a href="{{ route('delegate.orders.deleted') }}" class="nav-link group">
        <div class="flex items-center">
            <svg class="group-hover:!text-primary shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none">
                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">الطلبات المحذوفة</span>
            @php
                $deletedOrdersCount = auth()->check() ? \App\Models\Order::onlyTrashed()->where('delegate_id', auth()->id())->count() : 0;
            @endphp
            @if($deletedOrdersCount > 0)
                <span class="badge badge-danger ltr:ml-auto rtl:mr-auto">{{ $deletedOrdersCount }}</span>
            @endif
        </div>
    </a>
</li>
@endif
            </ul>
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
    });
</script>
