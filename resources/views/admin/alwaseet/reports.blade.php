<x-layout.admin>
    <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">التقارير والإحصائيات</h5>
            <form method="GET" action="{{ route('admin.alwaseet.reports') }}" class="mb-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-2 block">من تاريخ</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input">
                    </div>
                    <div>
                        <label class="mb-2 block">إلى تاريخ</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">تطبيق</button>
            </form>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-4 mb-10">
                <div class="panel">
                    <h6 class="text-gray-500">إجمالي الشحنات</h6>
                    <p class="text-2xl font-bold">{{ $totalShipments }}</p>
                </div>
                <div class="panel">
                    <h6 class="text-gray-500">إجمالي المبلغ</h6>
                    <p class="text-2xl font-bold">{{ number_format($totalAmount, 2) }} د.ع</p>
                </div>
                <div class="panel">
                    <h6 class="text-gray-500">إجمالي رسوم التوصيل</h6>
                    <p class="text-2xl font-bold">{{ number_format($totalDeliveryFee, 2) }} د.ع</p>
                </div>
                <div class="panel">
                    <h6 class="text-gray-500">الشحنات المسلمة</h6>
                    <p class="text-2xl font-bold">{{ $deliveredCount }}</p>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div class="panel">
                    <h6 class="mb-4 text-lg font-semibold">إحصائيات حسب الحالة</h6>
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>الحالة</th>
                                    <th>العدد</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statusStats as $stat)
                                    <tr>
                                        <td>{{ $stat->status }}</td>
                                        <td>{{ $stat->count }}</td>
                                        <td>{{ number_format($stat->total, 2) }} د.ع</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="panel">
                    <h6 class="mb-4 text-lg font-semibold">إحصائيات حسب المدينة</h6>
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>المدينة</th>
                                    <th>العدد</th>
                                    <th>المبلغ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cityStats as $stat)
                                    <tr>
                                        <td>{{ $stat->city_name }}</td>
                                        <td>{{ $stat->count }}</td>
                                        <td>{{ number_format($stat->total, 2) }} د.ع</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
</x-layout.admin>

