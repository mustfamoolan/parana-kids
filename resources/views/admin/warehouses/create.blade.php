<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إضافة مخزن جديد</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.warehouses.store') }}" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div>
                    <label for="name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        اسم المخزن <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="form-input @error('name') border-danger @enderror"
                        placeholder="أدخل اسم المخزن"
                        required
                    >
                    @error('name')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="location" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        موقع المخزن <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="location"
                        name="location"
                        value="{{ old('location') }}"
                        class="form-input @error('location') border-danger @enderror"
                        placeholder="أدخل موقع المخزن"
                        required
                    >
                    @error('location')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 pt-5">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    حفظ المخزن
                </button>
            </div>
        </form>
    </div>
</x-layout.admin>
