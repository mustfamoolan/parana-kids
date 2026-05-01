<x-layout.admin>
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
                        $backUrl = route('admin.product-links.index');
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

        <form method="POST" action="{{ route('admin.product-links.store') }}" class="space-y-5" id="product-link-form">
            @csrf
            <input type="hidden" name="filters" id="filters-input">

            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">اختيار المعايير العامة</h6>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <!-- النوع -->
                    <div>
                        <label for="gender_type" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            نوع المنتج (اختياري للكل)
                        </label>
                        <select
                            id="gender_type"
                            name="gender_type"
                            class="form-select @error('gender_type') border-danger @enderror"
                        >
                            <option value="">-- كل الأنواع --</option>
                            <option value="boys" {{ old('gender_type') == 'boys' ? 'selected' : '' }}>ولادي</option>
                            <option value="girls" {{ old('gender_type') == 'girls' ? 'selected' : '' }}>بناتي</option>
                            <option value="boys_girls" {{ old('gender_type') == 'boys_girls' ? 'selected' : '' }}>ولادي بناتي</option>
                            <option value="accessories" {{ old('gender_type') == 'accessories' ? 'selected' : '' }}>اكسسوار</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- قائمة الفلاتر المضافة -->
            <div id="selected-filters-container" class="hidden">
                <div class="panel border-primary">
                    <div class="mb-3 flex items-center justify-between">
                        <h6 class="text-base font-bold text-primary">المخازن والقياسات المضافة</h6>
                    </div>
                    <div id="filters-list" class="space-y-3">
                        <!-- تضاف هنا برمجياً -->
                    </div>
                </div>
            </div>

            <!-- إضافة مخزن جديد -->
            <div class="panel bg-gray-50 dark:bg-black/20 border-dashed border-2 border-gray-300">
                <div class="mb-4">
                    <h6 class="text-base font-bold">إضافة مخزن وقياسات</h6>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-bold text-gray-500">اختر المخزن</label>
                        <select id="warehouse_id" class="form-select">
                            <option value="">-- اختر المخزن --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-bold text-gray-500">القياسات المتاحة</label>
                        <div id="sizes-container" class="flex flex-wrap gap-2 p-3 bg-white dark:bg-gray-800 rounded-lg border min-h-[50px]">
                            <p class="text-gray-400 text-xs italic">اختر مخزن لعرض القياسات...</p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button type="button" id="add-filter-btn" class="btn btn-outline-primary btn-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        إضافة للمجموعة
                    </button>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-8">
                <a href="{{ route('admin.product-links.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                <button type="submit" class="btn btn-primary lg:px-10">إنشاء الرابط النهائي</button>
            </div>
        </form>
    </div>

    <script>
        let selectedFilters = [];
        let availableSizes = [];
        let currentSelectedSizes = new Set();

        const warehouseSelect = document.getElementById('warehouse_id');
        const genderSelect = document.getElementById('gender_type');
        const sizesContainer = document.getElementById('sizes-container');
        const addFilterBtn = document.getElementById('add-filter-btn');
        const filtersList = document.getElementById('filters-list');
        const filtersContainer = document.getElementById('selected-filters-container');
        const filtersInput = document.getElementById('filters-input');
        const form = document.getElementById('product-link-form');

        function loadSizes() {
            const warehouseId = warehouseSelect.value;
            const genderType = genderSelect.value;

            if (!warehouseId) {
                sizesContainer.innerHTML = '<p class="text-gray-400 text-xs italic">اختر مخزن لعرض القياسات...</p>';
                return;
            }

            sizesContainer.innerHTML = '<div class="flex items-center gap-2 text-xs"><span class="animate-spin h-3 w-3 border-2 border-primary border-t-transparent rounded-full"></span> جاري تحميل القياسات...</div>';
            currentSelectedSizes.clear();

            const url = `{{ route('admin.product-links.get-sizes') }}?warehouse_id=${warehouseId}&gender_type=${genderType || ''}`;
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                availableSizes = data.sizes || [];
                renderSizes();
            })
            .catch(error => {
                console.error('Error:', error);
                sizesContainer.innerHTML = '<p class="text-danger text-xs">خطأ في تحميل القياسات</p>';
            });
        }

        function renderSizes() {
            if (availableSizes.length === 0) {
                sizesContainer.innerHTML = '<p class="text-gray-400 text-xs italic">لا توجد قياسات متاحة حالياً لهذا المخزن</p>';
                return;
            }

            sizesContainer.innerHTML = '';
            availableSizes.forEach(size => {
                const badge = document.createElement('div');
                badge.className = `cursor-pointer px-3 py-1 rounded-full border text-xs font-medium transition-all ${currentSelectedSizes.has(size.name) ? 'bg-primary text-white border-primary' : 'bg-gray-100 text-gray-700 hover:border-primary'}`;
                badge.textContent = `${size.name} (${size.count})`;
                badge.onclick = () => toggleSize(size.name, badge);
                sizesContainer.appendChild(badge);
            });
        }

        function toggleSize(sizeName, element) {
            if (currentSelectedSizes.has(sizeName)) {
                currentSelectedSizes.delete(sizeName);
                element.className = 'cursor-pointer px-3 py-1 rounded-full border text-xs font-medium bg-gray-100 text-gray-700 hover:border-primary';
            } else {
                currentSelectedSizes.add(sizeName);
                element.className = 'cursor-pointer px-3 py-1 rounded-full border text-xs font-medium bg-primary text-white border-primary';
            }
        }

        function addFilter() {
            const warehouseId = warehouseSelect.value;
            const warehouseName = warehouseSelect.options[warehouseSelect.selectedIndex].text;

            if (!warehouseId || currentSelectedSizes.size === 0) {
                alert('يرجى اختيار مخزن وقياس واحد على الأقل');
                return;
            }

            const sizes = Array.from(currentSelectedSizes);
            
            // Check if warehouse already exists
            const existingIndex = selectedFilters.findIndex(f => f.warehouse_id == warehouseId);
            if (existingIndex !== -1) {
                // Merge sizes
                const newSizes = [...new Set([...selectedFilters[existingIndex].sizes, ...sizes])];
                selectedFilters[existingIndex].sizes = newSizes;
            } else {
                selectedFilters.push({
                    warehouse_id: warehouseId,
                    warehouse_name: warehouseName,
                    sizes: sizes
                });
            }

            // Reset current selection
            warehouseSelect.value = '';
            currentSelectedSizes.clear();
            sizesContainer.innerHTML = '<p class="text-gray-400 text-xs italic">اختر مخزن لعرض القياسات...</p>';
            
            renderFilters();
            updateHiddenInput();
        }

        function renderFilters() {
            if (selectedFilters.length === 0) {
                filtersContainer.classList.add('hidden');
                return;
            }

            filtersContainer.classList.remove('hidden');
            filtersList.innerHTML = '';

            selectedFilters.forEach((filter, index) => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between p-3 bg-primary/5 rounded-lg border border-primary/20';
                item.innerHTML = `
                    <div class="flex-1">
                        <div class="text-sm font-bold text-gray-800 dark:text-white">${filter.warehouse_name}</div>
                        <div class="flex flex-wrap gap-1 mt-1">
                            ${filter.sizes.map(s => `<span class="px-2 py-0.5 bg-white dark:bg-gray-700 border text-[10px] rounded">${s}</span>`).join('')}
                        </div>
                    </div>
                    <button type="button" onclick="removeFilter(${index})" class="text-danger hover:text-red-700 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                `;
                filtersList.appendChild(item);
            });
        }

        function removeFilter(index) {
            selectedFilters.splice(index, 1);
            renderFilters();
            updateHiddenInput();
        }

        function updateHiddenInput() {
            filtersInput.value = JSON.stringify(selectedFilters.map(f => ({
                warehouse_id: f.warehouse_id,
                warehouse_name: f.warehouse_name,
                sizes: f.sizes
            })));
        }

        warehouseSelect.addEventListener('change', loadSizes);
        genderSelect.addEventListener('change', loadSizes);
        addFilterBtn.addEventListener('click', addFilter);

        form.onsubmit = function() {
            if (selectedFilters.length === 0) {
                alert('يرجى إضافة مخزن واحد على الأقل للمجموعة');
                return false;
            }
            return true;
        };
    </script>
</x-layout.admin>

