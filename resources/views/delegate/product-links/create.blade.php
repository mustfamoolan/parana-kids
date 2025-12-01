<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إنشاء رابط جديد</h5>
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
                        $backUrl = route('delegate.product-links.index');
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للروابط
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('delegate.product-links.store') }}" class="space-y-5">
            @csrf

            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">اختيار المعايير</h6>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">
                    <!-- المخزن -->
                    <div>
                        <label for="warehouse_id" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            المخزن
                        </label>
                        <select
                            id="warehouse_id"
                            name="warehouse_id"
                            class="form-select @error('warehouse_id') border-danger @enderror"
                        >
                            <option value="">كل المخازن</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- النوع -->
                    <div>
                        <label for="gender_type" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            نوع المنتج
                        </label>
                        <select
                            id="gender_type"
                            name="gender_type"
                            class="form-select @error('gender_type') border-danger @enderror"
                        >
                            <option value="">-- اختر النوع --</option>
                            <option value="boys" {{ old('gender_type') == 'boys' ? 'selected' : '' }}>ولادي</option>
                            <option value="girls" {{ old('gender_type') == 'girls' ? 'selected' : '' }}>بناتي</option>
                            <option value="boys_girls" {{ old('gender_type') == 'boys_girls' ? 'selected' : '' }}>ولادي بناتي</option>
                            <option value="accessories" {{ old('gender_type') == 'accessories' ? 'selected' : '' }}>اكسسوار</option>
                        </select>
                        @error('gender_type')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- القياس -->
                    <div>
                        <label for="size_name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            القياس (اختياري)
                        </label>
                        <select
                            id="size_name"
                            name="size_name"
                            class="form-select @error('size_name') border-danger @enderror"
                        >
                            <option value="">-- اختر القياس --</option>
                        </select>
                        @error('size_name')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- فلتر المنتجات المخفضة -->
                    <div>
                        <label for="has_discount" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            المنتجات المخفضة
                        </label>
                        <select
                            id="has_discount"
                            name="has_discount"
                            class="form-select @error('has_discount') border-danger @enderror"
                        >
                            <option value="0" {{ old('has_discount', '0') == '0' ? 'selected' : '' }}>كل المنتجات</option>
                            <option value="1" {{ old('has_discount') == '1' ? 'selected' : '' }}>المنتجات المخفضة فقط</option>
                        </select>
                        @error('has_discount')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('delegate.product-links.index') }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    إنشاء رابط
                </button>
            </div>
        </form>
    </div>

    <script>
        function loadSizes() {
            const warehouseId = document.getElementById('warehouse_id').value;
            const genderType = document.getElementById('gender_type').value;
            const sizeSelect = document.getElementById('size_name');

            // Reset sizes dropdown
            sizeSelect.innerHTML = '<option value="">-- اختر القياس --</option>';

            // Show loading
            const loadingOption = document.createElement('option');
            loadingOption.value = '';
            loadingOption.textContent = 'جاري التحميل...';
            sizeSelect.appendChild(loadingOption);
            sizeSelect.disabled = true;

            // AJAX request (warehouse_id can be empty for "all warehouses")
            const url = `{{ route('delegate.product-links.get-sizes') }}?warehouse_id=${warehouseId || ''}&gender_type=${genderType || ''}`;
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                sizeSelect.innerHTML = '<option value="">-- اختر القياس --</option>';

                if (data.sizes && data.sizes.length > 0) {
                    data.sizes.forEach(size => {
                        const option = document.createElement('option');
                        option.value = size.name;
                        option.textContent = `${size.name} (${size.count} قطعة)`;
                        sizeSelect.appendChild(option);
                    });
                    sizeSelect.disabled = false;
                } else {
                    const noDataOption = document.createElement('option');
                    noDataOption.value = '';
                    noDataOption.textContent = 'لا توجد قياسات متاحة';
                    sizeSelect.appendChild(noDataOption);
                    sizeSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                sizeSelect.innerHTML = '<option value="">-- خطأ في التحميل --</option>';
                sizeSelect.disabled = false;
            });
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const warehouseSelect = document.getElementById('warehouse_id');
            const genderSelect = document.getElementById('gender_type');
            const sizeSelect = document.getElementById('size_name');

            // Add change event listeners
            warehouseSelect.addEventListener('change', loadSizes);
            genderSelect.addEventListener('change', loadSizes);

            // Load sizes on page load if warehouse is already selected
            if (warehouseSelect.value || genderSelect.value) {
                loadSizes();
            }
        });
    </script>
</x-layout.default>

