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

        // Base query: منتجات المخزن المحدد (أو المخازن المخصصة للمستخدم إذا كان null)
        $productsQuery = Product::with(['primaryImage', 'sizes', 'warehouse.activePromotion']);

        if ($productLink->warehouse_id) {
            // إذا كان هناك مخزن محدد، عرض المنتجات من ذلك المخزن فقط
            $productsQuery->where('warehouse_id', $productLink->warehouse_id);
        } else {
            // إذا لم يكن هناك مخزن محدد، نتعامل مع كل نوع مستخدم بشكل مختلف
            $creator = $productLink->creator;

            if ($creator->isAdmin()) {
                // المدير: عرض جميع المنتجات من جميع المخازن (لا نضيف فلتر)
                // لا نضيف أي whereIn هنا
            } elseif ($creator->isSupplier()) {
                // المجهز: عرض المنتجات من مخازن المجهز فقط
                $userWarehouseIds = $creator->warehouses()->pluck('warehouse_id');
                if ($userWarehouseIds->count() > 0) {
                    $productsQuery->whereIn('warehouse_id', $userWarehouseIds);
                } else {
                    // إذا لم يكن لدى المجهز مخازن، لا نعرض أي منتجات
                    $productsQuery->whereRaw('1 = 0'); // استعلام فارغ
                }
            } else {
                // المندوب: عرض المنتجات من مخازن المندوب فقط
                $userWarehouseIds = $creator->warehouses()->pluck('warehouse_id');
                if ($userWarehouseIds->count() > 0) {
                    $productsQuery->whereIn('warehouse_id', $userWarehouseIds);
                } else {
                    // إذا لم يكن لدى المندوب مخازن، لا نعرض أي منتجات
                    $productsQuery->whereRaw('1 = 0'); // استعلام فارغ
                }
            }
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

        // فلتر المنتجات المخفضة (إذا كان محددًا)
        if ($productLink->has_discount) {
            $products = $products->filter(function($product) {
                return $product->hasActiveDiscount();
            });
        }

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
