<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل المنتج: {{ $product->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.products.show', [$product->warehouse, $product]) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للتفاصيل
                </a>
            </div>
        </div>

        <!-- ملاحظة العملة العراقية -->
        <div class="mb-5">
            <div class="alert alert-info">
                <div class="flex items-start">
                    <svg class="w-5 h-5 ltr:mr-3 rtl:ml-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h6 class="font-semibold">ملاحظة مهمة حول العملة</h6>
                        <p class="text-sm">نحن في العراق وعملتنا هي الدينار العراقي. لا توجد فاصلة عشرية في العملة العراقية، لذلك المبالغ تظهر كأرقام صحيحة (مثل: 1000 دينار عراقي بدلاً من 1000.00).</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.warehouses.products.update', [$product->warehouse, $product]) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- معلومات المنتج الأساسية -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات المنتج الأساسية</h6>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div>
                        <label for="name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            اسم المنتج <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $product->name) }}"
                            class="form-input @error('name') border-danger @enderror"
                            placeholder="أدخل اسم المنتج"
                            required
                        >
                        @error('name')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="code" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            كود المنتج <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="code"
                            name="code"
                            value="{{ old('code', $product->code) }}"
                            class="form-input @error('code') border-danger @enderror"
                            placeholder="أدخل كود المنتج"
                            required
                        >
                        @error('code')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(auth()->user()->isAdmin())
                        <div>
                            <label for="purchase_price" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                سعر الشراء (دينار عراقي)
                            </label>
                            <input
                                type="number"
                                id="purchase_price"
                                name="purchase_price"
                                value="{{ old('purchase_price', $product->purchase_price) }}"
                                class="form-input @error('purchase_price') border-danger @enderror"
                                placeholder="أدخل سعر الشراء"
                                min="0"
                            >
                            @error('purchase_price')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="selling_price" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            سعر البيع (دينار عراقي) <span class="text-danger">*</span>
                        </label>
                        <input
                            type="number"
                            id="selling_price"
                            name="selling_price"
                            value="{{ old('selling_price', $product->selling_price) }}"
                            class="form-input @error('selling_price') border-danger @enderror"
                            placeholder="أدخل سعر البيع"
                            min="0"
                            required
                        >
                        @error('selling_price')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-5">
                    <label for="description" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        وصف المنتج
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="form-textarea @error('description') border-danger @enderror"
                        placeholder="أدخل وصف المنتج"
                    >{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-5">
                    <label for="link_1688" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        رابط 1688 (اختياري)
                    </label>
                    <input
                        type="url"
                        id="link_1688"
                        name="link_1688"
                        value="{{ old('link_1688', $product->link_1688) }}"
                        class="form-input @error('link_1688') border-danger @enderror"
                        placeholder="https://detail.1688.com/offer/..."
                    >
                    @error('link_1688')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- صورة المنتج -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">صورة المنتج</h6>
                    <p class="text-gray-500 dark:text-gray-400">يمكنك رفع صورة جديدة لاستبدال الصورة الحالية</p>
                </div>

                <!-- الصورة الحالية -->
                @if($product->images->count() > 0)
                    <div class="mb-5">
                        <label class="mb-3 block text-sm font-medium text-black dark:text-white">الصورة الحالية</label>
                        <div class="max-w-xs">
                            <img src="{{ $product->images->first()->image_url }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-auto object-cover rounded-lg border border-gray-200">
                        </div>
                    </div>
                @endif

                <div>
                    <label for="images" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        صورة جديدة (اختيارية)
                    </label>
                    <input
                        type="file"
                        id="images"
                        name="images[]"
                        class="form-input"
                        accept="image/*"
                    >
                    <p class="mt-2 text-sm text-gray-500">أنواع الصور المدعومة: JPG, PNG, GIF. الحد الأقصى: 5MB</p>
                    @error('images')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- القياسات -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">القياسات والكميات</h6>
                    <p class="text-gray-500 dark:text-gray-400">تعديل القياسات المختلفة للمنتج مع الكميات المتوفرة</p>
                </div>

                <div id="sizesContainer">
                    @foreach($product->sizes as $index => $size)
                        <div class="size-row grid grid-cols-1 gap-5 lg:grid-cols-2 mb-4">
                            <div>
                                <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                                    اسم القياس <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="sizes[{{ $index }}][size_name]"
                                    class="form-input"
                                    placeholder="مثل: S, M, L, 38, 40"
                                    value="{{ old('sizes.' . $index . '.size_name', $size->size_name) }}"
                                    required
                                >
                            </div>
                            <div>
                                <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                                    الكمية <span class="text-danger">*</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="number"
                                        name="sizes[{{ $index }}][quantity]"
                                        class="form-input"
                                        placeholder="أدخل الكمية"
                                        value="{{ old('sizes.' . $index . '.quantity', $size->quantity) }}"
                                        min="0"
                                        required
                                    >
                                    @if($index > 0)
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-size">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button type="button" id="addSize" class="btn btn-outline-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة قياس آخر
                </button>

                @error('sizes')
                    <div class="mt-2 text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-4 pt-5">
                <a href="{{ route('admin.warehouses.products.show', [$product->warehouse, $product]) }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    تحديث المنتج
                </button>
            </div>
        </form>
    </div>

    <script>
        let sizeIndex = {{ $product->sizes->count() }};

        document.getElementById('addSize').addEventListener('click', function() {
            const container = document.getElementById('sizesContainer');
            const newRow = document.createElement('div');
            newRow.className = 'size-row grid grid-cols-1 gap-5 lg:grid-cols-2 mb-4';
            newRow.innerHTML = `
                <div>
                    <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                        اسم القياس <span class="text-danger">*</span>
                    </label>
                    <input
                        type="text"
                        name="sizes[${sizeIndex}][size_name]"
                        class="form-input"
                        placeholder="مثل: S, M, L, 38, 40"
                        required
                    >
                </div>
                <div>
                    <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                        الكمية <span class="text-danger">*</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <input
                            type="number"
                            name="sizes[${sizeIndex}][quantity]"
                            class="form-input"
                            placeholder="أدخل الكمية"
                            min="0"
                            required
                        >
                        <button type="button" class="btn btn-outline-danger btn-sm remove-size">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            `;

            container.appendChild(newRow);
            sizeIndex++;

            // إضافة مستمع للحذف
            newRow.querySelector('.remove-size').addEventListener('click', function() {
                newRow.remove();
            });
        });

        // إضافة مستمع للحذف للصفوف الموجودة
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-size')) {
                e.target.closest('.size-row').remove();
            }
        });
    </script>
</x-layout.admin>
