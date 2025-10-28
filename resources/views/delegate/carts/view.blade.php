<x-layout.default>
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold">عرض الطلب الحالي</h1>
            <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-secondary">
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
                <div class="md:col-span-2">
                    <span class="text-gray-500">العنوان:</span>
                    <p class="font-medium">{{ $customerData['customer_address'] }}</p>
                </div>
                <div class="md:col-span-2">
                    <span class="text-gray-500">رابط التواصل:</span>
                    <p class="font-medium">{{ $customerData['customer_social_link'] }}</p>
                </div>
            </div>
        </div>

        <!-- Cart Items Panel -->
        <div class="panel">
            <div class="flex items-center justify-between mb-4">
                <h5 class="font-bold text-lg">المنتجات في السلة</h5>
                <span class="badge bg-primary">{{ $cart->items->count() }} منتج</span>
            </div>

            @if($cart->items->count() > 0)
                <div class="table-responsive">
                    <table class="table-hover">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>القياس</th>
                                <th>الكمية</th>
                                <th>السعر</th>
                                <th>الإجمالي</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cart->items as $item)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <img src="{{ $item->product->primaryImage->image_url ?? '/assets/images/no-image.png' }}"
                                                 class="w-12 h-12 object-cover rounded">
                                            <div>
                                                <p class="font-medium">{{ $item->product->name }}</p>
                                                <p class="text-xs text-gray-500">{{ $item->product->code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $item->size->size_name }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <input type="number"
                                                   value="{{ $item->quantity }}"
                                                   min="1"
                                                   max="{{ $item->size->available_quantity + $item->quantity }}"
                                                   class="form-input w-20"
                                                   onchange="updateQuantity({{ $item->id }}, this.value)">
                                        </div>
                                    </td>
                                    <td>{{ number_format($item->price, 0) }} د.ع</td>
                                    <td class="font-bold">{{ number_format($item->subtotal, 0) }} د.ع</td>
                                    <td class="text-center">
                                        <button type="button"
                                                onclick="deleteItem({{ $item->id }})"
                                                class="btn btn-sm btn-outline-danger">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            حذف
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right font-bold text-lg">المجموع الكلي:</td>
                                <td colspan="2" class="font-bold text-success text-2xl">{{ number_format($cart->total_amount, 0) }} د.ع</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 justify-end mt-5 pt-5 border-t">
                    <a href="{{ route('delegate.products.all') }}" class="btn btn-outline-primary">
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
                    <a href="{{ route('delegate.products.all') }}" class="btn btn-primary">
                        إضافة منتجات
                    </a>
                </div>
            @endif
        </div>
    </div>

    <script>
        function updateQuantity(itemId, newQuantity) {
            if (newQuantity < 1) {
                Swal.fire('خطأ', 'الكمية يجب أن تكون 1 على الأقل', 'error');
                return;
            }

            fetch(`/delegate/cart-items/${itemId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ quantity: parseInt(newQuantity) })
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
                    fetch(`/delegate/cart-items/${itemId}`, {
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
            form.action = '{{ route("delegate.orders.submit") }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
</x-layout.default>

