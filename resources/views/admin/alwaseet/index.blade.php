<x-layout.admin>
    <div class="panel">
                <h5 class="mb-5 text-lg font-semibold dark:text-white-light">مرحباً بك في صفحة الوسيط</h5>

                @if(!$isConnected)
                    <div class="alert alert-warning mb-5">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <strong>تنبيه:</strong> لم يتم ربط الواسط بعد. يرجى الذهاب إلى <a href="{{ route('admin.alwaseet.settings') }}" class="underline">الإعدادات</a> لإعداد الاتصال أولاً.
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                    <a href="{{ route('admin.alwaseet.auto-integration') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                            <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">التكامل التلقائي</h6>
                            <p class="text-sm text-gray-500">إعدادات إنشاء الشحنات تلقائياً</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.auto-sync') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10">
                            <svg class="h-6 w-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">المزامنة التلقائية</h6>
                            <p class="text-sm text-gray-500">إدارة المزامنة الدورية للطلبات</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.notifications') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10">
                            <svg class="h-6 w-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">الإشعارات</h6>
                            <p class="text-sm text-gray-500">إشعارات تغيير حالات الشحنات</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.reports') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-info/10">
                            <svg class="h-6 w-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">التقارير والإحصائيات</h6>
                            <p class="text-sm text-gray-500">تقارير شاملة عن الشحنات</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.rate-limiting') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-danger/10">
                            <svg class="h-6 w-6 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">Rate Limiting</h6>
                            <p class="text-sm text-gray-500">إدارة حدود الطلبات</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.orders') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-secondary/10">
                            <svg class="h-6 w-6 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">الطلبات المسجلة</h6>
                            <p class="text-sm text-gray-500">عرض جميع الطلبات من الواسط</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.receipts') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success/10">
                            <svg class="h-6 w-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">الوصولات</h6>
                            <p class="text-sm text-gray-500">عرض الإيصالات المتاحة للطباعة</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.invoices.index') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-info/10">
                            <svg class="h-6 w-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">الفواتير</h6>
                            <p class="text-sm text-gray-500">عرض وتتبع الفواتير والمدفوعات</p>
                        </div>
                    </a>

                    <a href="{{ route('admin.alwaseet.settings') }}" class="panel flex items-center gap-4 p-5 transition hover:shadow-lg">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-warning/10">
                            <svg class="h-6 w-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h6 class="font-semibold">الإعدادات</h6>
                            <p class="text-sm text-gray-500">إعدادات الربط مع الواسط</p>
                        </div>
                    </a>
                </div>
    </div>
</x-layout.admin>
