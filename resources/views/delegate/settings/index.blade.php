<x-layout.default>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">الإعدادات</h5>
        </div>

        @if(session('success'))
            <div class="panel mb-5 border-l-4 border-green-500">
                <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/20">
                    <svg class="w-6 h-6 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- صورة البروفايل -->
        <div class="panel mb-5">
            <div class="mb-5">
                <h6 class="text-lg font-semibold mb-4">صورة البروفايل</h6>
                <form method="POST" action="{{ route('delegate.settings.profile') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                        <div class="flex-shrink-0">
                            <img src="{{ auth()->user()->getProfileImageUrl() }}"
                                 alt="صورة البروفايل"
                                 class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 dark:border-gray-700">
                        </div>
                        <div class="flex-1">
                            <div class="mb-4">
                                <label for="profile_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    اختر صورة جديدة
                                </label>
                                <input type="file"
                                       id="profile_image"
                                       name="profile_image"
                                       accept="image/jpeg,image/jpg,image/png"
                                       class="form-input">
                                <p class="text-xs text-gray-500 mt-1">
                                    الصيغ المدعومة: JPG, JPEG, PNG. الحد الأقصى: 2MB
                                </p>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                حفظ الصورة
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="mb-5">
                <h6 class="text-lg font-semibold mb-4">إعدادات المظهر</h6>

                <!-- Dark/Light Theme -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        المظهر
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <button type="button"
                            class="btn flex items-center justify-center"
                            :class="[$store.app.theme === 'light' ? 'btn-primary' : 'btn-outline-primary']"
                            @click="$store.app.toggleTheme('light'); saveThemePreference('light')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 ltr:mr-2 rtl:ml-2">
                                <circle cx="12" cy="12" r="5" stroke="currentColor" stroke-width="1.5"></circle>
                                <path d="M12 2V4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path d="M12 20V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path d="M4 12L2 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path d="M22 12L20 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path opacity="0.5" d="M19.7778 4.22266L17.5558 6.25424" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path opacity="0.5" d="M4.22217 4.22266L6.44418 6.25424" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path opacity="0.5" d="M6.44434 17.5557L4.22211 19.7779" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path opacity="0.5" d="M19.7778 19.7773L17.5558 17.5551" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            </svg>
                            فاتح
                        </button>
                        <button type="button"
                            class="btn flex items-center justify-center"
                            :class="[$store.app.theme === 'dark' ? 'btn-primary' : 'btn-outline-primary']"
                            @click="$store.app.toggleTheme('dark'); saveThemePreference('dark')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 ltr:mr-2 rtl:ml-2">
                                <path d="M21.0672 11.8568L20.4253 11.469L21.0672 11.8568ZM12.1432 2.93276L11.7553 2.29085V2.29085L12.1432 2.93276ZM21.25 12C21.25 17.1086 17.1086 21.25 12 21.25V22.75C17.9371 22.75 22.75 17.9371 22.75 12H21.25ZM12 21.25C6.89137 21.25 2.75 17.1086 2.75 12H1.25C1.25 17.9371 6.06294 22.75 12 22.75V21.25ZM2.75 12C2.75 6.89137 6.89137 2.75 12 2.75V1.25C6.06294 1.25 1.25 6.06294 1.25 12H2.75ZM15.5 14.25C12.3244 14.25 9.75 11.6756 9.75 8.5H8.25C8.25 12.5041 11.4959 15.75 15.5 15.75V14.25ZM20.4253 11.469C19.4172 13.1373 17.5882 14.25 15.5 14.25V15.75C18.1349 15.75 20.4407 14.3439 21.7092 12.2447L20.4253 11.469ZM9.75 8.5C9.75 6.41182 10.8627 4.5828 12.531 3.57467L11.7553 2.29085C9.65609 3.5593 8.25 5.86509 8.25 8.5H9.75ZM12 2.75C11.9115 2.75 11.8077 2.71008 11.7324 2.63168C11.6686 2.56527 11.6538 2.50244 11.6503 2.47703C11.6461 2.44587 11.6482 2.35557 11.7553 2.29085L12.531 3.57467C13.0342 3.27065 13.196 2.71398 13.1368 2.27627C13.0754 1.82126 12.7166 1.25 12 1.25V2.75ZM21.7092 12.2447C21.6444 12.3518 21.5541 12.3539 21.523 12.3497C21.4976 12.3462 21.4347 12.3314 21.3683 12.2676C21.2899 12.1923 21.25 12.0885 21.25 12H22.75C22.75 11.2834 22.1787 10.9246 21.7237 10.8632C21.286 10.804 20.7293 10.9658 20.4253 11.469L21.7092 12.2447Z" fill="currentColor"></path>
                            </svg>
                            داكن
                        </button>
                        <button type="button"
                            class="btn flex items-center justify-center"
                            :class="[$store.app.theme === 'system' ? 'btn-primary' : 'btn-outline-primary']"
                            @click="$store.app.toggleTheme('system'); saveThemePreference('system')">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0 ltr:mr-2 rtl:ml-2">
                                <path d="M3 9C3 6.17157 3 4.75736 3.87868 3.87868C4.75736 3 6.17157 3 9 3H15C17.8284 3 19.2426 3 20.1213 3.87868C21 4.75736 21 6.17157 21 9V14C21 15.8856 21 16.8284 20.4142 17.4142C19.8284 18 18.8856 18 17 18H7C5.11438 18 4.17157 18 3.58579 17.4142C3 16.8284 3 15.8856 3 14V9Z" stroke="currentColor" stroke-width="1.5"></path>
                                <path opacity="0.5" d="M22 21H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                <path opacity="0.5" d="M15 15H9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            </svg>
                            تلقائي
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        اختر المظهر المفضل لك: فاتح، داكن، أو تلقائي (يتبع إعدادات النظام)
                    </p>
                </div>
            </div>
        </div>

        <!-- قسم تخصيص ألوان الخطوط -->
        <div class="panel mt-5">
            <div class="mb-5">
                <h6 class="text-lg font-semibold mb-4">تخصيص ألوان الخطوط</h6>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                    يمكنك تخصيص ألوان الخطوط (النص الأساسي والنص الثانوي) حسب تفضيلاتك. التغييرات تُطبق فوراً على جميع الصفحات.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- الوضع الفاتح -->
                    <div class="space-y-4">
                        <h6 class="text-base font-semibold mb-3 border-b pb-2">الوضع الفاتح</h6>

                        <!-- لون النص الأساسي -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                لون النص الأساسي
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       id="light-text-primary"
                                       value="#3b3f5c"
                                       class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                                <input type="text"
                                       id="light-text-primary-hex"
                                       value="#3b3f5c"
                                       class="form-input flex-1 font-mono text-sm"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>

                        <!-- لون النص الثانوي -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                لون النص الثانوي
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       id="light-text-secondary"
                                       value="#888ea8"
                                       class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                                <input type="text"
                                       id="light-text-secondary-hex"
                                       value="#888ea8"
                                       class="form-input flex-1 font-mono text-sm"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                    </div>

                    <!-- الوضع الداكن -->
                    <div class="space-y-4">
                        <h6 class="text-base font-semibold mb-3 border-b pb-2">الوضع الداكن</h6>

                        <!-- لون النص الأساسي -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                لون النص الأساسي
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       id="dark-text-primary"
                                       value="#e0e6ed"
                                       class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                                <input type="text"
                                       id="dark-text-primary-hex"
                                       value="#e0e6ed"
                                       class="form-input flex-1 font-mono text-sm"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>

                        <!-- لون النص الثانوي -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                لون النص الثانوي
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="color"
                                       id="dark-text-secondary"
                                       value="#888ea8"
                                       class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                                <input type="text"
                                       id="dark-text-secondary-hex"
                                       value="#888ea8"
                                       class="form-input flex-1 font-mono text-sm"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- أزرار الإجراء -->
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button"
                            id="reset-colors-btn"
                            class="btn btn-outline-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        إعادة تعيين
                    </button>
                    <button type="button"
                            id="save-colors-btn"
                            class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        حفظ الألوان
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // حفظ تفضيل الثيم في localStorage (بدون إشعار)
        function saveThemePreference(theme) {
            if (typeof Alpine !== 'undefined' && Alpine.store('app')) {
                // يتم الحفظ تلقائياً عبر Alpine.$persist
                // لا نعرض إشعار - التغيير فوري ومرئي
            }
        }

        // Custom Color Management System
        (function() {
            const colorStorageKey = 'custom_colors';
            const defaultColors = {
                light: {
                    textPrimary: '#3b3f5c',
                    textSecondary: '#888ea8',
                },
                dark: {
                    textPrimary: '#e0e6ed',
                    textSecondary: '#888ea8',
                }
            };

            // تحميل الألوان من localStorage
            function loadColors() {
                try {
                    const savedColors = localStorage.getItem(colorStorageKey);
                    if (savedColors) {
                        const colors = JSON.parse(savedColors);

                        // تطبيق الألوان على الحقول
                        if (colors.light) {
                            setColorValue('light-text-primary', colors.light.textPrimary);
                            setColorValue('light-text-secondary', colors.light.textSecondary);
                        }

                        if (colors.dark) {
                            setColorValue('dark-text-primary', colors.dark.textPrimary);
                            setColorValue('dark-text-secondary', colors.dark.textSecondary);
                        }
                    }
                } catch (error) {
                    console.error('Error loading colors:', error);
                }
            }

            // تعيين قيمة اللون في الحقلين (color picker و hex input)
            function setColorValue(id, value) {
                const colorInput = document.getElementById(id);
                const hexInput = document.getElementById(id + '-hex');
                if (colorInput) colorInput.value = value;
                if (hexInput) hexInput.value = value;
            }

            // مزامنة color picker مع hex input
            function syncColorInputs() {
                const colorInputs = document.querySelectorAll('input[type="color"]');
                colorInputs.forEach(input => {
                    const hexInput = document.getElementById(input.id + '-hex');
                    if (hexInput) {
                        // من color picker إلى hex input
                        input.addEventListener('input', () => {
                            hexInput.value = input.value.toUpperCase();
                            previewColors();
                        });

                        // من hex input إلى color picker
                        hexInput.addEventListener('input', (e) => {
                            const value = e.target.value;
                            if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                                input.value = value;
                                previewColors();
                            }
                        });
                    }
                });
            }

            // معاينة الألوان مباشرة
            function previewColors() {
                if (!window.customColorSystem) return;

                const colors = {
                    light: {
                        textPrimary: document.getElementById('light-text-primary')?.value || defaultColors.light.textPrimary,
                        textSecondary: document.getElementById('light-text-secondary')?.value || defaultColors.light.textSecondary,
                    },
                    dark: {
                        textPrimary: document.getElementById('dark-text-primary')?.value || defaultColors.dark.textPrimary,
                        textSecondary: document.getElementById('dark-text-secondary')?.value || defaultColors.dark.textSecondary,
                    }
                };

                window.customColorSystem.save(colors);
            }

            // حفظ الألوان
            function saveColors() {
                const colors = {
                    light: {
                        textPrimary: document.getElementById('light-text-primary').value,
                        textSecondary: document.getElementById('light-text-secondary').value,
                    },
                    dark: {
                        textPrimary: document.getElementById('dark-text-primary').value,
                        textSecondary: document.getElementById('dark-text-secondary').value,
                    }
                };

                if (window.customColorSystem) {
                    window.customColorSystem.save(colors);

                    if (typeof window.Swal !== 'undefined') {
                        window.Swal.fire({
                            icon: 'success',
                            title: 'تم الحفظ',
                            text: 'تم حفظ الألوان بنجاح',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    } else {
                        alert('تم حفظ الألوان بنجاح');
                    }
                }
            }

            // إعادة تعيين الألوان
            function resetColors() {
                if (typeof window.Swal !== 'undefined') {
                    window.Swal.fire({
                        icon: 'warning',
                        title: 'إعادة التعيين',
                        text: 'هل أنت متأكد من إعادة تعيين جميع الألوان إلى القيم الافتراضية؟',
                        showCancelButton: true,
                        confirmButtonText: 'نعم، إعادة التعيين',
                        cancelButtonText: 'إلغاء',
                        confirmButtonColor: '#e7515a',
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // إعادة تعيين الحقول
                            setColorValue('light-text-primary', defaultColors.light.textPrimary);
                            setColorValue('light-text-secondary', defaultColors.light.textSecondary);

                            setColorValue('dark-text-primary', defaultColors.dark.textPrimary);
                            setColorValue('dark-text-secondary', defaultColors.dark.textSecondary);

                            if (window.customColorSystem) {
                                window.customColorSystem.reset();
                            }

                            window.Swal.fire({
                                icon: 'success',
                                title: 'تم',
                                text: 'تم إعادة تعيين الألوان بنجاح',
                                timer: 2000,
                                showConfirmButton: false,
                            });
                        }
                    });
                } else {
                    if (confirm('هل أنت متأكد من إعادة تعيين جميع الألوان؟')) {
                        setColorValue('light-text-primary', defaultColors.light.textPrimary);
                        setColorValue('light-text-secondary', defaultColors.light.textSecondary);

                        setColorValue('dark-text-primary', defaultColors.dark.textPrimary);
                        setColorValue('dark-text-secondary', defaultColors.dark.textSecondary);

                        if (window.customColorSystem) {
                            window.customColorSystem.reset();
                        }
                    }
                }
            }

            // تهيئة النظام
            function init() {
                loadColors();
                syncColorInputs();

                // أزرار الحفظ والإعادة
                const saveBtn = document.getElementById('save-colors-btn');
                const resetBtn = document.getElementById('reset-colors-btn');

                if (saveBtn) {
                    saveBtn.addEventListener('click', saveColors);
                }

                if (resetBtn) {
                    resetBtn.addEventListener('click', resetColors);
                }
            }

            // بدء عند تحميل الصفحة
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }
        })();
    </script>
</x-layout.default>

