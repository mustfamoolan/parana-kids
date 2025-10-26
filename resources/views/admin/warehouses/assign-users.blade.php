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

                <div class="space-y-4">
                    @foreach($users as $user)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4 rtl:space-x-reverse">
                                <input
                                    type="checkbox"
                                    id="user_{{ $user->id }}"
                                    name="users[{{ $user->id }}][user_id]"
                                    value="{{ $user->id }}"
                                    class="form-checkbox"
                                    @if($assignedUsers->contains($user->id)) checked @endif
                                >
                                <div>
                                    <label for="user_{{ $user->id }}" class="font-medium text-black dark:text-white">
                                        {{ $user->name }}
                                    </label>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <span class="badge badge-outline-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'supplier' ? 'warning' : 'info') }}">
                                            @if($user->role === 'admin')
                                                مدير
                                            @elseif($user->role === 'supplier')
                                                مجهز
                                            @else
                                                مندوب
                                            @endif
                                        </span>
                                        - {{ $user->phone }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        name="users[{{ $user->id }}][can_manage]"
                                        value="1"
                                        class="form-checkbox"
                                        @if($assignedUsers->contains($user->id) && $assignedUsers->where('id', $user->id)->first()->pivot->can_manage) checked @endif
                                    >
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">صلاحية الإدارة</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
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
