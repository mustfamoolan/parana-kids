<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">روابط المنتجات</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('admin.product-links.create') }}" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    إنشاء رابط جديد
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

        @if($links->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($links as $link)
                    <div class="panel">
                        <div class="space-y-3">
                            <!-- الرابط -->
                            <div>
                                <label class="text-xs text-gray-500 dark:text-gray-400">الرابط:</label>
                                <div class="flex items-center gap-2 mt-1">
                                    <input
                                        type="text"
                                        id="link-{{ $link->id }}"
                                        value="{{ $link->full_url }}"
                                        readonly
                                        class="form-input flex-1 text-xs"
                                    >
                                    <button
                                        type="button"
                                        onclick="copyLink('link-{{ $link->id }}')"
                                        class="btn btn-outline-primary btn-sm"
                                        title="نسخ الرابط"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- المخزن -->
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">المخزن:</span>
                                <div class="font-semibold text-sm">
                                    @if($link->warehouse_id)
                                        {{ $link->warehouse->name }}
                                    @else
                                        <span class="badge badge-outline-info">كل المخازن</span>
                                    @endif
                                </div>
                            </div>

                            <!-- النوع -->
                            @if($link->gender_type)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">النوع:</span>
                                    <div>
                                        <span class="badge {{ $link->gender_type == 'boys' ? 'badge-outline-info' : ($link->gender_type == 'girls' ? 'badge-outline-pink' : ($link->gender_type == 'boys_girls' ? 'badge-outline-primary' : 'badge-outline-warning')) }} text-xs">
                                            @if($link->gender_type == 'boys')
                                                ولادي
                                            @elseif($link->gender_type == 'girls')
                                                بناتي
                                            @elseif($link->gender_type == 'boys_girls')
                                                ولادي بناتي
                                            @else
                                                اكسسوار
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            @endif

                            <!-- القياس -->
                            @if($link->size_name)
                                <div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">القياس:</span>
                                    <div><span class="badge badge-outline-primary text-xs">{{ $link->size_name }}</span></div>
                                </div>
                            @endif

                            <!-- تاريخ الإنشاء -->
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                                <div class="text-sm">{{ $link->created_at->format('Y-m-d H:i') }}</div>
                            </div>

                            <!-- منشئ الرابط -->
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">منشئ الرابط:</span>
                                <div class="text-sm">{{ $link->creator->name }}</div>
                            </div>

                            <!-- الأزرار -->
                            <div class="flex gap-2 pt-2 border-t">
                                <a
                                    href="{{ $link->full_url }}"
                                    target="_blank"
                                    class="btn btn-outline-info btn-sm flex-1"
                                >
                                    <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    عرض
                                </a>
                                <form
                                    action="{{ route('admin.product-links.destroy', $link->id) }}"
                                    method="POST"
                                    class="flex-1"
                                    onsubmit="return confirm('هل أنت متأكد من حذف هذا الرابط؟')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-full">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-5">
                {{ $links->links() }}
            </div>
        @else
            <div class="text-center py-10">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400">لا توجد روابط منشأة بعد</p>
                <a href="{{ route('admin.product-links.create') }}" class="btn btn-primary mt-4">
                    إنشاء رابط جديد
                </a>
            </div>
        @endif
    </div>

    <script>
        function copyLink(inputId) {
            const input = document.getElementById(inputId);
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            document.execCommand('copy');

            // Show notification
            const btn = input.nextElementSibling;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            btn.classList.add('btn-success');
            btn.classList.remove('btn-outline-primary');

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-primary');
            }, 2000);
        }
    </script>
</x-layout.admin>

