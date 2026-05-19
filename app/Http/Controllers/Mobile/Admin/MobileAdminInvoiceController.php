<?php

namespace App\Http\Controllers\Mobile\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\InvoiceItem;
use App\Models\PrivateWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileAdminInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user || (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier())) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بالوصول.',
                ], 403);
            }
            return $next($request);
        });
    }

    /**
     * جلب الفواتير مع الفلترة حسب المخزن الخاص
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        try {
            $query = Invoice::with(['items.invoiceProduct', 'creator', 'privateWarehouse']);

            if ($request->filled('private_warehouse_id')) {
                $query->where('private_warehouse_id', $request->private_warehouse_id);
            } elseif ($user->isPrivateSupplier()) {
                if ($user->private_warehouse_id) {
                    $query->where('private_warehouse_id', $user->private_warehouse_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            $perPage = $request->input('per_page', 20);
            $invoices = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'invoices' => $invoices
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Invoice Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الفواتير: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حفظ فاتورة جديدة
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:invoice_products,id',
            'items.*.size' => 'nullable|string',
            'items.*.quantity' => 'required|integer|min:1',
            'private_warehouse_id' => 'nullable|exists:private_warehouses,id',
        ]);

        DB::beginTransaction();
        try {
            $invoiceData = [
                'created_by' => $user->id,
                'total_amount' => 0,
            ];

            if ($request->filled('private_warehouse_id')) {
                if ($user->isAdmin()) {
                    $invoiceData['private_warehouse_id'] = $request->private_warehouse_id;
                } else {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بإضافة فاتورة لهذا المخزن الخاص',
                    ], 403);
                }
            } elseif ($user->isPrivateSupplier() && $user->private_warehouse_id) {
                $invoiceData['private_warehouse_id'] = $user->private_warehouse_id;
            }

            $invoice = Invoice::create($invoiceData);

            $totalAmount = 0;
            foreach ($request->items as $item) {
                $product = InvoiceProduct::findOrFail($item['product_id']);

                if ($product->price_yuan === null || $product->price_yuan < 0) {
                    throw new \Exception("المنتج #{$product->id} لا يحتوي على سعر صحيح");
                }

                $itemTotal = $product->price_yuan * $item['quantity'];
                $totalAmount += $itemTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'invoice_product_id' => $product->id,
                    'size' => $item['size'] ?? null,
                    'quantity' => $item['quantity'],
                    'price_yuan' => $product->price_yuan,
                ]);
            }

            $invoice->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الفاتورة بنجاح.',
                'data' => [
                    'invoice' => $invoice->load(['items.invoiceProduct', 'creator'])
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mobile Invoice Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الفاتورة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف فاتورة
     */
    public function destroy($id)
    {
        $user = Auth::user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بحذف الفواتير.',
            ], 403);
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->items()->delete();
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفاتورة بنجاح.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mobile Invoice Destroy Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الفاتورة: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * جلب المنتجات المتاحة للفواتير
     */
    public function getProducts(Request $request)
    {
        $user = Auth::user();
        try {
            $query = InvoiceProduct::with(['creator']);

            if ($request->filled('private_warehouse_id')) {
                $query->where('private_warehouse_id', $request->private_warehouse_id);
            } elseif ($user->isPrivateSupplier()) {
                if ($user->private_warehouse_id) {
                    $query->where('private_warehouse_id', $user->private_warehouse_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('product_link', 'like', "%{$search}%");
                });
            }

            $perPage = $request->input('per_page', 20);
            $products = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'products' => $products
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Invoice Products Get Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المنتجات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حفظ منتج جديد للفواتير
     */
    public function storeProduct(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'image_url' => 'required|string',
            'product_link' => 'nullable|string',
            'price_yuan' => 'nullable|numeric|min:0',
            'available_sizes' => 'required|array|min:1',
            'private_warehouse_id' => 'nullable|exists:private_warehouses,id',
        ]);

        try {
            $productData = [
                'image_url' => $request->image_url,
                'product_link' => $request->product_link,
                'price_yuan' => $request->price_yuan,
                'available_sizes' => $request->available_sizes,
                'created_by' => $user->id,
            ];

            if ($request->filled('private_warehouse_id')) {
                if ($user->isAdmin()) {
                    $productData['private_warehouse_id'] = $request->private_warehouse_id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بإضافة منتج لهذا المخزن الخاص',
                    ], 403);
                }
            } elseif ($user->isPrivateSupplier() && $user->private_warehouse_id) {
                $productData['private_warehouse_id'] = $user->private_warehouse_id;
            }

            $product = InvoiceProduct::create($productData);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المنتج بنجاح.',
                'data' => [
                    'product' => InvoiceProduct::with(['creator'])->find($product->id)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Invoice Product Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة المنتج: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * تحديث منتج فواتير
     */
    public function updateProduct(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'image_url' => 'required|string',
            'product_link' => 'nullable|string',
            'price_yuan' => 'nullable|numeric|min:0',
            'available_sizes' => 'required|array|min:1',
        ]);

        try {
            if ($user->isAdmin()) {
                $product = InvoiceProduct::findOrFail($id);
            } elseif ($user->isPrivateSupplier()) {
                if ($user->private_warehouse_id) {
                    $product = InvoiceProduct::where('private_warehouse_id', $user->private_warehouse_id)
                        ->where('created_by', $user->id)
                        ->findOrFail($id);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بتعديل هذا المنتج.',
                    ], 403);
                }
            } else {
                $product = InvoiceProduct::findOrFail($id);
            }

            $product->update([
                'image_url' => $request->image_url,
                'product_link' => $request->product_link,
                'price_yuan' => $request->price_yuan,
                'available_sizes' => $request->available_sizes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المنتج بنجاح.',
                'data' => [
                    'product' => InvoiceProduct::with(['creator'])->find($product->id)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Invoice Product Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث المنتج: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * حذف منتج فواتير
     */
    public function destroyProduct($id)
    {
        $user = Auth::user();

        try {
            if ($user->isAdmin()) {
                $product = InvoiceProduct::findOrFail($id);
            } elseif ($user->isPrivateSupplier()) {
                if ($user->private_warehouse_id) {
                    $product = InvoiceProduct::where('private_warehouse_id', $user->private_warehouse_id)
                        ->where('created_by', $user->id)
                        ->findOrFail($id);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بحذف هذا المنتج.',
                    ], 403);
                }
            } else {
                $product = InvoiceProduct::findOrFail($id);
            }

            // التحقق من وجود فواتير تحتوي على هذا المنتج
            if (InvoiceItem::where('invoice_product_id', $product->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف المنتج لأنه مستخدم في فواتير سابقة.',
                ], 400);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المنتج بنجاح.'
            ]);
        } catch (\Exception $e) {
            Log::error('Mobile Invoice Product Destroy Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنتج: ' . $e->getMessage(),
            ], 500);
        }
    }
}
