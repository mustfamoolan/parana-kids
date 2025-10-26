<x-layout.default>
    <div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">الطلبات المحذوفة</h3>
                    <div class="card-tools">
                        <a href="{{ route('delegate.orders.index') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> العودة للطلبات
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- تحذير -->
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>تنبيه:</strong> هذه الطلبات محذوفة ويمكن استرجاعها. عند الاسترجاع، سيتم إرجاع الطلب إلى حالته الأصلية.
                    </div>

                    <!-- فلاتر البحث -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>البحث</label>
                                    <input type="text" name="search" class="form-control"
                                           value="{{ request('search') }}"
                                           placeholder="رقم الطلب، ملاحظات، أو اسم المنتج">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>من تاريخ</label>
                                    <input type="date" name="date_from" class="form-control"
                                           value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>من وقت</label>
                                    <input type="time" name="time_from" class="form-control"
                                           value="{{ request('time_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>إلى تاريخ</label>
                                    <input type="date" name="date_to" class="form-control"
                                           value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>إلى وقت</label>
                                    <input type="time" name="time_to" class="form-control"
                                           value="{{ request('time_to') }}">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        @if(request()->hasAny(['search', 'date_from', 'time_from', 'date_to', 'time_to']))
                            <a href="{{ route('delegate.orders.trashed') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> مسح الفلاتر
                            </a>
                        @endif
                    </form>

                    <!-- نتائج البحث -->
                    @if(request()->hasAny(['search', 'date_from', 'time_from', 'date_to', 'time_to']))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            عرض {{ $orders->total() }} طلب محذوف
                        </div>
                    @endif

                    <!-- جدول الطلبات -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>تاريخ الحذف</th>
                                    <th>محذوف بواسطة</th>
                                    <th>عدد المنتجات</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <strong>{{ $order->order_number }}</strong>
                                            @if($order->notes)
                                                <br><small class="text-muted">{{ Str::limit($order->notes, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $order->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td>
                                            {{ $order->deleted_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td>
                                            @if($order->deletedBy)
                                                {{ $order->deletedBy->name }}
                                            @else
                                                <span class="text-muted">غير محدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-info">{{ $order->items->count() }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">{{ ucfirst($order->status) }}</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('delegate.orders.show', $order) }}"
                                                   class="btn btn-sm btn-info" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form method="POST" action="{{ route('delegate.orders.restore', $order) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('هل أنت متأكد من استرجاع هذا الطلب؟')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="استرجاع">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="py-4">
                                                <i class="fas fa-trash fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">لا توجد طلبات محذوفة</h5>
                                                <p class="text-muted">لم يتم حذف أي طلبات بعد</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($orders->hasPages())
                        <div class="d-flex justify-content-center">
                            {{ $orders->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</x-layout.default>
