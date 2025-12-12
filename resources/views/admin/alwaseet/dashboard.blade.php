<x-layout.admin>
    <div class="container mx-auto px-4 py-6">
        <!-- العنوان -->
        <h1 class="text-2xl font-bold mb-6 text-center">لوحة تحكم الوسيط</h1>

        <!-- الأزرار الرئيسية -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 max-w-4xl mx-auto">
            <!-- 1. تتبع طلبات -->
            <a href="{{ route('admin.alwaseet.track-orders') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-indigo/10 to-indigo/5 border-2 border-indigo/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-indigo/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M12 2C8.13401 2 5 5.13401 5 9C5 14.25 12 22 12 22C12 22 19 14.25 19 9C19 5.13401 15.866 2 12 2Z" fill="currentColor"/>
                        <path d="M12 11C13.1046 11 14 10.1046 14 9C14 7.89543 13.1046 7 12 7C10.8954 7 10 7.89543 10 9C10 10.1046 10.8954 11 12 11Z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-indigo mb-2">تتبع طلبات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">تتبع حالة الطلبات المقيدة</p>
            </a>

            <!-- 2. رفع وطباع طلبات الوسيط -->
            <a href="{{ route('admin.alwaseet.print-and-upload-orders') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-purple/10 to-purple/5 border-2 border-purple/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-purple/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-purple mb-2">رفع وطباع طلبات الوسيط</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">رفع وطباعة طلبات الوسيط</p>
            </a>

            <!-- 3. الوسيط - الإعدادات -->
            <a href="{{ route('admin.alwaseet.settings') }}" class="panel hover:shadow-lg transition-all duration-300 text-center p-6 bg-gradient-to-br from-secondary/10 to-secondary/5 border-2 border-secondary/20">
                <div class="w-16 h-16 mx-auto mb-4 bg-secondary/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" fill="currentColor"/>
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" fill="currentColor"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-secondary mb-2">الإعدادات</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">إعدادات الوسيط</p>
            </a>
        </div>
    </div>
</x-layout.admin>

