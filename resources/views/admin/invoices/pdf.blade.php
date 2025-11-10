<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #333;
        }

        .product-page {
            page-break-after: always;
            page-break-inside: avoid;
            padding: 30px;
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .product-page:last-child {
            page-break-after: auto;
        }

        .product-image {
            max-width: 100%;
            width: auto;
            height: auto;
            margin: 0 auto 20px;
            display: block;
            border: 2px solid #ddd;
            object-fit: contain;
        }

        .product-info {
            text-align: center;
            margin-bottom: 15px;
            flex-shrink: 0;
        }

        .product-link {
            font-size: 11px;
            color: #0066cc;
            word-break: break-all;
            margin-bottom: 15px;
        }

        .product-link a {
            color: #0066cc;
            text-decoration: underline;
        }

        .sizes-section {
            margin: 15px 0;
            flex: 1;
            overflow: hidden;
        }

        .sizes-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        .sizes-list {
            list-style: none;
            display: block;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .sizes-list li {
            display: inline-block;
            margin: 5px;
        }

        .size-item {
            background: #f5f5f5;
            padding: 8px 15px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .size-label {
            font-weight: bold;
            margin-right: 5px;
        }


        @media print {
            .product-page {
                page-break-after: always;
            }

            .product-page:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    @php
        // تجميع العناصر حسب المنتج
        $groupedItems = [];
        if ($invoice->items && count($invoice->items) > 0) {
            foreach ($invoice->items as $item) {
                if ($item->invoiceProduct) {
                    $productId = $item->invoice_product_id;
                    if (!isset($groupedItems[$productId])) {
                        $groupedItems[$productId] = [
                            'product' => $item->invoiceProduct,
                            'items' => []
                        ];
                    }
                    $groupedItems[$productId]['items'][] = $item;
                }
            }
        }
    @endphp

    @if(count($groupedItems) > 0)
        @foreach($groupedItems as $productId => $group)
        @php
            $product = $group['product'];
            $items = $group['items'];
            $sizesCount = count($items);

            // حساب ارتفاع الصورة بناءً على عدد القياسات
            // كلما زاد عدد القياسات، تصغر الصورة
            // تصغير الصورة بنسبة 150% من الحجم الحالي (350% / 1.5 = 233%)
            $imageMaxHeight = max(350, 933 - ($sizesCount * 35));
        @endphp

        <div class="product-page">
            <div class="product-info">
                @if($product->image_url)
                    <img src="{{ $product->image_url }}" alt="Product Image" class="product-image" style="max-height: {{ $imageMaxHeight }}px;">
                @else
                    <div class="product-image" style="background: #f0f0f0; height: {{ $imageMaxHeight }}px; display: flex; align-items: center; justify-content: center;">
                        <span>No Image</span>
                    </div>
                @endif

                @if($product->code)
                <div class="product-code" style="text-align: center; margin-bottom: 15px; font-size: 16px; font-weight: bold;">
                    <strong>Product Code:</strong> {{ $product->code }}
                </div>
                @endif

                @if($product->product_link)
                <div class="product-link">
                    <strong>Product Link:</strong><br>
                    <a href="{{ $product->product_link }}" style="color: #0066cc; text-decoration: underline;">{{ $product->product_link }}</a>
                </div>
                @endif
            </div>

            <div class="sizes-section">
                <div class="sizes-title">Sizes & Quantities</div>
                <ul class="sizes-list">
                    @foreach($items as $invoiceItem)
                        <li class="size-item">
                            <span class="size-label">Size:</span> {{ $invoiceItem->size ?? 'N/A' }}
                            <span style="margin: 0 5px;">|</span>
                            <span class="size-label">Qty:</span> {{ $invoiceItem->quantity }}
                        </li>
                    @endforeach
                </ul>
            </div>

        </div>
        @endforeach
    @else
        <div class="product-page">
            <div style="text-align: center; padding: 50px;">
                <h2>No items found in this invoice</h2>
            </div>
        </div>
    @endif
</body>
</html>

