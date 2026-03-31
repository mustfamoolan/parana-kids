<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة العملاء (Customers)</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <!-- لا يوجد زر إضافة لأن العملاء يسجلون عبر التطبيق حصراً، أو يمكن إضافة زر مستقبلاً للمدير -->
            </div>
        </div>

        <!-- كاردات المستخدمين -->
        <div class="panel">
            @if($customers->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($customers as $customer)
                        <div class="panel">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center overflow-hidden">
                                        @if($customer->profile_image)
                                            <img src="{{ $customer->getProfileImageUrl() }}" alt="{{ $customer->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xl font-bold text-primary">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-semibold text-lg">{{ $customer->name }}</div>
                                        <span class="badge badge-success text-xs">عميل</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">البريد الإلكتروني:</span>
                                    <div><span class="font-medium">{{ $customer->email ?? '-' }}</span></div>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                                    <div>
                                        @if($customer->phone)
                                            <span class="badge badge-outline-primary">{{ $customer->phone }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">Google ID:</span>
                                    <div>
                                        @if($customer->google_id)
                                            <span class="badge badge-outline-secondary text-xs break-all" style="white-space: normal;">{{ $customer->google_id }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">تاريخ التسجيل:</span>
                                    <div class="text-sm font-medium">{{ $customer->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t">
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-warning flex-1">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    تعديل
                                </a>
                                <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" class="flex-1"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا العميل؟ سيتم فقدان كل بياناته وحساب تسجيل الدخول الخاص به.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger w-full">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($customers->hasPages())
                    <div class="mt-6">
                        {{ $customers->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">لا يوجد عملاء حالياً</p>
                        <p class="text-sm">لم يسجل أي عميل عبر التطبيق حتى الآن.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
