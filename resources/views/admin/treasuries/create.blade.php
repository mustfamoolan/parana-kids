<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إضافة خزنة جديدة</h5>
            <a href="{{ route('admin.treasuries.index') }}" class="btn btn-outline-secondary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة
            </a>
        </div>

        <form method="POST" action="{{ route('admin.treasuries.store') }}" class="space-y-5">
            @csrf

            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">المعلومات الأساسية</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium mb-2">اسم الخزنة <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                               class="form-input @error('name') border-red-500 @enderror" required>
                        @error('name')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="initial_capital" class="block text-sm font-medium mb-2">رأس المال <span class="text-red-500">*</span></label>
                        <input type="number" id="initial_capital" name="initial_capital" value="{{ old('initial_capital', 0) }}" step="0.01" min="0"
                               class="form-input @error('initial_capital') border-red-500 @enderror" required>
                        @error('initial_capital')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium mb-2">ملاحظات</label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="form-textarea @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.treasuries.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">إضافة الخزنة</button>
                </div>
            </div>
        </form>
    </div>
</x-layout.admin>

