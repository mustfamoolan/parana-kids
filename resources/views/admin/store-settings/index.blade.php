<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">إعدادات تطبيق المتجر المتنقل (Mobile App)</h5>
        </div>

        @if(session('success'))
            <div class="flex items-center p-3.5 rounded text-success bg-success-light dark:bg-success-dark-light mb-5">
                <span class="ltr:pr-2 rtl:pl-2">
                    <strong class="ltr:mr-1 rtl:ml-1">{{ session('success') }}</strong>
                </span>
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-center p-3.5 rounded text-danger bg-danger-light dark:bg-danger-dark-light mb-5">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel">
            <form action="{{ route('admin.store-settings.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <!-- 1. صور شاشات الدخول (Login) -->
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4 text-primary">① صور شاشات تسجيل الدخول</h6>
                    <p class="text-sm text-gray-500 mb-4">هذه الصور ستظهر للمستخدم في شاشة تسجيل الدخول بالتطبيق عند تمريره، يرجى رفع صور عالية الجودة بشكل طولي (Portrait).</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @for($i = 1; $i <= 4; $i++)
                            <div class="border border-gray-200 dark:border-gray-700 p-4 rounded-lg flex flex-col items-center">
                                <label class="w-full text-center mb-2 font-semibold">الصورة رقم {{ $i }}</label>
                                <div class="w-full h-48 bg-gray-100 dark:bg-gray-800 rounded-md mb-4 flex items-center justify-center overflow-hidden">
                                    @if(isset($loginImages[$i]) && $loginImages[$i] != '')
                                        <img src="{{ Storage::url($loginImages[$i]) }}" alt="Login Image {{ $i }}" class="h-full object-cover">
                                    @else
                                        <span class="text-gray-400">لا توجد صورة</span>
                                    @endif
                                </div>
                                <input type="file" name="login_image_{{ $i }}" accept="image/*" class="form-input form-input-sm w-full">
                            </div>
                        @endfor
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-dark" />

                <!-- 2. سلايدر الرئيسية (Home Slider) -->
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4 text-primary">② صور البنرات المتحركة (الرئيسية)</h6>
                    <p class="text-sm text-gray-500 mb-4">ارفع ما يصل إلى 4 صور لعرضها كسلايدر في أعلى الصفحة الرئيسية. يفضل أن تكون المستطيلة (Landscape) متناسبة مع شاشة الموبايل (مثلاً 16:9 بنسبة 800x450 بكسل).</p>
                    
                    <div class="mb-4">
                        <label for="slider_images" class="font-semibold mb-2 block">رفع جميع الصور معاً (تصل إلى 4 صور):</label>
                        <input type="file" id="slider_images" name="slider_images[]" accept="image/*" multiple class="form-input">
                        <p class="text-xs text-danger mt-1">ملاحظة: رفع صور جديدة سيقوم بمسح الصور القديمة بشكل كامل واستبدالها.</p>
                    </div>

                    @if(isset($sliderImages) && is_array($sliderImages) && count($sliderImages) > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
                            @foreach($sliderImages as $index => $sliderImage)
                                <div class="border border-gray-200 p-2 rounded-md">
                                    <div class="h-24 bg-gray-100 overflow-hidden flex items-center justify-center rounded">
                                        <img src="{{ Storage::url($sliderImage) }}" class="w-full h-full object-cover" alt="Slider">
                                    </div>
                                    <p class="text-center text-xs mt-2 text-gray-400">سلايدر {{ $index + 1 }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <hr class="my-6 border-white-light dark:border-dark" />

                <!-- 3. بانر الإعلان (Announcement Banner) -->
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4 text-primary">③ الإعلان التسويقي (تحت الأصناف)</h6>
                    <p class="text-sm text-gray-500 mb-4">هذا البانر يظهر في منتصف الصفحة الرئيسية للتطبيق لعرض خصم، أو تشكيلة جديدة.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="mb-4">
                                <label for="announcement_title">عنوان الإعلان الأساسي</label>
                                <input type="text" id="announcement_title" name="announcement_title" value="{{ old('announcement_title', $announcementTitle) }}" class="form-input" placeholder="مثال: خصومات الصيف 50%">
                            </div>
                            
                            <div class="mb-4">
                                <label for="announcement_subtitle">الوصف الفرعي للإعلان</label>
                                <input type="text" id="announcement_subtitle" name="announcement_subtitle" value="{{ old('announcement_subtitle', $announcementSubtitle) }}" class="form-input" placeholder="مثال: تسوق الآن أحدث التشكيلات">
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block">صورة الإعلان الحالية (مربعة أو مستطيلة قليلاً)</label>
                            <div class="flex items-center gap-4">
                                <div class="w-32 h-32 bg-gray-100 dark:bg-gray-800 rounded-md flex items-center justify-center overflow-hidden border">
                                    @if(isset($announcementImage) && $announcementImage != '')
                                        <img src="{{ Storage::url($announcementImage) }}" alt="Announcement" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-gray-400 text-sm">لا توجد صورة</span>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="announcement_image" accept="image/*" class="form-input w-full">
                                    <p class="text-xs text-info mt-2">اختر صورة جديدة إذا كنت ترغب بتغييرها.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-6 border-white-light dark:border-dark" />

                <!-- 4. المخازن المتاحة للعملاء (Customer Allowed Warehouses) -->
                <div class="mb-5">
                    <h6 class="text-lg font-semibold mb-4 text-primary">④ المخازن المسموحة لعملاء التطبيق (الكستمر)</h6>
                    <p class="text-sm text-gray-500 mb-4">
                        العملاء سيرون فقط منتجات المخازن المحددة، ويمكنهم الطلب منها. (هذا الإعداد ينطبق على جميع العملاء بشكل عام، ولا يؤثر على المندوبين).
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        @if($warehouses->isEmpty())
                            <div class="col-span-full text-center text-gray-500 py-4">لا توجد مخازن مضافة في النظام حالياً.</div>
                        @else
                            @foreach($warehouses as $warehouse)
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="customer_allowed_warehouses[]" value="{{ $warehouse->id }}" class="form-checkbox text-primary rounded border-gray-300" 
                                        {{ in_array($warehouse->id, old('customer_allowed_warehouses', $customerAllowedWarehouses ?? [])) ? 'checked' : '' }}>
                                    <span class="ltr:ml-2 rtl:mr-2 text-sm">{{ $warehouse->name }}</span>
                                </label>
                            @endforeach
                        @endif
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="btn btn-primary w-full sm:w-auto">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        حفظ جميع التغييرات وتطبيقها مباشرة
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layout.admin>
