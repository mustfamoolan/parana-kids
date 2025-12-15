<x-layout.admin>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">دفتر تلفونات</h5>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-5">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- نموذج إضافة شخص جديد -->
        <div class="mb-5 panel">
            <h6 class="text-base font-semibold mb-4">إضافة شخص جديد</h6>
            <form method="POST" action="{{ route('admin.phone-book.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">اسم الشخص</label>
                        <input type="text" name="name" class="form-input" placeholder="اسم الشخص" required>
                    </div>
                    <div>
                        <label class="form-label">رقم الهاتف</label>
                        <input type="text" name="phone_number" class="form-input" placeholder="رقم الهاتف" required>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            إضافة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- البحث -->
        <div class="mb-5">
            <form method="GET" action="{{ route('admin.phone-book.index') }}" class="flex gap-4">
                <div class="flex-1">
                    <input
                        type="text"
                        name="search"
                        class="form-input"
                        placeholder="ابحث بالاسم أو رقم الهاتف..."
                        value="{{ request('search') }}"
                    >
                </div>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    بحث
                </button>
                @if(request('search'))
                    <a href="{{ route('admin.phone-book.index') }}" class="btn btn-outline-secondary">
                        <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        مسح
                    </a>
                @endif
            </form>
        </div>

        <!-- قائمة الأشخاص -->
        <div class="space-y-4">
            @forelse($contacts as $contact)
                <div class="panel">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <h6 class="text-lg font-semibold mb-2">{{ $contact->name }}</h6>
                            <div class="space-y-3">
                                @foreach($contact->phoneNumbers as $phoneNumber)
                                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                        <!-- رقم الهاتف -->
                                        <div class="mb-3">
                                            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ $phoneNumber->phone_number }}</span>
                                        </div>
                                        <!-- الأزرار -->
                                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            <!-- زر واتساب -->
                                            <a
                                                href="https://wa.me/{{ formatPhoneForWhatsApp($phoneNumber->phone_number) }}"
                                                target="_blank"
                                                class="btn btn-success w-full flex items-center justify-center gap-2 py-3"
                                                title="واتساب"
                                            >
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                                </svg>
                                                <span class="hidden sm:inline">واتساب</span>
                                            </a>
                                            <!-- زر اتصال -->
                                            <a
                                                href="tel:{{ $phoneNumber->phone_number }}"
                                                class="btn btn-primary w-full flex items-center justify-center gap-2 py-3"
                                                title="اتصال"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                </svg>
                                                <span class="hidden sm:inline">اتصال</span>
                                            </a>
                                            <!-- زر نسخ -->
                                            <button
                                                type="button"
                                                onclick="copyPhoneNumber('{{ $phoneNumber->phone_number }}')"
                                                class="btn btn-info w-full flex items-center justify-center gap-2 py-3"
                                                title="نسخ"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                                <span class="hidden sm:inline">نسخ</span>
                                            </button>
                                            <!-- زر حذف الرقم -->
                                            <form method="POST" action="{{ route('admin.phone-book.delete-phone', $phoneNumber->id) }}" class="w-full" onsubmit="return confirm('هل أنت متأكد من حذف هذا الرقم؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger w-full flex items-center justify-center gap-2 py-3" title="حذف">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    <span class="hidden sm:inline">حذف</span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <!-- نموذج إضافة رقم جديد -->
                            <div class="mt-4">
                                <form method="POST" action="{{ route('admin.phone-book.add-phone', $contact->id) }}" class="flex flex-col sm:flex-row gap-2">
                                    @csrf
                                    <input type="text" name="phone_number" class="form-input flex-1" placeholder="إضافة رقم جديد" required>
                                    <button type="submit" class="btn btn-outline-primary py-3 px-4">
                                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        إضافة رقم
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="mt-4 sm:mt-0">
                            <!-- زر حذف الشخص -->
                            <form method="POST" action="{{ route('admin.phone-book.delete-contact', $contact->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا الشخص وجميع أرقامه؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-full sm:w-auto py-3 px-4">
                                    <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    حذف الشخص
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="panel text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">لا توجد أرقام في دفتر التلفونات</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-5">
            {{ $contacts->links() }}
        </div>
    </div>

    <script>
        function copyPhoneNumber(phoneNumber) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(phoneNumber).then(function() {
                    showCopyNotification('تم نسخ الرقم بنجاح');
                }, function() {
                    fallbackCopyTextToClipboard(phoneNumber);
                });
            } else {
                fallbackCopyTextToClipboard(phoneNumber);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyNotification('تم نسخ الرقم بنجاح');
                } else {
                    showCopyNotification('فشل نسخ الرقم', 'error');
                }
            } catch (err) {
                showCopyNotification('فشل نسخ الرقم', 'error');
            }
            document.body.removeChild(textArea);
        }

        function showCopyNotification(message, type = 'success') {
            // استخدام SweetAlert إذا كان متاحاً
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type === 'success' ? 'success' : 'error',
                    title: message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            } else {
                // Fallback إلى alert عادي
                alert(message);
            }
        }
    </script>
</x-layout.admin>

