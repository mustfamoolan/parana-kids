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
     * Get the storage disk to use (cloud or public)
     * 
     * @return string
     */
    private function getStorageDisk()
    {
        // استخدام public disk بشكل افتراضي
        $disk = 'public';
        
        // محاولة استخدام cloud disk إذا كان متاحاً
        if (env('AWS_BUCKET') && config('filesystems.disks.cloud')) {
            try {
                // التحقق من أن cloud disk متاح ويعمل
                $test = Storage::disk('cloud');
                $disk = 'cloud';
            } catch (\Exception $e) {
                // إذا فشل، استخدم public disk
                $disk = 'public';
            }
        }
        
        return $disk;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Warehouse $warehouse)
    {
        $this->authorize('view', $warehouse);

        $perPage = $request->input('per_page', 15);
        $products = $warehouse->products()
                              ->with(['images', 'sizes', 'creator', 'warehouse.activePromotion'])
                              ->latest()
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
            'gender_type' => 'required|in:boys,girls,accessories,boys_girls',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'is_hidden' => 'nullable|boolean',
            'discount_type' => 'nullable|in:none,amount,percentage',
            'discount_value' => 'nullable|numeric|min:0|required_if:discount_type,amount,percentage',
            'discount_start_date' => 'nullable|date|required_with:discount_end_date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date|required_with:discount_start_date',
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

        // تحويل تواريخ التخفيض إلى UTC إذا كانت موجودة
        $discountStartDate = $request->discount_start_date
            ? \Carbon\Carbon::parse($request->discount_start_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;
        $discountEndDate = $request->discount_end_date
            ? \Carbon\Carbon::parse($request->discount_end_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;

        $product = Product::create([
            'warehouse_id' => $warehouse->id,
            'name' => $productName,
            'code' => $request->code,
            'gender_type' => $request->gender_type,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'description' => $request->description,
            'link_1688' => $request->link_1688,
            'is_hidden' => $request->has('is_hidden') && auth()->user()->isAdmin() ? (bool)$request->is_hidden : false,
            'discount_type' => $request->discount_type ?? 'none',
            'discount_value' => $request->discount_value,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
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
        $disk = $this->getStorageDisk();

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', $disk);

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

                        Storage::disk($disk)->put($path, $imageContent);

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

        $product->load(['warehouse.activePromotion', 'images', 'sizes', 'creator']);

        // حساب السعر الكلي للبيع والشراء (للمدير فقط)
        $totalQuantity = $product->sizes->sum('quantity');
        $totalSellingPrice = $product->effective_price * $totalQuantity;
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
            'gender_type' => 'nullable|in:boys,girls,accessories,boys_girls',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'link_1688' => 'nullable|url|max:500',
            'is_hidden' => 'nullable|boolean',
            'discount_type' => 'nullable|in:none,amount,percentage',
            'discount_value' => 'nullable|numeric|min:0|required_if:discount_type,amount,percentage',
            'discount_start_date' => 'nullable|date|required_with:discount_end_date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date|required_with:discount_start_date',
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

        // تحويل تواريخ التخفيض إلى UTC إذا كانت موجودة
        $discountStartDate = $request->discount_start_date
            ? \Carbon\Carbon::parse($request->discount_start_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;
        $discountEndDate = $request->discount_end_date
            ? \Carbon\Carbon::parse($request->discount_end_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;

        $updateData = [
            'name' => $productName,
            'code' => $request->code,
            'gender_type' => $request->gender_type,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'description' => $request->description,
            'link_1688' => $request->link_1688,
        ];

        // فقط المدير يمكنه تعديل الحجب والتخفيض
        if (auth()->user()->isAdmin()) {
            $updateData['is_hidden'] = $request->has('is_hidden') ? (bool)$request->is_hidden : false;
            $updateData['discount_type'] = $request->discount_type ?? 'none';
            $updateData['discount_value'] = $request->discount_value;
            $updateData['discount_start_date'] = $discountStartDate;
            $updateData['discount_end_date'] = $discountEndDate;
        }

        $product->update($updateData);

        // حفظ القياسات القديمة لمقارنتها
        $oldSizes = $product->sizes->keyBy('size_name');
        $newSizes = collect($request->sizes)->keyBy('size_name');

        // حفظ القياسات الموجودة قبل أي تعديل (للاستخدام لاحقاً)
        $existingSizes = $product->sizes->keyBy('size_name');

        // تسجيل القياسات المحذوفة قبل الحذف (فقط إذا لم تعد موجودة في القياسات الجديدة)
        foreach ($oldSizes as $sizeName => $oldSize) {
            if (!isset($newSizes[$sizeName])) {
                // حذف القياس فقط إذا لم يعد موجوداً في القائمة الجديدة
                ProductMovement::record([
                    'product_id' => $product->id,
                    'size_id' => $oldSize->id,
                    'warehouse_id' => $product->warehouse_id,
                    'movement_type' => 'delete',
                    'quantity' => -$oldSize->quantity,
                    'balance_after' => 0,
                    'notes' => "حذف القياس - المنتج: {$product->name} - القياس: {$sizeName} (كان الرصيد: {$oldSize->quantity})",
                ]);
                // حذف القياس فقط إذا لم يعد موجوداً في القائمة الجديدة
                $oldSize->delete();
                // إزالة من existingSizes أيضاً
                unset($existingSizes[$sizeName]);
            }
        }

        // Update sizes - تحديث القياسات الموجودة بدلاً من حذفها وإنشاء قياسات جديدة
        // هذا يحافظ على size_id في order_items
        foreach ($request->sizes as $sizeData) {
            $oldQuantity = null;

            if (isset($existingSizes[$sizeData['size_name']])) {
                // تحديث القياس الموجود (حتى لو كانت الكمية 0، نحافظ على size_id)
                $size = $existingSizes[$sizeData['size_name']];
                $oldQuantity = $size->quantity; // حفظ الكمية القديمة قبل التحديث
                $size->quantity = $sizeData['quantity'];
                $size->save();
            } else {
                // إنشاء قياس جديد
                $size = ProductSize::create([
                    'product_id' => $product->id,
                    'size_name' => $sizeData['size_name'],
                    'quantity' => $sizeData['quantity'],
                ]);
            }

            // تسجيل حركة التعديل عند تغيير الكمية
            if ($oldQuantity !== null) {
                // القياس موجود، حساب الفرق من الكمية القديمة المحفوظة
                $quantityDifference = $sizeData['quantity'] - $oldQuantity;

                // تسجيل الحركة فقط إذا تغيرت الكمية
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
        $disk = $this->getStorageDisk();

        // حذف الصور التي لم يتم الاحتفاظ بها
        $keepImageIds = $request->keep_images ?? [];
        foreach ($product->images as $oldImage) {
            if (!in_array($oldImage->id, $keepImageIds)) {
                Storage::disk($disk)->delete($oldImage->image_path);
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
                $path = $image->store('products', $disk);

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

                        Storage::disk($disk)->put($path, $imageContent);

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

        // التحقق من وجود back_url وإذا كان موجوداً، نعيد التوجيه إليه (نفس منطق OrderController)
        $backUrl = $request->input('back_url');
        if ($backUrl) {
            $backUrl = urldecode($backUrl);
            $parsed = parse_url($backUrl);
            $currentHost = $request->getHost();

            // التحقق من أن back_url من نفس النطاق (Security check)
            if (isset($parsed['host']) && $parsed['host'] !== $currentHost) {
                $backUrl = null;
            }
        }

        // إذا كان back_url موجوداً وصحيحاً، نعيد التوجيه إليه مع إضافة #product-{id} للانتقال مباشرة إلى المنتج
        if ($backUrl) {
            // إضافة #product-{id} إلى URL للانتقال مباشرة إلى المنتج
            // إزالة أي hash موجود مسبقاً وإضافة hash جديد
            $backUrl = preg_replace('/#.*$/', '', $backUrl);
            $backUrl .= '#product-' . $product->id;
            return redirect($backUrl)->with('success', 'تم تحديث المنتج بنجاح');
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
        $disk = $this->getStorageDisk();

        foreach ($product->images as $image) {
            Storage::disk($disk)->delete($image->image_path);
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

    /**
     * Toggle product hidden status
     */
    public function toggleHidden(Request $request, Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذه العملية'
            ], 403);
        }

        $request->validate([
            'is_hidden' => 'required|boolean',
        ]);

        $product->update([
            'is_hidden' => $request->is_hidden,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->is_hidden ? 'تم حجب المنتج بنجاح' : 'تم إلغاء حجب المنتج بنجاح',
        ]);
    }

    /**
     * Update product discount
     */
    public function updateDiscount(Request $request, Warehouse $warehouse, Product $product)
    {
        $this->authorize('view', $warehouse);
        $this->authorize('update', $product);

        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتنفيذ هذه العملية'
            ], 403);
        }

        $request->validate([
            'discount_type' => 'required|in:none,amount,percentage',
            'discount_value' => 'nullable|numeric|min:0|required_if:discount_type,amount,percentage',
            'discount_start_date' => 'nullable|date|required_with:discount_end_date',
            'discount_end_date' => 'nullable|date|after_or_equal:discount_start_date|required_with:discount_start_date',
        ]);

        $discountStartDate = $request->discount_start_date
            ? \Carbon\Carbon::parse($request->discount_start_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;
        $discountEndDate = $request->discount_end_date
            ? \Carbon\Carbon::parse($request->discount_end_date, 'Asia/Baghdad')->setTimezone('UTC')
            : null;

        $product->update([
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_type !== 'none' ? $request->discount_value : null,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->discount_type === 'none' ? 'تم إلغاء التخفيض بنجاح' : 'تم تحديث التخفيض بنجاح',
        ]);
    }
}
