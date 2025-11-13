<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            // السماح للمدير والمجهزين والموردين بالوصول
            if (!$user->isAdmin() && !$user->isSupplier() && !$user->isPrivateSupplier()) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
            }
            return $next($request);
        });
    }

    /**
     * Display the invoice creation page.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $privateWarehouse = null;

        // إذا كان هناك private_warehouse_id في الطلب (من صفحة عرض المخزن الخاص)
        if ($request->filled('private_warehouse_id')) {
            $privateWarehouse = \App\Models\PrivateWarehouse::findOrFail($request->private_warehouse_id);

            // المدير فقط يمكنه عرض مخزن خاص محدد
            if (!$user->isAdmin()) {
                abort(403, 'غير مصرح لك بالوصول إلى هذا المخزن الخاص');
            }

            // فلترة المنتجات حسب المخزن الخاص
            $query = InvoiceProduct::where('private_warehouse_id', $privateWarehouse->id);
        } else {
            // فلترة حسب المخزن الخاص
            if ($user->isAdmin()) {
                // المدير يرى كل المنتجات
                $query = InvoiceProduct::query();
            } elseif ($user->isPrivateSupplier()) {
                // المورد يرى فقط منتجات مخزنه الخاص
                if ($user->private_warehouse_id) {
                    $query = InvoiceProduct::where('private_warehouse_id', $user->private_warehouse_id);
                    $privateWarehouse = $user->privateWarehouse;
                } else {
                    // إذا لم يكن له مخزن خاص، لا يرى أي منتجات
                    $query = InvoiceProduct::whereRaw('1 = 0');
                }
            } else {
                // المجهز (supplier) يرى كل المنتجات (أو يمكن تحديد منطق آخر)
                $query = InvoiceProduct::query();
            }
        }

        // البحث بالكود
        if ($request->filled('code')) {
            $query->where('code', 'LIKE', '%' . $request->code . '%');
        }

        // فلتر السعر من
        if ($request->filled('price_from')) {
            $query->where('price_yuan', '>=', $request->price_from);
        }

        // فلتر السعر إلى
        if ($request->filled('price_to')) {
            $query->where('price_yuan', '<=', $request->price_to);
        }

        $products = $query->orderBy('created_at', 'desc')->get();

        return view('admin.invoices.index', compact('products', 'privateWarehouse'));
    }

    /**
     * Store a new invoice product.
     */
    public function storeProduct(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'image_url' => 'required|string',
            'product_link' => 'nullable|url',
            'price_yuan' => 'nullable|numeric|min:0',
            'available_sizes' => 'required|array|min:1',
            'private_warehouse_id' => 'nullable|exists:private_warehouses,id',
        ]);

        $productData = [
            'image_url' => $request->image_url,
            'product_link' => $request->product_link,
            'price_yuan' => $request->price_yuan,
            'available_sizes' => $request->available_sizes,
            'created_by' => $user->id,
        ];

        // ربط المنتج بالمخزن الخاص
        if ($request->filled('private_warehouse_id')) {
            // المدير يمكنه إضافة منتج لمخزن خاص محدد
            if ($user->isAdmin()) {
                $productData['private_warehouse_id'] = $request->private_warehouse_id;
            } else {
                abort(403, 'غير مصرح لك بإضافة منتج لهذا المخزن الخاص');
            }
        } elseif ($user->isPrivateSupplier() && $user->private_warehouse_id) {
            // المورد يضيف منتج لمخزنه الخاص
            $productData['private_warehouse_id'] = $user->private_warehouse_id;
        }

        $product = InvoiceProduct::create($productData);

        return response()->json([
            'success' => true,
            'product' => $product->load('creator'),
        ]);
    }

    /**
     * Update an invoice product.
     */
    public function updateProduct(Request $request, $id)
    {
        $user = Auth::user();

        // فلترة حسب المخزن الخاص
        if ($user->isAdmin()) {
            $product = InvoiceProduct::findOrFail($id);
        } elseif ($user->isPrivateSupplier()) {
            if ($user->private_warehouse_id) {
                $product = InvoiceProduct::where('private_warehouse_id', $user->private_warehouse_id)
                    ->where('created_by', $user->id)
                    ->findOrFail($id);
            } else {
                abort(404);
            }
        } else {
            // المجهز (supplier) يمكنه تعديل المنتجات
            $product = InvoiceProduct::findOrFail($id);
        }

        $request->validate([
            'image_url' => 'required|string',
            'product_link' => 'nullable|url',
            'price_yuan' => 'nullable|numeric|min:0',
            'available_sizes' => 'required|array|min:1',
        ]);

        $product->update([
            'image_url' => $request->image_url,
            'product_link' => $request->product_link,
            'price_yuan' => $request->price_yuan,
            'available_sizes' => $request->available_sizes,
        ]);

        return response()->json([
            'success' => true,
            'product' => $product->load('creator'),
        ]);
    }

    /**
     * Delete an invoice product.
     */
    public function deleteProduct($id)
    {
        $user = Auth::user();

        // فلترة حسب المخزن الخاص
        if ($user->isAdmin()) {
            $product = InvoiceProduct::findOrFail($id);
        } elseif ($user->isPrivateSupplier()) {
            if ($user->private_warehouse_id) {
                $product = InvoiceProduct::where('private_warehouse_id', $user->private_warehouse_id)
                    ->where('created_by', $user->id)
                    ->findOrFail($id);
            } else {
                abort(404);
            }
        } else {
            // المجهز (supplier) يمكنه حذف المنتجات
            $product = InvoiceProduct::findOrFail($id);
        }

        $product->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Save invoice to database.
     */
    public function saveInvoice(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:invoice_products,id',
                'items.*.size' => 'nullable|string',
                'items.*.quantity' => 'required|integer|min:1',
                'private_warehouse_id' => 'nullable|exists:private_warehouses,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Invoice validation error', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $e->errors(),
            ], 422);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $invoiceData = [
                'created_by' => $user->id,
                'total_amount' => 0,
            ];

            // ربط الفاتورة بالمخزن الخاص
            if ($request->filled('private_warehouse_id')) {
                // المدير يمكنه إضافة فاتورة لمخزن خاص محدد
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
                // المورد يضيف فاتورة لمخزنه الخاص
                $invoiceData['private_warehouse_id'] = $user->private_warehouse_id;
            }

            $invoice = Invoice::create($invoiceData);

            $totalAmount = 0;
            foreach ($request->items as $index => $item) {
                try {
                    $product = InvoiceProduct::findOrFail($item['product_id']);

                    // التحقق من وجود السعر
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
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    DB::rollBack();
                    Log::error('Invoice product not found', [
                        'product_id' => $item['product_id'] ?? null,
                        'item_index' => $index
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "المنتج المحدد في العنصر #" . ($index + 1) . " غير موجود",
                    ], 404);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Invoice item creation error', [
                        'item' => $item,
                        'index' => $index,
                        'error' => $e->getMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "خطأ في العنصر #" . ($index + 1) . ": " . $e->getMessage(),
                    ], 500);
                }
            }

            $invoice->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => true,
                'invoice' => $invoice->load(['items.invoiceProduct', 'creator']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice save error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token'])
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الفاتورة. يرجى التحقق من البيانات وإعادة المحاولة.',
            ], 500);
        }
    }

    /**
     * View supplier invoices (for admin only).
     */
    public function viewSupplierInvoices($userId)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        $supplier = \App\Models\User::findOrFail($userId);

        if (!$supplier->isPrivateSupplier()) {
            abort(404, 'المستخدم المحدد ليس مورداً');
        }

        $invoices = Invoice::where('private_warehouse_id', $supplier->private_warehouse_id)
            ->with(['items.invoiceProduct', 'creator'])
            ->latest()
            ->get();

        return view('admin.invoices.supplier-invoices', compact('supplier', 'invoices'));
    }

    /**
     * View my invoices (for private supplier only) or invoices for a specific private warehouse (for admin).
     */
    public function myInvoices(Request $request)
    {
        $user = Auth::user();
        $privateWarehouse = null;

        // إذا كان هناك private_warehouse_id في الطلب (للمدير)
        if ($request->filled('private_warehouse_id')) {
            if (!$user->isAdmin()) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
            }

            $privateWarehouse = \App\Models\PrivateWarehouse::findOrFail($request->private_warehouse_id);
            $invoices = Invoice::where('private_warehouse_id', $privateWarehouse->id)
                ->with(['items.invoiceProduct', 'creator'])
                ->latest()
                ->get();
        } elseif ($user->isAdmin()) {
            // المدير يرى جميع الفواتير إذا لم يكن هناك private_warehouse_id
            $invoices = Invoice::with(['items.invoiceProduct', 'creator'])
                ->latest()
                ->get();
        } elseif ($user->isPrivateSupplier()) {
            // المورد يرى فواتيره الخاصة
            if (!$user->private_warehouse_id) {
                $invoices = collect();
            } else {
                $invoices = Invoice::where('private_warehouse_id', $user->private_warehouse_id)
                    ->with(['items.invoiceProduct', 'creator'])
                    ->latest()
                    ->get();
                $privateWarehouse = $user->privateWarehouse;
            }
        } else {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة.');
        }

        return view('admin.invoices.my-invoices', compact('invoices', 'privateWarehouse'));
    }

    /**
     * Download invoice PDF.
     */
    public function downloadPdf($id)
    {
        try {
            $user = Auth::user();

            // فلترة حسب المخزن الخاص
            if ($user->isAdmin()) {
                $invoice = Invoice::with(['items.invoiceProduct', 'creator'])->findOrFail($id);
            } elseif ($user->isPrivateSupplier()) {
                if ($user->private_warehouse_id) {
                    $invoice = Invoice::where('private_warehouse_id', $user->private_warehouse_id)
                        ->with(['items.invoiceProduct', 'creator'])
                        ->findOrFail($id);
                } else {
                    abort(404);
                }
            } else {
                // المجهز (supplier) يمكنه الوصول لجميع الفواتير
                $invoice = Invoice::with(['items.invoiceProduct', 'creator'])->findOrFail($id);
            }

            // التحقق من وجود عناصر في الفاتورة
            if ($invoice->items->isEmpty()) {
                abort(404, 'Invoice has no items');
            }

            $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'));
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('enable-local-file-access', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            abort(500, 'Error generating PDF: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified invoice (admin only).
     */
    public function edit($id)
    {
        $user = Auth::user();

        // فقط المدير يمكنه تعديل الفواتير
        if (!$user->isAdmin()) {
            abort(403, 'غير مصرح لك بتعديل الفواتير.');
        }

        $invoice = Invoice::with(['items.invoiceProduct', 'creator', 'privateWarehouse'])->findOrFail($id);
        $privateWarehouse = $invoice->privateWarehouse;

        // جلب المنتجات حسب المخزن الخاص
        if ($privateWarehouse) {
            $products = InvoiceProduct::where('private_warehouse_id', $privateWarehouse->id)
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $products = InvoiceProduct::orderBy('created_at', 'desc')->get();
        }

        return view('admin.invoices.index', compact('products', 'privateWarehouse', 'invoice'));
    }

    /**
     * Update the specified invoice (admin only).
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        // فقط المدير يمكنه تعديل الفواتير
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل الفواتير.',
            ], 403);
        }

        // إذا كانت items string (من form data)، تحويلها إلى array
        $items = $request->items;
        if (is_string($items)) {
            $items = json_decode($items, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invoice update JSON decode error', [
                    'json_error' => json_last_error_msg(),
                    'items_string' => $items
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في تنسيق البيانات المرسلة',
                ], 400);
            }
        }

        // التحقق من صحة البيانات
        try {
            $request->merge(['items' => $items]);
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:invoice_products,id',
                'items.*.size' => 'nullable|string',
                'items.*.quantity' => 'required|integer|min:1',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Invoice update validation error', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['_token', '_method'])
            ]);
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المرسلة',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $invoice = Invoice::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Invoice not found for update', ['invoice_id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'الفاتورة المحددة غير موجودة',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // حذف العناصر القديمة
            $invoice->items()->delete();

            // إضافة العناصر الجديدة
            $totalAmount = 0;
            foreach ($items as $index => $item) {
                try {
                    $product = InvoiceProduct::findOrFail($item['product_id']);

                    // التحقق من وجود السعر
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
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    DB::rollBack();
                    Log::error('Invoice product not found in update', [
                        'product_id' => $item['product_id'] ?? null,
                        'item_index' => $index,
                        'invoice_id' => $id
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "المنتج المحدد في العنصر #" . ($index + 1) . " غير موجود",
                    ], 404);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Invoice item update error', [
                        'item' => $item,
                        'index' => $index,
                        'invoice_id' => $id,
                        'error' => $e->getMessage()
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => "خطأ في العنصر #" . ($index + 1) . ": " . $e->getMessage(),
                    ], 500);
                }
            }

            // تحديث المبلغ الإجمالي
            $invoice->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'success' => true,
                'invoice' => $invoice->load(['items.invoiceProduct', 'creator']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice update error', [
                'invoice_id' => $id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['_token', '_method'])
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الفاتورة. يرجى التحقق من البيانات وإعادة المحاولة.',
            ], 500);
        }
    }

    /**
     * Delete an invoice (admin only).
     */
    public function destroy($id)
    {
        $user = Auth::user();

        // فقط المدير يمكنه حذف الفواتير
        if (!$user->isAdmin()) {
            abort(403, 'غير مصرح لك بحذف الفواتير.');
        }

        $invoice = Invoice::findOrFail($id);

        // حذف العناصر المرتبطة أولاً
        $invoice->items()->delete();

        // حذف الفاتورة
        $invoice->delete();

        return redirect()->back()->with('success', 'تم حذف الفاتورة بنجاح');
    }
}
