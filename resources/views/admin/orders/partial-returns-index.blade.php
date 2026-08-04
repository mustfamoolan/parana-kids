<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إرجاع جزئي - الطلبات المقيدة</h5>
        </div>

        <!-- فلتر وبحث -->
        <div class="mb-5">
            <form method="GET" action="{{ route('admin.orders.partial-returns.index') }}" id="partialReturnsFilterForm" class="space-y-4">
                <!-- قسم اختيار المخازن (مربعات اختيار فوق البحث) -->
                @if(isset($warehouses) && count($warehouses) > 0)
                <div class="panel bg-gray-50 dark:bg-[#0e1726] p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                        <span class="text-sm font-bold text-black dark:text-white-light flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            المخازن المصرح بها (اختر مخزن واحد أو أكثر):
                        </span>
                        <div class="flex items-center gap-3 text-xs">
                            <button type="button" onclick="selectAllWarehousesPartial(true)" class="text-primary dark:text-primary-light hover:underline font-semibold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                تحديد الكل
                            </button>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <button type="button" onclick="selectAllWarehousesPartial(false)" class="text-danger dark:text-danger-light hover:underline font-semibold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                إلغاء التحديد
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3" id="warehouseCheckboxesContainerPartial">
                        @php
                            $hasWarehouseIdsFilter = request()->has('warehouse_ids');
                            $selectedWarehouseIds = $hasWarehouseIdsFilter ? (array) request('warehouse_ids', []) : [];
                        @endphp
                        @foreach($warehouses as $warehouse)
                            @php
                                $isChecked = !$hasWarehouseIdsFilter || in_array($warehouse->id, $selectedWarehouseIds);
                            @endphp
                            <label class="flex items-center gap-2 cursor-pointer bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 px-3 py-1.5 rounded-md hover:border-primary transition-colors">
                                <input
                                    type="checkbox"
                                    name="warehouse_ids[]"
                                    value="{{ $warehouse->id }}"
                                    class="form-checkbox text-primary warehouse-checkbox-partial"
                                    {{ $isChecked ? 'checked' : '' }}
                                >
                                <span class="text-sm font-medium text-black dark:text-white-light">{{ $warehouse->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- زر الباركود المربع فوق البحث وتحت الجيك بوكس -->
                <div class="flex items-center justify-center py-3">
                    <button
                        type="button"
                        onclick="openBarcodeScannerModal()"
                        class="w-28 h-28 sm:w-36 sm:h-36 rounded-2xl bg-[#1a2744] hover:bg-[#243560] text-white shadow-xl hover:shadow-2xl flex flex-col items-center justify-center gap-2 transition-all duration-300 transform hover:scale-105 active:scale-95 border-4 border-[#1a2744] group"
                        title="انقر لمسح الباركود بالكاميرا"
                    >
                        <img
                            src="{{ asset('assets/images/qr.png') }}"
                            alt="مسح باركود"
                            class="w-16 h-16 sm:w-20 sm:h-20 object-contain group-hover:scale-110 transition-transform"
                        >
                        <span class="text-xs sm:text-sm font-bold tracking-wide">مسح باركود</span>
                    </button>
                </div>

                <!-- الصف الأول: البحث -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <input
                            type="text"
                            name="search"
                            id="partial-returns-search-input"
                            class="form-input ltr:pr-10 rtl:pl-10"
                            placeholder="ابحث برقم الطلب، اسم الزبون، رقم الهاتف، كود الوسيط، اسم المندوب، أو اسم المجهز..."
                            value="{{ request('search') }}"
                        >
                        @if(request('search'))
                            <button
                                type="button"
                                onclick="document.getElementById('partial-returns-search-input').value=''; this.closest('form').submit();"
                                class="absolute top-1/2 -translate-y-1/2 ltr:right-3 rtl:left-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                title="إلغاء البحث"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>

                <!-- الصف الثاني: المندوب والمجهز -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="sm:w-48">
                        @php
                            $orderCreators = \App\Models\User::whereIn('role', ['delegate', 'admin', 'supplier'])->orderBy('role')->orderBy('name')->get();
                        @endphp
                        <select name="delegate_id" class="form-select">
                            <option value="">كل المندوبين والمديرين والمجهزين</option>
                            @foreach($orderCreators as $creator)
                                <option value="{{ $creator->id }}" {{ request('delegate_id') == $creator->id ? 'selected' : '' }}>
                                    {{ $creator->name }} ({{ $creator->code }}) - {{ $creator->role === 'admin' ? 'مدير' : ($creator->role === 'supplier' ? 'مجهز' : 'مندوب') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <select name="confirmed_by" class="form-select">
                            <option value="">كل المجهزين والمديرين</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('confirmed_by') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }} ({{ $supplier->code }}) - {{ $supplier->role === 'admin' ? 'مدير' : 'مجهز' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:w-48">
                        <input
                            type="date"
                            name="date_from"
                            class="form-input"
                            placeholder="من تاريخ"
                            value="{{ request('date_from') }}"
                        >
                    </div>
                    <div class="sm:w-48">
                        <input
                            type="date"
                            name="date_to"
                            class="form-input"
                            placeholder="إلى تاريخ"
                            value="{{ request('date_to') }}"
                        >
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            بحث
                        </button>
                        @if(request()->hasAny(['search', 'date_from', 'date_to', 'delegate_id', 'confirmed_by', 'warehouse_ids']))
                            <a href="{{ route('admin.orders.partial-returns.index') }}" class="btn btn-outline-secondary">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                مسح
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- نتائج البحث -->
        @if(request()->hasAny(['search', 'date_from', 'date_to', 'delegate_id', 'confirmed_by', 'warehouse_ids']))
            <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                        عرض {{ $orders->total() }} طلب مقيد
                        @if(request('search'))
                            للبحث: "{{ request('search') }}"
                        @endif
                        @if(request('date_from') || request('date_to'))
                            -
                            @if(request('date_from') && request('date_to'))
                                من {{ request('date_from') }} إلى {{ request('date_to') }}
                            @elseif(request('date_from'))
                                من {{ request('date_from') }}
                            @elseif(request('date_to'))
                                حتى {{ request('date_to') }}
                            @endif
                        @endif
                    </span>
                </div>
            </div>
        @endif

        <!-- كروت الطلبات -->
        @if($orders->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($orders as $index => $order)
                    <div id="order-{{ $order->id }}" class="panel border-2 border-green-500 dark:border-green-600">
                        <!-- هيدر الكارت -->
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="text-lg font-bold text-primary dark:text-primary-light">
                                        رقم الطلب: {{ $order->order_number }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    #{{ $orders->firstItem() + $index }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-outline-success">مقيد</span>
                            </div>
                        </div>

                        <!-- معلومات الزبون -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">اسم الزبون</span>
                                <p class="font-medium">{{ $order->customer_name }}</p>
                            </div>
                        </div>

                        <!-- المندوب -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المندوب</span>
                                @if($order->delegate)
                                <p class="font-medium">{{ $order->delegate->name }}</p>
                                @else
                                    <p class="font-medium text-gray-400">-</p>
                                @endif
                                @if($order->delivery_code)
                                <div class="flex items-center gap-2 mt-1">
                                    <p class="text-sm text-gray-500">كود الوسيط: <span class="font-medium text-primary font-mono text-base">{{ $order->delivery_code }}</span></p>
                                    <button
                                        type="button"
                                        onclick="copyDeliveryCode('{{ $order->delivery_code }}', 'delivery')"
                                        class="btn btn-xs btn-outline-primary flex items-center gap-1"
                                        title="نسخ كود الوسيط"
                                    >
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        نسخ
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- المجهز -->
                        @if($order->confirmedBy)
                            <div class="mb-4">
                                <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">المجهز</span>
                                    <p class="font-medium">{{ $order->confirmedBy->name }}</p>
                                </div>
                            </div>
                        @endif

                        <!-- التاريخ -->
                        <div class="mb-4">
                            <div class="bg-gray-50 dark:bg-gray-800/50 p-3 rounded-lg">
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mb-1">التاريخ</span>
                                <p class="font-medium">{{ $order->created_at->format('Y-m-d') }}</p>
                                <p class="text-sm text-gray-500">{{ $order->created_at->format('H:i') }}</p>
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="flex gap-2 flex-wrap">
                            <a href="{{ route('admin.orders.partial-return', $order) }}" class="btn btn-sm btn-warning flex-1" title="إرجاع جزئي">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                </svg>
                                إرجاع جزئي
                            </a>
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-primary flex-1" title="عرض">
                                <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                عرض
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <x-pagination :items="$orders" />
        @else
            <div class="panel">
                <div class="flex flex-col items-center justify-center py-10">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">لا توجد طلبات مقيدة</h3>
                    <p class="text-gray-500 dark:text-gray-400">لم يتم العثور على أي طلبات مقيدة تطابق معايير البحث</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal الكاميرا والماسح الضوئي للباركود -->
    <div id="barcodeScannerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 backdrop-blur-sm p-4 overflow-y-auto">
        <div class="relative w-full max-w-lg bg-white dark:bg-[#0e1726] rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#1b2e4b]">
                <div class="flex items-center gap-2">
                    <div class="p-2 bg-primary/10 rounded-lg text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4M3 7V5a2 2 0 012-2h2m10 0h2a2 2 0 012 2v2m0 10v2a2 2 0 01-2 2h-2M7 21H5a2 2 0 01-2-2v-2"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-black dark:text-white">مسح كود الوسيط (الباركود)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">وجه الكاميرا نحو باركود ملصق الشحنة</p>
                    </div>
                </div>
                <button type="button" onclick="closeBarcodeScannerModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-white p-1 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-4 flex flex-col items-center justify-center">
                <!-- حالة الكاميرا -->
                <div id="scanner-status" class="w-full text-center mb-3 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 py-2 px-3 rounded-lg flex items-center justify-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
                    <span>جاري تشغيل الكاميرا...</span>
                </div>

                <!-- حاوية الفيديو والكاميرا -->
                <div id="reader-container" class="w-full relative bg-black rounded-xl overflow-hidden min-h-[280px] flex items-center justify-center border-2 border-primary shadow-inner">
                    <div id="reader" class="w-full"></div>
                </div>

                <!-- خيار الإدخال اليدوي احتياطاً -->
                <div class="w-full mt-4">
                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">أو أدخل كود الوسيط يدوياً:</label>
                    <div class="flex gap-2">
                        <input type="text" id="manual-barcode-input" class="form-input text-center font-mono text-lg tracking-wider" placeholder="مثال: W-10293">
                        <button type="button" onclick="submitManualBarcode()" class="btn btn-primary">بحث</button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#1b2e4b] flex justify-end gap-2">
                <button type="button" onclick="closeBarcodeScannerModal()" class="btn btn-secondary w-full sm:w-auto">
                    إغلاق الكاميرا
                </button>
            </div>
        </div>
    </div>

    <!-- مكتبة html5-qrcode لمسح الباركود عبر الكاميرا -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

    <script>
        // --- قسم إدارة فلاتر المخازن والـ LocalStorage ---
        function savePartialWarehouseCheckboxes() {
            const checkedCBs = document.querySelectorAll('.warehouse-checkbox-partial:checked');
            const ids = Array.from(checkedCBs).map(cb => cb.value);
            localStorage.setItem('selectedWarehouseIds_global', JSON.stringify(ids));
            localStorage.setItem('selectedWarehouseIds_admin_dashboard', JSON.stringify(ids));
            localStorage.setItem('selectedWarehouseIds_alwaseet_print_upload', JSON.stringify(ids));
        }

        function onPartialWarehouseChange() {
            savePartialWarehouseCheckboxes();
            const form = document.getElementById('partialReturnsFilterForm');
            if (form) {
                form.submit();
            }
        }

        function selectAllWarehousesPartial(select) {
            const checkboxes = document.querySelectorAll('.warehouse-checkbox-partial');
            checkboxes.forEach(cb => {
                cb.checked = select;
            });
            onPartialWarehouseChange();
        }

        // --- قسم الكاميرا والماسح الضوئي (Html5Qrcode) ---
        let html5QrCode = null;
        let isScanning = false;

        function openBarcodeScannerModal() {
            const modal = document.getElementById('barcodeScannerModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';

            startBarcodeScanner();
        }

        function closeBarcodeScannerModal() {
            stopBarcodeScanner();
            const modal = document.getElementById('barcodeScannerModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function startBarcodeScanner() {
            const statusEl = document.getElementById('scanner-status');
            statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-blue-500 animate-ping"></span> جاري فتح الكاميرا...`;

            if (html5QrCode) {
                html5QrCode.stop().then(() => {
                    initScannerInstance();
                }).catch(() => {
                    initScannerInstance();
                });
            } else {
                initScannerInstance();
            }
        }

        function initScannerInstance() {
            try {
                html5QrCode = new Html5Qrcode("reader");
                const config = {
                    fps: 15,
                    qrbox: { width: 280, height: 180 },
                    aspectRatio: 1.333333
                };

                // إعطاء الأولوية للكاميرا الخلفية (environment)
                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    onBarcodeScannedSuccess,
                    onBarcodeScannedError
                ).then(() => {
                    isScanning = true;
                    const statusEl = document.getElementById('scanner-status');
                    statusEl.className = "w-full text-center mb-3 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 py-2 px-3 rounded-lg flex items-center justify-center gap-2";
                    statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> الكاميرا تعمل الآن، وجه الباركود داخل الإطار`;
                }).catch(err => {
                    console.warn("فشل فتح الكاميرا الخلفية، تجربة الكاميرا الافتراضية...", err);
                    // تجربة البديل بأي كاميرا متوفرة
                    html5QrCode.start(
                        { facingMode: "user" },
                        config,
                        onBarcodeScannedSuccess,
                        onBarcodeScannedError
                    ).then(() => {
                        isScanning = true;
                        const statusEl = document.getElementById('scanner-status');
                        statusEl.className = "w-full text-center mb-3 text-sm font-medium text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 py-2 px-3 rounded-lg flex items-center justify-center gap-2";
                        statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> الكاميرا تعمل، وجه الباركود داخل الإطار`;
                    }).catch(e => {
                        console.error("فشل فتح أي كاميرا:", e);
                        const statusEl = document.getElementById('scanner-status');
                        statusEl.className = "w-full text-center mb-3 text-sm font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 py-2 px-3 rounded-lg flex items-center justify-center gap-2";
                        statusEl.innerHTML = `تعذر الوصول للكاميرا (تأكد من إعطاء الصلاحية في المتصفح أو ادخل الكود يدوياً أدناه)`;
                    });
                });
            } catch (e) {
                console.error("خطأ في تشغيل مكتبة الباركود:", e);
            }
        }

        function stopBarcodeScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().then(() => {
                    isScanning = false;
                }).catch(err => {
                    console.error("خطأ في إيقاف الكاميرا:", err);
                });
            }
        }

        // عند قراءة الباركود بنجاح
        function onBarcodeScannedSuccess(decodedText, decodedResult) {
            if (!decodedText) return;
            const code = decodedText.trim();

            // إصدار صوت تنبيه خفيف (Beep Sound)
            playBeepSound();

            // إيقاف الماسح وإغلاق النافذة
            closeBarcodeScannerModal();

            // وضع القيمة في مربع البحث وإرسال النموذج فورياً!
            executeSearchByCode(code);
        }

        function onBarcodeScannedError(errorMessage) {
            // تجاهل أخطاء عدم اكتشاف الباركود في الإطار أثناء المسح المستمر
        }

        function submitManualBarcode() {
            const manualInput = document.getElementById('manual-barcode-input');
            if (manualInput && manualInput.value.trim()) {
                const code = manualInput.value.trim();
                closeBarcodeScannerModal();
                executeSearchByCode(code);
            }
        }

        function executeSearchByCode(code) {
            const searchInput = document.getElementById('partial-returns-search-input');
            if (searchInput) {
                searchInput.value = code;
                const form = document.getElementById('partialReturnsFilterForm');
                if (form) {
                    form.submit();
                }
            }
        }

        // صوت النغمة التفاعلية عند القراءة الناجحة
        function playBeepSound() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // 880 Hz (A5)
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.15);
            } catch (e) {
                console.log('Audio playback error:', e);
            }
        }

        // --- تهيئة الصفحة والـ Local Storage ---
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasUrlParams = urlParams.has('warehouse_ids[]') || urlParams.has('warehouse_ids') || urlParams.has('warehouse_id') || urlParams.has('search') || urlParams.has('confirmed_by') ||
                                urlParams.has('delegate_id') || urlParams.has('date_from') || urlParams.has('date_to');

            document.querySelectorAll('.warehouse-checkbox-partial').forEach(cb => {
                cb.addEventListener('change', function() {
                    onPartialWarehouseChange();
                });
            });

            if (!hasUrlParams) {
                const savedWarehouseIdsStr = localStorage.getItem('selectedWarehouseIds_global')
                                          || localStorage.getItem('selectedWarehouseIds_admin_dashboard')
                                          || localStorage.getItem('selectedWarehouseIds_alwaseet_print_upload');
                if (savedWarehouseIdsStr) {
                    try {
                        const savedIds = JSON.parse(savedWarehouseIdsStr);
                        if (Array.isArray(savedIds) && savedIds.length > 0) {
                            const savedParams = new URLSearchParams();
                            savedIds.forEach(id => {
                                savedParams.append('warehouse_ids[]', id);
                            });
                            if (savedParams.toString()) {
                                window.location.href = '{{ route('admin.orders.partial-returns.index') }}?' + savedParams.toString();
                            }
                        }
                    } catch (e) {}
                }
            }
        });

        // نسخ كود الوسيط
        function copyDeliveryCode(text, type = '') {
            let successMessage = 'تم نسخ كود الوسيط بنجاح!';
            let errorMessage = 'فشل في نسخ كود الوسيط';

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            textarea.select();
            textarea.setSelectionRange(0, 99999);

            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textarea);

                if (successful) {
                    if (typeof showNotification === 'function') {
                        showNotification(successMessage, 'success');
                    } else {
                        alert(successMessage);
                    }
                } else {
                    if (typeof showNotification === 'function') {
                        showNotification(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                }
            } catch (err) {
                document.body.removeChild(textarea);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        if (typeof showNotification === 'function') {
                            showNotification(successMessage, 'success');
                        } else {
                            alert(successMessage);
                        }
                    }).catch(() => {
                        if (typeof showNotification === 'function') {
                            showNotification(errorMessage, 'error');
                        } else {
                            alert(errorMessage);
                        }
                    });
                }
            }
        }
    </script>
</x-layout.admin>

