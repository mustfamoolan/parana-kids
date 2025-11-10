<div class="border rounded-lg p-4" data-product-id="{{ $product->id }}" data-product='{"id": {{ $product->id }}, "image_url": "{{ $product->image_url }}", "product_link": "{{ $product->product_link }}", "price_yuan": {{ $product->price_yuan }}, "available_sizes": {{ json_encode($product->available_sizes) }}}'>
    <div class="mb-3">
        <img src="{{ $product->image_url }}" alt="صورة المنتج" class="w-full h-48 object-cover rounded">
    </div>
    <div class="mb-2">
        <a href="{{ $product->product_link }}" target="_blank" class="text-primary text-sm break-all">{{ $product->product_link }}</a>
    </div>
    <div class="mb-2">
        <strong>السعر:</strong> {{ number_format($product->price_yuan, 2) }} ¥
    </div>
    <div class="mb-3">
        <strong>القياسات المتوفرة:</strong>
        <div class="flex flex-wrap gap-1 mt-1">
            @foreach($product->available_sizes as $size)
                <span class="badge bg-primary">{{ $size }}</span>
            @endforeach
        </div>
    </div>
    <div class="flex gap-2">
        <button onclick="addToInvoice({{ $product->id }})" class="btn btn-primary btn-sm flex-1">
            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            إضافة للفاتورة
        </button>
        <button onclick="deleteProduct({{ $product->id }})" class="btn btn-outline-danger btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>
</div>

