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

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $item) {
                // التحقق من الكمية المتاحة مع تحميل العلاقة
                $sourceSize = ProductSize::with('product')
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

                // إعادة تحميل للحصول على القيمة المحدثة
                $sourceSize->refresh();

                // تسجيل حركة الخروج
                ProductMovement::record([
                    'product_id' => $item['product_id'],
                    'size_id' => $item['size_id'],
                    'warehouse_id' => $validated['from_warehouse_id'],
                    'movement_type' => 'transfer_out',
                    'quantity' => -$item['quantity'],
                    'balance_after' => $sourceSize->quantity,
                    'notes' => "نقل إلى مخزن ID: {$validated['to_warehouse_id']}",
                ]);

                // إضافة للمخزن المستهدف
                // التحقق من وجود المنتج في المخزن المستهدف
                $targetProduct = Product::where('id', $item['product_id'])->first();

                // نقل المنتج للمخزن الجديد
                $targetProduct->warehouse_id = $validated['to_warehouse_id'];
                $targetProduct->save();

                // البحث عن القياس (هو نفسه لأن المنتج نُقل)
                $targetSize = ProductSize::where('id', $item['size_id'])->first();

                // إضافة الكمية (أعد إضافتها بعد الخصم السابق)
                $targetSize->increment('quantity', $item['quantity']);

                // إعادة تحميل للحصول على القيمة المحدثة
                $targetSize->refresh();

                // تسجيل حركة الدخول
                ProductMovement::record([
                    'product_id' => $targetProduct->id,
                    'size_id' => $targetSize->id,
                    'warehouse_id' => $validated['to_warehouse_id'],
                    'movement_type' => 'transfer_in',
                    'quantity' => $item['quantity'],
                    'balance_after' => $targetSize->quantity,
                    'notes' => "نقل من مخزن ID: {$validated['from_warehouse_id']}",
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
