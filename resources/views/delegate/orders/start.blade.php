<x-layout.default>
    <div class="panel p-0">
        <div class="panel-header">
            <h5 class="font-semibold text-lg dark:text-white-light">إنشاء طلب جديد</h5>
        </div>
        <div class="p-5">
            <form action="{{ route('delegate.orders.initialize') }}" method="POST" class="space-y-5">
                @csrf

                <!-- اسم الزبون -->
                <div>
                    <label for="customer_name" class="font-semibold">اسم الزبون <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        id="customer_name"
                        name="customer_name"
                        value="{{ old('customer_name') }}"
                        placeholder="أدخل اسم الزبون"
                        class="form-input"
                        required
                    >
                    @error('customer_name')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- رقم الهاتف -->
                <div>
                    <label for="customer_phone" class="font-semibold">رقم الهاتف <span class="text-danger">*</span></label>
                    <input
                        type="tel"
                        id="customer_phone"
                        name="customer_phone"
                        value="{{ old('customer_phone') }}"
                        placeholder="07742209251"
                        class="form-input"
                        required
                        oninput="formatPhoneNumber(this)"
                        onpaste="handlePhonePaste(event)"
                    >
                    @error('customer_phone')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                    <p id="phone_error" class="text-danger text-xs mt-1" style="display: none;">الرقم يجب أن يكون بالضبط 11 رقم بعد التنسيق</p>
                </div>

                <!-- العنوان -->
                <div>
                    <label for="customer_address" class="font-semibold">العنوان <span class="text-danger">*</span></label>
                    <textarea
                        id="customer_address"
                        name="customer_address"
                        rows="3"
                        placeholder="أدخل عنوان الزبون"
                        class="form-textarea"
                        required
                    >{{ old('customer_address') }}</textarea>
                    @error('customer_address')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- رابط السوشل ميديا -->
                <div>
                    <label for="customer_social_link" class="font-semibold">رابط السوشل ميديا <span class="text-danger">*</span></label>
                    <input
                        type="url"
                        id="customer_social_link"
                        name="customer_social_link"
                        value="{{ old('customer_social_link') }}"
                        placeholder="https://..."
                        class="form-input"
                        required
                    >
                    @error('customer_social_link')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- ملاحظات -->
                <div>
                    <label for="notes" class="font-semibold">ملاحظات</label>
                    <textarea
                        id="notes"
                        name="notes"
                        rows="3"
                        placeholder="ملاحظات إضافية (اختياري)"
                        class="form-textarea"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- أزرار -->
                <div class="flex gap-3 justify-end mt-6">
                    <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-danger">
                        إلغاء
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                        التالي - اختيار المنتجات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // دالة تحويل الأرقام العربية إلى إنجليزية
        function convertArabicToEnglishNumbers(str) {
            const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            const englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            let result = str;
            for (let i = 0; i < arabicNumbers.length; i++) {
                result = result.replace(new RegExp(arabicNumbers[i], 'g'), englishNumbers[i]);
            }
            return result;
        }

        // معالجة اللصق
        function handlePhonePaste(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const convertedText = convertArabicToEnglishNumbers(pastedText);
            const input = e.target;
            input.value = convertedText;
            formatPhoneNumber(input);
        }

        function formatPhoneNumber(input) {
            let value = input.value;

            // تحويل الأرقام العربية إلى إنجليزية أولاً
            value = convertArabicToEnglishNumbers(value);

            // إزالة كل شيء غير الأرقام
            let cleaned = value.replace(/[^0-9]/g, '');

            // إزالة البادئات الدولية
            if (cleaned.startsWith('00964')) {
                cleaned = cleaned.substring(5); // إزالة 00964
            } else if (cleaned.startsWith('964')) {
                cleaned = cleaned.substring(3); // إزالة 964
            }

            // إضافة 0 في البداية إذا لم تكن موجودة
            if (cleaned.length > 0 && !cleaned.startsWith('0')) {
                cleaned = '0' + cleaned;
            }

            // التأكد من 11 رقم فقط - إذا كان أكثر من 11، نأخذ أول 11 رقم
            if (cleaned.length > 11) {
                cleaned = cleaned.substring(0, 11);
            }

            // تحديث قيمة الحقل
            input.value = cleaned;

            // التحقق من أن الرقم بالضبط 11 رقم
            const errorElement = document.getElementById('phone_error');
            const form = input.closest('form');
            const submitButton = form.querySelector('button[type="submit"]');

            if (cleaned.length > 0 && cleaned.length !== 11) {
                errorElement.style.display = 'block';
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.style.opacity = '0.5';
                    submitButton.style.cursor = 'not-allowed';
                }
            } else {
                errorElement.style.display = 'none';
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.style.opacity = '1';
                    submitButton.style.cursor = 'pointer';
                }
            }
        }

        // تطبيق التنسيق عند تحميل الصفحة إذا كان هناك قيمة قديمة
        document.addEventListener('DOMContentLoaded', function() {
            const phoneInput = document.getElementById('customer_phone');
            if (phoneInput && phoneInput.value) {
                formatPhoneNumber(phoneInput);
            }
        });
    </script>
</x-layout.default>

