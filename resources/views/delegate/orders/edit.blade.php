<x-layout.default>
    <div class="panel">
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل الطلب: {{ $order->order_number }}</h5>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <a href="{{ route('delegate.orders.show', $order) }}" class="btn btn-outline-secondary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للطلب
                </a>
            </div>
        </div>

        <form method="POST" action="{{ route('delegate.orders.update', $order) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- معلومات الزبون -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="panel">
                    <h6 class="text-lg font-semibold dark:text-white-light mb-4">معلومات الزبون</h6>

                    <div class="space-y-4">
                        <div>
                            <label for="customer_name" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                اسم الزبون
                            </label>
                            <input type="text" id="customer_name" name="customer_name"
                                   class="form-input"
                                   value="{{ old('customer_name', $order->customer_name) }}"
                                   required>
                            @error('customer_name')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                رقم الهاتف
                            </label>
                            <input type="text" id="customer_phone" name="customer_phone"
                                   class="form-input"
                                   value="{{ old('customer_phone', $order->customer_phone) }}"
                                   required>
                            @error('customer_phone')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_address" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                العنوان
                            </label>
                            <textarea id="customer_address" name="customer_address"
                                      class="form-textarea" rows="3" required>{{ old('customer_address', $order->customer_address) }}</textarea>
                            @error('customer_address')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="customer_social_link" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                رابط السوشل ميديا
                            </label>
                            <input type="url" id="customer_social_link" name="customer_social_link"
                                   class="form-input"
                                   value="{{ old('customer_social_link', $order->customer_social_link) }}"
                                   required>
                            @error('customer_social_link')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="mb-3 block text-sm font-medium text-black dark:text-white">
                                ملاحظات
                            </label>
                            <textarea id="notes" name="notes"
                                      class="form-textarea" rows="3">{{ old('notes', $order->notes) }}</textarea>
                            @error('notes')
                                <div class="mt-1 text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- تفاصيل الطلب -->
                <div class="panel">
                    <h6 class="text-lg font-semibold dark:text-white-light mb-4">تفاصيل الطلب</h6>

                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500">رقم الطلب:</span>
                            <span class="font-semibold">{{ $order->order_number }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">تاريخ الطلب:</span>
                            <span class="font-semibold">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">الحالة:</span>
                            <span class="badge badge-outline-warning">{{ $order->status }}</span>
                        </div>

                        <div class="flex justify-between">
                            <span class="text-gray-500">إجمالي الطلب:</span>
                            <span class="font-semibold text-success">{{ number_format($order->total_amount, 0) }} د.ع</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- منتجات الطلب -->
            <div class="panel">
                <h6 class="text-lg font-semibold dark:text-white-light mb-4">منتجات الطلب</h6>

                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800">
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">المنتج</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">القياس</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">الكمية</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">السعر</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">المجموع</th>
                                <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($order->items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($item->product && $item->product->primaryImage)
                                                <img src="{{ $item->product->primaryImage->image_url }}"
                                                     alt="{{ $item->product_name }}"
                                                     class="w-12 h-12 object-cover rounded">
                                            @endif
                                            <div>
                                                <div class="font-medium">{{ $item->product_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $item->product_code }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge badge-outline-primary">{{ $item->size_name }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    onclick="decrementQuantity({{ $index }})"
                                                    class="btn btn-sm btn-outline-danger">-</button>
                                            <input type="number"
                                                   name="items[{{ $index }}][quantity]"
                                                   value="{{ $item->quantity }}"
                                                   min="1"
                                                   class="form-input w-20 text-center"
                                                   onchange="updateSubtotal({{ $index }})">
                                            <button type="button"
                                                    onclick="incrementQuantity({{ $index }})"
                                                    class="btn btn-sm btn-outline-success">+</button>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-medium">{{ number_format($item->unit_price, 0) }} د.ع</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-semibold text-success" id="subtotal-{{ $index }}">
                                            {{ number_format($item->subtotal, 0) }} د.ع
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button type="button"
                                                onclick="removeItem({{ $index }})"
                                                class="btn btn-sm btn-outline-danger">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            حذف
                                        </button>
                                    </td>
                                </tr>

                                <!-- Hidden inputs -->
                                <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                                <input type="hidden" name="items[{{ $index }}][size_id]" value="{{ $item->size_id }}">
                                <input type="hidden" name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}">
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- أزرار العمل -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('delegate.orders.show', $order) }}" class="btn btn-outline-secondary">
                    إلغاء
                </a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>

    <script>
        const orderItems = @json($order->items->map(function($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'size_id' => $item->size_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal
            ];
        }));

        function decrementQuantity(index) {
            const input = document.querySelector(`input[name="items[${index}][quantity]"]`);
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
                updateSubtotal(index);
            }
        }

        function incrementQuantity(index) {
            const input = document.querySelector(`input[name="items[${index}][quantity]"]`);
            const currentValue = parseInt(input.value);
            input.value = currentValue + 1;
            updateSubtotal(index);
        }

        function updateSubtotal(index) {
            const quantity = parseInt(document.querySelector(`input[name="items[${index}][quantity]"]`).value);
            const unitPrice = orderItems[index].unit_price;
            const subtotal = quantity * unitPrice;

            document.getElementById(`subtotal-${index}`).textContent =
                new Intl.NumberFormat('ar-IQ').format(subtotal) + ' د.ع';
        }

        function removeItem(index) {
            if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                const row = document.querySelector(`input[name="items[${index}][quantity]"]`).closest('tr');
                row.remove();
            }
        }
    </script>
</x-layout.default>
