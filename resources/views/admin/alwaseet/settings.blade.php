<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إعدادات ربط الواسط</h5>
            <a href="{{ route('admin.alwaseet.index') }}" class="btn btn-outline-primary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة للشحنات
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-5">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- نموذج الربط -->
            <div class="panel">
                <h6 class="mb-4 text-lg font-semibold">بيانات الربط</h6>
                <form method="POST" action="{{ route('admin.alwaseet.settings.update') }}">
                    @csrf
                    <div class="mb-4">
                        <label for="username" class="mb-2 block text-sm font-medium">اسم المستخدم</label>
                        <input type="text" name="username" id="username"
                               value="{{ old('username', $username) }}"
                               class="form-input" required>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="mb-2 block text-sm font-medium">كلمة المرور</label>
                        <input type="password" name="password" id="password"
                               value="{{ old('password') }}"
                               class="form-input">
                        <small class="text-gray-500">اتركه فارغاً إذا كنت لا تريد تغييره</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        حفظ الإعدادات
                    </button>
                </form>
            </div>

            <!-- حالة الاتصال -->
            <div class="panel">
                <h6 class="mb-4 text-lg font-semibold">حالة الاتصال</h6>

                @if($isConfigured)
                    @if($connectionStatus['success'])
                        <div class="mb-4 rounded-lg bg-success/10 p-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-semibold text-success">متصل بنجاح</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">{{ $connectionStatus['message'] }}</p>

                            @if($tokenExists)
                                <div class="mt-3 rounded bg-white/50 p-2 dark:bg-gray-900/50">
                                    <p class="text-xs text-gray-500 mb-1">Token الحالي:</p>
                                    <code class="text-xs font-mono">{{ $tokenPreview ?? 'موجود' }}</code>
                                </div>
                            @endif

                            @if(isset($accountType) && $accountType)
                                <div class="mt-4 rounded-lg p-4 {{ $accountType['is_merchant'] ? 'bg-success/10 border border-success/20' : 'bg-warning/10 border border-warning/20' }}">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 mt-0.5">
                                            @if($accountType['is_merchant'])
                                                <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <h6 class="font-semibold {{ $accountType['is_merchant'] ? 'text-success' : 'text-warning' }} mb-1">
                                                نوع الحساب
                                            </h6>
                                            <p class="text-sm {{ $accountType['is_merchant'] ? 'text-success' : 'text-warning' }} font-medium mb-2">
                                                {{ $accountType['message'] ?? 'غير محدد' }}
                                            </p>
                                            @if(isset($accountType['warning']) && $accountType['warning'])
                                                <div class="mt-2 rounded bg-warning/20 p-2 border border-warning/30">
                                                    <p class="text-xs text-warning font-medium">
                                                        ⚠️ {{ $accountType['warning'] }}
                                                    </p>
                                                </div>
                                            @endif
                                            @if(isset($accountType['token_starts_with']))
                                                <div class="mt-2 text-xs {{ $accountType['is_merchant'] ? 'text-success' : 'text-warning' }}">
                                                    <span class="font-medium">Token يبدأ بـ:</span>
                                                    <code class="mx-1 px-1.5 py-0.5 rounded bg-white dark:bg-gray-800 border {{ $accountType['is_merchant'] ? 'border-success/30' : 'border-warning/30' }}">
                                                        {{ $accountType['token_starts_with'] }}
                                                    </code>
                                                    @if($accountType['is_merchant'])
                                                        <span class="text-success">(Merchant Token)</span>
                                                    @else
                                                        <span class="text-warning">(Merchant User Token)</span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="mb-4 rounded-lg bg-warning/10 p-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <span class="font-semibold text-warning">غير متصل</span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">{{ $connectionStatus['message'] }}</p>
                            <p class="mt-2 text-xs text-gray-500">قد تكون الجلسة منتهية. يرجى إعادة تسجيل الدخول.</p>
                        </div>
                    @endif
                @else
                    <div class="mb-4 rounded-lg bg-danger/10 p-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            <span class="font-semibold text-danger">غير متصل</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">يرجى إدخال بيانات الربط أعلاه</p>
                    </div>
                @endif

                <!-- أزرار الإجراءات -->
                @if($isConfigured)
                    <div class="mb-4 space-y-2">
                        <form method="POST" action="{{ route('admin.alwaseet.test-connection') }}" id="testConnectionForm" class="inline">
                            @csrf
                            <button type="button" onclick="testConnection()" class="btn btn-outline-primary w-full">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                اختبار الاتصال
                            </button>
                        </form>

                        @if($tokenExists)
                            <form method="POST" action="{{ route('admin.alwaseet.logout') }}" class="inline" onsubmit="return confirm('هل أنت متأكد من تسجيل الخروج؟ سيتم مسح الـ token الحالي.')">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-full">
                                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                    تسجيل خروج
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('admin.alwaseet.reconnect') }}" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-warning w-full">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                إعادة تسجيل الدخول
                            </button>
                        </form>
                    </div>
                @endif

                <!-- معلومات تشخيصية -->
                @if($isConfigured)
                    <div class="mt-4 rounded-lg bg-info/10 border border-info/20 p-4">
                        <h6 class="mb-3 font-semibold text-info flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            معلومات تشخيصية
                        </h6>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-900 rounded">
                                <span class="text-gray-600">اسم المستخدم:</span>
                                <code class="font-mono font-semibold">{{ $username }}</code>
                            </div>
                            @if($tokenExists && isset($accountType))
                                <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-900 rounded">
                                    <span class="text-gray-600">طول Token:</span>
                                    <code class="font-mono">{{ strlen($tokenPreview ?? '') + 3 }} حرف</code>
                                </div>
                                <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-900 rounded">
                                    <span class="text-gray-600">نوع Token:</span>
                                    <span class="font-medium {{ $accountType['is_merchant'] ? 'text-success' : 'text-warning' }}">
                                        {{ $accountType['is_merchant'] ? 'Merchant Token (@@...)' : 'Merchant User Token' }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="mt-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <h6 class="mb-2 font-semibold">معلومات مهمة:</h6>
                    <ul class="list-inside list-disc space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>يتم حفظ كلمة المرور بشكل آمن</li>
                        <li>Token يتم تحديثه تلقائياً عند المزامنة</li>
                        <li>Token ينتهي عند تغيير كلمة المرور</li>
                        <li>يمكنك تسجيل الخروج لإعادة تسجيل الدخول</li>
                        <li class="text-warning font-medium">إذا استمرت مشكلة الصلاحيات، تواصل مع دعم الواسط</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testConnection() {
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<svg class="animate-spin w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>جاري الاختبار...';

            fetch('{{ route('admin.alwaseet.test-connection') }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                button.disabled = false;
                button.innerHTML = originalText;

                if (data.success) {
                    alert('✓ الاتصال ناجح: ' + data.message);
                    // إعادة تحميل الصفحة لتحديث الحالة
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('✗ فشل الاتصال: ' + data.message);
                }
            })
            .catch(error => {
                button.disabled = false;
                button.innerHTML = originalText;
                console.error('Error:', error);
                alert('حدث خطأ أثناء اختبار الاتصال');
            });
        }
    </script>
</x-layout.admin>

