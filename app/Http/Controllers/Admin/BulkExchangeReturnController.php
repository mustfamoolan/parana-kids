<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkExchangeReturnController extends Controller
{
    /**
     * Display the bulk exchange return page.
     */
    public function index()
    {
        // التحقق من الصلاحيات
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            abort(403, 'غير مصرح لك بالوصول لهذه الصفحة');
        }

        // تحميل المخازن حسب الصلاحيات
        if (Auth::user()->isAdmin()) {
            $warehouses = Warehouse::all();
        } else {
            $warehouses = Auth::user()->warehouses;
        }

        $delegates = User::where('role', 'delegate')->get();

        return view('admin.bulk-exchange-returns.index', compact('warehouses', 'delegates'));
    }

    /**
     * Search for products by name or code.
     */
    public function searchProducts(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $query = Product::with(['sizes']);

        // فلتر حسب صلاحيات المجهز
        if (Auth::user()->isSupplier()) {
            $warehouseIds = Auth::user()->warehouses()->pluck('warehouses.id');
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        // فلتر حسب المخزن (اختياري)
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

    /**
     * Process bulk exchange return of products.
     */
    public function returnProducts(Request $request)
    {
        if (!Auth::user()->isAdmin() && !Auth::user()->isSupplier()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $validated = $request->validate([
            'delegate_id' => 'required|exists:users,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size_id' => 'required|exists:product_sizes,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $delegate = User::find($validated['delegate_id']);

            foreach ($validated['items'] as $item) {
                $size = ProductSize::find($item['size_id']);

                // إضافة الكمية للمخزن
                $size->increment('quantity', $item['quantity']);
                $size->refresh();

                // تحديث warehouse_id للمنتج
                $product = Product::find($item['product_id']);
                $product->warehouse_id = $validated['warehouse_id'];
                $product->save();

                // تسجيل الحركة بنوع return_exchange_bulk
                ProductMovement::record([
                    'product_id' => $item['product_id'],
                    'size_id' => $item['size_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'delegate_id' => $validated['delegate_id'],
                    'movement_type' => 'return_exchange_bulk',
                    'quantity' => $item['quantity'],
                    'balance_after' => $size->quantity,
                    'notes' => 'إرجاع استبدال - مندوب: ' . $delegate->name,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إرجاع المواد بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الإرجاع: ' . $e->getMessage()
            ], 500);
        }
    }
}

