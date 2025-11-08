<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل المصروف</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمصروفات
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" class="space-y-5" x-data="{ expenseType: '{{ old('expense_type', $expense->expense_type) }}' }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <!-- نوع المصروف -->
                <div>
                    <label for="expense_type" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        نوع المصروف <span class="text-danger">*</span>
                    </label>
                    <select
                        id="expense_type"
                        name="expense_type"
                        class="form-select @error('expense_type') border-danger @enderror"
                        x-model="expenseType"
                        required
                    >
                        <option value="">اختر نوع المصروف</option>
                        <option value="rent" {{ old('expense_type', $expense->expense_type) == 'rent' ? 'selected' : '' }}>إيجار</option>
                        <option value="salary" {{ old('expense_type', $expense->expense_type) == 'salary' ? 'selected' : '' }}>رواتب</option>
                        <option value="other" {{ old('expense_type', $expense->expense_type) == 'other' ? 'selected' : '' }}>صرفيات أخرى</option>
                        <option value="promotion" {{ old('expense_type', $expense->expense_type) == 'promotion' ? 'selected' : '' }}>ترويج</option>
                    </select>
                    @error('expense_type')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- المبلغ -->
                <div>
                    <label for="amount" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        المبلغ <span class="text-danger">*</span>
                    </label>
                    <input
                        type="number"
                        id="amount"
                        name="amount"
                        value="{{ old('amount', $expense->amount) }}"
                        class="form-input @error('amount') border-danger @enderror"
                        placeholder="أدخل المبلغ"
                        step="0.01"
                        min="0"
                        required
                    >
                    @error('amount')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- التاريخ -->
                <div>
                    <label for="expense_date" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        التاريخ <span class="text-danger">*</span>
                    </label>
                    <input
                        type="date"
                        id="expense_date"
                        name="expense_date"
                        value="{{ old('expense_date', $expense->expense_date->format('Y-m-d')) }}"
                        class="form-input @error('expense_date') border-danger @enderror"
                        required
                    >
                    @error('expense_date')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- اسم الشخص (للرواتب فقط) -->
                <div x-show="expenseType === 'salary'">
                    <label for="person_name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        اسم الشخص
                    </label>
                    <input
                        type="text"
                        id="person_name"
                        name="person_name"
                        value="{{ old('person_name', $expense->person_name) }}"
                        class="form-input @error('person_name') border-danger @enderror"
                        placeholder="أدخل اسم الشخص أو اختر من القائمة أدناه"
                    >
                    @error('person_name')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- الراتب (للرواتب فقط) -->
                <div x-show="expenseType === 'salary'">
                    <label for="salary_amount" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        الراتب (اختياري)
                    </label>
                    <input
                        type="number"
                        id="salary_amount"
                        name="salary_amount"
                        value="{{ old('salary_amount', $expense->salary_amount) }}"
                        class="form-input @error('salary_amount') border-danger @enderror"
                        placeholder="الراتب الشهري"
                        step="0.01"
                        min="0"
                    >
                    <p class="mt-1 text-xs text-gray-500">الراتب الشهري للشخص (اختياري)</p>
                    @error('salary_amount')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- اختيار المستخدم (للرواتب فقط) -->
                <div x-show="expenseType === 'salary'">
                    <label for="user_id" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        أو اختر من المستخدمين
                    </label>
                    <select
                        id="user_id"
                        name="user_id"
                        class="form-select @error('user_id') border-danger @enderror"
                    >
                        <option value="">اختر مستخدم (اختياري)</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id', $expense->user_id) == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->code ?? 'N/A' }}) - {{ $user->role === 'delegate' ? 'مندوب' : 'مجهز' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">يمكنك إدخال اسم شخص جديد أو اختيار من القائمة</p>
                    @error('user_id')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- البحث عن المنتج (للترويج فقط) -->
                <div x-show="expenseType === 'promotion'" class="lg:col-span-2">
                    <label for="product_search" class="mb-3 block text-sm font-medium text-black dark:text-white">
                        البحث عن المنتج (اختياري)
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="product_search"
                            class="form-input @error('product_id') border-danger @enderror"
                            placeholder="ابحث بكود المنتج أو اسم المنتج..."
                            autocomplete="off"
                            oninput="handleProductSearch(this.value)"
                        >
                        <input type="hidden" name="product_id" id="product_id" value="{{ old('product_id', $expense->product_id) }}">
                        <div id="product_results" class="absolute z-10 w-full mt-1 bg-white dark:bg-[#1b2e4b] border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                            <!-- نتائج البحث ستظهر هنا -->
                        </div>
                    </div>
                    <div id="selected_product" class="mt-2 {{ $expense->product ? '' : 'hidden' }}">
                        <div class="flex items-center justify-between p-2 bg-primary/10 rounded">
                            <span id="selected_product_name" class="text-sm font-medium">
                                @if($expense->product)
                                    {{ $expense->product->name }} ({{ $expense->product->code }})
                                @endif
                            </span>
                            <button type="button" onclick="clearProduct()" class="text-danger hover:text-danger-dark">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @if($expense->product)
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                document.getElementById('product_search').value = '{{ $expense->product->name }} ({{ $expense->product->code }})';
                            });
                        </script>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">ابحث عن المنتج الذي تم الترويج له (اختياري)</p>
                    @error('product_id')
                        <div class="mt-1 text-danger">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- الملاحظات -->
            <div>
                <label for="notes" class="mb-3 block text-sm font-medium text-black dark:text-white">
                    الملاحظات (اختياري)
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="4"
                    class="form-textarea @error('notes') border-danger @enderror"
                    placeholder="أدخل أي ملاحظات إضافية..."
                >{{ old('notes', $expense->notes) }}</textarea>
                @error('notes')
                    <div class="mt-1 text-danger">{{ $message }}</div>
                @enderror
            </div>

            <div class="flex items-center justify-end gap-4 pt-5">
                <a href="{{ route('admin.expenses.index') }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    تحديث المصروف
                </button>
            </div>
        </form>
    </div>

    <script>
        let searchTimeout = null;

        function handleProductSearch(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(query);
            }, 300);
        }

        function searchProducts(query) {
            const resultsDiv = document.getElementById('product_results');

            if (!query || query.length < 1) {
                resultsDiv.classList.add('hidden');
                return;
            }

            const url = `{{ route('admin.expenses.search-products') }}?search=${encodeURIComponent(query)}`;
            console.log('Searching products with URL:', url);

            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Response error:', text);
                        throw new Error(`HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(products => {
                console.log('Products received:', products);
                resultsDiv.innerHTML = '';

                if (!products || products.length === 0) {
                    resultsDiv.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">لا توجد نتائج</div>';
                } else {
                    products.forEach(product => {
                        const item = document.createElement('div');
                        item.className = 'p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition-colors';
                        item.innerHTML = `
                            <div class="font-medium text-black dark:text-white">${escapeHtml(product.name)}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">كود: ${escapeHtml(product.code || 'N/A')}</div>
                        `;
                        item.onclick = () => selectProduct(product.id, product.name, product.code);
                        resultsDiv.appendChild(item);
                    });
                }

                resultsDiv.classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error searching products:', error);
                resultsDiv.innerHTML = '<div class="p-3 text-sm text-danger text-center">حدث خطأ في البحث</div>';
                resultsDiv.classList.remove('hidden');
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function selectProduct(productId, productName, productCode) {
            document.getElementById('product_id').value = productId;
            document.getElementById('product_search').value = `${productName} (${productCode})`;
            document.getElementById('selected_product_name').textContent = `${productName} (${productCode})`;
            document.getElementById('product_results').classList.add('hidden');
            document.getElementById('selected_product').classList.remove('hidden');
        }

        function clearProduct() {
            document.getElementById('product_id').value = '';
            document.getElementById('product_search').value = '';
            document.getElementById('selected_product').classList.add('hidden');
        }

        // إخفاء نتائج البحث عند النقر خارجها
        document.addEventListener('click', function(event) {
            const productSearch = document.getElementById('product_search');
            const productResults = document.getElementById('product_results');
            if (!productSearch.contains(event.target) && !productResults.contains(event.target)) {
                productResults.classList.add('hidden');
            }
        });

        // عند تحميل الصفحة، إذا كان هناك منتج محدد مسبقاً
        @if(old('product_id'))
            @php
                $selectedProduct = \App\Models\Product::find(old('product_id'));
            @endphp
            @if($selectedProduct)
                document.addEventListener('DOMContentLoaded', function() {
                    selectProduct({{ $selectedProduct->id }}, '{{ $selectedProduct->name }}', '{{ $selectedProduct->code }}');
                });
            @endif
        @endif
    </script>
</x-layout.admin>


