<x-layout.admin>
    <div class="container mx-auto px-4 py-6">
        <!-- العنوان -->
        <h1 class="text-2xl font-bold mb-6 text-center">لوحة تحكم الوسيط</h1>

        <!-- الأزرار الرئيسية -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
            <!-- 1. الوسيط - الرئيسية -->
            <a href="{{ route('admin.alwaseet.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M3 10C3 6.22876 3 4.34315 4.17157 3.17157C5.34315 2.34315 7.22876 2.34315 11 2.34315H13C16.7712 2.34315 18.6569 2.34315 19.8284 3.17157C21 4.34315 21 6.22876 21 10V14C21 17.7712 21 19.6569 19.8284 20.8284C18.6569 22 16.7712 22 13 22H11C7.22876 22 5.34315 22 4.17157 20.8284C3 19.6569 3 17.7712 3 14V10Z" fill="currentColor"/>
                        <path d="M8 12C8 11.4477 8.44772 11 9 11H15C15.5523 11 16 11.4477 16 12C16 12.5523 15.5523 13 15 13H9C8.44772 13 8 12.5523 8 12Z" fill="currentColor"/>
                        <path d="M8 16C8 15.4477 8.44772 15 9 15H13C13.5523 15 14 15.4477 14 16C14 16.5523 13.5523 17 13 17H9C8.44772 17 8 16.5523 8 16Z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">الوسيط - الرئيسية</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">الصفحة الرئيسية للوسيط</p>
            </a>

            <!-- 2. الوسيط - الطلبات المسجلة -->
            <a href="{{ route('admin.alwaseet.orders') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">الوسيط - الطلبات المسجلة</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض الطلبات المسجلة في الوسيط</p>
            </a>

            <!-- 3. الوسيط - إضافة طلب -->
            <a href="{{ route('admin.alwaseet.add-order-from-pending') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">الوسيط - إضافة طلب</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إضافة طلب جديد للوسيط</p>
            </a>

            <!-- 4. رفع وطباع طلبات الوسيط -->
            <a href="{{ route('admin.alwaseet.print-and-upload-orders') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-purple/10 to-purple/5 border-2 border-purple/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4v16m8-8H4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-purple mb-2">رفع وطباع طلبات الوسيط</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">رفع وطباعة طلبات الوسيط</p>
            </a>

            <!-- 5. الوسيط - الوصولات -->
            <a href="{{ route('admin.alwaseet.receipts') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">الوسيط - الوصولات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض وصولات الوسيط</p>
            </a>

            <!-- 6. الوسيط - الفواتير -->
            <a href="{{ route('admin.alwaseet.invoices.index') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-danger/10 to-danger/5 border-2 border-danger/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-danger/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-danger mb-2">الوسيط - الفواتير</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض فواتير الوسيط</p>
            </a>

            <!-- 7. الوسيط - التكامل التلقائي -->
            <a href="{{ route('admin.alwaseet.auto-integration') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-primary/10 to-primary/5 border-2 border-primary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-primary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-primary mb-2">الوسيط - التكامل التلقائي</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إعدادات التكامل التلقائي</p>
            </a>

            <!-- 8. الوسيط - المزامنة التلقائية -->
            <a href="{{ route('admin.alwaseet.auto-sync') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-success/10 to-success/5 border-2 border-success/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-success/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-success mb-2">الوسيط - المزامنة التلقائية</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إعدادات المزامنة التلقائية</p>
            </a>

            <!-- 9. الوسيط - الإشعارات -->
            <a href="{{ route('admin.alwaseet.notifications') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-warning/10 to-warning/5 border-2 border-warning/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-warning/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-warning mb-2">الوسيط - الإشعارات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض إشعارات الوسيط</p>
            </a>

            <!-- 10. الوسيط - التقارير -->
            <a href="{{ route('admin.alwaseet.reports') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-info/10 to-info/5 border-2 border-info/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-info/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-info mb-2">الوسيط - التقارير</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">عرض تقارير الوسيط</p>
            </a>

            <!-- 11. الوسيط - Rate Limiting -->
            <a href="{{ route('admin.alwaseet.rate-limiting') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-danger/10 to-danger/5 border-2 border-danger/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-danger/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-danger mb-2">الوسيط - Rate Limiting</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إعدادات Rate Limiting</p>
            </a>

            <!-- 12. الوسيط - الإعدادات -->
            <a href="{{ route('admin.alwaseet.settings') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-secondary/10 to-secondary/5 border-2 border-secondary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-secondary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" fill="currentColor"/>
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-secondary mb-2">الوسيط - الإعدادات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إعدادات الوسيط</p>
            </a>
        </div>
    </div>
</x-layout.admin>

