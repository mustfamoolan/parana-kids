<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إضافة منتج جديد - {{ $warehouse->name }}</h5>
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
                        $backUrl = route('admin.warehouses.products.index', $warehouse);
                    }
                @endphp
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمنتجات
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

        <form method="POST" action="{{ route('admin.warehouses.products.store', $warehouse) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <!-- معلومات المنتج الأساسية -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">معلومات المنتج الأساسية</h6>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div>
                        <label for="name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            اسم المنتج (اختياري)
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-input @error('name') border-danger @enderror"
                            placeholder="أدخل اسم المنتج (سيستخدم الكود كاسم افتراضي إذا ترك فارغاً)"
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
                            value="{{ old('code') }}"
                            class="form-input @error('code') border-danger @enderror"
                            placeholder="أدخل كود المنتج (إنجليزية فقط)"
                            inputmode="latin"
                            pattern="[A-Za-z0-9_-]*"
                            lang="en"
                            dir="ltr"
                            autocapitalize="off"
                            autocomplete="off"
                            autocorrect="off"
                            spellcheck="false"
                            required
                        >
                        @error('code')
                            <div class="mt-1 text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="gender_type" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            نوع المنتج <span class="text-danger">*</span>
                        </label>
                        <select
                            id="gender_type"
                            name="gender_type"
                            class="form-select @error('gender_type') border-danger @enderror"
                            required
                        >
                            <option value="">-- اختر نوع المنتج --</option>
                            <option value="boys" {{ old('gender_type') == 'boys' ? 'selected' : '' }}>ولادي</option>
                            <option value="girls" {{ old('gender_type') == 'girls' ? 'selected' : '' }}>بناتي</option>
                            <option value="boys_girls" {{ old('gender_type') == 'boys_girls' ? 'selected' : '' }}>ولادي بناتي</option>
                            <option value="accessories" {{ old('gender_type') == 'accessories' ? 'selected' : '' }}>اكسسوار</option>
                        </select>
                        @error('gender_type')
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
                                value="{{ old('purchase_price') }}"
                                class="form-input @error('purchase_price') border-danger @enderror"
                                placeholder="أدخل سعر الشراء (سيتم إضافة 000 تلقائياً)"
                                min="0"
                                step="1"
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
                            value="{{ old('selling_price') }}"
                            class="form-input @error('selling_price') border-danger @enderror"
                            placeholder="أدخل سعر البيع (سيتم إضافة 000 تلقائياً)"
                            min="0"
                            step="1"
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
                    >{{ old('description') }}</textarea>
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
                        value="{{ old('link_1688') }}"
                        class="form-input @error('link_1688') border-danger @enderror"
                        placeholder="https://detail.1688.com/offer/..."
                    >
                    @error('link_1688')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- صور المنتج (متعددة) -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">صور المنتج</h6>
                    <p class="text-gray-500 dark:text-gray-400">يمكنك رفع عدة صور للمنتج (غير محدود)</p>
                </div>

                <!-- حاوية الصور المضافة -->
                <div id="addedImagesContainer" class="mb-5 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>

                <!-- حاوية مناطق رفع الصور الديناميكية -->
                <div id="uploadSlotsContainer"></div>

                <!-- File input مخفي للاستخدام الداخلي -->
                <input type="file" id="hiddenFileInput" name="images[]" accept="image/*" style="display: none;" multiple>
            </div>

            <!-- القياسات -->
            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">القياسات والكميات</h6>
                    <p class="text-gray-500 dark:text-gray-400">أضف القياسات المختلفة للمنتج مع الكميات المتوفرة</p>
                </div>

                <div id="sizesContainer">
                    <div class="size-row grid grid-cols-1 gap-5 lg:grid-cols-2 mb-4">
                        <div>
                            <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                                اسم القياس <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                name="sizes[0][size_name]"
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
                                    name="sizes[0][quantity]"
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
                    </div>
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
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    حفظ المنتج
                </button>
            </div>
        </form>
    </div>

    <script>
        let sizeIndex = 1;
        let uploadedImages = []; // Array of uploaded image data
        let imageSlotIndex = 0;

        // تهيئة أول منطقة رفع
        document.addEventListener('DOMContentLoaded', function() {
            addUploadSlot();
        });

        function addUploadSlot() {
            const slotId = `slot-${imageSlotIndex}`;
            const container = document.getElementById('uploadSlotsContainer');

            const slotHTML = `
                <div id="${slotId}" class="upload-slot border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 mb-4">
                    <div class="text-center">
                        <h6 class="font-semibold mb-3">إضافة صورة ${uploadedImages.length + 1}</h6>

                        <!-- منطقة اللصق -->
                        <div class="paste-zone-${slotId} border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg p-8 mb-3 cursor-pointer hover:border-primary hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-sm text-gray-600 dark:text-gray-400">انقر أو الصق صورة (Ctrl+V)</p>
                        </div>

                        <!-- زر اللصق -->
                        <button type="button" class="paste-btn-${slotId} btn btn-outline-primary btn-sm mb-3">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            لصق من الحافظة
                        </button>

                        <!-- حقل URL -->
                        <input type="url" class="url-input-${slotId} form-input mb-3" placeholder="أو الصق رابط الصورة">

                        <!-- زر اختيار ملف -->
                        <button type="button" class="file-btn-${slotId} btn btn-outline-secondary btn-sm">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            اختر ملف
                        </button>
                        <input type="file" class="file-input-${slotId}" style="display:none;" accept="image/*">
                    </div>
                </div>
            `;

            container.innerHTML = slotHTML;
            initializeSlotEvents(slotId);
            imageSlotIndex++;
        }

        // Global paste handler (once only)
        let globalPasteHandlerAdded = false;

        function initializeSlotEvents(slotId) {
            const pasteZone = document.querySelector(`.paste-zone-${slotId}`);
            const pasteBtn = document.querySelector(`.paste-btn-${slotId}`);
            const urlInput = document.querySelector(`.url-input-${slotId}`);
            const fileBtn = document.querySelector(`.file-btn-${slotId}`);
            const fileInput = document.querySelector(`.file-input-${slotId}`);

            // اللصق من الحافظة عبر Ctrl+V (مرة واحدة فقط)
            if (!globalPasteHandlerAdded) {
                document.addEventListener('paste', function(e) {
                    const items = e.clipboardData.items;
                    for (let item of items) {
                        if (item.type.indexOf('image') !== -1) {
                            e.preventDefault();
                            const file = item.getAsFile();
                            const currentSlot = document.querySelector('[id^="slot-"]:last-of-type');
                            if (currentSlot) {
                                addImage(file, currentSlot.id);
                            }
                            break;
                        }
                    }
                });
                globalPasteHandlerAdded = true;
            }

            // زر اللصق - استخدام Clipboard API
            pasteBtn.addEventListener('click', async function() {
                try {
                    // التحقق من توفر Clipboard API
                    if (!navigator.clipboard || !navigator.clipboard.read) {
                        // Fallback: الطلب من المستخدم استخدام Ctrl+V
                        alert('هذا المتصفح لا يدعم لصق الصور من الحافظة مباشرة. الرجاء استخدام Ctrl+V');
                        return;
                    }

                    // قراءة clipboard
                    const clipboardItems = await navigator.clipboard.read();

                    // البحث عن صورة في clipboard
                    for (const clipboardItem of clipboardItems) {
                        // البحث عن نوع صورة في أنواع clipboard item
                        for (const type of clipboardItem.types) {
                            if (type.startsWith('image/')) {
                                const blob = await clipboardItem.getType(type);
                                const file = new File([blob], `pasted-image-${Date.now()}.${type.split('/')[1]}`, { type: type });
                                addImage(file, slotId);
                                return; // نجحنا في لصق الصورة
                            }
                        }
                    }

                    // إذا لم نجد صورة
                    alert('لا توجد صورة في الحافظة. يرجى نسخ صورة أولاً ثم الضغط على زر اللصق.');
                } catch (error) {
                    console.error('خطأ في لصق الصورة:', error);
                    // في حالة رفض الإذن أو خطأ آخر
                    if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
                        alert('تم رفض الوصول إلى الحافظة. يرجى السماح للموقع بالوصول إلى الحافظة أو استخدم Ctrl+V بدلاً من ذلك.');
                    } else {
                        alert('حدث خطأ أثناء محاولة لصق الصورة. يرجى المحاولة باستخدام Ctrl+V بدلاً من ذلك.');
                    }
                }
            });

            // السحب والإفلات
            pasteZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('border-primary');
            });

            pasteZone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('border-primary');
            });

            pasteZone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('border-primary');
                const file = e.dataTransfer.files[0];
                if (file && file.type.indexOf('image') !== -1) {
                    addImage(file, slotId);
                }
            });

            // حقل URL
            urlInput.addEventListener('change', function() {
                if (this.value) {
                    addImage(this.value, slotId, 'url');
                }
            });

            // زر اختيار ملف
            fileBtn.addEventListener('click', function() {
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    addImage(this.files[0], slotId);
                }
            });
        }

        function addImage(source, slotId, type = 'file') {
            const imageData = {
                id: Date.now(),
                source: source,
                type: type
            };

            uploadedImages.push(imageData);
            displayAddedImage(imageData);

            // إزالة منطقة الرفع الحالية
            document.getElementById(slotId).remove();

            // إضافة منطقة رفع جديدة
            addUploadSlot();

            // تحديث file input المخفي
            updateHiddenFileInput();
        }

        function displayAddedImage(imageData) {
            const container = document.getElementById('addedImagesContainer');
            const imageCard = document.createElement('div');
            imageCard.className = 'relative border rounded-lg overflow-hidden';
            imageCard.id = `image-${imageData.id}`;

            if (imageData.type === 'file') {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imageCard.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-48 object-cover">
                        <button type="button" onclick="removeImage(${imageData.id})" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                        ${uploadedImages.length === 1 ? '<span class="absolute top-2 left-2 bg-primary text-white px-2 py-1 rounded text-xs">الصورة الرئيسية</span>' : ''}
                    `;
                };
                reader.readAsDataURL(imageData.source);
            } else {
                imageCard.innerHTML = `
                    <img src="${imageData.source}" class="w-full h-48 object-cover">
                    <button type="button" onclick="removeImage(${imageData.id})" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    ${uploadedImages.length === 1 ? '<span class="absolute top-2 left-2 bg-primary text-white px-2 py-1 rounded text-xs">الصورة الرئيسية</span>' : ''}
                    <input type="hidden" name="image_urls[]" value="${imageData.source}">
                `;
            }

            container.appendChild(imageCard);
        }

        function removeImage(imageId) {
            uploadedImages = uploadedImages.filter(img => img.id !== imageId);
            document.getElementById(`image-${imageId}`).remove();
            updateHiddenFileInput();
        }

        function updateHiddenFileInput() {
            const fileInput = document.getElementById('hiddenFileInput');
            const dataTransfer = new DataTransfer();

            uploadedImages.forEach(img => {
                if (img.type === 'file') {
                    dataTransfer.items.add(img.source);
                }
            });

            fileInput.files = dataTransfer.files;
        }

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

        // إضافة مستمع للحذف للصف الأول
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-size')) {
                const sizeRow = e.target.closest('.size-row');
                const container = document.getElementById('sizesContainer');
                // السماح بالحذف إذا كان هناك أكثر من قياس واحد
                if (container.querySelectorAll('.size-row').length > 1) {
                    sizeRow.remove();
                } else {
                    alert('يجب أن يكون هناك على الأقل قياس واحد');
                }
            }
        });

        // التحقق من وجود قياس واحد على الأقل قبل الإرسال
        document.querySelector('form').addEventListener('submit', function(e) {
            const sizeRows = document.querySelectorAll('#sizesContainer .size-row');
            if (sizeRows.length === 0) {
                e.preventDefault();
                alert('يجب إضافة قياس واحد على الأقل للمنتج');
                return false;
            }
        });

        // إجبار الكتابة بالإنجليزية فقط في حقل الكود وتحويل لوحة المفاتيح
        const codeInput = document.getElementById('code');
        if (codeInput) {
            // عند التركيز: تحويل إلى إنجليزية
            codeInput.addEventListener('focus', function() {
                this.setAttribute('lang', 'en');
                this.setAttribute('dir', 'ltr');
                this.style.textAlign = 'left';
                // محاولة تغيير لغة لوحة المفاتيح (يعمل على بعض الأجهزة)
                if (navigator.userAgent.match(/iPhone|iPad|iPod|Android/i)) {
                    this.setAttribute('inputmode', 'latin');
                }
            });

            // عند فقدان التركيز: إرجاع إلى الوضع الطبيعي
            codeInput.addEventListener('blur', function() {
                // إبقاء lang="en" و dir="ltr" لأن الحقل يحتوي على كود إنجليزي
            });

            codeInput.addEventListener('input', function(e) {
                // السماح فقط بالأحرف الإنجليزية والأرقام والشرطة السفلية والشرطة
                this.value = this.value.replace(/[^A-Za-z0-9_-]/g, '');
            });

            codeInput.addEventListener('keypress', function(e) {
                // منع الأحرف العربية والأحرف الخاصة
                const char = String.fromCharCode(e.which);
                if (!/[A-Za-z0-9_-]/.test(char)) {
                    e.preventDefault();
                }
            });
        }

        // إضافة 000 تلقائياً في حقول الأسعار (مع استخدام الفاصلة لتقليل الأصفار)
        function addZerosToPrice(input) {
            if (!input) return;

            // إزالة listener القديم إذا كان موجوداً لمنع التكرار
            if (input._priceBlurHandler) {
                input.removeEventListener('blur', input._priceBlurHandler);
            }

            // إنشاء named function لحفظ reference
            input._priceBlurHandler = function() {
                let inputValue = this.value.trim();
                if (!inputValue || inputValue === '0') return;

                const numericValue = parseFloat(inputValue);
                if (isNaN(numericValue) || numericValue <= 0) return;

                // التحقق من أن القيمة لم يتم معالجتها بالفعل (لا تنتهي بـ 000 أو 00)
                const valueStr = String(Math.floor(numericValue));

                // إذا كانت القيمة تنتهي بـ 000 وطولها أكبر من 3، فهي معالجة بالفعل
                if (valueStr.endsWith('000') && valueStr.length > 3) {
                    return; // القيمة معالجة بالفعل، لا نضيف أصفار
                }

                // التحقق من وجود فاصلة عشرية (نقطة)
                const hasDecimal = inputValue.includes('.');

                if (hasDecimal) {
                    // إذا كان هناك فاصلة → أضف 00 فقط (الفاصلة تقلل صفر واحد)
                    const parts = inputValue.split('.');
                    const integerPart = parts[0] || '0';
                    const decimalPart = parts[1] || '';
                    // دمج الأجزاء بدون فاصلة
                    const combinedValue = integerPart + decimalPart;
                    const combinedNumeric = parseFloat(combinedValue);
                    if (!isNaN(combinedNumeric)) {
                        const resultValue = combinedNumeric * 100;
                        const resultStr = String(Math.floor(resultValue));
                        // التحقق من أن القيمة الناتجة لا تنتهي بـ 00 (معالجة بالفعل)
                        if (!resultStr.endsWith('00') || resultStr.length <= 2) {
                            this.value = resultValue;
                        }
                    }
                } else {
                    // إذا لم يكن هناك فاصلة → أضف 000 (3 أصفار)
                    // لكن فقط إذا كانت القيمة لا تنتهي بـ 000 بالفعل
                    if (!valueStr.endsWith('000') || valueStr.length <= 3) {
                        this.value = Math.floor(numericValue) * 1000;
                    }
                }
            };

            // إضافة listener جديد
            input.addEventListener('blur', input._priceBlurHandler);
        }

        // تطبيق على حقول الأسعار
        const purchasePriceInput = document.getElementById('purchase_price');
        const sellingPriceInput = document.getElementById('selling_price');

        if (purchasePriceInput) {
            addZerosToPrice(purchasePriceInput);
        }

        if (sellingPriceInput) {
            addZerosToPrice(sellingPriceInput);
        }
    </script>

    <style>
        /* إخفاء أسهم الزيادة والنقصان من حقول number */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }
    </style>
</x-layout.admin>
