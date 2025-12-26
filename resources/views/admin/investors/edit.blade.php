<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل مستثمر: {{ $investor->name }}</h5>
            <a href="{{ route('admin.investors.show', $investor) }}" class="btn btn-outline-secondary">العودة</a>
        </div>

        <form method="POST" action="{{ route('admin.investors.update', $investor) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">المعلومات الأساسية</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">الاسم <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $investor->name) }}"
                               class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium mb-2">رقم الهاتف <span class="text-red-500">*</span></label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $investor->phone) }}"
                               class="form-input @error('phone') border-red-500 @enderror" required>
                        @error('phone')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium mb-2">كلمة المرور</label>
                        <input type="password" id="password" name="password"
                               class="form-input @error('password') border-red-500 @enderror"
                               placeholder="اتركه فارغاً إذا لم ترد تغييره">
                        @error('password')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium mb-2">الحالة <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="form-select @error('status') border-red-500 @enderror" required>
                            <option value="active" {{ old('status', $investor->status) === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="inactive" {{ old('status', $investor->status) === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                        @error('status')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium mb-2">ملاحظات</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="form-textarea @error('notes') border-red-500 @enderror">{{ old('notes', $investor->notes) }}</textarea>
                        @error('notes')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- معلومات الخزنة -->
            @if($investor->treasury)
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">معلومات الخزنة</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">الرصيد الحالي</label>
                        <div class="form-input bg-gray-100 dark:bg-gray-800 cursor-not-allowed">
                            {{ number_format($investor->treasury->current_balance, 2) }} دينار
                        </div>
                        <small class="text-gray-500">للاطلاع فقط - لا يمكن تعديله من هنا</small>
                    </div>
                </div>
            </div>
            @endif

            <div class="panel">
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.investors.show', $investor) }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </div>
        </form>
    </div>
</x-layout.admin>

