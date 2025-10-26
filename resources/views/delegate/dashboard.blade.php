<x-layout.default>
    <script defer src="/assets/js/apexcharts.js"></script>
    <div x-data="sales">
        <ul class="flex space-x-2 rtl:space-x-reverse">
            <li>
                <a href="javascript:;" class="text-primary hover:underline">لوحة التحكم</a>
            </li>
            <li class="before:content-['/'] ltr:before:mr-1 rtl:before:ml-1">
                <span>الرئيسية</span>
            </li>
        </ul>

        <div class="pt-5">
            <div class="grid xl:grid-cols-3 gap-6 mb-6">
                <div class="panel h-full xl:col-span-2">
                    <div class="flex items-center dark:text-white-light mb-5">
                        <h5 class="font-semibold text-lg">مرحباً {{ auth()->user()->name }}</h5>
                        <div x-data="dropdown" @click.outside="open = false"
                            class="dropdown ltr:ml-auto rtl:mr-auto">
                            <a href="javascript:;" @click="toggle">
                                <svg class="w-5 h-5 text-black/70 dark:text-white/70 hover:!text-primary"
                                    viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="5" cy="12" r="2" stroke="currentColor"
                                        stroke-width="1.5" />
                                    <circle opacity="0.5" cx="12" cy="12" r="2"
                                        stroke="currentColor" stroke-width="1.5" />
                                    <circle cx="19" cy="12" r="2" stroke="currentColor"
                                        stroke-width="1.5" />
                                </svg>
                            </a>
                            <ul x-cloak x-show="open" x-transition x-transition.duration.300ms
                                class="ltr:right-0 rtl:left-0">
                                <li><a href="javascript:;" @click="toggle">اليوم</a></li>
                                <li><a href="javascript:;" @click="toggle">هذا الأسبوع</a></li>
                                <li><a href="javascript:;" @click="toggle">هذا الشهر</a></li>
                            </ul>
                        </div>
                    </div>
                    <p class="text-lg dark:text-white-light/90">المهام المكتملة اليوم <span
                            class="text-primary ml-2">12</span></p>
                    <div class="relative overflow-hidden">
                        <div x-ref="revenueChart" class="bg-white dark:bg-black rounded-lg">
                            <!-- loader -->
                            <div
                                class="min-h-[325px] grid place-content-center bg-white-light/30 dark:bg-dark dark:bg-opacity-[0.08] ">
                                <span
                                    class="animate-spin border-2 border-black dark:border-white !border-l-transparent  rounded-full w-5 h-5 inline-flex"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel h-full">
                    <div class="flex items-center dark:text-white-light mb-5">
                        <h5 class="font-semibold text-lg">إحصائياتي</h5>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm font-medium">المهام المكتملة</p>
                                    <p class="text-xs text-gray-500">45 هذا الشهر</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-primary">+15%</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-success/10 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm font-medium">الأرباح</p>
                                    <p class="text-xs text-gray-500">$2,340</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-success">+8%</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-warning/10 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm font-medium">المهام المعلقة</p>
                                    <p class="text-xs text-gray-500">8</p>
                                </div>
                            </div>
                            <span class="text-lg font-bold text-warning">-3</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="panel">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-lg font-semibold">المهام اليوم</h6>
                            <p class="text-2xl font-bold text-primary">12</p>
                        </div>
                        <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-lg font-semibold">المكتملة</h6>
                            <p class="text-2xl font-bold text-success">8</p>
                        </div>
                        <div class="w-12 h-12 bg-success/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-lg font-semibold">المعلقة</h6>
                            <p class="text-2xl font-bold text-warning">4</p>
                        </div>
                        <div class="w-12 h-12 bg-warning/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-center justify-between">
                        <div>
                            <h6 class="text-lg font-semibold">الأرباح</h6>
                            <p class="text-2xl font-bold text-info">$456</p>
                        </div>
                        <div class="w-12 h-12 bg-info/10 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="panel">
                    <div class="flex items-center justify-between mb-5">
                        <h5 class="font-semibold text-lg">المهام الموكلة</h5>
                        <a href="#" class="text-primary hover:underline">عرض الكل</a>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium">توصيل طلب رقم #1234</p>
                                <p class="text-sm text-gray-500">منطقة الرياض - عاجل</p>
                            </div>
                            <span class="px-2 py-1 bg-warning/10 text-warning text-xs rounded-full">معلق</span>
                        </div>

                        <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="w-10 h-10 bg-success/10 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium">توصيل طلب رقم #1233</p>
                                <p class="text-sm text-gray-500">منطقة جدة - عادي</p>
                            </div>
                            <span class="px-2 py-1 bg-success/10 text-success text-xs rounded-full">مكتمل</span>
                        </div>

                        <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="w-10 h-10 bg-info/10 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium">توصيل طلب رقم #1232</p>
                                <p class="text-sm text-gray-500">منطقة الدمام - عادي</p>
                            </div>
                            <span class="px-2 py-1 bg-info/10 text-info text-xs rounded-full">قيد التنفيذ</span>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-center justify-between mb-5">
                        <h5 class="font-semibold text-lg">التقارير اليومية</h5>
                        <a href="#" class="text-primary hover:underline">إضافة تقرير</a>
                    </div>
                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="font-medium">تقرير اليوم</h6>
                                <span class="text-sm text-gray-500">اليوم</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">تم إنجاز 8 مهام من أصل 12. الأداء جيد جداً...</p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="font-medium">تقرير أمس</h6>
                                <span class="text-sm text-gray-500">أمس</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">تم إنجاز جميع المهام الموكلة بنجاح...</p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <h6 class="font-medium">تقرير أول أمس</h6>
                                <span class="text-sm text-gray-500">منذ يومين</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300">أداء ممتاز مع تجاوز الهدف المطلوب...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout.default>
