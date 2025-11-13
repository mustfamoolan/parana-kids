<x-layout.admin>
    <script src="/assets/js/simple-datatables.js"></script>
    <div class="space-y-5">
        @if(isset($privateWarehouse) && $privateWarehouse)
        <!-- معلومات المخزن الخاص -->
        <div class="panel">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h5 class="text-lg font-semibold dark:text-white-light">المخزن الخاص: {{ $privateWarehouse->name }}</h5>
                    @if($privateWarehouse->description)
                        <p class="text-sm text-gray-500 mt-1">{{ $privateWarehouse->description }}</p>
                    @endif
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <a href="{{ route('admin.private-warehouses.index') }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        العودة للمخازن الخاصة
                    </a>
                    @if(auth()->check() && auth()->user()->isAdmin())
                    <a href="{{ route('admin.invoices.my-invoices') }}?private_warehouse_id={{ $privateWarehouse->id }}" class="btn btn-outline-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        عرض الفواتير المحفوظة
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if(isset($invoice) && $invoice)
        <!-- معلومات الفاتورة عند التعديل -->
        <div class="panel">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h5 class="text-lg font-semibold dark:text-white-light">تعديل الفاتورة: {{ $invoice->invoice_number }}</h5>
                    <p class="text-sm text-gray-500 mt-1">تاريخ الإنشاء: {{ $invoice->created_at->format('Y-m-d H:i') }} | منشئ الفاتورة: {{ $invoice->creator->name ?? 'غير معروف' }}</p>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    @if(isset($privateWarehouse) && $privateWarehouse)
                        <a href="{{ route('admin.invoices.my-invoices', ['private_warehouse_id' => $privateWarehouse->id]) }}" class="btn btn-outline-secondary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            العودة للفواتير
                        </a>
                    @else
                        <a href="{{ route('admin.invoices.my-invoices') }}" class="btn btn-outline-secondary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            العودة للفواتير
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- قسم جمع المنتجات -->
        <div class="panel">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h5 class="text-lg font-semibold dark:text-white-light">@if(isset($invoice) && $invoice) تعديل منتجات الفاتورة @else إضافة منتج جديد @endif</h5>
                @if(auth()->check() && (auth()->user()->isPrivateSupplier() || auth()->user()->isAdmin()) && !isset($privateWarehouse) && !isset($invoice))
                <a href="{{ route('admin.invoices.my-invoices') }}" class="btn btn-outline-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    عرض الفواتير المحفوظة
                </a>
                @endif
            </div>

            <form id="productForm" class="space-y-5">
                <!-- صورة المنتج -->
                <div>
                    <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                        صورة المنتج
                    </label>

                    <!-- حاوية الصور المضافة -->
                    <div id="addedImagesContainer" class="mb-5 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>

                    <!-- حاوية مناطق رفع الصور -->
                    <div id="uploadSlotsContainer"></div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <!-- رابط المنتج -->
                    <div>
                        <label for="productLink" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            رابط المنتج (1688)
                        </label>
                        <input
                            type="url"
                            id="productLink"
                            class="form-input"
                            placeholder="https://detail.1688.com/..."
                        >
                    </div>

                    <!-- سعر المنتج -->
                    <div>
                        <label for="priceYuan" class="mb-3 block text-sm font-medium text-black dark:text-white">
                            سعر المنتج (يوان صيني)
                        </label>
                        <input
                            type="number"
                            id="priceYuan"
                            class="form-input"
                            placeholder="أدخل السعر باليوان"
                            min="0"
                            step="0.01"
                        >
                    </div>

                    <!-- القياسات المتوفرة -->
                    <div>
                        <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                            القياسات المتوفرة <span class="text-danger">*</span>
                        </label>
                        <div class="max-h-48 overflow-y-auto border rounded p-3">
                            <div class="mb-3">
                                <strong class="text-sm">قياسات الأرقام الكبيرة:</strong>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach(['56', '64', '70', '80', '90', '100', '110', '120', '130', '140', '150', '160', '170', '180'] as $size)
                                        <label class="flex items-center">
                                            <input type="checkbox" name="sizes[]" value="{{ $size }}" class="form-checkbox">
                                            <span class="mr-2">{{ $size }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong class="text-sm">قياسات الأرقام الصغيرة:</strong>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach(range(21, 46) as $size)
                                        <label class="flex items-center">
                                            <input type="checkbox" name="sizes[]" value="{{ $size }}" class="form-checkbox">
                                            <span class="mr-2">{{ $size }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mb-3">
                                <strong class="text-sm">قياسات الحروف:</strong>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach(['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'] as $size)
                                        <label class="flex items-center">
                                            <input type="checkbox" name="sizes[]" value="{{ $size }}" class="form-checkbox">
                                            <span class="mr-2">{{ strtoupper($size) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t">
                                <strong class="text-sm mb-2 block">قياسات مخصصة:</strong>
                                <div class="flex gap-2 mb-2">
                                    <input type="text" id="customSizeInput" class="form-input flex-1" placeholder="أدخل قياس مخصص (مثلاً: 85, XXL, إلخ)">
                                    <button type="button" id="addCustomSizeBtn" class="btn btn-outline-primary btn-sm">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        إضافة
                                    </button>
                                </div>
                                <div id="customSizesContainer" class="flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        حفظ المنتج
                    </button>
                </div>
            </form>
        </div>

        <!-- قسم المنتجات المحفوظة -->
        <div class="panel">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h5 class="text-lg font-semibold dark:text-white-light">المنتجات المحفوظة</h5>
            </div>

            <!-- البحث والفلترة -->
            <div class="mb-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="searchCode" class="mb-2 block text-sm font-medium text-black dark:text-white">
                        البحث بالكود
                    </label>
                    <input
                        type="text"
                        id="searchCode"
                        class="form-input"
                        placeholder="أدخل الكود..."
                        value="{{ request('code') }}"
                    >
                </div>
                <div>
                    <label for="priceFrom" class="mb-2 block text-sm font-medium text-black dark:text-white">
                        السعر من
                    </label>
                    <input
                        type="number"
                        id="priceFrom"
                        class="form-input"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        value="{{ request('price_from') }}"
                    >
                </div>
                <div>
                    <label for="priceTo" class="mb-2 block text-sm font-medium text-black dark:text-white">
                        السعر إلى
                    </label>
                    <input
                        type="number"
                        id="priceTo"
                        class="form-input"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        value="{{ request('price_to') }}"
                    >
                </div>
            </div>
            <div class="mb-5 flex gap-2">
                <button type="button" id="searchBtn" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    بحث
                </button>
                <button type="button" id="clearSearchBtn" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    مسح
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <div class="max-w-[19rem] w-full bg-white shadow-[4px_6px_10px_-3px_#bfc9d4] rounded border border-[#e0e6ed] dark:border-[#1b2e4b] dark:bg-[#191e3a] dark:shadow-none">
                        <div class="py-7 px-6">
                            <div class="-mt-7 mb-7 -mx-6 rounded-tl rounded-tr h-[215px] overflow-hidden">
                                <img src="{{ $product->image_url }}" alt="صورة المنتج" class="w-full h-full object-cover" />
                            </div>
                            <div class="mb-3">
                                <span class="badge bg-primary/10 text-primary dark:bg-primary dark:text-white">الكود: {{ $product->code }}</span>
                            </div>
                            <h5 class="text-[#3b3f5c] text-xl font-semibold mb-4 dark:text-white-light">السعر: {{ number_format($product->price_yuan, 2) }} ¥</h5>
                            <p class="text-white-dark mb-3">
                                <a href="{{ $product->product_link }}" target="_blank" class="text-primary break-all text-sm">{{ $product->product_link }}</a>
                            </p>
                            <div class="mb-4">
                                <strong class="text-sm">القياسات:</strong>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($product->available_sizes as $size)
                                        <span class="badge bg-primary">{{ $size }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button onclick="addToInvoice({{ $product->id }})" class="btn btn-primary flex-1">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    إضافة للفاتورة
                                </button>
                                <button onclick="editProduct({{ $product->id }})" class="btn btn-outline-primary">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="deleteProduct({{ $product->id }})" class="btn btn-outline-danger">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($products->isEmpty())
                <p class="text-center text-gray-500 py-8">لا توجد منتجات محفوظة</p>
            @endif
        </div>

        <!-- قسم الفاتورة (السلة) -->
        <div class="panel" id="invoiceSection" style="display: none;" x-data="invoiceTable">
            <div class="mb-5 flex justify-between items-center">
                <h5 class="text-lg font-semibold dark:text-white-light">الفاتورة</h5>
                <div class="flex gap-2">
                    <button type="button" id="clearInvoiceBtn" class="btn btn-outline-danger">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        مسح الفاتورة
                    </button>
                    <button type="button" id="saveInvoiceBtn" class="btn btn-success">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span id="saveInvoiceBtnText">حفظ الفاتورة</span>
                    </button>
                    <button type="button" id="printInvoiceBtn" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        تحميل PDF
                    </button>
                </div>
            </div>

            <table id="invoiceTable" class="whitespace-nowrap"></table>
        </div>
    </div>

    <!-- Modal لتحديد القياسات والعدد -->
    <div x-data="addToInvoiceModal">
        <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto" :class="open && '!block'">
            <div class="flex items-start justify-center min-h-screen px-4 py-8" @click.self="open = false">
                <div x-show="open" x-transition x-transition.duration.300 class="panel border-0 p-0 rounded-lg w-full max-w-5xl my-8">
                    <div class="flex bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-between px-5 py-3 sticky top-0 z-10">
                        <h5 class="font-bold text-lg">اختر القياسات والعدد</h5>
                        <button type="button" class="text-white-dark hover:text-dark" @click="toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="p-5">
                        <div id="productInfo" class="mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded">
                            <div id="productImageContainer" class="mb-2"></div>
                            <div id="productLinkContainer" class="text-sm"></div>
                        </div>
                        <div id="sizesContainer" class="mb-4">
                            <!-- سيتم ملء القياسات ديناميكياً -->
                        </div>
                        <div id="selectedSizesContainer" class="mb-4">
                            <h4 class="text-sm font-semibold mb-2">القياسات المختارة:</h4>
                            <div id="selectedSizesList" class="space-y-2">
                                <!-- سيتم ملء القياسات المختارة هنا -->
                            </div>
                        </div>
                        <div class="flex justify-end items-center mt-8">
                            <button type="button" class="btn btn-outline-danger" @click="toggle">إلغاء</button>
                            <button type="button" class="btn btn-primary ltr:ml-4 rtl:mr-4" id="confirmQuantityBtn">إضافة للفاتورة</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تعديل المنتج -->
    <div x-data="editProductModal">
        <div class="fixed inset-0 bg-[black]/60 z-[999] hidden overflow-y-auto" :class="open && '!block'">
            <div class="flex items-start justify-center min-h-screen px-4 py-8" @click.self="open = false">
                <div x-show="open" x-transition x-transition.duration.300 class="panel border-0 p-0 rounded-lg w-full max-w-5xl my-8">
                    <div class="flex bg-[#fbfbfb] dark:bg-[#121c2c] items-center justify-between px-5 py-3 sticky top-0 z-10">
                        <h5 class="font-bold text-lg">تعديل المنتج</h5>
                        <button type="button" class="text-white-dark hover:text-dark" @click="toggle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="p-5">
                        <form id="editProductForm" class="space-y-5">
                            <!-- صورة المنتج -->
                            <div>
                                <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                                    صورة المنتج
                                </label>
                                <div id="editProductImageContainer" class="mb-3">
                                    <img id="editProductImage" src="" alt="صورة المنتج" class="w-32 h-32 object-cover rounded">
                                </div>
                                <input type="text" id="editProductImageUrl" class="form-input" placeholder="رابط الصورة أو base64">
                            </div>

                            <!-- رابط المنتج -->
                            <div>
                                <label for="editProductLink" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                    رابط المنتج (1688)
                                </label>
                                <input type="url" id="editProductLink" class="form-input">
                            </div>

                            <!-- السعر -->
                            <div>
                                <label for="editProductPrice" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                    السعر (يوان)
                                </label>
                                <input type="number" id="editProductPrice" step="0.01" min="0" class="form-input">
                            </div>

                            <!-- القياسات المتوفرة -->
                            <div>
                                <label class="mb-3 block text-sm font-medium text-black dark:text-white">
                                    القياسات المتوفرة <span class="text-danger">*</span>
                                </label>
                                <div class="max-h-48 overflow-y-auto border rounded p-3">
                                    <div class="mb-3">
                                        <strong class="text-sm">قياسات الأرقام الكبيرة:</strong>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach(['56', '64', '70', '80', '90', '100', '110', '120', '130', '140', '150', '160', '170', '180'] as $size)
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="editSizes[]" value="{{ $size }}" class="form-checkbox edit-size-checkbox">
                                                    <span class="mr-2">{{ $size }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong class="text-sm">قياسات الأرقام الصغيرة:</strong>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach(range(21, 46) as $size)
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="editSizes[]" value="{{ $size }}" class="form-checkbox edit-size-checkbox">
                                                    <span class="mr-2">{{ $size }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <strong class="text-sm">قياسات الحروف:</strong>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            @foreach(['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl', '7xl'] as $size)
                                                <label class="flex items-center">
                                                    <input type="checkbox" name="editSizes[]" value="{{ $size }}" class="form-checkbox edit-size-checkbox">
                                                    <span class="mr-2">{{ strtoupper($size) }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-4 border-t">
                                        <strong class="text-sm mb-2 block">قياسات مخصصة:</strong>
                                        <div class="flex gap-2 mb-2">
                                            <input type="text" id="editCustomSizeInput" class="form-input flex-1" placeholder="أدخل قياس مخصص">
                                            <button type="button" id="addEditCustomSizeBtn" class="btn btn-outline-primary btn-sm">
                                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                إضافة
                                            </button>
                                        </div>
                                        <div id="editCustomSizesContainer" class="flex flex-wrap gap-2 mt-2"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end items-center mt-8">
                                <button type="button" class="btn btn-outline-danger" @click="toggle">إلغاء</button>
                                <button type="submit" class="btn btn-primary ltr:ml-4 rtl:mr-4">حفظ التعديلات</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // تهيئة Alpine.js modals
        document.addEventListener("alpine:init", () => {
            // Modal إضافة للفاتورة
            Alpine.data("addToInvoiceModal", () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                },
            }));

            // Modal تعديل المنتج
            Alpine.data("editProductModal", () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                },
            }));

            // تهيئة جدول الفاتورة
            Alpine.data("invoiceTable", () => ({
                datatable: null,
                init() {
                    // سيتم تحديث الجدول من renderInvoice
                },
                updateTable(data) {
                    if (this.datatable) {
                        this.datatable.destroy();
                    }

                    const headings = ['#', 'الصورة', 'الرابط', 'القياسات والكميات', 'السعر (يوان)', 'العدد الكلي', 'المجموع', 'الإجراءات'];

                    this.datatable = new simpleDatatables.DataTable('#invoiceTable', {
                        data: {
                            headings: headings,
                            data: data
                        },
                        searchable: true,
                        perPage: 10,
                        perPageSelect: [10, 20, 30, 50, 100],
                        firstLast: true,
                        firstText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                        lastText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M11 19L17 12L11 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> <path opacity="0.5" d="M6.99976 19L12.9998 12L6.99976 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                        prevText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                        nextText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                        labels: {
                            perPage: "{select}"
                        },
                        layout: {
                            top: "{search}",
                            bottom: "{info}{select}{pager}",
                        },
                    });
                }
            }));
        });

        let uploadedImages = [];
        let imageSlotIndex = 0;
        let invoiceItems = [];
        let savedInvoiceId = null;
        let currentProductId = null;
        let currentProductData = null;
        let customSizes = [];
        let selectedSizesForInvoice = {}; // {size: quantity}
        let isEditMode = {{ isset($invoice) && $invoice ? 'true' : 'false' }};
        let editingInvoiceId = {{ isset($invoice) && $invoice ? $invoice->id : 'null' }};

        // تحميل بيانات الفاتورة عند التعديل
        @if(isset($invoice) && $invoice)
        (function() {
            const invoiceData = @json($invoice->items);

            // انتظار تحميل DOM و productsData
            document.addEventListener('DOMContentLoaded', function() {
                if (invoiceData && invoiceData.length > 0) {
                    // البحث عن المنتجات في productsData للحصول على بيانات كاملة (بما في ذلك الصورة)
                    invoiceItems = invoiceData.map(item => {
                        // البحث عن المنتج في productsData أولاً (يحتوي على جميع البيانات بما في ذلك الصورة)
                        const productFromData = productsData.find(p => p.id === item.invoice_product_id);
                        // استخدام بيانات productsData إذا وُجدت، وإلا استخدام invoiceProduct كـ fallback
                        const product = productFromData || item.invoiceProduct || {};

                        // التأكد من أن image_url موجود
                        const imageUrl = product.image_url || '';

                        const productObj = {
                            id: item.invoice_product_id,
                            code: product.code || '',
                            image_url: imageUrl,
                            product_link: product.product_link || '',
                            price_yuan: parseFloat(product.price_yuan || 0)
                        };

                        return {
                            product_id: item.invoice_product_id,
                            product_code: product.code || '',
                            product_image: imageUrl,
                            product_link: product.product_link || '',
                            price_yuan: product.price_yuan || 0,
                            size: item.size || null,
                            quantity: item.quantity,
                            // إنشاء object product للتوافق مع renderInvoice
                            product: productObj
                        };
                    });
                    savedInvoiceId = {{ $invoice->id }};

                    const saveBtn = document.getElementById('saveInvoiceBtnText');
                    if (saveBtn) {
                        saveBtn.textContent = 'تحديث الفاتورة';
                    }
                    const invoiceSection = document.getElementById('invoiceSection');
                    if (invoiceSection) {
                        invoiceSection.style.display = 'block';
                    }

                    // انتظار تحميل جميع scripts
                    setTimeout(() => {
                        if (typeof renderInvoice === 'function') {
                            renderInvoice();
                        } else {
                            console.error('renderInvoice function not found, retrying...');
                            setTimeout(() => {
                                if (typeof renderInvoice === 'function') {
                                    renderInvoice();
                                }
                            }, 500);
                        }
                    }, 800);
                }
            });
        })();
        @endif

        // البحث والفلترة
        document.getElementById('searchBtn')?.addEventListener('click', function() {
            const code = document.getElementById('searchCode').value;
            const priceFrom = document.getElementById('priceFrom').value;
            const priceTo = document.getElementById('priceTo').value;

            const params = new URLSearchParams();
            if (code) params.append('code', code);
            if (priceFrom) params.append('price_from', priceFrom);
            if (priceTo) params.append('price_to', priceTo);

            // الحفاظ على private_warehouse_id في البحث
            const urlParams = new URLSearchParams(window.location.search);
            const privateWarehouseId = urlParams.get('private_warehouse_id');
            if (privateWarehouseId) {
                params.append('private_warehouse_id', privateWarehouseId);
            }

            window.location.href = '{{ route("admin.invoices.index") }}' + (params.toString() ? '?' + params.toString() : '');
        });

        document.getElementById('clearSearchBtn')?.addEventListener('click', function() {
            document.getElementById('searchCode').value = '';
            document.getElementById('priceFrom').value = '';
            document.getElementById('priceTo').value = '';

            // الحفاظ على private_warehouse_id عند مسح البحث
            const urlParams = new URLSearchParams(window.location.search);
            const privateWarehouseId = urlParams.get('private_warehouse_id');
            const url = '{{ route("admin.invoices.index") }}' + (privateWarehouseId ? '?private_warehouse_id=' + privateWarehouseId : '');
            window.location.href = url;
        });

        // البحث عند الضغط على Enter
        ['searchCode', 'priceFrom', 'priceTo'].forEach(id => {
            document.getElementById(id)?.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    document.getElementById('searchBtn').click();
                }
            });
        });

        // حفظ بيانات المنتجات للوصول إليها من addToInvoice
        const productsData = @json($products);

        // نظام رفع الصور (نفس النظام من صفحة إنشاء المنتج)
        let globalPasteHandlerAdded = false;

        // تهيئة نظام رفع الصور
        document.addEventListener('DOMContentLoaded', function() {
            addUploadSlot();

            // إضافة مستمع للصق العام (مرة واحدة فقط)
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
        });

        function addUploadSlot() {
            const slotId = `slot-${imageSlotIndex}`;
            const container = document.getElementById('uploadSlotsContainer');
            const slotHTML = `
                <div id="${slotId}" class="upload-slot border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 mb-4">
                    <div class="text-center">
                        <h6 class="font-semibold mb-3">إضافة صورة</h6>
                        <div class="paste-zone-${slotId} border-2 border-dashed border-gray-200 dark:border-gray-600 rounded-lg p-8 mb-3 cursor-pointer hover:border-primary hover:bg-gray-50 dark:hover:bg-gray-800 transition-all">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-sm text-gray-600 dark:text-gray-400">انقر أو الصق صورة (Ctrl+V)</p>
                        </div>
                        <button type="button" class="paste-btn-${slotId} btn btn-outline-primary btn-sm mb-3">لصق من الحافظة</button>
                        <input type="url" class="url-input-${slotId} form-input mb-3" placeholder="أو الصق رابط الصورة">
                        <button type="button" class="file-btn-${slotId} btn btn-outline-secondary btn-sm">اختر ملف</button>
                        <input type="file" class="file-input-${slotId}" style="display:none;" accept="image/*">
                    </div>
                </div>
            `;
            container.innerHTML = slotHTML;

            // استخدام setTimeout لضمان أن العناصر موجودة في DOM
            setTimeout(() => {
                initializeSlotEvents(slotId);
            }, 50);

            imageSlotIndex++;
        }

        function initializeSlotEvents(slotId) {
            const pasteZone = document.querySelector(`.paste-zone-${slotId}`);
            const pasteBtn = document.querySelector(`.paste-btn-${slotId}`);
            const urlInput = document.querySelector(`.url-input-${slotId}`);
            const fileBtn = document.querySelector(`.file-btn-${slotId}`);
            const fileInput = document.querySelector(`.file-input-${slotId}`);

            if (!pasteZone || !pasteBtn || !urlInput || !fileBtn || !fileInput) {
                console.error('لم يتم العثور على عناصر رفع الصور');
                return;
            }

            pasteBtn.addEventListener('click', async function() {
                try {
                    if (!navigator.clipboard || !navigator.clipboard.read) {
                        alert('هذا المتصفح لا يدعم لصق الصور من الحافظة مباشرة. الرجاء استخدام Ctrl+V');
                        return;
                    }
                    const clipboardItems = await navigator.clipboard.read();
                    for (const clipboardItem of clipboardItems) {
                        for (const type of clipboardItem.types) {
                            if (type.startsWith('image/')) {
                                const blob = await clipboardItem.getType(type);
                                const file = new File([blob], `pasted-image-${Date.now()}.${type.split('/')[1]}`, { type: type });
                                addImage(file, slotId);
                                return;
                            }
                        }
                    }
                    alert('لا توجد صورة في الحافظة.');
                } catch (error) {
                    console.error('خطأ في لصق الصورة:', error);
                    alert('حدث خطأ أثناء محاولة لصق الصورة. يرجى المحاولة باستخدام Ctrl+V بدلاً من ذلك.');
                }
            });

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

            urlInput.addEventListener('change', function() {
                if (this.value) {
                    addImage(this.value, slotId, 'url');
                }
            });

            urlInput.addEventListener('paste', function(e) {
                setTimeout(() => {
                    if (this.value) {
                        addImage(this.value, slotId, 'url');
                    }
                }, 10);
            });

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

            document.getElementById(slotId).remove();
            addUploadSlot();
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
                `;
            }

            container.appendChild(imageCard);
        }

        window.removeImage = function(imageId) {
            uploadedImages = uploadedImages.filter(img => img.id !== imageId);
            const imageElement = document.getElementById(`image-${imageId}`);
            if (imageElement) {
                imageElement.remove();
            }
        };

        // حفظ منتج
        document.getElementById('productForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            if (uploadedImages.length === 0) {
                alert('يرجى إضافة صورة للمنتج');
                return;
            }

            const selectedSizes = Array.from(document.querySelectorAll('input[name="sizes[]"]:checked')).map(cb => cb.value);
            const allSizes = [...selectedSizes, ...customSizes];

            if (allSizes.length === 0) {
                alert('يرجى اختيار قياس واحد على الأقل أو إضافة قياس مخصص');
                return;
            }

            // ضغط الصورة قبل إرسالها
            const imageUrl = uploadedImages[0].type === 'file'
                ? await getImageDataUrl(uploadedImages[0].source)
                : uploadedImages[0].source;

            // الحصول على private_warehouse_id من URL إذا كان موجود
            const urlParams = new URLSearchParams(window.location.search);
            const privateWarehouseId = urlParams.get('private_warehouse_id');

            const formData = {
                image_url: imageUrl,
                product_link: document.getElementById('productLink').value,
                price_yuan: document.getElementById('priceYuan').value,
                available_sizes: allSizes,
            };

            // إضافة private_warehouse_id إذا كان موجود (للمدير فقط)
            if (privateWarehouseId) {
                formData.private_warehouse_id = privateWarehouseId;
            }

            try {
                const response = await fetch('{{ route("admin.invoices.products.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(formData)
                });

                // التحقق من نوع الاستجابة
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    alert('حدث خطأ في الخادم. يرجى التحقق من البيانات وإعادة المحاولة.');
                    return;
                }

                // التحقق من نجاح الطلب
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'حدث خطأ غير معروف' }));
                    let errorMessage = 'حدث خطأ أثناء حفظ المنتج';

                    if (response.status === 422 && errorData.errors) {
                        const validationErrors = Object.values(errorData.errors).flat().join('\n');
                        errorMessage = 'خطأ في البيانات:\n' + validationErrors;
                    } else if (errorData.message) {
                        errorMessage = errorData.message;
                    }

                    alert(errorMessage);
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حفظ المنتج: ' + (data.message || ''));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء حفظ المنتج: ' + (error.message || ''));
            }
        });

        // ضغط الصورة قبل base64
        function compressImage(file, maxWidth = 800, maxHeight = 800, quality = 0.8) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => {
                        const canvas = document.createElement('canvas');
                        let width = img.width;
                        let height = img.height;

                        // حساب الأبعاد الجديدة مع الحفاظ على النسبة
                        if (width > height) {
                            if (width > maxWidth) {
                                height = (height * maxWidth) / width;
                                width = maxWidth;
                            }
                        } else {
                            if (height > maxHeight) {
                                width = (width * maxHeight) / height;
                                height = maxHeight;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;

                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, width, height);

                        // تحويل إلى base64 مع ضغط الجودة
                        const compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
                        resolve(compressedDataUrl);
                    };
                    img.onerror = () => {
                        // في حالة الفشل، إرجاع الصورة الأصلية
                        resolve(e.target.result);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            });
        }

        // ضغط الصورة من URL
        function compressImageFromUrl(url, maxWidth = 800, maxHeight = 800, quality = 0.8) {
            return new Promise((resolve) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;

                    // حساب الأبعاد الجديدة مع الحفاظ على النسبة
                    if (width > height) {
                        if (width > maxWidth) {
                            height = (height * maxWidth) / width;
                            width = maxWidth;
                        }
                    } else {
                        if (height > maxHeight) {
                            width = (width * maxHeight) / height;
                            height = maxHeight;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // تحويل إلى base64 مع ضغط الجودة
                    const compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
                    resolve(compressedDataUrl);
                };
                img.onerror = () => {
                    // في حالة الفشل (مثلاً CORS)، أعد URL كما هو
                    resolve(url);
                };
                img.src = url;
            });
        }

        function getImageDataUrl(file) {
            // إذا كانت الصورة من نوع file، قم بضغطها أولاً
            if (file instanceof File) {
                return compressImage(file);
            }
            // إذا كانت URL، حاول ضغطها (قد تفشل بسبب CORS)
            if (typeof file === 'string' && (file.startsWith('http://') || file.startsWith('https://'))) {
                return compressImageFromUrl(file);
            }
            // إذا كانت base64 أو أي شيء آخر، أعدها كما هي
            return Promise.resolve(file);
        }

        // إدارة القياسات المخصصة
        document.getElementById('addCustomSizeBtn').addEventListener('click', function() {
            const input = document.getElementById('customSizeInput');
            const size = input.value.trim();

            if (size && !customSizes.includes(size)) {
                customSizes.push(size);
                updateCustomSizesDisplay();
                input.value = '';
            }
        });

        document.getElementById('customSizeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('addCustomSizeBtn').click();
            }
        });

        function updateCustomSizesDisplay() {
            const container = document.getElementById('customSizesContainer');
            container.innerHTML = customSizes.map((size, index) => `
                <span class="badge bg-primary flex items-center gap-1">
                    ${size}
                    <button type="button" onclick="removeCustomSize(${index})" class="text-white hover:text-red-300">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </span>
            `).join('');
        }

        window.removeCustomSize = function(index) {
            customSizes.splice(index, 1);
            updateCustomSizesDisplay();
        };

        // إضافة منتج للفاتورة
        window.addToInvoice = function(productId) {
            currentProductId = productId;
            selectedSizesForInvoice = {};

            // البحث عن المنتج في productsData
            currentProductData = productsData.find(p => p.id === productId);
            if (!currentProductData) {
                alert('لم يتم العثور على المنتج');
                return;
            }

            // عرض معلومات المنتج
            const productImageContainer = document.getElementById('productImageContainer');
            productImageContainer.innerHTML = `<img src="${currentProductData.image_url}" alt="صورة المنتج" class="w-32 h-32 object-cover rounded">`;

            const productLinkContainer = document.getElementById('productLinkContainer');
            productLinkContainer.innerHTML = `<a href="${currentProductData.product_link}" target="_blank" class="text-primary break-all">${currentProductData.product_link}</a>`;

            // عرض القياسات المتوفرة
            const sizesContainer = document.getElementById('sizesContainer');
            if (!currentProductData.available_sizes || currentProductData.available_sizes.length === 0) {
                sizesContainer.innerHTML = '<p class="text-danger">لا توجد قياسات متوفرة لهذا المنتج</p>';
            } else {
                sizesContainer.innerHTML = `
                    <h4 class="text-sm font-semibold mb-2">القياسات المتوفرة:</h4>
                    <div class="grid grid-cols-3 gap-2">
                        ${currentProductData.available_sizes.map(size => `
                            <label class="flex items-center p-2 border rounded cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700">
                                <input type="checkbox" class="form-checkbox size-checkbox" value="${size}" onchange="toggleSize('${size}')">
                                <span class="mr-2">${size}</span>
                            </label>
                        `).join('')}
                    </div>
                `;
            }

            updateSelectedSizesDisplay();

            // فتح modal باستخدام Alpine.js
            const modalElement = document.querySelector('[x-data="addToInvoiceModal"]');
            if (modalElement && modalElement._x_dataStack && modalElement._x_dataStack[0]) {
                modalElement._x_dataStack[0].open = true;
            }
        };

        window.toggleSize = function(size) {
            const checkbox = document.querySelector(`.size-checkbox[value="${size}"]`);
            if (checkbox.checked) {
                // إضافة القياس مع كمية افتراضية 1
                selectedSizesForInvoice[size] = 1;
            } else {
                // إزالة القياس
                delete selectedSizesForInvoice[size];
            }
            updateSelectedSizesDisplay();
        };

        function updateSelectedSizesDisplay() {
            const container = document.getElementById('selectedSizesList');
            const selectedContainer = document.getElementById('selectedSizesContainer');

            if (Object.keys(selectedSizesForInvoice).length === 0) {
                selectedContainer.style.display = 'none';
                return;
            }

            selectedContainer.style.display = 'block';
            container.innerHTML = Object.keys(selectedSizesForInvoice).map(size => `
                <div class="flex items-center gap-2 p-2 bg-gray-100 dark:bg-gray-700 rounded">
                    <span class="font-semibold w-16">${size}:</span>
                    <div class="flex items-center gap-1">
                        <button type="button"
                                onclick="decreaseQuantity('${size}')"
                                class="btn btn-outline-primary btn-sm p-1 w-8 h-8 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </button>
                        <input type="number"
                               id="quantity-${size}"
                               class="form-input w-20 text-center"
                               min="1"
                               value="${selectedSizesForInvoice[size]}"
                               onchange="updateSizeQuantity('${size}', this.value)">
                        <button type="button"
                                onclick="increaseQuantity('${size}')"
                                class="btn btn-outline-primary btn-sm p-1 w-8 h-8 flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                    <button type="button"
                            onclick="removeSize('${size}')"
                            class="btn btn-outline-danger btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `).join('');
        }

        window.updateSizeQuantity = function(size, quantity) {
            const qty = parseInt(quantity);
            if (qty < 1) {
                alert('العدد يجب أن يكون أكبر من صفر');
                selectedSizesForInvoice[size] = 1;
                updateSelectedSizesDisplay();
                return;
            }
            selectedSizesForInvoice[size] = qty;
        };

        window.increaseQuantity = function(size) {
            const currentQty = selectedSizesForInvoice[size] || 1;
            selectedSizesForInvoice[size] = currentQty + 1;
            updateSelectedSizesDisplay();
        };

        window.decreaseQuantity = function(size) {
            const currentQty = selectedSizesForInvoice[size] || 1;
            if (currentQty > 1) {
                selectedSizesForInvoice[size] = currentQty - 1;
                updateSelectedSizesDisplay();
            }
        };

        window.removeSize = function(size) {
            delete selectedSizesForInvoice[size];
            const checkbox = document.querySelector(`.size-checkbox[value="${size}"]`);
            if (checkbox) {
                checkbox.checked = false;
            }
            updateSelectedSizesDisplay();
        };

        document.getElementById('confirmQuantityBtn').addEventListener('click', function() {
            if (Object.keys(selectedSizesForInvoice).length === 0) {
                alert('يرجى اختيار قياس واحد على الأقل');
                return;
            }

            const productId = parseInt(currentProductId);

            // إضافة كل قياس كعنصر منفصل في الفاتورة
            Object.keys(selectedSizesForInvoice).forEach(size => {
                const quantity = selectedSizesForInvoice[size];

                // البحث عن عنصر موجود بنفس المنتج والقياس
                const existingItemIndex = invoiceItems.findIndex(item =>
                    item.product_id === productId && item.size === size
                );

                if (existingItemIndex !== -1) {
                    // تحديث الكمية للعنصر الموجود
                    invoiceItems[existingItemIndex].quantity += quantity;
                } else {
                    // إضافة عنصر جديد
                    invoiceItems.push({
                        product_id: productId,
                        size: size,
                        quantity: quantity,
                        product: currentProductData
                    });
                }
            });

            // إغلاق modal
            const modalElement = document.querySelector('[x-data="addToInvoiceModal"]');
            if (modalElement && modalElement._x_dataStack && modalElement._x_dataStack[0]) {
                modalElement._x_dataStack[0].open = false;
            }

            renderInvoice();
        });

        // تعديل منتج
        let editCustomSizes = [];
        let editingProductId = null;

        window.editProduct = function(productId) {
            editingProductId = productId;
            editCustomSizes = [];

            const product = productsData.find(p => p.id === productId);
            if (!product) {
                alert('لم يتم العثور على المنتج');
                return;
            }

            // ملء الحقول
            document.getElementById('editProductImage').src = product.image_url;
            document.getElementById('editProductImageUrl').value = product.image_url;
            document.getElementById('editProductLink').value = product.product_link;
            document.getElementById('editProductPrice').value = product.price_yuan;

            // تفريغ جميع checkboxes
            document.querySelectorAll('.edit-size-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('editCustomSizesContainer').innerHTML = '';

            // تحديد القياسات الموجودة
            if (product.available_sizes && Array.isArray(product.available_sizes)) {
                product.available_sizes.forEach(size => {
                    const checkbox = document.querySelector(`.edit-size-checkbox[value="${size}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    } else {
                        // إذا كان قياس مخصص
                        editCustomSizes.push(size);
                    }
                });
            }

            updateEditCustomSizesDisplay();

            // فتح modal
            const modalElement = document.querySelector('[x-data="editProductModal"]');
            if (modalElement && modalElement._x_dataStack && modalElement._x_dataStack[0]) {
                modalElement._x_dataStack[0].open = true;
            }
        };

        // إدارة القياسات المخصصة في modal التعديل
        document.getElementById('addEditCustomSizeBtn').addEventListener('click', function() {
            const input = document.getElementById('editCustomSizeInput');
            const size = input.value.trim();

            if (size && !editCustomSizes.includes(size)) {
                editCustomSizes.push(size);
                updateEditCustomSizesDisplay();
                input.value = '';
            }
        });

        document.getElementById('editCustomSizeInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('addEditCustomSizeBtn').click();
            }
        });

        function updateEditCustomSizesDisplay() {
            const container = document.getElementById('editCustomSizesContainer');
            container.innerHTML = editCustomSizes.map((size, index) => `
                <span class="badge bg-primary flex items-center gap-1">
                    ${size}
                    <button type="button" onclick="removeEditCustomSize(${index})" class="text-white hover:text-red-300">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </span>
            `).join('');
        }

        window.removeEditCustomSize = function(index) {
            editCustomSizes.splice(index, 1);
            updateEditCustomSizesDisplay();
        };

        // حفظ التعديلات
        document.getElementById('editProductForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const selectedSizes = Array.from(document.querySelectorAll('.edit-size-checkbox:checked')).map(cb => cb.value);
            const allSizes = [...selectedSizes, ...editCustomSizes];

            if (allSizes.length === 0) {
                alert('يرجى اختيار قياس واحد على الأقل أو إضافة قياس مخصص');
                return;
            }

            let imageUrl = document.getElementById('editProductImageUrl').value.trim();

            // إذا كانت الصورة من URL، حاول ضغطها
            if (imageUrl && (imageUrl.startsWith('http://') || imageUrl.startsWith('https://'))) {
                try {
                    imageUrl = await compressImageFromUrl(imageUrl);
                } catch (error) {
                    console.error('خطأ في ضغط الصورة:', error);
                }
            }

            const formData = {
                image_url: imageUrl || document.getElementById('editProductImage').src,
                product_link: document.getElementById('editProductLink').value,
                price_yuan: document.getElementById('editProductPrice').value,
                available_sizes: allSizes,
            };

            try {
                const response = await fetch(`{{ route("admin.invoices.products.update", ":id") }}`.replace(':id', editingProductId), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(formData)
                });

                // التحقق من نوع الاستجابة
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    alert('حدث خطأ في الخادم. يرجى التحقق من البيانات وإعادة المحاولة.');
                    return;
                }

                // التحقق من نجاح الطلب
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({ message: 'حدث خطأ غير معروف' }));
                    let errorMessage = 'حدث خطأ أثناء تحديث المنتج';

                    if (response.status === 422 && errorData.errors) {
                        const validationErrors = Object.values(errorData.errors).flat().join('\n');
                        errorMessage = 'خطأ في البيانات:\n' + validationErrors;
                    } else if (errorData.message) {
                        errorMessage = errorData.message;
                    }

                    alert(errorMessage);
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    // إغلاق modal
                    const modalElement = document.querySelector('[x-data="editProductModal"]');
                    if (modalElement && modalElement._x_dataStack && modalElement._x_dataStack[0]) {
                        modalElement._x_dataStack[0].open = false;
                    }
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء تحديث المنتج');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء تحديث المنتج');
            }
        });

        // دالة لإزالة الأصفار الزائدة من الأرقام
        function formatNumber(num) {
            const parsed = parseFloat(num);
            // إذا كان الرقم عدد صحيح، أرجعه بدون فاصلة عشرية
            if (parsed % 1 === 0) {
                return parsed.toString();
            }
            // إذا كان به أصفار زائدة في النهاية، أزيلها
            return parsed.toString().replace(/\.0+$/, '');
        }

        // عرض الفاتورة
        function renderInvoice() {
            const invoiceSection = document.getElementById('invoiceSection');

            if (invoiceItems.length === 0) {
                invoiceSection.style.display = 'none';
                return;
            }

            invoiceSection.style.display = 'block';

            // تجميع العناصر حسب المنتج
            const groupedItems = {};
            invoiceItems.forEach((item, index) => {
                const productId = item.product_id;
                if (!groupedItems[productId]) {
                    // التأكد من وجود product object
                    const product = item.product || {
                        id: item.product_id,
                        code: item.product_code || '',
                        image_url: item.product_image || '',
                        product_link: item.product_link || '',
                        price_yuan: parseFloat(item.price_yuan || 0)
                    };
                    groupedItems[productId] = {
                        product: product,
                        items: [],
                        indices: []
                    };
                }
                groupedItems[productId].items.push(item);
                groupedItems[productId].indices.push(index);
            });

            let total = 0;
            let rowIndex = 0;
            const tableData = Object.keys(groupedItems).map(productId => {
                const group = groupedItems[productId];
                const product = group.product;

                // حساب مجموع الكميات للمنتج
                let totalQuantity = 0;
                const sizesCount = group.items.length;
                const sizesHtml = group.items.map((item, idx) => {
                    const originalIndex = group.indices[idx];
                    totalQuantity += item.quantity;
                    return `
                        <div class="flex items-center gap-1 mb-2">
                            <span class="badge bg-primary min-w-[3rem] text-center">${item.size || 'N/A'}</span>
                            <span>:</span>
                            <div class="flex items-center gap-1">
                                <button type="button"
                                        onclick="decreaseInvoiceQuantity(${originalIndex})"
                                        class="btn btn-outline-primary btn-sm p-1 w-7 h-7 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                </button>
                                <input type="number"
                                       class="form-input w-14 text-center"
                                       value="${item.quantity}"
                                       min="1"
                                       onchange="updateQuantity(${originalIndex}, this.value)">
                                <button type="button"
                                        onclick="increaseInvoiceQuantity(${originalIndex})"
                                        class="btn btn-outline-primary btn-sm p-1 w-7 h-7 flex items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </button>
                            </div>
                            <button type="button"
                                    onclick="removeSizeFromInvoice(${originalIndex})"
                                    class="btn btn-outline-danger btn-sm p-1 w-7 h-7 flex items-center justify-center">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }).join('');

                // حساب المجموع الكلي للمنتج
                const productTotal = parseFloat(product.price_yuan) * totalQuantity;
                total += productTotal;

                // حساب ارتفاع الصورة ديناميكياً حسب عدد القياسات (لكن بشكل معقول)
                // كل قياس يحتاج حوالي 45px، والصورة يجب أن تكبر لتظهر كل محتواها
                const sizesHeight = sizesCount * 45; // ارتفاع القياسات
                const imageHeight = Math.max(150, Math.min(sizesHeight, 400)); // بين 150px و 400px

                rowIndex++;
                // التأكد من أن الصورة موجودة
                const productImage = (product && product.image_url) ? product.image_url : '/assets/images/no-image.png';
                const productLink = (product && product.product_link) ? product.product_link : '#';
                const productPrice = (product && product.price_yuan) ? product.price_yuan : 0;

                return [
                    rowIndex,
                    `<img src="${productImage}" alt="صورة المنتج" class="object-contain rounded border" style="width: 150px; height: ${imageHeight}px; min-height: 150px; background: #f5f5f5;" onerror="this.src='/assets/images/no-image.png'; this.onerror=null;">`,
                    productLink !== '#' ? `<a href="${productLink}" target="_blank" class="btn btn-outline-primary btn-sm">
                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        الرابط
                    </a>` : '<span class="text-gray-400">-</span>',
                    `<div class="space-y-1">${sizesHtml}</div>`,
                    `${formatNumber(productPrice)} ¥`,
                    `${totalQuantity}`,
                    `${formatNumber(productTotal.toFixed(2))} ¥`,
                    `<button onclick="removeProductFromInvoice(${productId})" class="btn btn-outline-danger btn-sm">حذف المنتج</button>`
                ];
            });

            // إضافة صف المجموع الكلي
            tableData.push([
                '',
                '',
                '',
                '',
                '',
                '<strong>المجموع الكلي:</strong>',
                `<strong>${formatNumber(total.toFixed(2))} ¥</strong>`,
                ''
            ]);

            // تحديث الجدول باستخدام Alpine.js
            setTimeout(() => {
                const invoiceSectionEl = document.querySelector('#invoiceSection');
                if (invoiceSectionEl && invoiceSectionEl._x_dataStack && invoiceSectionEl._x_dataStack[0]) {
                    const invoiceTableComponent = invoiceSectionEl._x_dataStack[0];
                    if (invoiceTableComponent && invoiceTableComponent.updateTable) {
                        invoiceTableComponent.updateTable(tableData);
                    }
                } else {
                    // إذا لم يكن Alpine.js جاهزاً، استخدم طريقة مباشرة
                    const invoiceTableEl = document.getElementById('invoiceTable');
                    if (invoiceTableEl) {
                        // إنشاء جدول مباشرة
                        if (window.invoiceDatatable) {
                            window.invoiceDatatable.destroy();
                        }
                        window.invoiceDatatable = new simpleDatatables.DataTable('#invoiceTable', {
                            data: {
                                headings: ['#', 'الصورة', 'الرابط', 'القياسات والكميات', 'السعر (يوان)', 'العدد الكلي', 'المجموع', 'الإجراءات'],
                                data: tableData
                            },
                            searchable: true,
                            perPage: 10,
                            perPageSelect: [10, 20, 30, 50, 100],
                            firstLast: true,
                            firstText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M13 19L7 12L13 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> <path opacity="0.5" d="M16.9998 19L10.9998 12L16.9998 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                            lastText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M11 19L17 12L11 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> <path opacity="0.5" d="M6.99976 19L12.9998 12L6.99976 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                            prevText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                            nextText: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-4.5 h-4.5 rtl:rotate-180"> <path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/> </svg>',
                            labels: {
                                perPage: "{select}"
                            },
                            layout: {
                                top: "{search}",
                                bottom: "{info}{select}{pager}",
                            },
                        });
                    }
                }
            }, 100);
        }

        window.updateQuantity = function(index, quantity) {
            const qty = parseInt(quantity);
            if (qty < 1) {
                alert('العدد يجب أن يكون أكبر من صفر');
                invoiceItems[index].quantity = 1;
                renderInvoice();
                return;
            }
            invoiceItems[index].quantity = qty;
            renderInvoice();
        };

        window.removeFromInvoice = function(index) {
            invoiceItems.splice(index, 1);
            renderInvoice();
        };

        window.removeProductFromInvoice = function(productId) {
            if (confirm('هل أنت متأكد من حذف هذا المنتج بجميع قياساته من الفاتورة؟')) {
                invoiceItems = invoiceItems.filter(item => item.product_id !== productId);
                renderInvoice();
            }
        };

        window.removeSizeFromInvoice = function(index) {
            if (confirm('هل أنت متأكد من حذف هذا القياس من الفاتورة؟')) {
                invoiceItems.splice(index, 1);
                renderInvoice();
            }
        };

        window.increaseInvoiceQuantity = function(index) {
            const currentQty = invoiceItems[index].quantity || 1;
            invoiceItems[index].quantity = currentQty + 1;
            renderInvoice();
        };

        window.decreaseInvoiceQuantity = function(index) {
            const currentQty = invoiceItems[index].quantity || 1;
            if (currentQty > 1) {
                invoiceItems[index].quantity = currentQty - 1;
                renderInvoice();
            }
        };

        // حفظ الفاتورة
        document.getElementById('saveInvoiceBtn').addEventListener('click', async function() {
            // Validation على الجانب العميل قبل الإرسال
            if (invoiceItems.length === 0) {
                alert('الفاتورة فارغة. يرجى إضافة منتجات أولاً.');
                return;
            }

            const items = invoiceItems.map((item, index) => {
                const productId = parseInt(item.product_id);
                const quantity = parseInt(item.quantity);

                // التحقق من صحة البيانات
                if (isNaN(productId) || productId <= 0) {
                    throw new Error(`المنتج في العنصر #${index + 1} غير صحيح`);
                }
                if (isNaN(quantity) || quantity <= 0) {
                    throw new Error(`الكمية في العنصر #${index + 1} غير صحيحة`);
                }

                return {
                    product_id: productId,
                    size: item.size || null,
                    quantity: quantity
                };
            });

            // الحصول على private_warehouse_id من URL إذا كان موجود
            const urlParams = new URLSearchParams(window.location.search);
            const privateWarehouseId = urlParams.get('private_warehouse_id');

            const requestData = { items };
            if (privateWarehouseId) {
                const warehouseId = parseInt(privateWarehouseId);
                if (!isNaN(warehouseId) && warehouseId > 0) {
                    requestData.private_warehouse_id = warehouseId;
                }
            }

            try {
                // تحديد route و method حسب وضع التعديل
                let url, method;
                if (isEditMode && editingInvoiceId) {
                    url = '{{ route("admin.invoices.update", ":id") }}'.replace(':id', editingInvoiceId);
                    method = 'PUT';
                } else {
                    url = '{{ route("admin.invoices.save") }}';
                    method = 'POST';
                }

                // Laravel يتطلب POST مع _method=PUT للـ PUT requests
                let response;
                if (method === 'PUT') {
                    // استخدام form data للـ PUT requests
                    const formData = new FormData();
                    formData.append('_method', 'PUT');
                    formData.append('items', JSON.stringify(requestData.items));
                    if (requestData.private_warehouse_id) {
                        formData.append('private_warehouse_id', requestData.private_warehouse_id);
                    }

                    response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                } else {
                    response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(requestData)
                    });
                }

                // التحقق من نوع الاستجابة
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // الاستجابة ليست JSON، قراءة النص
                    const text = await response.text();
                    console.error('Non-JSON response:', text);
                    console.error('Response status:', response.status);
                    console.error('Response headers:', response.headers);
                    alert('حدث خطأ في الخادم. يرجى التحقق من البيانات وإعادة المحاولة.\n\nكود الخطأ: ' + response.status);
                    return;
                }

                // التحقق من نجاح الطلب
                if (!response.ok) {
                    let errorData;
                    try {
                        errorData = await response.json();
                    } catch (e) {
                        console.error('Error parsing JSON response:', e);
                        errorData = { message: 'حدث خطأ غير معروف' };
                    }

                    let errorMessage = isEditMode ? 'حدث خطأ أثناء تحديث الفاتورة' : 'حدث خطأ أثناء حفظ الفاتورة';

                    if (response.status === 422 && errorData.errors) {
                        // خطأ في الـ validation
                        const validationErrors = Object.values(errorData.errors).flat().join('\n');
                        errorMessage = 'خطأ في البيانات:\n' + validationErrors;
                    } else if (errorData.message) {
                        errorMessage = errorData.message;
                    } else if (errorData.error) {
                        errorMessage = errorData.error;
                    }

                    console.error('Invoice save/update error:', {
                        status: response.status,
                        errorData: errorData,
                        requestData: requestData
                    });

                    alert(errorMessage);
                    return;
                }

                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    console.error('Error parsing success response:', e);
                    alert('حدث خطأ أثناء معالجة الاستجابة من الخادم');
                    return;
                }

                if (data.success) {
                    savedInvoiceId = data.invoice.id;
                    alert(isEditMode ? 'تم تحديث الفاتورة بنجاح' : 'تم حفظ الفاتورة بنجاح');
                    if (!isEditMode) {
                        invoiceItems = [];
                        renderInvoice();
                    } else {
                        // عند التعديل، إعادة تحميل الصفحة للعودة لقائمة الفواتير
                        window.location.href = '{{ route("admin.invoices.my-invoices") }}';
                    }
                } else {
                    const errorMsg = data.message || 'حدث خطأ غير معروف';
                    console.error('Invoice save/update failed:', data);
                    alert((isEditMode ? 'حدث خطأ أثناء تحديث الفاتورة: ' : 'حدث خطأ أثناء حفظ الفاتورة: ') + errorMsg);
                }
            } catch (error) {
                console.error('Invoice save/update exception:', {
                    error: error,
                    message: error.message,
                    stack: error.stack,
                    requestData: requestData
                });
                alert((isEditMode ? 'حدث خطأ أثناء تحديث الفاتورة: ' : 'حدث خطأ أثناء حفظ الفاتورة: ') + (error.message || 'حدث خطأ غير متوقع'));
            }
        });

        // تحميل PDF
        document.getElementById('printInvoiceBtn').addEventListener('click', async function() {
            if (invoiceItems.length === 0) {
                alert('الفاتورة فارغة');
                return;
            }

            // إظهار loading
            const btn = document.getElementById('printInvoiceBtn');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-spin">⏳</span> جاري التحميل...';

            try {
                let invoiceId = savedInvoiceId;

                // إذا لم تكن الفاتورة محفوظة، احفظها أولاً
                if (!invoiceId) {
                    const items = invoiceItems.map(item => ({
                        product_id: parseInt(item.product_id),
                        size: item.size || null,
                        quantity: parseInt(item.quantity)
                    }));

                    // الحصول على private_warehouse_id من URL إذا كان موجود
                    const urlParams = new URLSearchParams(window.location.search);
                    const privateWarehouseId = urlParams.get('private_warehouse_id');

                    const requestData = { items };
                    if (privateWarehouseId) {
                        requestData.private_warehouse_id = privateWarehouseId;
                    }

                    const response = await fetch('{{ route("admin.invoices.save") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(requestData)
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({ message: 'حدث خطأ غير معروف' }));
                        throw new Error(errorData.message || 'حدث خطأ أثناء حفظ الفاتورة');
                    }

                    const data = await response.json();
                    if (!data.success) {
                        throw new Error(data.message || 'حدث خطأ أثناء حفظ الفاتورة');
                    }

                    invoiceId = data.invoice.id;
                    savedInvoiceId = invoiceId;
                }

                // إعادة الزر لحالته الأصلية قبل التحميل
                btn.disabled = false;
                btn.innerHTML = originalText;

                // تحميل PDF من السيرفر
                window.location.href = `/admin/invoices/${invoiceId}/pdf`;
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ: ' + (error.message || ''));
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });

        // مسح الفاتورة
        document.getElementById('clearInvoiceBtn').addEventListener('click', function() {
            if (confirm('هل أنت متأكد من مسح الفاتورة؟')) {
                invoiceItems = [];
                renderInvoice();
            }
        });

        // حذف منتج
        window.deleteProduct = async function(productId) {
            if (!confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                return;
            }

            try {
                const response = await fetch(`/admin/invoices/products/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('حدث خطأ أثناء حذف المنتج');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ أثناء حذف المنتج');
            }
        };

        // CSS للطباعة
        const style = document.createElement('style');
        style.textContent = `
            @media print {
                body > *:not(#invoiceSection) {
                    display: none !important;
                }
                #invoiceSection {
                    display: block !important;
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    background: white !important;
                }
                #invoiceSection > div:first-child {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</x-layout.admin>
