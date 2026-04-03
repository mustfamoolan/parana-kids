<x-layout.admin>
    <div class="container mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">إرسال إشعارات فايربيس</h1>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة للرئيسية
            </a>
        </div>

        @if(session('success'))
            <div class="bg-success/10 border border-success/20 text-success px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-danger/10 border border-danger/20 text-danger px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('warning'))
            <div class="bg-warning/10 border border-warning/20 text-warning px-4 py-3 rounded relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('warning') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Section -->
            <div class="lg:col-span-2">
                <div class="panel bg-white dark:bg-[#0e1726] shadow-md border-0 rounded-xl overflow-hidden">
                    <div class="p-6">
                        <form action="{{ route('admin.notifications.send') }}" method="POST" id="notificationForm" x-data="notificationForm()">
                            @csrf
                            
                            <!-- Target Group Selection -->
                            <div class="mb-5">
                                <label class="text-sm font-semibold mb-2 block text-gray-700 dark:text-white-dark">الفئة المستهدفة</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="target_group" value="customer" class="hidden peer" x-model="targetGroup" @change="resetUserSelection()">
                                        <div class="p-3 text-center border-2 border-gray-100 dark:border-gray-800 rounded-lg peer-checked:border-primary peer-checked:bg-primary/5 transition-all">
                                            <svg class="w-6 h-6 mx-auto mb-1 text-gray-400 peer-checked:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span class="text-xs font-bold block">الزبائن</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="target_group" value="supplier" class="hidden peer" x-model="targetGroup" @change="resetUserSelection()">
                                        <div class="p-3 text-center border-2 border-gray-100 dark:border-gray-800 rounded-lg peer-checked:border-primary peer-checked:bg-primary/5 transition-all">
                                            <svg class="w-6 h-6 mx-auto mb-1 text-gray-400 peer-checked:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            <span class="text-xs font-bold block">المجهزين</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="target_group" value="delegate" class="hidden peer" x-model="targetGroup" @change="resetUserSelection()">
                                        <div class="p-3 text-center border-2 border-gray-100 dark:border-gray-800 rounded-lg peer-checked:border-primary peer-checked:bg-primary/5 transition-all">
                                            <svg class="w-6 h-6 mx-auto mb-1 text-gray-400 peer-checked:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                                            </svg>
                                            <span class="text-xs font-bold block">المناديب</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Target Scope Selection -->
                            <div class="mb-5">
                                <label class="text-sm font-semibold mb-2 block text-gray-700 dark:text-white-dark">نطاق الإرسال</label>
                                <div class="flex items-center space-x-10 rtl:space-x-reverse">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="target_scope" value="all" class="form-radio text-primary" x-model="targetScope">
                                        <span class="mx-2 text-gray-700 dark:text-white-dark">إرسال للجميع في هذه الفئة</span>
                                    </label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="radio" name="target_scope" value="specific" class="form-radio text-primary" x-model="targetScope">
                                        <span class="mx-2 text-gray-700 dark:text-white-dark">إرسال لمستخدم محدد</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Specific User Selection (Conditional) -->
                            <div class="mb-5" x-show="targetScope === 'specific'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0">
                                <label class="text-sm font-semibold mb-2 block text-gray-700 dark:text-white-dark">اختر المستخدم</label>
                                <div class="relative">
                                    <input type="text" 
                                           class="form-input" 
                                           placeholder="ابحث بالاسم أو رقم الهاتف..." 
                                           x-model="userSearch" 
                                           @input.debounce.300ms="searchUsers()">
                                    
                                    <!-- Search Results Dropdown -->
                                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto" x-show="searchResults.length > 0 && !selectedUser">
                                        <template x-for="user in searchResults" :key="user.id">
                                            <div @click="selectUser(user)" class="p-3 hover:bg-primary/10 cursor-pointer border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                <div class="font-bold text-sm" x-text="user.name"></div>
                                                <div class="text-xs text-gray-500" x-text="user.phone"></div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Selected User Badge -->
                                    <div x-show="selectedUser" class="mt-2 p-2 bg-primary/10 border border-primary/20 rounded-lg flex items-center justify-between">
                                        <div>
                                            <span class="font-bold text-primary" x-text="selectedUser?.name"></span>
                                            <span class="text-xs text-gray-500 mx-2" x-text="selectedUser?.phone"></span>
                                        </div>
                                        <button type="button" @click="resetUserSelection()" class="text-danger hover:text-danger/80">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <input type="hidden" name="user_id" :value="selectedUser?.id">
                                </div>
                            </div>

                            <hr class="my-6 border-gray-100 dark:border-gray-800">

                            <!-- Content Section -->
                            <div class="mb-4">
                                <label for="title" class="text-sm font-semibold mb-2 block text-gray-700 dark:text-white-dark">عنوان الإشعار</label>
                                <input id="title" type="text" name="title" class="form-input" placeholder="أدخل عنواناً جذاباً..." required>
                            </div>

                            <div class="mb-6">
                                <label for="message" class="text-sm font-semibold mb-2 block text-gray-700 dark:text-white-dark">نص الرسالة</label>
                                <textarea id="message" name="message" rows="4" class="form-textarea" placeholder="اكتب محتوى الإشعار هنا..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-full p-4 text-lg font-bold shadow-lg shadow-primary/20">
                                <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                                إرسال الإشعار الآن
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview/Instructions Section -->
            <div class="lg:col-span-1">
                <div class="panel bg-gradient-to-br from-primary/10 to-info/10 border-0 rounded-xl p-6 h-full shadow-sm">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        تعليمات الإرسال
                    </h3>
                    <ul class="space-y-4 text-sm text-gray-700 dark:text-white-dark leading-relaxed">
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold ltr:mr-2 rtl:ml-2 shrink-0">1</span>
                            <span>اختر الفئة المستهدفة (زبائن للطلب، مناديب للتوصيل، أو مجهزين للمخازن).</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold ltr:mr-2 rtl:ml-2 shrink-0">2</span>
                            <span>حدد ما إذا كان الإشعار سيذهب للجميع أم لشخص محدد للخصوصية.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold ltr:mr-2 rtl:ml-2 shrink-0">3</span>
                            <span>استخدم عناوين قصيرة ومباشرة لضمان قراءتها من قبل المستخدم.</span>
                        </li>
                        <li class="flex items-start">
                            <span class="w-6 h-6 rounded-full bg-primary/20 text-primary flex items-center justify-center text-xs font-bold ltr:mr-2 rtl:ml-2 shrink-0">4</span>
                            <span class="text-danger font-bold">تنبيه: لا يمكن التراجع عن الإرسال بعد الضغط على الزر.</span>
                        </li>
                    </ul>

                    <div class="mt-8 p-4 bg-white/50 dark:bg-white/5 rounded-lg border border-white/20">
                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-3 tracking-wider">شكل الإشعار التقريبي</h4>
                        <div class="bg-white dark:bg-[#1b2e4b] rounded shadow-sm p-3 border-l-4 border-primary">
                            <div class="flex items-center mb-1">
                                <div class="w-4 h-4 bg-primary/20 rounded-full mr-2"></div>
                                <span class="text-[10px] font-bold text-gray-500 uppercase">Parana Kids</span>
                            </div>
                            <div class="font-bold text-xs">عنوان الإشعار يظهر هنا</div>
                            <div class="text-[10px] text-gray-500">محتوى الرسالة التي قمت بكتابتها سيظهر في منطقة الإشعارات في هاتف المستخدم...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function notificationForm() {
            return {
                targetGroup: 'customer',
                targetScope: 'all',
                userSearch: '',
                searchResults: [],
                selectedUser: null,
                
                async searchUsers() {
                    if (this.userSearch.length < 2) {
                        this.searchResults = [];
                        return;
                    }
                    
                    try {
                        const response = await fetch(`{{ route('admin.notifications.users-by-role') }}?role=${this.targetGroup}&search=${this.userSearch}`);
                        this.searchResults = await response.json();
                    } catch (error) {
                        console.error('Error fetching users:', error);
                    }
                },
                
                selectUser(user) {
                    this.selectedUser = user;
                    this.userSearch = user.name;
                    this.searchResults = [];
                },
                
                resetUserSelection() {
                    this.selectedUser = null;
                    this.userSearch = '';
                    this.searchResults = [];
                }
            }
        }
    </script>
</x-layout.admin>
