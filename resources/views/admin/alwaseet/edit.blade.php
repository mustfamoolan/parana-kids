<x-layout.admin>
    <div class="panel">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">تعديل طلب #{{ $shipment->alwaseet_order_id }}</h5>
                    <a href="{{ route('admin.alwaseet.show', $shipment->id) }}" class="btn btn-outline-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة
                    </a>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger mb-5">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.alwaseet.orders.update', $shipment->id) }}">
                    @csrf
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <!-- معلومات العميل -->
                        <div class="panel">
                            <h6 class="mb-4 text-lg font-semibold">معلومات العميل</h6>

                            <div class="mb-4">
                                <label for="client_name" class="mb-2 block text-sm font-medium">اسم العميل <span class="text-danger">*</span></label>
                                <input type="text" name="client_name" id="client_name" value="{{ old('client_name', $shipment->client_name) }}"
                                       class="form-input" required>
                            </div>

                            <div class="mb-4">
                                <label for="client_mobile" class="mb-2 block text-sm font-medium">رقم الهاتف <span class="text-danger">*</span></label>
                                <input type="text" name="client_mobile" id="client_mobile" value="{{ old('client_mobile', $shipment->client_mobile) }}"
                                       placeholder="+9647700000000" class="form-input" required>
                                <small class="text-gray-500">يجب أن يبدأ بـ +964</small>
                            </div>

                            <div class="mb-4">
                                <label for="client_mobile2" class="mb-2 block text-sm font-medium">رقم الهاتف الثاني (اختياري)</label>
                                <input type="text" name="client_mobile2" id="client_mobile2" value="{{ old('client_mobile2', $shipment->client_mobile2) }}"
                                       placeholder="+9647700000000" class="form-input">
                            </div>
                        </div>

                        <!-- معلومات الشحن -->
                        <div class="panel">
                            <h6 class="mb-4 text-lg font-semibold">معلومات الشحن</h6>

                            <div class="mb-4">
                                <label for="city_id" class="mb-2 block text-sm font-medium">المدينة <span class="text-danger">*</span></label>
                                <select name="city_id" id="city_id" class="form-select" required>
                                    <option value="">-- اختر المدينة --</option>
                                    @foreach($cities as $city)
                                        <option value="{{ $city['id'] }}" {{ old('city_id', $shipment->city_id) == $city['id'] ? 'selected' : '' }}>
                                            {{ $city['city_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="region_id" class="mb-2 block text-sm font-medium">المنطقة <span class="text-danger">*</span></label>
                                <select name="region_id" id="region_id" class="form-select" required>
                                    <option value="">-- اختر المنطقة --</option>
                                    @foreach($regions as $region)
                                        <option value="{{ $region['id'] }}" {{ old('region_id', $shipment->region_id) == $region['id'] ? 'selected' : '' }}>
                                            {{ $region['region_name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="location" class="mb-2 block text-sm font-medium">الموقع <span class="text-danger">*</span></label>
                                <textarea name="location" id="location" rows="3" class="form-textarea" required>{{ old('location', $shipment->location) }}</textarea>
                            </div>
                        </div>

                        <!-- معلومات الطلب -->
                        <div class="panel">
                            <h6 class="mb-4 text-lg font-semibold">معلومات الطلب</h6>

                            <div class="mb-4">
                                <label for="price" class="mb-2 block text-sm font-medium">السعر (يشمل رسوم التوصيل) <span class="text-danger">*</span></label>
                                <input type="number" name="price" id="price" value="{{ old('price', $shipment->price) }}"
                                       step="0.01" min="0" class="form-input" required>
                            </div>

                            <div class="mb-4">
                                <label for="package_size" class="mb-2 block text-sm font-medium">حجم الطرد <span class="text-danger">*</span></label>
                                <select name="package_size" id="package_size" class="form-select" required>
                                    <option value="">-- اختر الحجم --</option>
                                    @foreach($packageSizes as $size)
                                        <option value="{{ $size['id'] }}" {{ old('package_size', $shipment->package_size) == $size['id'] ? 'selected' : '' }}>
                                            {{ $size['size'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="type_name" class="mb-2 block text-sm font-medium">نوع البضاعة <span class="text-danger">*</span></label>
                                <input type="text" name="type_name" id="type_name" value="{{ old('type_name', $shipment->type_name) }}"
                                       class="form-input" required>
                            </div>

                            <div class="mb-4">
                                <label for="items_number" class="mb-2 block text-sm font-medium">عدد القطع <span class="text-danger">*</span></label>
                                <input type="number" name="items_number" id="items_number" value="{{ old('items_number', $shipment->items_number ?? '1') }}"
                                       min="1" class="form-input" required>
                                <small class="text-gray-500">عدد القطع في الطلب</small>
                            </div>
                        </div>

                        <!-- معلومات إضافية -->
                        <div class="panel">
                            <h6 class="mb-4 text-lg font-semibold">معلومات إضافية</h6>

                            <div class="mb-4">
                                <label for="merchant_notes" class="mb-2 block text-sm font-medium">ملاحظات التاجر</label>
                                <textarea name="merchant_notes" id="merchant_notes" rows="3" class="form-textarea">{{ old('merchant_notes', $shipment->merchant_notes) }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="replacement" value="1" {{ old('replacement', $shipment->replacement) ? 'checked' : '' }} class="form-checkbox">
                                    <span class="ltr:ml-2 rtl:mr-2">طلب استبدال</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            تحديث الطلب
                        </button>
                        <a href="{{ route('admin.alwaseet.show', $shipment->id) }}" class="btn btn-outline-secondary">إلغاء</a>
                    </div>
                </form>
    </div>

    <script>
        // جلب المناطق عند اختيار المدينة
        document.getElementById('city_id').addEventListener('change', function() {
            const cityId = this.value;
            const regionSelect = document.getElementById('region_id');

            if (!cityId) {
                regionSelect.innerHTML = '<option value="">-- اختر المدينة أولاً --</option>';
                regionSelect.disabled = true;
                return;
            }

            regionSelect.disabled = true;
            regionSelect.innerHTML = '<option value="">جاري التحميل...</option>';

            fetch(`{{ route('admin.alwaseet.api.regions') }}?city_id=${cityId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.regions) {
                    const currentRegionId = '{{ $shipment->region_id }}';
                    regionSelect.innerHTML = '<option value="">-- اختر المنطقة --</option>';
                    data.regions.forEach(region => {
                        const selected = region.id == currentRegionId ? 'selected' : '';
                        regionSelect.innerHTML += `<option value="${region.id}" ${selected}>${region.region_name}</option>`;
                    });
                    regionSelect.disabled = false;
                } else {
                    regionSelect.innerHTML = '<option value="">لا توجد مناطق</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                regionSelect.innerHTML = '<option value="">خطأ في جلب المناطق</option>';
            });
        });
    </script>
</x-layout.admin>

