<x-layout.admin>
    <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">الإشعارات</h5>
            <form method="POST" action="{{ route('admin.alwaseet.notifications.update') }}" class="mb-5">
                @csrf
                <div class="mb-5">
                    <label class="mb-2 block">الحالات المراد إرسال إشعارات لها (مفصولة بفواصل)</label>
                    <input type="text" name="notify_statuses" value="{{ $notifyStatuses }}" class="form-input" placeholder="مثل: 2,3,4">
                    <small class="text-gray-500">اتركه فارغاً لإرسال إشعارات لجميع الحالات</small>
                </div>
                <button type="submit" class="btn btn-primary">حفظ</button>
            </form>
            <div class="mb-5">
                <form method="POST" action="{{ route('admin.alwaseet.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary">تحديد الكل كمقروء</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>النوع</th>
                            <th>العنوان</th>
                            <th>الرسالة</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notifications as $notification)
                            <tr class="{{ !$notification->is_read ? 'bg-gray-50' : '' }}">
                                <td>{{ $notification->type }}</td>
                                <td>{{ $notification->title }}</td>
                                <td>{{ $notification->message }}</td>
                                <td>{{ $notification->old_status }} → {{ $notification->new_status }}</td>
                                <td>{{ $notification->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if(!$notification->is_read)
                                        <form method="POST" action="{{ route('admin.alwaseet.notifications.read', $notification->id) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">تحديد كمقروء</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $notifications->links() }}
        </div>
</x-layout.admin>

