<x-layout.default>
    <div class="container mx-auto px-4 py-6">
        <h1 class="text-2xl font-bold mb-6">الطلبات المؤرشفة</h1>

        @if($archivedOrders->count() > 0)
            <div class="space-y-4">
                @foreach($archivedOrders as $archived)
                    <div class="panel">
                        <div class="flex flex-col md:flex-row justify-between gap-4">
                            <div class="flex-1">
                                <h5 class="font-bold mb-2 text-black dark:text-white">{{ $archived->customer_name }}</h5>
                                <p class="text-sm text-gray-700 dark:text-gray-300">الهاتف: {{ $archived->customer_phone }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">المنتجات: {{ count($archived->items) }}</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">الإجمالي: {{ number_format($archived->total_amount, 0) }} د.ع</p>
                                <p class="text-xs text-gray-500">أرشف في: {{ $archived->archived_at->diffForHumans() }}</p>
                            </div>
                            <div class="flex gap-2 items-start">
                                <form method="POST" action="{{ route('delegate.archived.restore', $archived) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                        </svg>
                                        استرجاع
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('delegate.archived.destroy', $archived) }}"
                                      onsubmit="return confirm('هل تريد حذف هذا الطلب نهائياً؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        حذف
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- عرض تفاصيل المنتجات -->
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <h6 class="font-semibold mb-2 text-black dark:text-white">المنتجات:</h6>
                            <div class="space-y-2">
                                @foreach($archived->items as $item)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-700 dark:text-gray-300">
                                            {{ $item['product_name'] }} ({{ $item['size_name'] }}) × {{ $item['quantity'] }}
                                        </span>
                                        <span class="font-medium text-black dark:text-white">
                                            {{ number_format($item['subtotal'], 0) }} د.ع
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $archivedOrders->links() }}
            </div>
        @else
            <div class="panel text-center py-10">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                <p class="text-gray-500">لا توجد طلبات مؤرشفة</p>
                <a href="{{ route('delegate.dashboard') }}" class="btn btn-primary mt-4">
                    العودة للرئيسية
                </a>
            </div>
        @endif
    </div>
</x-layout.default>

