# إعداد Storage Link لعرض الصور

## ما هو Storage Link؟

`storage:link` هو أمر Laravel ينشئ **symbolic link** (رابط رمزي) من `public/storage` إلى `storage/app/public`، مما يسمح للمتصفح بالوصول إلى الملفات المخزنة في `storage/app/public` عبر URL عام.

## الحالة الحالية

✅ **الرابط موجود ويعمل بشكل صحيح!**

- الرابط: `public/storage` → `storage/app/public`
- الصور المخزنة في: `storage/app/public/products/` و `storage/app/public/messages/`
- الوصول عبر: `http://your-domain.com/storage/products/image.png`

## كيفية استخدامه في الكود

### 1. حفظ الصور

```php
use Illuminate\Support\Facades\Storage;

// حفظ صورة منتج
$path = $request->file('image')->store('products', 'public');
// النتيجة: 'products/image-name.jpg'

// حفظ صورة رسالة
$path = $request->file('image')->store('messages', 'public');
// النتيجة: 'messages/image-name.png'
```

### 2. عرض الصور في Blade

```blade
{{-- طريقة 1: استخدام asset() --}}
<img src="{{ asset('storage/' . $productImage->image_path) }}" alt="Product">

{{-- طريقة 2: استخدام Storage::url() --}}
<img src="{{ Storage::url($productImage->image_path) }}" alt="Product">

{{-- مثال من الكود الحالي --}}
<img src="{{ $product->primaryImage->image_url }}" alt="Product">
```

### 3. في Models (Accessors)

```php
// في ProductImage.php
public function getImageUrlAttribute()
{
    return asset('storage/' . $this->image_path);
}

// الاستخدام:
$productImage->image_url; // يعيد: http://domain.com/storage/products/image.jpg
```

### 4. حذف الصور

```php
use Illuminate\Support\Facades\Storage;

// حذف صورة
Storage::disk('public')->delete($imagePath);
// أو
Storage::delete('public/' . $imagePath);
```

## إعادة إنشاء الرابط

### في التطوير المحلي (Windows)

```bash
php artisan storage:link
```

### في Laravel Cloud / Production

في Laravel Cloud، قد تحتاج إلى إعادة إنشاء الرابط بعد كل deployment. يمكنك:

#### الطريقة 1: إضافة إلى Deployment Script

في Laravel Cloud → Settings → Build & Deploy، أضف:

```bash
php artisan storage:link
```

#### الطريقة 2: إضافة إلى AppServiceProvider

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if ($this->app->environment('production')) {
        $link = public_path('storage');
        $target = storage_path('app/public');
        
        if (!file_exists($link)) {
            symlink($target, $link);
        }
    }
}
```

## التحقق من عمل الرابط

### 1. التحقق من وجود الرابط

```bash
# Windows
dir public\storage

# Linux/Mac
ls -la public/storage
```

### 2. اختبار الوصول للصور

افتح في المتصفح:
```
http://localhost:8000/storage/products/[اسم-صورة-موجودة].png
```

إذا ظهرت الصورة = الرابط يعمل ✅
إذا ظهر 404 = الرابط غير موجود ❌

## هيكل الملفات

```
storage/
└── app/
    └── public/          ← هنا تُحفظ الصور
        ├── products/    ← صور المنتجات
        └── messages/   ← صور الرسائل

public/
└── storage/            ← رابط رمزي (symbolic link)
    ├── products/       ← يشير إلى storage/app/public/products
    └── messages/       ← يشير إلى storage/app/public/messages
```

## ملاحظات مهمة

1. **لا تحذف `storage/app/public`**: هذا هو المكان الفعلي للملفات
2. **`public/storage` هو رابط فقط**: حذفه لا يحذف الملفات الفعلية
3. **في Git**: أضف `public/storage` إلى `.gitignore` (عادة موجود بالفعل)
4. **الأمان**: الملفات في `storage/app/public` متاحة للجميع عبر المتصفح

## حل المشاكل الشائعة

### المشكلة: الصور لا تظهر (404)

**الحل:**
```bash
# حذف الرابط القديم (إن وجد)
rm public/storage  # Linux/Mac
rmdir public\storage  # Windows

# إنشاء رابط جديد
php artisan storage:link
```

### المشكلة: "Link already exists"

**الحل:**
```bash
# حذف الرابط القديم أولاً
rm public/storage  # Linux/Mac
rmdir /s public\storage  # Windows

# ثم إنشاء رابط جديد
php artisan storage:link
```

### المشكلة: الصور لا تظهر في Laravel Cloud

**الحل:**
1. تأكد من وجود `storage/app/public` في Git (أو رفعه يدوياً)
2. أضف `php artisan storage:link` إلى deployment script
3. أو استخدم `AppServiceProvider` كما ذكرنا أعلاه

## الملفات الحالية في المشروع

- ✅ `storage/app/public/products/` - صور المنتجات
- ✅ `storage/app/public/messages/` - صور الرسائل
- ✅ `public/storage` - الرابط الرمزي (موجود ويعمل)

## الكود الحالي في المشروع

### Models تستخدم Storage:

1. **ProductImage.php**:
   ```php
   public function getImageUrlAttribute()
   {
       return asset('storage/' . $this->image_path);
   }
   ```

2. **Message.php**:
   ```php
   public function getImageUrlAttribute()
   {
       if (!$this->image_path) {
           return null;
       }
       return asset('storage/' . $this->image_path);
   }
   ```

### Controllers تستخدم Storage:

- `ProductController.php` - حفظ/حذف صور المنتجات
- `ChatController.php` - حفظ/عرض صور الرسائل

---

**الخلاصة**: الرابط موجود ويعمل! الصور تُحفظ في `storage/app/public` وتُعرض عبر `public/storage` (الرابط الرمزي).

