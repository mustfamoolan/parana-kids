<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعيين المستخدمين للمخزن: {{ $warehouse->name }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للمخزن
                </a>
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

        <form method="POST" action="{{ route('admin.warehouses.update-users', $warehouse) }}" class="space-y-5">
            @csrf

            <div class="panel">
                <div class="mb-5">
                    <h6 class="text-lg font-semibold dark:text-white-light">اختيار المستخدمين</h6>
                    <p class="text-gray-500 dark:text-gray-400">اختر المستخدمين الذين تريد منحهم صلاحية الوصول لهذا المخزن</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($users as $user)
                        <div class="panel">
                            <div class="flex items-center gap-3 mb-3">
                                <input
                                    type="checkbox"
                                    id="user_{{ $user->id }}"
                                    name="users[{{ $user->id }}][user_id]"
                                    value="{{ $user->id }}"
                                    class="form-checkbox"
                                    @if($assignedUsers->contains($user->id)) checked @endif
                                    onchange="toggleUserCard(this)"
                                >
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary/20 to-primary/10 flex items-center justify-center">
                                    <span class="text-sm font-bold text-primary">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                </div>
                                <div class="flex-1">
                                    <label for="user_{{ $user->id }}" class="font-semibold text-black dark:text-white cursor-pointer block">
                                        {{ $user->name }}
                                    </label>
                                    <span class="badge badge-outline-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'supplier' ? 'warning' : 'info') }} text-xs">
                                        @if($user->role === 'admin')
                                            مدير
                                        @elseif($user->role === 'supplier')
                                            مجهز
                                        @else
                                            مندوب
                                        @endif
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">رقم الهاتف:</span>
                                    <div class="font-medium font-mono text-sm">{{ $user->phone }}</div>
                                </div>
                                <div class="border-t pt-2 mt-2">
                                    <label class="flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            id="can_manage_{{ $user->id }}"
                                            name="users[{{ $user->id }}][can_manage]"
                                            value="1"
                                            class="form-checkbox"
                                            @if($assignedUsers->contains($user->id) && $assignedUsers->where('id', $user->id)->first()->pivot->can_manage) checked @endif
                                        >
                                        <span class="mr-2 text-sm text-gray-600 dark:text-gray-400">صلاحية الإدارة</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <script>
                    function toggleUserCard(checkbox) {
                        const card = checkbox.closest('.panel');
                        const manageCheckbox = card.querySelector('input[name*="[can_manage]"]');
                        if (!checkbox.checked && manageCheckbox) {
                            manageCheckbox.checked = false;
                        }
                    }
                </script>
            </div>

            <div class="flex items-center justify-end gap-4 pt-5">
                <a href="{{ route('admin.warehouses.show', $warehouse) }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</x-layout.admin>
