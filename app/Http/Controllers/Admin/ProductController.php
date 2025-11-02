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
    public function index(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $perPage = $request->input('per_page', 15);
        $products = $warehouse->products()
                              ->with(['images', 'sizes', 'creator'])
                              ->paginate($perPage)
                              ->appends($request->except('page'));

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
            'name' => 'nullable|string|max:255',
            'code' => 'required|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'url|max:1000',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // استخدام الكود كاسم افتراضي إذا لم يتم إدخال اسم
        $productName = $request->name ?: $request->code;

        $product = Product::create([
            'warehouse_id' => $warehouse->id,
            'name' => $productName,
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
            ProductMovement::record([
                'product_id' => $product->id,
                'size_id' => $size->id,
                'warehouse_id' => $product->warehouse_id,
                'movement_type' => 'add',
                'quantity' => $sizeData['quantity'],
                'balance_after' => $sizeData['quantity'],
                'notes' => "إضافة منتج جديد: {$product->name}",
            ]);
        }

        // رفع الصور إن وجدت - دعم صور متعددة
        $imageIndex = 0;
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $imageIndex === 0, // أول صورة = primary
                ]);
                $imageIndex++;
            }
        }

        // تحميل الصور من URLs
        if ($request->filled('image_urls')) {
            foreach ($request->image_urls as $imageUrl) {
                if (empty($imageUrl)) continue;

                try {
                    $imageContent = file_get_contents($imageUrl);

                    if ($imageContent !== false) {
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $extension = 'jpg';
                        }

                        $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
                        $path = 'products/' . $filename;

                        Storage::disk('public')->put($path, $imageContent);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                            'is_primary' => $imageIndex === 0,
                        ]);
                        $imageIndex++;
                    }
                } catch (\Exception $e) {
                    // تجاهل الخطأ والاستمرار مع الصور الأخرى
                }
            }
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
            'name' => 'nullable|string|max:255',
            'code' => 'required|string|max:255',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'image_urls' => 'nullable|array',
            'image_urls.*' => 'url|max:1000',
            'keep_images' => 'nullable|array',
            'keep_images.*' => 'exists:product_images,id',
            'sizes' => 'required|array|min:1',
            'sizes.*.size_name' => 'required|string|max:50',
            'sizes.*.quantity' => 'required|integer|min:0',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        // استخدام الكود كاسم افتراضي إذا لم يتم إدخال اسم
        $productName = $request->name ?: $request->code;

        $product->update([
            'name' => $productName,
            'code' => $request->code,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'description' => $request->description,
            'link_1688' => $request->link_1688,
        ]);

        // حفظ القياسات القديمة لمقارنتها
        $oldSizes = $product->sizes->keyBy('size_name');
        $newSizes = collect($request->sizes)->keyBy('size_name');

        // تسجيل القياسات المحذوفة قبل الحذف
        foreach ($oldSizes as $sizeName => $oldSize) {
            if (!isset($newSizes[$sizeName])) {
                ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $oldSize->id,
                    'warehouse_id' => $product->warehouse_id,
                    'movement_type' => 'delete',
                    'quantity' => -$oldSize->quantity,
                    'balance_after' => 0,
                    'notes' => "حذف القياس - المنتج: {$product->name} - القياس: {$sizeName} (كان الرصيد: {$oldSize->quantity})",
                ]);
            }
        }

        // Update sizes - الآن يمكن حذفها بأمان
        $product->sizes()->delete();
        foreach ($request->sizes as $sizeData) {
            $size = ProductSize::create([
                'product_id' => $product->id,
                'size_name' => $sizeData['size_name'],
                'quantity' => $sizeData['quantity'],
            ]);

            // تسجيل حركة التعديل عند تغيير الكمية
            if (isset($oldSizes[$sizeData['size_name']])) {
                $oldSize = $oldSizes[$sizeData['size_name']];
                $quantityDifference = $sizeData['quantity'] - $oldSize->quantity;

                if ($quantityDifference > 0) {
                    // زيادة الكمية
                    ProductMovement::record([
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'warehouse_id' => $product->warehouse_id,
                        'movement_type' => 'increase',
                        'quantity' => $quantityDifference,
                        'balance_after' => $sizeData['quantity'],
                        'notes' => "زيادة كمية - المنتج: {$product->name} - القياس: {$sizeData['size_name']} (+{$quantityDifference})",
                    ]);
                } elseif ($quantityDifference < 0) {
                    // نقص الكمية
                    ProductMovement::record([
                        'product_id' => $product->id,
                        'size_id' => $size->id,
                        'warehouse_id' => $product->warehouse_id,
                        'movement_type' => 'decrease',
                        'quantity' => $quantityDifference,
                        'balance_after' => $sizeData['quantity'],
                        'notes' => "نقص كمية - المنتج: {$product->name} - القياس: {$sizeData['size_name']} ({$quantityDifference})",
                    ]);
                }
            } else {
                // إذا كان القياس جديد، سجله كإضافة
                ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $size->id,
                    'warehouse_id' => $product->warehouse_id,
                    'movement_type' => 'add',
                    'quantity' => $sizeData['quantity'],
                    'balance_after' => $sizeData['quantity'],
                    'notes' => "إضافة قياس جديد - المنتج: {$product->name} - القياس: {$sizeData['size_name']}",
                ]);
            }
        }

        // معالجة الصور
        // حذف الصور التي لم يتم الاحتفاظ بها
        $keepImageIds = $request->keep_images ?? [];
        foreach ($product->images as $oldImage) {
            if (!in_array($oldImage->id, $keepImageIds)) {
                Storage::disk('public')->delete($oldImage->image_path);
                $oldImage->delete();
            }
        }

        // إعادة تعيين primary للصورة الأولى المتبقية
        $remainingImages = $product->images()->get();
        if ($remainingImages->count() > 0) {
            $remainingImages->each(function($img) { $img->update(['is_primary' => false]); });
            $remainingImages->first()->update(['is_primary' => true]);
        }

        // حساب عدد الصور الحالية
        $imageIndex = $remainingImages->count();

        // رفع صور جديدة من الملفات
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $imageIndex === 0,
                ]);
                $imageIndex++;
            }
        }

        // تحميل صور من URLs
        if ($request->filled('image_urls')) {
            foreach ($request->image_urls as $imageUrl) {
                if (empty($imageUrl)) continue;

                try {
                    $imageContent = file_get_contents($imageUrl);

                    if ($imageContent !== false) {
                        $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            $extension = 'jpg';
                        }

                        $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
                        $path = 'products/' . $filename;

                        Storage::disk('public')->put($path, $imageContent);

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                            'is_primary' => $imageIndex === 0,
                        ]);
                        $imageIndex++;
                    }
                } catch (\Exception $e) {
                    // تجاهل الخطأ والاستمرار
                }
            }
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

        // تسجيل حركات الحذف لجميع القياسات قبل الحذف
        foreach ($product->sizes as $size) {
            ProductMovement::record([
                'product_id' => $product->id,
                'size_id' => $size->id,
                'warehouse_id' => $product->warehouse_id,
                'movement_type' => 'delete',
                'quantity' => -$size->quantity,
                'balance_after' => 0,
                'notes' => "حذف منتج: {$product->name} - القياس: {$size->size_name} (كان الرصيد: {$size->quantity})",
            ]);
        }

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
