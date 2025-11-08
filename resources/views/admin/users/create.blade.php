<x-layout.admin>
    <div x-data="{
        selectedRole: '{{ old('role', '') }}',
        init() {
            if (!this.selectedRole) {
                this.selectedRole = 'admin';
            }
            console.log('Alpine.js initialized, selectedRole:', this.selectedRole);
        },
        validateForm() {
            if (!this.selectedRole) {
                alert('يرجى تحديد نوع المستخدم');
                return false;
            }
            if ((this.selectedRole === 'supplier' || this.selectedRole === 'delegate') && !document.getElementById('code').value) {
                alert('يرجى إدخال الكود');
                return false;
            }
            return true;
        }
    }" x-init="init()">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إضافة مستخدم جديد</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @php
                    $backUrl = request()->query('back_url');
                    if ($backUrl) {
                        $backUrl = urldecode($backUrl);
                        $parsed = parse_url($backUrl);
                        $currentHost = parse_url(config('app.url'), PHP_URL_HOST);
                        if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                            $backUrl = null;
                        }
                    }
                    if (!$backUrl) {
                        $backUrl = route('admin.users.index');
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمستخدمين
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5" @submit="validateForm">
            @csrf

            <!-- نوع المستخدم -->
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">نوع المستخدم</h6>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                           :class="{ 'border-primary bg-primary/5': selectedRole === 'admin' }">
                        <input type="radio" x-model="selectedRole" value="admin" class="form-radio text-primary">
                        <div class="mr-3">
                            <div class="font-medium">مدير</div>
                            <div class="text-sm text-gray-500">صلاحيات كاملة</div>
                        </div>
                    </label>

                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                           :class="{ 'border-primary bg-primary/5': selectedRole === 'supplier' }">
                        <input type="radio" x-model="selectedRole" value="supplier" class="form-radio text-primary" @change="console.log('Role changed to:', selectedRole)">
                        <div class="mr-3">
                            <div class="font-medium">مجهز</div>
                            <div class="text-sm text-gray-500">إدارة المخازن</div>
                        </div>
                    </label>

                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800"
                           :class="{ 'border-primary bg-primary/5': selectedRole === 'delegate' }">
                        <input type="radio" x-model="selectedRole" value="delegate" class="form-radio text-primary" @change="console.log('Role changed to:', selectedRole)">
                        <div class="mr-3">
                            <div class="font-medium">مندوب</div>
                            <div class="text-sm text-gray-500">إدارة الطلبات والمخازن</div>
                        </div>
                    </label>
                </div>
                @error('role')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                @enderror
            </div>

            <!-- حقل مخفي لإرسال الدور -->
            <input type="hidden" name="role" x-model="selectedRole">

            <!-- المعلومات الأساسية -->
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">المعلومات الأساسية</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- الاسم -->
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">الاسم <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                               class="form-input @error('name') border-red-500 @enderror"
                               placeholder="أدخل اسم المستخدم" required>
                        @error('name')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- رقم الهاتف -->
                    <div>
                        <label for="phone" class="block text-sm font-medium mb-2">رقم الهاتف <span class="text-red-500">*</span></label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                               class="form-input @error('phone') border-red-500 @enderror"
                               placeholder="0501234567" required>
                        @error('phone')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- الكود (للمجهز والمندوب فقط) -->
                    <div x-show="selectedRole === 'supplier' || selectedRole === 'delegate'">
                        <label for="code" class="block text-sm font-medium mb-2">الكود <span class="text-red-500">*</span></label>
                        <input type="text" id="code" name="code" value="{{ old('code') }}"
                               class="form-input @error('code') border-red-500 @enderror"
                               :placeholder="selectedRole === 'supplier' ? 'SUP001' : 'DEL001'"
                               :required="selectedRole === 'supplier' || selectedRole === 'delegate'">
                        <div class="text-sm text-gray-500 mt-1">
                            مثال: <span x-text="selectedRole === 'supplier' ? 'SUP001' : 'DEL001'"></span>
                        </div>
                        @error('code')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- اسم البيج (للمندوب فقط) -->
                    <div x-show="selectedRole === 'delegate'">
                        <label for="page_name" class="block text-sm font-medium mb-2">اسم البيج</label>
                        <input type="text" id="page_name" name="page_name" value="{{ old('page_name') }}"
                               class="form-input @error('page_name') border-red-500 @enderror"
                               placeholder="مثال: برنا كدز">
                        <div class="text-sm text-gray-500 mt-1">اختياري - سيظهر في رسالة الواتساب</div>
                        @error('page_name')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- البريد الإلكتروني (اختياري للجميع) -->
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2">البريد الإلكتروني</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-input @error('email') border-red-500 @enderror"
                               placeholder="user@example.com">
                        <div class="text-sm text-gray-500 mt-1">اختياري</div>
                        @error('email')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- كلمة المرور -->
                    <div>
                        <label for="password" class="block text-sm font-medium mb-2">كلمة المرور <span class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password"
                               class="form-input @error('password') border-red-500 @enderror"
                               placeholder="أدخل كلمة المرور" required>
                        <div class="text-sm text-gray-500 mt-1">الحد الأدنى 6 أحرف</div>
                        @error('password')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- المخازن (للمجهز والمندوب) -->
            <div class="panel" x-show="selectedRole === 'supplier' || selectedRole === 'delegate'" x-transition>
                <h6 class="text-lg font-semibold mb-4">المخازن المخصصة</h6>

                @if($warehouses->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($warehouses as $warehouse)
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800">
                                <input type="checkbox" name="warehouses[]" value="{{ $warehouse->id }}"
                                       class="form-checkbox text-primary">
                                <div class="mr-3">
                                    <div class="font-medium">{{ $warehouse->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $warehouse->address ?: 'لا يوجد عنوان' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <p>لا يوجد مخازن في النظام</p>
                        <p class="text-sm">يرجى إنشاء مخازن أولاً</p>
                    </div>
                @endif

                @error('warehouses')
                    <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                @enderror
            </div>

            <!-- أزرار الإجراءات -->
            <div class="panel">
                <div class="flex flex-col sm:flex-row gap-4 justify-end">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        إلغاء
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة المستخدم
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-layout.admin>
