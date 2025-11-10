<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل المخزن الخاص</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.private-warehouses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.private-warehouses.update', $privateWarehouse) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <div>
                    <label for="name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        اسم المخزن الخاص <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $privateWarehouse->name) }}"
                        class="form-input @error('name') border-danger @enderror"
                        placeholder="أدخل اسم المخزن الخاص"
                        required
                    >
                    @error('name')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="lg:col-span-2">
                    <label for="description" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        الوصف
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="form-textarea @error('description') border-danger @enderror"
                        placeholder="أدخل وصفاً للمخزن الخاص (اختياري)"
                    >{{ old('description', $privateWarehouse->description) }}</textarea>
                    @error('description')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 pt-5">
                <a href="{{ route('admin.private-warehouses.index') }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    تحديث المخزن الخاص
                </button>
            </div>
        </form>
    </div>
</x-layout.admin>

