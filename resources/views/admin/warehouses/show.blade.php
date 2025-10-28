<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تفاصيل المخزن: {{ $warehouse->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.index') }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للقائمة
                </a>
                @can('update', $warehouse)
                    <a href="{{ route('admin.warehouses.edit', $warehouse) }}" class="btn btn-outline-warning">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        تعديل
                    </a>
                @endcan
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

        @if(session('success'))
            <div class="alert alert-success mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <!-- معلومات المخزن -->
            <div class="lg:col-span-2">
                <div class="panel">
                    <div class="mb-5">
                        <h6 class="text-lg font-semibold dark:text-white-light">معلومات المخزن</h6>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">اسم المخزن:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">الموقع:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->location }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المنشئ:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->creator->name }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->created_at->format('Y-m-d H:i') }}</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">آخر تحديث:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->updated_at->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الإحصائيات -->
            <div>
                <div class="panel">
                    <div class="mb-5">
                        <h6 class="text-lg font-semibold dark:text-white-light">الإحصائيات</h6>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">عدد المنتجات:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->products->count() }} منتج</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">إجمالي القطع:</span>
                            <span class="font-medium text-black dark:text-white">{{ number_format($totalPieces) }} قطعة</span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400">المستخدمين المصرح لهم:</span>
                            <span class="font-medium text-black dark:text-white">{{ $warehouse->users->count() }}</span>
                        </div>

                        @if(auth()->user()->isAdmin())
                            <div class="border-t pt-4 mt-4">
                                <div class="mb-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">السعر الكلي للبيع:</span>
                                    </div>
                                    <div class="text-2xl font-bold text-black dark:text-white">
                                        {{ number_format($totalSellingPrice, 0) }}
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">دينار عراقي</span>
                                    </div>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-500 dark:text-gray-400">السعر الكلي للشراء:</span>
                                    </div>
                                    <div class="text-2xl font-bold text-black dark:text-white">
                                        {{ number_format($totalPurchasePrice, 0) }}
                                        <span class="text-sm font-normal text-gray-600 dark:text-gray-400">دينار عراقي</span>
                                    </div>
                                </div>

                                @if($totalSellingPrice > 0 && $totalPurchasePrice > 0)
                                    <div class="mt-4 pt-4 border-t">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-gray-500 dark:text-gray-400">الربح المتوقع:</span>
                                        </div>
                                        <div class="text-2xl font-bold text-black dark:text-white">
                                            {{ number_format($totalSellingPrice - $totalPurchasePrice, 0) }}
                                            <span class="text-sm font-normal text-gray-600 dark:text-gray-400">دينار عراقي</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- المستخدمين المصرح لهم -->
        @if($warehouse->users->count() > 0)
            <div class="panel mt-5">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h6 class="text-lg font-semibold dark:text-white-light">المستخدمين المصرح لهم</h6>
                    @can('manage', $warehouse)
                        <a href="{{ route('admin.warehouses.assign-users', $warehouse) }}" class="btn btn-outline-info">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            إدارة المستخدمين
                        </a>
                    @endcan
                </div>

                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>الدور</th>
                                <th>رقم الهاتف</th>
                                <th>صلاحية الإدارة</th>
                                <th>تاريخ التعيين</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouse->users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>
                                        <span class="badge badge-outline-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'supplier' ? 'warning' : 'info') }}">
                                            @if($user->role === 'admin')
                                                مدير
                                            @elseif($user->role === 'supplier')
                                                مجهز
                                            @else
                                                مندوب
                                            @endif
                                        </span>
                                    </td>
                                    <td>{{ $user->phone }}</td>
                                    <td>
                                        @if($user->pivot->can_manage)
                                            <span class="badge badge-success">نعم</span>
                                        @else
                                            <span class="badge badge-secondary">لا</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->pivot->created_at->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- المنتجات -->
        <div class="panel mt-5">
            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h6 class="text-lg font-semibold dark:text-white-light">منتجات المخزن</h6>
                @can('create', App\Models\Product::class)
                    <a href="{{ route('admin.warehouses.products.create', $warehouse) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إضافة منتج جديد
                    </a>
                @endcan
            </div>

            @if($warehouse->products->count() > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>اسم المنتج</th>
                                <th>الكود</th>
                                @if(auth()->user()->isAdmin())
                                    <th>سعر الشراء</th>
                                @endif
                                <th>سعر البيع</th>
                                <th>الكمية الإجمالية</th>
                                <th>المنشئ</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouse->products as $product)
                                <tr>
                                    <td>{{ $product->id }}</td>
                                    <td>
                                        @if($product->primaryImage)
                                            <img src="{{ $product->primaryImage->image_url }}" alt="{{ $product->name }}" class="w-10 h-10 object-cover rounded border border-gray-200 dark:border-gray-700">
                                        @else
                                            <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $product->name }}</td>
                                    <td>
                                        <span class="badge badge-outline-primary">{{ $product->code }}</span>
                                    </td>
                                    @if(auth()->user()->isAdmin())
                                        <td>
                                            @if($product->purchase_price)
                                                <span class="font-medium text-info">{{ number_format($product->purchase_price, 0) }} دينار عراقي</span>
                                            @else
                                                <span class="text-gray-400">غير محدد</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>{{ number_format($product->selling_price, 0) }} دينار عراقي</td>
                                    <td>
                                        <span class="badge badge-outline-success">{{ $product->total_quantity }}</span>
                                    </td>
                                    <td>{{ $product->creator->name }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            @can('view', $product)
                                                <a href="{{ route('admin.warehouses.products.show', [$warehouse, $product]) }}" class="btn btn-sm btn-outline-primary">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                            @endcan

                                            @can('update', $product)
                                                <a href="{{ route('admin.warehouses.products.edit', [$warehouse, $product]) }}" class="btn btn-sm btn-outline-warning">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    لا توجد منتجات في هذا المخزن
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
