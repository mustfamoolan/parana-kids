<x-layout.admin>
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">عرض الطلب الحالي</h1>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة للمنتجات
            </a>
        </div>

        <!-- Customer Info Panel -->
        <div class="panel mb-5">
            <h5 class="font-bold text-lg mb-4">معلومات الزبون</h5>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <span class="text-gray-500">الاسم:</span>
                    <p class="font-medium">{{ $customerData['customer_name'] }}</p>
                </div>
                <div>
                    <span class="text-gray-500">الهاتف:</span>
                    <p class="font-medium">{{ $customerData['customer_phone'] }}</p>
                </div>
                @if(!empty($customerData['customer_phone2']))
                <div>
                    <span class="text-gray-500">الهاتف الثاني:</span>
                    <p class="font-medium">{{ $customerData['customer_phone2'] }}</p>
                </div>
                @endif
                <div class="md:col-span-2">
                    <span class="text-gray-500">العنوان:</span>
                    <p class="font-medium">{{ $customerData['customer_address'] }}</p>
                </div>
                <div class="md:col-span-2">
                    <span class="text-gray-500">رابط التواصل:</span>
                    @php
                        $socialLink = $customerData['customer_social_link'];
                        // إضافة http:// إذا لم يكن الرابط يحتوي على بروتوكول
                        if (!empty($socialLink) && !preg_match('/^https?:\/\//', $socialLink)) {
                            $socialLink = 'https://' . $socialLink;
                        }
                    @endphp
                    @if(!empty($customerData['customer_social_link']))
                        <a href="{{ $socialLink }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="font-medium text-primary hover:text-primary-dark hover:underline inline-flex items-center gap-1">
                            {{ $customerData['customer_social_link'] }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    @else
                        <p class="font-medium text-gray-400">-</p>
                    @endif
                </div>
                @if(!empty($customerData['notes']))
                <div class="md:col-span-2">
                    <span class="text-gray-500">ملاحظات:</span>
                    <p class="font-medium">{{ $customerData['notes'] }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Cart Items Panel -->
        <div class="panel">
            <div class="flex items-center justify-between mb-4">
                <h5 class="font-bold text-lg">المنتجات في السلة</h5>
                <span class="badge bg-primary">{{ $cart->items->count() }} منتج</span>
            </div>

            @if($cart->items->count() > 0)
                <!-- Products Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">
                    @foreach($cart->items as $item)
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow p-4">
                            <!-- Product Image -->
                            <div class="mb-3">
                                <img src="{{ $item->product->primaryImage->image_url ?? '/assets/images/no-image.png' }}"
                                     class="w-full h-48 object-cover rounded-lg"
                                     alt="{{ $item->product->name }}">
                            </div>

                            <!-- Product Info -->
                            <div class="mb-3">
                                <h6 class="font-bold text-base dark:text-white-light mb-1 line-clamp-2">{{ $item->product->name }}</h6>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mb-2">{{ $item->product->code }}</p>
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="badge badge-outline-primary text-sm font-semibold">{{ $item->size->size_name }}</span>
                                </div>
                            </div>

                            <!-- Price and Quantity -->
                            <div class="space-y-3 mb-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">السعر:</span>
                                    <span class="font-semibold text-primary">{{ number_format($item->price, 0) }} د.ع</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">الكمية:</span>
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                                onclick="decrementQuantity({{ $item->id }}, {{ $item->quantity }}, {{ $item->size->available_quantity + $item->quantity }})"
                                                class="btn btn-sm btn-outline-danger">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                        </button>
                                        <input type="number"
                                               id="qty-{{ $item->id }}"
                                               value="{{ $item->quantity }}"
                                               min="1"
                                               max="{{ $item->size->available_quantity + $item->quantity }}"
                                               class="form-input w-20 text-center"
                                               onchange="updateQuantity({{ $item->id }}, this.value, {{ $item->size->available_quantity + $item->quantity }})">
                                        <button type="button"
                                                onclick="incrementQuantity({{ $item->id }}, {{ $item->quantity }}, {{ $item->size->available_quantity + $item->quantity }})"
                                                class="btn btn-sm btn-outline-success">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border-t pt-2">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">الإجمالي:</span>
                                    <span class="text-lg font-bold text-success">{{ number_format($item->subtotal, 0) }} د.ع</span>
                                </div>
                            </div>

                            <!-- Delete Button -->
                            <button type="button"
                                    onclick="deleteItem({{ $item->id }})"
                                    class="btn btn-outline-danger w-full btn-sm">
                                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                حذف
                            </button>
                        </div>
                    @endforeach
                </div>

                <!-- Total Amount -->
                <div class="panel bg-success-light dark:bg-success/20 border-2 border-success mb-5">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-bold text-gray-700 dark:text-gray-300">المجموع الكلي:</span>
                        <span class="text-3xl font-bold text-success">{{ number_format($cart->total_amount, 0) }} د.ع</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end mt-5 pt-5 border-t">
                    <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        إضافة منتجات
                    </a>
                    <button type="button" onclick="submitOrder()" class="btn btn-success">
                        <svg class="w-5 h-5 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        إرسال الطلب
                    </button>
                </div>
            @else
                <div class="text-center py-10">
                    <svg class="w-20 h-20 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-gray-500 mb-4">السلة فارغة</p>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-primary">
                        إضافة منتجات
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script>
        function updateQuantity(itemId, newQuantity, maxQuantity) {
            newQuantity = parseInt(newQuantity);
            if (newQuantity < 1) {
                Swal.fire('خطأ', 'الكمية يجب أن تكون 1 على الأقل', 'error');
                document.getElementById(`qty-${itemId}`).value = 1;
                return;
            }
            if (maxQuantity && newQuantity > maxQuantity) {
                Swal.fire('خطأ', `الكمية المتوفرة: ${maxQuantity}`, 'error');
                document.getElementById(`qty-${itemId}`).value = maxQuantity;
                return;
            }

            fetch(`{{ url('/admin/cart-items') }}/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ quantity: newQuantity })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم!',
                        text: 'تم تحديث الكمية',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    Swal.fire('خطأ', data.message || 'حدث خطأ أثناء التحديث', 'error');
                }
            })
            .catch(error => {
                Swal.fire('خطأ', 'حدث خطأ أثناء التحديث', 'error');
            });
        }

        function incrementQuantity(itemId, currentQuantity, maxQuantity) {
            const newQuantity = parseInt(currentQuantity) + 1;
            if (maxQuantity && newQuantity > maxQuantity) {
                Swal.fire('خطأ', `الكمية المتوفرة: ${maxQuantity}`, 'error');
                return;
            }
            updateQuantity(itemId, newQuantity, maxQuantity);
        }

        function decrementQuantity(itemId, currentQuantity, maxQuantity) {
            const newQuantity = parseInt(currentQuantity) - 1;
            if (newQuantity < 1) {
                Swal.fire('خطأ', 'الكمية يجب أن تكون 1 على الأقل', 'error');
                return;
            }
            updateQuantity(itemId, newQuantity, maxQuantity);
        }

        function deleteItem(itemId) {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم حذف هذا المنتج من السلة',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ url('/admin/cart-items') }}/${itemId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('تم!', 'تم حذف المنتج', 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            Swal.fire('خطأ', data.message || 'حدث خطأ', 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('خطأ', 'حدث خطأ أثناء الحذف', 'error');
                    });
                }
            });
        }

        function submitOrder() {
            // نموذج مخفي لإرسال الطلب
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.orders.create.submit") }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-layout.admin>

