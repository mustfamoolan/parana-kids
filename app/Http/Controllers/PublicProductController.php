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

        if ($productLink->isExpired()) {
            abort(404, 'الرابط منتهي الصلاحية');
        }

        // استخدام الفلاتر الجديدة إذا كانت موجودة
        $filters = $productLink->filters;
        $groupedProducts = [];

        if ($filters && is_array($filters) && count($filters) > 0) {
            // منطق الفلاتر المتعددة
            $warehouseIds = collect($filters)->pluck('warehouse_id')->unique()->toArray();
            
            $baseQuery = Product::where('is_hidden', false)
                ->whereIn('warehouse_id', $warehouseIds)
                ->with(['primaryImage', 'sizes', 'warehouse.activePromotion']);

            // فلتر النوع (إذا كان موجوداً في الرابط الأصلي)
            if ($productLink->gender_type) {
                if ($productLink->gender_type == 'boys') {
                    $baseQuery->whereIn('gender_type', ['boys', 'boys_girls']);
                } elseif ($productLink->gender_type == 'girls') {
                    $baseQuery->whereIn('gender_type', ['girls', 'boys_girls']);
                } else {
                    $baseQuery->where('gender_type', $productLink->gender_type);
                }
            }

            $allProducts = $baseQuery->get();

            // فلتر المنتجات المخفضة (إذا كان محددًا)
            if ($productLink->has_discount) {
                $allProducts = $allProducts->filter(function($product) {
                    return $product->hasActiveDiscount();
                });
            }

            // تجميع المنتجات حسب القياسات المحددة في الفلاتر
            foreach ($filters as $filter) {
                $whId = $filter['warehouse_id'];
                $whSizes = $filter['sizes'] ?? [];

                foreach ($whSizes as $sizeName) {
                    if (!isset($groupedProducts[$sizeName])) {
                        $groupedProducts[$sizeName] = collect();
                    }

                    $sizeProducts = $allProducts->where('warehouse_id', $whId)
                        ->filter(function($product) use ($sizeName) {
                            return $product->sizes()
                                ->where('size_name', $sizeName)
                                ->where('quantity', '>', 0)
                                ->exists();
                        });
                    
                    // دمج المنتجات (تجنب التكرار لنفس القياس إذا كان هناك منطق مخازن متقاطع، رغم أنه نادراً هنا)
                    foreach ($sizeProducts as $sp) {
                        if (!$groupedProducts[$sizeName]->contains('id', $sp->id)) {
                            $groupedProducts[$sizeName]->push($sp);
                        }
                    }
                }
            }

            // ترتيب القياسات أبجدياً
            ksort($groupedProducts);

        } else {
            // المنطق القديم (رابط واحد لمخزن واحد وقياس واحد)
            $productsQuery = Product::where('is_hidden', false)
                ->with(['primaryImage', 'sizes', 'warehouse.activePromotion']);

            if ($productLink->warehouse_id) {
                $productsQuery->where('warehouse_id', $productLink->warehouse_id);
            } else {
                $creator = $productLink->creator;
                if (!$creator->isAdmin()) {
                    $userWarehouseIds = $creator->warehouses()->pluck('warehouse_id');
                    $productsQuery->whereIn('warehouse_id', $userWarehouseIds);
                }
            }

            if ($productLink->gender_type) {
                if ($productLink->gender_type == 'boys') {
                    $productsQuery->whereIn('gender_type', ['boys', 'boys_girls']);
                } elseif ($productLink->gender_type == 'girls') {
                    $productsQuery->whereIn('gender_type', ['girls', 'boys_girls']);
                } else {
                    $productsQuery->where('gender_type', $productLink->gender_type);
                }
            }

            $products = $productsQuery->get();

            if ($productLink->has_discount) {
                $products = $products->filter(function($product) {
                    return $product->hasActiveDiscount();
                });
            }

            if ($productLink->size_name) {
                $products = $products->filter(function($product) use ($productLink) {
                    return $product->sizes()
                        ->where('size_name', $productLink->size_name)
                        ->where('quantity', '>', 0)
                        ->exists();
                });
                $groupedProducts[$productLink->size_name] = $products;
            } else {
                $products = $products->filter(function($product) {
                    return $product->sizes()->sum('quantity') > 0;
                });
                $groupedProducts['الكل'] = $products;
            }
        }

        return view('public.products.show', compact('groupedProducts', 'productLink'));
    }
}
