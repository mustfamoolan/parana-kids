<x-layout.admin>
    <div class="panel">
            <h5 class="mb-5 text-lg font-semibold dark:text-white-light">إدارة Rate Limiting</h5>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-3 mb-10">
                <div class="panel">
                    <h6 class="text-gray-500">الطلبات الحالية</h6>
                    <p class="text-2xl font-bold">{{ $rateLimitCount }} / {{ $rateLimitMax }}</p>
                    <div class="mt-2 h-2 bg-gray-200 rounded-full">
                        <div class="h-2 bg-primary rounded-full" style="width: {{ ($rateLimitCount / $rateLimitMax) * 100 }}%"></div>
                    </div>
                </div>
                <div class="panel">
                    <h6 class="text-gray-500">الحد الأقصى</h6>
                    <p class="text-2xl font-bold">{{ $rateLimitMax }} طلب</p>
                    <small class="text-gray-500">كل {{ $rateLimitWindow }} ثانية</small>
                </div>
                <div class="panel">
                    <h6 class="text-gray-500">Jobs المعلقة</h6>
                    <p class="text-2xl font-bold">{{ $pendingJobs }}</p>
                </div>
            </div>
            @if($queueLogs->count() > 0)
                <div class="panel">
                    <h6 class="mb-4 text-lg font-semibold">سجل الأخطاء</h6>
                    <div class="table-responsive">
                        <table class="table-hover">
                            <thead>
                                <tr>
                                    <th>التاريخ</th>
                                    <th>الخطأ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($queueLogs as $log)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($log->failed_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ $log->exception }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
</x-layout.admin>

