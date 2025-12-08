<x-layout.admin>
    <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">إدارة المزامنة التلقائية</h5>
            @if(session('success'))
                <div class="alert alert-success mb-5">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('admin.alwaseet.auto-sync.update') }}">
                @csrf
                <div class="mb-5">
                    <label class="flex items-center">
                        <input type="checkbox" name="auto_sync_enabled" value="1" {{ $syncEnabled === '1' ? 'checked' : '' }} class="form-checkbox">
                        <span class="mr-2">تفعيل المزامنة التلقائية</span>
                    </label>
                </div>
                <div class="mb-5">
                    <label class="mb-2 block">الفترة الزمنية (بالدقائق)</label>
                    <input type="number" name="auto_sync_interval" value="{{ $syncInterval }}" min="5" max="1440" class="form-input">
                </div>
                <div class="mb-5">
                    <label class="mb-2 block">الحالات المراد مزامنتها (مفصولة بفواصل)</label>
                    <input type="text" name="auto_sync_status_ids" value="{{ $syncStatusIds }}" class="form-input" placeholder="مثل: 1,2,3">
                    <small class="text-gray-500">اتركه فارغاً لمزامنة جميع الحالات</small>
                </div>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </form>
            <div class="mt-10">
                <h6 class="mb-4 text-lg font-semibold">سجل المزامنات</h6>
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>النوع</th>
                                <th>الحالة</th>
                                <th>عدد الطلبات</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($syncLogs as $log)
                                <tr>
                                    <td>{{ $log->type === 'automatic' ? 'تلقائي' : 'يدوي' }}</td>
                                    <td><span class="badge badge-{{ $log->status === 'success' ? 'success' : ($log->status === 'failed' ? 'danger' : 'warning') }}">{{ $log->status }}</span></td>
                                    <td>{{ $log->orders_synced }}</td>
                                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $syncLogs->links() }}
            </div>
        </div>
</x-layout.admin>

