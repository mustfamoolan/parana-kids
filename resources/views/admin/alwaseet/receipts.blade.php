<x-layout.admin>
    <div class="panel">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <h5 class="text-lg font-semibold dark:text-white-light">الوصولات (الإيصالات)</h5>
                    <form method="POST" action="{{ route('admin.alwaseet.sync') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            مزامنة الطلبات
                        </button>
                    </form>
                </div>

                @if(session('success'))
                    <div class="alert alert-success mb-5">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger mb-5">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(!$isConnected)
                    <div class="alert alert-warning mb-5">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <strong>تنبيه:</strong> لم يتم ربط الواسط بعد. يرجى الذهاب إلى <a href="{{ route('admin.alwaseet.settings') }}" class="underline">الإعدادات</a> لإعداد الاتصال أولاً.
                    </div>
                @endif

                <!-- فلتر البحث -->
                <div class="panel mb-5">
                    <form method="GET" action="{{ route('admin.alwaseet.receipts') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="search" class="mb-2 block text-sm font-medium">بحث</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                   placeholder="اسم العميل، رقم الهاتف، أو رقم الطلب" class="form-input">
                        </div>
                        <div>
                            <label for="status_id" class="mb-2 block text-sm font-medium">الحالة</label>
                            <select name="status_id" id="status_id" class="form-select">
                                <option value="">جميع الحالات</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status['id'] }}" {{ request('status_id') == $status['id'] ? 'selected' : '' }}>
                                        {{ $status['status'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="city_id" class="mb-2 block text-sm font-medium">المدينة</label>
                            <select name="city_id" id="city_id" class="form-select">
                                <option value="">جميع المدن</option>
                                @foreach($cities as $city)
                                    <option value="{{ $city['id'] }}" {{ request('city_id') == $city['id'] ? 'selected' : '' }}>
                                        {{ $city['city_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="mb-2 block text-sm font-medium">من تاريخ</label>
                            <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-input">
                        </div>
                        <div>
                            <label for="date_to" class="mb-2 block text-sm font-medium">إلى تاريخ</label>
                            <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-input">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn btn-primary">فلترة</button>
                            <a href="{{ route('admin.alwaseet.receipts') }}" class="btn btn-outline-secondary">إعادة تعيين</a>
                        </div>
                    </form>
                </div>

                <!-- جدول الوصولات -->
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>اسم العميل</th>
                                <th>رقم الهاتف</th>
                                <th>المدينة</th>
                                <th>المنطقة</th>
                                <th>السعر</th>
                                <th>الحالة</th>
                                <th>حالة الطباعة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receipts as $receipt)
                                <tr>
                                    <td>
                                        <span class="font-semibold">{{ $receipt->alwaseet_order_id }}</span>
                                    </td>
                                    <td>{{ $receipt->client_name }}</td>
                                    <td>{{ $receipt->client_mobile }}</td>
                                    <td>{{ $receipt->city_name }}</td>
                                    <td>{{ $receipt->region_name }}</td>
                                    <td>
                                        <span class="font-semibold">{{ number_format($receipt->price, 0) }} د.ع</span>
                                        @if($receipt->delivery_price > 0)
                                            <br><small class="text-gray-500">توصيل: {{ number_format($receipt->delivery_price, 0) }} د.ع</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $receipt->status_badge_class }}">{{ $receipt->status }}</span>
                                    </td>
                                    <td>
                                        @if($receipt->qr_link)
                                            <span class="badge badge-outline-success">متوفر للطباعة</span>
                                        @else
                                            <span class="badge badge-outline-secondary">غير متوفر</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            @if($receipt->qr_link)
                                                <a href="{{ route('admin.alwaseet.receipts.download', $receipt->id) }}"
                                                   class="btn btn-sm btn-success"
                                                   download>
                                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    تحميل PDF
                                                </a>
                                            @endif
                                            <a href="{{ route('admin.alwaseet.show', $receipt->id) }}" class="btn btn-sm btn-outline-primary">
                                                تفاصيل
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-8 text-gray-500">
                                        @if(!$isConnected)
                                            <div class="flex flex-col items-center gap-2">
                                                <p class="text-lg font-semibold">لم يتم ربط الواسط بعد</p>
                                                <p class="text-sm">يرجى الذهاب إلى إعدادات الربط لإعداد الاتصال</p>
                                                <a href="{{ route('admin.alwaseet.settings') }}" class="btn btn-primary mt-2">
                                                    إعدادات الربط
                                                </a>
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center gap-2">
                                                <p class="text-lg font-semibold">لا توجد وصولات</p>
                                                <p class="text-sm">لا توجد إيصالات متاحة للطباعة حالياً</p>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-5">
                    {{ $receipts->links() }}
                </div>
    </div>
</x-layout.admin>

