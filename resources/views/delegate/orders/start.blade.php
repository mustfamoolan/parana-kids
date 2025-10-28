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
                        placeholder="07XXXXXXXXX"
                        class="form-input"
                        required
                    >
                    @error('customer_phone')
                        <span class="text-danger text-xs mt-1">{{ $message }}</span>
                    @enderror
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
</x-layout.default>

