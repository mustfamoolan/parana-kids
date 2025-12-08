<x-layout.admin>
    <div class="panel">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">إعدادات التكامل التلقائي</h5>
                    <a href="{{ route('admin.alwaseet.index') }}" class="btn btn-outline-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة
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

                <form method="POST" action="{{ route('admin.alwaseet.auto-integration.update') }}">
                    @csrf
                    <div class="mb-5">
                        <label class="flex items-center">
                            <input type="checkbox" name="auto_create_shipment" value="1"
                                   {{ $autoCreate === '1' ? 'checked' : '' }}
                                   class="form-checkbox">
                            <span class="mr-2">تفعيل إنشاء الشحنة تلقائياً عند إنشاء طلب جديد</span>
                        </label>
                        <p class="mt-2 text-sm text-gray-500">عند تفعيل هذا الخيار، سيتم إنشاء شحنة في الواسط تلقائياً عند إنشاء أي طلب جديد في النظام</p>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="default_city_id" class="mb-2 block text-sm font-medium">المدينة الافتراضية</label>
                            <select name="default_city_id" id="default_city_id" class="form-select">
                                <option value="">اختر المدينة</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city['id'] }}" {{ $defaultCityId == $city['id'] ? 'selected' : '' }}>
                                        {{ $city['city_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="default_region_id" class="mb-2 block text-sm font-medium">المنطقة الافتراضية</label>
                            <select name="default_region_id" id="default_region_id" class="form-select">
                                <option value="">اختر المنطقة</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region['id'] }}" {{ $defaultRegionId == $region['id'] ? 'selected' : '' }}>
                                        {{ $region['region_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="default_package_size_id" class="mb-2 block text-sm font-medium">حجم الطرد الافتراضي</label>
                            <select name="default_package_size_id" id="default_package_size_id" class="form-select">
                                <option value="">اختر الحجم</option>
                                @foreach($packageSizes as $size)
                                    <option value="{{ $size['id'] }}" {{ $defaultPackageSizeId == $size['id'] ? 'selected' : '' }}>
                                        {{ $size['size'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="default_type_name" class="mb-2 block text-sm font-medium">نوع البضاعة الافتراضي</label>
                            <input type="text" name="default_type_name" id="default_type_name"
                                   value="{{ old('default_type_name', $defaultTypeName) }}"
                                   class="form-input" placeholder="مثل: ملابس">
                        </div>
                    </div>

                    <div class="mt-5">
                        <button type="submit" class="btn btn-primary">
                            حفظ الإعدادات
                        </button>
                    </div>
                </form>
    </div>

    <script>
        document.getElementById('default_city_id').addEventListener('change', function() {
            const cityId = this.value;
            const regionSelect = document.getElementById('default_region_id');

            if (!cityId) {
                regionSelect.innerHTML = '<option value="">اختر المنطقة</option>';
                return;
            }

            fetch(`{{ route('admin.alwaseet.api.regions') }}?city_id=${cityId}`)
                .then(response => response.json())
                .then(data => {
                    regionSelect.innerHTML = '<option value="">اختر المنطقة</option>';
                    data.regions.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.id;
                        option.textContent = region.region_name;
                        regionSelect.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });
    </script>
</x-layout.admin>

