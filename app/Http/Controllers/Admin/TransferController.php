<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransferController extends Controller
{
    public function index()
    {
        // التحقق من الصلاحيات
        if (!auth()->user()->isAdmin() && !auth()->user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        // تحميل المخازن حسب الصلاحيات
        if (auth()->user()->isAdmin()) {
            $warehouses = Warehouse::all();
        } else {
            $warehouses = auth()->user()->warehouses;
        }

        return view('admin.transfers.index', compact('warehouses'));
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with(['sizes', 'warehouse']);

        // فلتر حسب صلاحيات المجهز
        if (auth()->user()->isSupplier()) {
            $warehouseIds = auth()->user()->warehouses()->pluck('warehouses.id');
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('code', 'like', '%' . $request->search . '%');
            });
        }

        return response()->json($query->limit(10)->get());
    }

    public function transfer(Request $request)
    {
        // التحقق من الصلاحيات
        if (!auth()->user()->isAdmin() && !auth()->user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        // تحميل أسماء المخازن لتجنب استدعاءات متعددة
        $fromWarehouse = Warehouse::find($validated['from_warehouse_id']);
        $toWarehouse = Warehouse::find($validated['to_warehouse_id']);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $item) {
                // التحقق من الكمية المتاحة مع تحميل العلاقات (المنتج والصور)
                $sourceSize = ProductSize::with(['product.images'])
                    ->where('id', $item['size_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();

                if (!$sourceSize) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'القياس المحدد غير موجود'
                    ], 404);
                }

                if ($sourceSize->quantity < $item['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "الكمية المطلوبة ({$item['quantity']}) أكبر من المتاح ({$sourceSize->quantity}) للقياس {$sourceSize->size_name}"
                    ], 422);
                }

                // خصم من المخزن المصدر
                $sourceSize->decrement('quantity', $item['quantity']);
                $sourceSize->refresh();

                // تسجيل حركة الخروج
                ProductMovement::record([
                    'product_id' => $item['product_id'],
                    'size_id' => $item['size_id'],
                    'warehouse_id' => $validated['from_warehouse_id'],
                    'movement_type' => 'transfer_out',
                    'quantity' => -$item['quantity'],
                    'balance_after' => $sourceSize->quantity,
                    'notes' => "نقل إلى مخزن: {$toWarehouse->name}",
                ]);

                // التحقق من وجود المنتج في المخزن المستهدف (باستخدام الاسم والكود)
                $sourceProduct = $sourceSize->product;

                // البحث عن منتج موجود في المخزن المستهدف بنفس الاسم والكود
                $targetProduct = Product::where('warehouse_id', $validated['to_warehouse_id'])
                    ->where('name', $sourceProduct->name)
                    ->where('code', $sourceProduct->code)
                    ->first();

                if ($targetProduct) {
                    // المنتج موجود في المخزن المستهدف - التحقق من القياس
                    $targetSize = ProductSize::where('product_id', $targetProduct->id)
                        ->where('size_name', $sourceSize->size_name)
                        ->first();

                    if ($targetSize) {
                        // القياس موجود - إضافة الكمية فقط (لا إنشاء منتج جديد)
                        $targetSize->increment('quantity', $item['quantity']);
                        $targetSize->refresh();
                    } else {
                        // المنتج موجود لكن القياس غير موجود - إنشاء قياس جديد فقط
                        $targetSize = ProductSize::create([
                            'product_id' => $targetProduct->id,
                            'size_name' => $sourceSize->size_name,
                            'quantity' => $item['quantity'],
                        ]);
                    }

                    // نسخ الصور إذا لم تكن موجودة في المنتج المستهدف
                    if ($sourceProduct->images && $sourceProduct->images->count() > 0) {
                        $existingImages = $targetProduct->images()->pluck('image_path')->toArray();
                        foreach ($sourceProduct->images as $image) {
                            // التحقق من عدم وجود الصورة مسبقاً
                            if (!in_array($image->image_path, $existingImages)) {
                                \App\Models\ProductImage::create([
                                    'product_id' => $targetProduct->id,
                                    'image_path' => $image->image_path, // نفس المسار، لا نسخ الملف
                                    'is_primary' => $image->is_primary,
                                    'order' => $image->order,
                                ]);
                            }
                        }
                    }
                } else {
                    // المنتج غير موجود في المخزن المستهدف - إنشاء منتج جديد
                    // استخدام نفس الكود والاسم والمعلومات تماماً
                    $targetProduct = Product::create([
                        'warehouse_id' => $validated['to_warehouse_id'],
                        'name' => $sourceProduct->name,
                        'code' => $sourceProduct->code, // نفس الكود تماماً
                        'purchase_price' => $sourceProduct->purchase_price,
                        'selling_price' => $sourceProduct->selling_price,
                        'description' => $sourceProduct->description,
                        'link_1688' => $sourceProduct->link_1688,
                        'created_by' => auth()->id(),
                    ]);

                    // نسخ مسار الصور فقط (نفس المسار، لا نسخ الملف الفعلي)
                    if ($sourceProduct->images && $sourceProduct->images->count() > 0) {
                        foreach ($sourceProduct->images as $image) {
                            \App\Models\ProductImage::create([
                                'product_id' => $targetProduct->id,
                                'image_path' => $image->image_path, // نفس المسار بالضبط
                                'is_primary' => $image->is_primary,
                                'order' => $image->order,
                            ]);
                        }
                    }

                    // إنشاء القياس المطلوب فقط (وليس جميع القياسات)
                    $targetSize = ProductSize::create([
                        'product_id' => $targetProduct->id,
                        'size_name' => $sourceSize->size_name,
                        'quantity' => $item['quantity'],
                    ]);
                }

                // تسجيل حركة الدخول
                ProductMovement::record([
                    'product_id' => $targetProduct->id,
                    'size_id' => $targetSize->id,
                    'warehouse_id' => $validated['to_warehouse_id'],
                    'movement_type' => 'transfer_in',
                    'quantity' => $item['quantity'],
                    'balance_after' => $targetSize->quantity,
                    'notes' => "نقل من مخزن: {$fromWarehouse->name}",
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم نقل المواد بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء النقل: ' . $e->getMessage()
            ], 500);
        }
    }
}
