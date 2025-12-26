<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل استثمار</h5>
            <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-secondary">العودة</a>
        </div>

        <form method="POST" action="{{ route('admin.investments.update', $investment) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">معلومات الاستثمار</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="investor_id" class="block text-sm font-medium mb-2">المستثمر <span class="text-red-500">*</span></label>
                        <select id="investor_id" name="investor_id" class="form-select" required>
                            <option value="">-- اختر المستثمر --</option>
                            @foreach($investors as $investor)
                                <option value="{{ $investor->id }}" {{ old('investor_id', $investment->investor_id) == $investor->id ? 'selected' : '' }}>
                                    {{ $investor->name }} ({{ $investor->phone }})
                                </option>
                            @endforeach
                        </select>
                        @error('investor_id')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="investment_type" class="block text-sm font-medium mb-2">نوع الاستثمار <span class="text-red-500">*</span></label>
                        <select id="investment_type" name="investment_type" class="form-select" required>
                            <option value="">-- اختر النوع --</option>
                            <option value="product" {{ old('investment_type', $investment->investment_type) === 'product' ? 'selected' : '' }}>منتج</option>
                            <option value="warehouse" {{ old('investment_type', $investment->investment_type) === 'warehouse' ? 'selected' : '' }}>مخزن</option>
                            <option value="private_warehouse" {{ old('investment_type', $investment->investment_type) === 'private_warehouse' ? 'selected' : '' }}>مخزن خاص</option>
                        </select>
                        @error('investment_type')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="product_field" style="display: {{ old('investment_type', $investment->investment_type) === 'product' ? 'block' : 'none' }};">
                        <label for="product_id" class="block text-sm font-medium mb-2">المنتج <span class="text-red-500">*</span></label>
                        <select id="product_id" name="product_id" class="form-select">
                            <option value="">-- اختر المنتج --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" {{ old('product_id', $investment->product_id) == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="warehouse_field" style="display: {{ old('investment_type', $investment->investment_type) === 'warehouse' ? 'block' : 'none' }};">
                        <label for="warehouse_id" class="block text-sm font-medium mb-2">المخزن <span class="text-red-500">*</span></label>
                        <select id="warehouse_id" name="warehouse_id" class="form-select">
                            <option value="">-- اختر المخزن --</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $investment->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div id="private_warehouse_field" style="display: {{ old('investment_type', $investment->investment_type) === 'private_warehouse' ? 'block' : 'none' }};">
                        <label for="private_warehouse_id" class="block text-sm font-medium mb-2">المخزن الخاص <span class="text-red-500">*</span></label>
                        <select id="private_warehouse_id" name="private_warehouse_id" class="form-select">
                            <option value="">-- اختر المخزن الخاص --</option>
                            @foreach($privateWarehouses as $privateWarehouse)
                                <option value="{{ $privateWarehouse->id }}" {{ old('private_warehouse_id', $investment->private_warehouse_id) == $privateWarehouse->id ? 'selected' : '' }}>
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
                        <input type="number" id="profit_percentage" name="profit_percentage" value="{{ old('profit_percentage', $investment->profit_percentage) }}"
                               class="form-input" step="0.01" min="0" max="100" required>
                        @error('profit_percentage')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="investment_amount" class="block text-sm font-medium mb-2">مبلغ الاستثمار <span class="text-red-500">*</span></label>
                        <input type="number" id="investment_amount" name="investment_amount" value="{{ old('investment_amount', $investment->investment_amount) }}"
                               class="form-input" step="0.01" min="0" required>
                        @error('investment_amount')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="start_date" class="block text-sm font-medium mb-2">تاريخ البدء <span class="text-red-500">*</span></label>
                        <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $investment->start_date->format('Y-m-d')) }}"
                               class="form-input" required>
                        @error('start_date')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium mb-2">تاريخ الانتهاء</label>
                        <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $investment->end_date ? $investment->end_date->format('Y-m-d') : '') }}"
                               class="form-input">
                        @error('end_date')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium mb-2">الحالة <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            <option value="active" {{ old('status', $investment->status) === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="completed" {{ old('status', $investment->status) === 'completed' ? 'selected' : '' }}>مكتمل</option>
                            <option value="cancelled" {{ old('status', $investment->status) === 'cancelled' ? 'selected' : '' }}>ملغى</option>
                            <option value="suspended" {{ old('status', $investment->status) === 'suspended' ? 'selected' : '' }}>معلق</option>
                        </select>
                        @error('status')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label for="notes" class="block text-sm font-medium mb-2">ملاحظات</label>
                        <textarea id="notes" name="notes" rows="3" class="form-textarea">{{ old('notes', $investment->notes) }}</textarea>
                        @error('notes')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('investment_type').addEventListener('change', function() {
            const type = this.value;
            document.getElementById('product_field').style.display = type === 'product' ? 'block' : 'none';
            document.getElementById('warehouse_field').style.display = type === 'warehouse' ? 'block' : 'none';
            document.getElementById('private_warehouse_field').style.display = type === 'private_warehouse' ? 'block' : 'none';
        });
    </script>
</x-layout.admin>

