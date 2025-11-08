<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إدارة المستخدمين</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إضافة مستخدم جديد
                </a>
            </div>
        </div>

        <!-- فلاتر البحث -->
        <div class="panel mb-5">
            <form method="GET" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium mb-2">البحث</label>
                    <input type="text" id="search" name="search" value="{{ request('search') }}"
                           class="form-input" placeholder="ابحث بالاسم، الهاتف، الكود، أو البريد...">
                </div>
                <div class="sm:w-48">
                    <label for="role" class="block text-sm font-medium mb-2">نوع المستخدم</label>
                    <select id="role" name="role" class="form-select">
                        <option value="">جميع الأنواع</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>مدير</option>
                        <option value="supplier" {{ request('role') === 'supplier' ? 'selected' : '' }}>مجهز</option>
                        <option value="delegate" {{ request('role') === 'delegate' ? 'selected' : '' }}>مندوب</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        بحث
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        إعادة تعيين
                    </a>
                </div>
            </form>
        </div>

        <!-- كاردات المستخدمين -->
        <div class="panel">
            @if($users->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($users as $user)
                        <div class="panel">
                            <!-- Header -->
                            <div class="flex items-center justify-between mb-4 pb-4 border-b">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center">
                                        <span class="text-xl font-bold text-primary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-lg">{{ $user->name }}</div>
                                        @if($user->role === 'admin')
                                            <span class="badge badge-danger text-xs">مدير</span>
                                        @elseif($user->role === 'supplier')
                                            <span class="badge badge-warning text-xs">مجهز</span>
                                        @elseif($user->role === 'delegate')
                                            <span class="badge badge-info text-xs">مندوب</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                                    <div class="font-medium font-mono">{{ $user->phone }}</div>
                                </div>

                                @if($user->code)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">الكود:</span>
                                    <div><span class="badge badge-outline-primary">{{ $user->code }}</span></div>
                                </div>
                                @endif

                                @if($user->role === 'delegate' && $user->page_name)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">اسم البيج:</span>
                                    <div><span class="badge badge-outline-primary">{{ $user->page_name }}</span></div>
                                </div>
                                @endif

                                @if($user->email)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">البريد الإلكتروني:</span>
                                    <div class="text-sm break-all">{{ $user->email }}</div>
                                </div>
                                @endif

                                @if($user->warehouses->count() > 0)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">المخازن المخصصة:</span>
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach($user->warehouses as $warehouse)
                                            <span class="badge badge-outline-primary text-xs">{{ $warehouse->name }}</span>
                                        @endforeach
                                    </div>
                                </div>
                                @else
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">المخازن المخصصة:</span>
                                    <div class="text-sm text-gray-400">-</div>
                                </div>
                                @endif

                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                                    <div class="text-sm">{{ $user->created_at->format('Y-m-d H:i') }}</div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 mt-4 pt-4 border-t">
                                <a href="{{ route('admin.users.edit', $user) }}?back_url={{ urlencode(request()->fullUrl()) }}" class="btn btn-sm btn-warning flex-1">
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    تعديل
                                </a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="flex-1"
                                          onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger w-full">
                                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            حذف
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-secondary flex-1 cursor-not-allowed" title="لا يمكن حذف نفسك" disabled>
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        محمي
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($users->hasPages())
                    <div class="mt-6">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <p class="text-lg font-medium">لا يوجد مستخدمين</p>
                        <p class="text-sm">ابدأ بإضافة مستخدم جديد</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layout.admin>
