<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\ProductImage;
use App\Models\ProductSize;
use App\Models\ProductMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $products = $warehouse->products()
                              ->with(['images', 'sizes', 'creator'])
                              ->paginate(10);

        return view('admin.products.index', compact('warehouse', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('create', Product::class);

        return view('admin.products.create', compact('warehouse'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('create', Product::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array|max:1',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $product = Product::create([
            'warehouse_id' => $warehouse->id,
            'name' => $request->name,
            'code' => $request->code,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'description' => $request->description,
            'link_1688' => $request->link_1688,
            'created_by' => Auth::id(),
        ]);

        // Add sizes
        foreach ($request->sizes as $sizeData) {
            $size = ProductSize::create([
                'product_id' => $product->id,
                'size_name' => $sizeData['size_name'],
                'quantity' => $sizeData['quantity'],
            ]);

            // تسجيل حركة الإضافة
            ProductMovement::record(
                $size,
                'add',
                $sizeData['quantity'],
                null, // لا يوجد order_id
                "إضافة منتج جديد: {$product->name}"
            );
        }

        // رفع الصورة إن وجدت
        if ($request->hasFile('images')) {
            $image = $request->file('images')[0]; // أخذ الصورة الأولى فقط
            $path = $image->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => true,
            ]);
        }

        return redirect()->route('admin.warehouses.products.index', $warehouse)
                        ->with('success', 'تم إنشاء المنتج بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('view', $product);

        $product->load(['warehouse', 'images', 'sizes', 'creator']);

        // حساب السعر الكلي للبيع والشراء (للمدير فقط)
        $totalQuantity = $product->sizes->sum('quantity');
        $totalSellingPrice = $product->selling_price * $totalQuantity;
        $totalPurchasePrice = 0;

        if (auth()->user()->isAdmin() && $product->purchase_price) {
            $totalPurchasePrice = $product->purchase_price * $totalQuantity;
        }

        return view('admin.products.show', compact('product', 'totalQuantity', 'totalSellingPrice', 'totalPurchasePrice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        $product->load(['sizes']);

        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:products,code,' . $product->id,
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array|max:1',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $product->update($request->only([
            'name', 'code', 'purchase_price', 'selling_price', 'description', 'link_1688'
        ]));

        // Update sizes
        $product->sizes()->delete();
        foreach ($request->sizes as $sizeData) {
            $size = ProductSize::create([
                'product_id' => $product->id,
                'size_name' => $sizeData['size_name'],
                'quantity' => $sizeData['quantity'],
            ]);

            // تسجيل حركة التعديل
            ProductMovement::record(
                $size,
                'add',
                $sizeData['quantity'],
                null, // لا يوجد order_id
                "تعديل منتج: {$product->name}"
            );
        }

        // رفع صورة جديدة إن وجدت
        if ($request->hasFile('images')) {
            // حذف الصورة القديمة
            foreach ($product->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->image_path);
                $oldImage->delete();
            }

            // رفع الصورة الجديدة
            $image = $request->file('images')[0];
            $path = $image->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => true,
            ]);
        }

        return redirect()->route('admin.warehouses.products.show', [$warehouse, $product])
                        ->with('success', 'تم تحديث المنتج بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('delete', $product);

        // Delete images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $warehouse = $product->warehouse;
        $product->delete();

        return redirect()->route('admin.warehouses.products.index', $warehouse)
                        ->with('success', 'تم حذف المنتج بنجاح');
    }

    /**
     * Upload images for product - DISABLED (using single image upload in store/update methods)
     */
    /*
    public function uploadImages(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $request->validate([
            'images' => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $index === 0 && $product->images()->count() === 0,
                'order' => $product->images()->count() + $index,
            ]);
        }

        return redirect()->back()->with('success', 'تم رفع الصور بنجاح');
    }
    */

    /**
     * Delete product image - DISABLED (using single image replacement in update method)
     */
    /*
    public function deleteImage(ProductImage $image)
    {
        $this->authorize('update', $image->product);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return redirect()->back()->with('success', 'تم حذف الصورة بنجاح');
    }
    */
}
