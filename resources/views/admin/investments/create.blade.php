<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إضافة استثمار جديد</h5>
            @if($backUrl ?? null)
                <a href="{{ $backUrl }}" class="btn btn-outline-secondary">العودة</a>
            @else
                <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-secondary">العودة</a>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.investments.store') }}" class="space-y-5">
            @csrf
            @if($backUrl ?? null)
                <input type="hidden" name="back_url" value="{{ $backUrl }}">
            @endif

            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">معلومات الاستثمار</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(isset($investorId) && $investorId)
                        <input type="hidden" name="investor_id" value="{{ $investorId }}">
                        @php
                            $selectedInvestor = $investors->firstWhere('id', $investorId);
                        @endphp
                        @if($selectedInvestor)
                            <div>
                                <label class="block text-sm font-medium mb-2">المستثمر</label>
                                <div class="form-input bg-gray-100 dark:bg-gray-800">
                                    {{ $selectedInvestor->name }} ({{ $selectedInvestor->phone }})
                                </div>
                            </div>
                        @endif
                    @else
                        <div>
                            <label for="investor_id" class="block text-sm font-medium mb-2">المستثمر <span class="text-red-500">*</span></label>
                            <select id="investor_id" name="investor_id" class="form-select" required>
                                <option value="">-- اختر المستثمر --</option>
                                @foreach($investors as $investor)
                                    <option value="{{ $investor->id }}" {{ old('investor_id') == $investor->id ? 'selected' : '' }}>
                                        {{ $investor->name }} ({{ $investor->phone }})
                                    </option>
                                @endforeach
                            </select>
                            @error('investor_id')
                                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div>
                        <label for="investment_type" class="block text-sm font-medium mb-2">نوع الاستثمار <span class="text-red-500">*</span></label>
                        <select id="investment_type" name="investment_type" class="form-select" required>
                            <option value="">-- اختر النوع --</option>
                            @php
                                $selectedType = old('investment_type');
                                if (!$selectedType && isset($productId)) {
                                    $selectedType = 'product';
                                } elseif (!$selectedType && isset($warehouseId)) {
                                    $selectedType = 'warehouse';
                                } elseif (!$selectedType && isset($privateWarehouseId)) {
                                    $selectedType = 'private_warehouse';
                                }
                            @endphp
                            <option value="product" {{ $selectedType === 'product' ? 'selected' : '' }}>منتج</option>
                            <option value="warehouse" {{ $selectedType === 'warehouse' ? 'selected' : '' }}>مخزن</option>
                            <option value="private_warehouse" {{ $selectedType === 'private_warehouse' ? 'selected' : '' }}>مخزن خاص</option>
                        </select>
                        @error('investment_type')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="product_field" style="display: {{ ($selectedType ?? '') === 'product' ? 'block' : 'none' }};">
                        <div id="product_warehouse_field">
                            <label for="product_warehouse_id" class="block text-sm font-medium mb-2">المخزن <span class="text-red-500">*</span></label>
                            <select id="product_warehouse_id" name="product_warehouse_id" class="form-select">
                                <option value="">-- اختر المخزن أولاً --</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ (old('product_warehouse_id') ?? ($productId ? \App\Models\Product::find($productId)?->warehouse_id : null) ?? null) == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_warehouse_id')
                                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div id="product_select_field" style="margin-top: 1rem;">
                            <label for="product_id" class="block text-sm font-medium mb-2">المنتج <span class="text-red-500">*</span></label>
                            <select id="product_id" name="product_id" class="form-select" disabled>
                                <option value="">-- اختر المخزن أولاً --</option>
                            </select>
                            @error('product_id')
                                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div id="warehouse_field" style="display: {{ ($selectedType ?? '') === 'warehouse' ? 'block' : 'none' }};">
                        <label for="warehouse_id" class="block text-sm font-medium mb-2">المخزن <span class="text-red-500">*</span></label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select">
                            <option value="">-- اختر المخزن --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ (old('warehouse_id') ?? $warehouseId ?? null) == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="private_warehouse_field" style="display: {{ ($selectedType ?? '') === 'private_warehouse' ? 'block' : 'none' }};">
                        <label for="private_warehouse_id" class="block text-sm font-medium mb-2">المخزن الخاص <span class="text-red-500">*</span></label>
                        <select id="private_warehouse_id" name="private_warehouse_id" class="form-select">
                            <option value="">-- اختر المخزن الخاص --</option>
                            @foreach($privateWarehouses as $privateWarehouse)
                                <option value="{{ $privateWarehouse->id }}" {{ (old('private_warehouse_id') ?? $privateWarehouseId ?? null) == $privateWarehouse->id ? 'selected' : '' }}>
                                    {{ $privateWarehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('private_warehouse_id')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="profit_percentage" class="block text-sm font-medium mb-2">نسبة الربح <span class="text-red-500">*</span></label>
                        <input type="number" id="profit_percentage" name="profit_percentage" value="{{ old('profit_percentage') }}"
                               class="form-input" step="0.01" min="0" max="100" required>
                        @error('profit_percentage')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="investment_amount" class="block text-sm font-medium mb-2">مبلغ الاستثمار <span class="text-red-500">*</span></label>
                        <input type="number" id="investment_amount" name="investment_amount" value="{{ old('investment_amount') }}"
                               class="form-input" step="0.01" min="0" required>
                        @error('investment_amount')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium mb-2">تاريخ البدء <span class="text-red-500">*</span></label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}"
                               class="form-input" required>
                        @error('start_date')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium mb-2">تاريخ الانتهاء</label>
                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                               class="form-input">
                        @error('end_date')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium mb-2">ملاحظات</label>
                        <textarea id="notes" name="notes" rows="3" class="form-textarea">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex justify-end gap-4">
                    @if($backUrl ?? null)
                        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">إلغاء</a>
                    @else
                        <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                    @endif
                    <button type="submit" class="btn btn-primary">إضافة الاستثمار</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function updateFields() {
            const type = document.getElementById('investment_type').value;
            document.getElementById('product_field').style.display = type === 'product' ? 'block' : 'none';
            document.getElementById('warehouse_field').style.display = type === 'warehouse' ? 'block' : 'none';
            document.getElementById('private_warehouse_field').style.display = type === 'private_warehouse' ? 'block' : 'none';
            
            // إعادة تعيين حقل المنتج عند تغيير النوع
            if (type !== 'product') {
                const productSelect = document.getElementById('product_id');
                productSelect.innerHTML = '<option value="">-- اختر المخزن أولاً --</option>';
                productSelect.disabled = true;
            }
        }

        function loadProductsByWarehouse(warehouseId) {
            const productSelect = document.getElementById('product_id');
            
            if (!warehouseId) {
                productSelect.innerHTML = '<option value="">-- اختر المخزن أولاً --</option>';
                productSelect.disabled = true;
                return;
            }

            productSelect.disabled = true;
            productSelect.innerHTML = '<option value="">جاري التحميل...</option>';

            fetch(`/admin/api/warehouses/${warehouseId}/products`)
                .then(response => response.json())
                .then(data => {
                    productSelect.innerHTML = '<option value="">-- اختر المنتج --</option>';
                    
                    if (data.products && data.products.length > 0) {
                        const preSelectedProductId = @json($productId ?? null);
                        data.products.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.name;
                            if (preSelectedProductId && product.id == preSelectedProductId) {
                                option.selected = true;
                            }
                            productSelect.appendChild(option);
                        });
                        productSelect.disabled = false;
                    } else {
                        productSelect.innerHTML = '<option value="">لا توجد منتجات في هذا المخزن</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    productSelect.innerHTML = '<option value="">حدث خطأ في تحميل المنتجات</option>';
                });
        }

        document.getElementById('investment_type').addEventListener('change', updateFields);
        
        // عند تغيير المخزن في قسم المنتج
        const productWarehouseSelect = document.getElementById('product_warehouse_id');
        if (productWarehouseSelect) {
            productWarehouseSelect.addEventListener('change', function() {
                loadProductsByWarehouse(this.value);
            });
        }
        
        // Trigger on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateFields();
            
            // إذا كان نوع الاستثمار منتج وكان هناك منتج محدد مسبقاً، جلب المنتجات
            @if(isset($selectedType) && $selectedType === 'product' && isset($productId))
                @php
                    $preSelectedProduct = \App\Models\Product::find($productId);
                @endphp
                @if($preSelectedProduct && $preSelectedProduct->warehouse_id)
                    setTimeout(function() {
                        const preSelectedWarehouseId = {{ $preSelectedProduct->warehouse_id }};
                        const warehouseSelect = document.getElementById('product_warehouse_id');
                        if (warehouseSelect) {
                            warehouseSelect.value = preSelectedWarehouseId;
                            loadProductsByWarehouse(preSelectedWarehouseId);
                        }
                    }, 100);
                @endif
            @endif
        });
    </script>
</x-layout.admin>

