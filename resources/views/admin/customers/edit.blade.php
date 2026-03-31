<x-layout.admin>
    <div>
        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل بيانات العميل</h5>
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-primary whitespace-nowrap">
                العودة للقائمة
            </a>
        </div>

        <div class="panel">
            <div class="mb-5">
                <form action="{{ route('admin.customers.update', $customer) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Name -->
                        <div>
                            <label for="name">اسم العميل <span class="text-danger">*</span></label>
                            <input id="name" type="text" name="name" class="form-input" required
                                value="{{ old('name', $customer->name) }}" placeholder="أدخل اسم العميل" />
                            @error('name')
                                <span class="text-danger text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email">البريد الإلكتروني <span class="text-gray-400 text-xs">(مستورد من Google)</span></label>
                            <input id="email" type="email" name="email" class="form-input" 
                                value="{{ old('email', $customer->email) }}" placeholder="example@gmail.com" />
                            @error('email')
                                <span class="text-danger text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone">رقم الهاتف <span class="text-gray-400 text-xs">(اختياري)</span></label>
                            <input id="phone" type="text" name="phone" class="form-input"
                                value="{{ old('phone', $customer->phone) }}" placeholder="07XXXXXXXXX" />
                            @error('phone')
                                <span class="text-danger text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Google ID (Read Only) -->
                        <div>
                            <label for="google_id">مُعرف Google ID <span class="text-gray-400 text-xs">(للقراءة فقط)</span></label>
                            <input id="google_id" type="text" class="form-input bg-gray-100 dark:bg-gray-800 text-gray-500"
                                value="{{ $customer->google_id }}" disabled />
                        </div>
                    </div>

                    <div class="mt-8 flex items-center gap-4">
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layout.admin>
