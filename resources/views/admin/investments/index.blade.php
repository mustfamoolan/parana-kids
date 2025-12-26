<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة الاستثمارات</h5>
            <a href="{{ route('admin.investments.create') }}" class="btn btn-primary">إضافة استثمار جديد</a>
        </div>

        <div class="panel mb-5">
            <form method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium mb-2">البحث</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           class="form-input" placeholder="ابحث بالاسم أو الرقم...">
                </div>
                <div class="sm:w-48">
                    <label for="investment_type" class="block text-sm font-medium mb-2">نوع الاستثمار</label>
                    <select id="investment_type" name="investment_type" class="form-select">
                        <option value="">جميع الأنواع</option>
                        <option value="product" {{ request('investment_type') === 'product' ? 'selected' : '' }}>منتج</option>
                        <option value="warehouse" {{ request('investment_type') === 'warehouse' ? 'selected' : '' }}>مخزن</option>
                        <option value="private_warehouse" {{ request('investment_type') === 'private_warehouse' ? 'selected' : '' }}>مخزن خاص</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">بحث</button>
                    <a href="{{ route('admin.investments.index') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                </div>
            </form>
        </div>

        <div class="panel">
            @if($investments->count() > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>المستثمر</th>
                                <th>النوع</th>
                                <th>الهدف</th>
                                <th>نسبة الربح</th>
                                <th>مبلغ الاستثمار</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($investments as $investment)
                                <tr>
                                    <td>{{ $investment->investor->name }}</td>
                                    <td>
                                        @if($investment->investment_type === 'product')
                                            <span class="badge badge-info">منتج</span>
                                        @elseif($investment->investment_type === 'warehouse')
                                            <span class="badge badge-primary">مخزن</span>
                                        @else
                                            <span class="badge badge-success">مخزن خاص</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($investment->product)
                                            {{ $investment->product->name }}
                                        @elseif($investment->warehouse)
                                            {{ $investment->warehouse->name }}
                                        @elseif($investment->privateWarehouse)
                                            {{ $investment->privateWarehouse->name }}
                                        @endif
                                    </td>
                                    <td>{{ $investment->profit_percentage }}%</td>
                                    <td>{{ number_format($investment->investment_amount, 2) }} دينار</td>
                                    <td>
                                        @if($investment->status === 'active')
                                            <span class="badge badge-success">نشط</span>
                                        @else
                                            <span class="badge badge-danger">{{ $investment->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.investments.edit', $investment) }}" class="btn btn-sm btn-warning">تعديل</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $investments->links() }}
            @else
                <p class="text-center text-gray-500 py-8">لا توجد استثمارات</p>
            @endif
        </div>
    </div>
</x-layout.admin>

