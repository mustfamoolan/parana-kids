<?php

namespace App\Http\Controllers;

use App\Models\ProductLink;
use App\Models\Product;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    /**
     * Display products for the given link token
     */
    public function show($token)
    {
        // البحث عن الرابط (غير محذوف)
        $productLink = ProductLink::where('token', $token)->first();

        if (!$productLink) {
            abort(404, 'الرابط غير موجود أو تم حذفه');
        }

        // Base query: منتجات المخزن المحدد (أو كل المخازن إذا كان null)
        $productsQuery = Product::with(['primaryImage', 'sizes']);

        if ($productLink->warehouse_id) {
            $productsQuery->where('warehouse_id', $productLink->warehouse_id);
        }

        // فلتر النوع (مع دعم boys_girls)
        if ($productLink->gender_type) {
            if ($productLink->gender_type == 'boys') {
                // عرض "ولادي" و "ولادي بناتي"
                $productsQuery->whereIn('gender_type', ['boys', 'boys_girls']);
            } elseif ($productLink->gender_type == 'girls') {
                // عرض "بناتي" و "ولادي بناتي"
                $productsQuery->whereIn('gender_type', ['girls', 'boys_girls']);
            } else {
                // عرض النوع المحدد فقط (boys_girls أو accessories)
                $productsQuery->where('gender_type', $productLink->gender_type);
            }
        }

        $products = $productsQuery->get();

        // فلتر القياس (إذا كان محددًا)
        if ($productLink->size_name) {
            $products = $products->filter(function($product) use ($productLink) {
                return $product->sizes()
                    ->where('size_name', $productLink->size_name)
                    ->where('quantity', '>', 0)
                    ->exists();
            });
        } else {
            // إذا لم يكن القياس محددًا، نعرض فقط المنتجات التي لديها كمية متاحة
            $products = $products->filter(function($product) {
                return $product->sizes()->sum('quantity') > 0;
            });
        }

        return view('public.products.show', compact('products', 'productLink'));
    }
}
