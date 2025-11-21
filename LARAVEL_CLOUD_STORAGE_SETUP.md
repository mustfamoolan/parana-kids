# إعداد Persistent Storage في Laravel Cloud

## المشكلة

عند كل deployment في Laravel Cloud:
1. يتم حذف `public/storage` symlink لأنه غير موجود في Git
2. يتم حذف الملفات في `storage/app/public` إذا لم يكن Persistent Storage مفعّل
3. يجب إعادة إنشاء symlink يدوياً بعد كل deployment

## الحل: تفعيل Persistent Storage

### الخطوة 1: تفعيل Persistent Storage في Laravel Cloud

1. اذهب إلى Laravel Cloud Dashboard
2. اختر مشروعك
3. اذهب إلى **Settings** → **Storage** أو **Persistent Storage**
4. فعّل **Persistent Storage** للمجلد: `storage/app/public`
5. احفظ الإعدادات

### الخطوة 2: التأكد من استخدام المسار الصحيح

✅ **صحيح**: استخدام `Storage::disk('public')` أو `->store('...', 'public')`
```php
// حفظ صورة
$path = $request->file('image')->store('products', 'public');
// أو
Storage::disk('public')->put('products/image.jpg', $fileContent);
```

❌ **خطأ**: حفظ الملفات في `public/uploads` أو `public/images`
```php
// ❌ لا تفعل هذا
$request->file('image')->move(public_path('uploads'), $filename);
```

### الخطوة 3: التحقق من الإعدادات

#### في `config/filesystems.php`:

```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),  // ✅ المسار الصحيح
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
    'throw' => false,
],
```

#### في `app/Providers/AppServiceProvider.php`:

الكود الحالي يقوم تلقائياً بـ:
- إنشاء `storage/app/public` إذا لم يكن موجوداً
- إنشاء `public/storage` symlink تلقائياً عند كل deployment
- التحقق من صحة الرابط وإعادة إنشائه إذا لزم الأمر

### الخطوة 4: التحقق من عمل النظام

بعد deployment:

1. **تحقق من وجود symlink**:
   ```bash
   ls -la public/storage
   # يجب أن ترى: public/storage -> ../storage/app/public
   ```

2. **تحقق من وجود الملفات**:
   ```bash
   ls storage/app/public/products
   # يجب أن ترى الملفات المرفوعة سابقاً
   ```

3. **اختبر رفع صورة جديدة**:
   - ارفع صورة من التطبيق
   - تحقق من وجودها في `storage/app/public`
   - قم بعمل deployment جديد
   - تحقق من أن الصورة لا تزال موجودة

## الكود الحالي في المشروع

### Controllers تستخدم المسار الصحيح:

✅ **ProductController.php**:
```php
$path = $image->store('products', 'public');
Storage::disk('public')->put($path, $imageContent);
Storage::disk('public')->delete($oldImage->image_path);
```

✅ **ChatController.php**:
```php
$imagePath = $image->store('messages', 'public');
```

✅ **SettingController.php** (Profile Images):
```php
$path = $image->storeAs('profiles', $filename, 'public');
Storage::disk('public')->delete($user->profile_image);
```

### Models تستخدم المسار الصحيح:

✅ **ProductImage.php**:
```php
public function getImageUrlAttribute()
{
    return asset('storage/' . $this->image_path);
}
```

✅ **User.php** (Profile Images):
```php
public function getProfileImageUrl()
{
    if ($this->profile_image) {
        return asset('storage/' . $this->profile_image);
    }
    // Default image...
}
```

## ملاحظات مهمة

1. **Persistent Storage في Laravel Cloud**:
   - Laravel Cloud يقوم تلقائياً بعمل Mount للمجلد المحدد
   - الملفات في `storage/app/public` تبقى موجودة بين deployments
   - لا حاجة لنسخ الملفات يدوياً

2. **Symlink التلقائي**:
   - `AppServiceProvider` يقوم بإنشاء symlink تلقائياً عند كل deployment
   - لا حاجة لتشغيل `php artisan storage:link` يدوياً

3. **التحقق من الإعدادات**:
   - تأكد من تفعيل Persistent Storage في Laravel Cloud
   - تأكد من أن المسار المحدد هو `storage/app/public`
   - تأكد من أن الكود يستخدم `Storage::disk('public')`

## حل المشاكل

### المشكلة: الصور لا تزال تُحذف بعد deployment

**الحل**:
1. تحقق من تفعيل Persistent Storage في Laravel Cloud
2. تحقق من أن المسار المحدد هو `storage/app/public`
3. تحقق من أن الملفات تُحفظ في `storage/app/public` وليس `public/`

### المشكلة: Symlink لا يُنشأ تلقائياً

**الحل**:
1. تحقق من logs في Laravel Cloud
2. تحقق من أن `AppServiceProvider` يعمل بشكل صحيح
3. يمكنك إضافة `php artisan storage:link` إلى deployment script كحل بديل

### المشكلة: 404 عند الوصول للصور

**الحل**:
1. تحقق من وجود symlink: `ls -la public/storage`
2. تحقق من وجود الملفات: `ls storage/app/public/products`
3. أعد إنشاء symlink: `php artisan storage:link`

## Deployment Script (اختياري)

إذا أردت إضافة `php artisan storage:link` إلى deployment script:

في Laravel Cloud → Settings → Build & Deploy → Deploy Script:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**ملاحظة**: مع `AppServiceProvider` المحسّن، هذا غير ضروري، لكنه لا يضر.

---

**الخلاصة**: 
- ✅ فعّل Persistent Storage في Laravel Cloud للمجلد `storage/app/public`
- ✅ استخدم `Storage::disk('public')` في الكود
- ✅ `AppServiceProvider` يقوم بإنشاء symlink تلقائياً
- ✅ الملفات تبقى موجودة بين deployments

