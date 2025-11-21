# إعداد Bucket في Laravel Cloud لحفظ صور المنتجات

## المشكلة
النظام الحالي يحفظ الصور في `storage/app/public` مما يسبب خطأ 500 في Laravel Cloud. الحل هو استخدام Bucket (Object Storage) المدمج في Laravel Cloud.

## الخطوات

### 1. إضافة Bucket في Laravel Cloud

1. اذهب إلى **Laravel Cloud** → **Your App** → **Resources**
2. اضغط على **Add object storage**
3. اختر **Create new bucket** أو استخدم bucket موجود
4. اختر **Visibility**: **Private** (للملفات الخاصة) أو **Public** (للملفات العامة)
5. احفظ اسم الـ Bucket

### 2. إضافة Environment Variables في Laravel Cloud

في Laravel Cloud، يتم توفير متغيرات البيئة تلقائياً للـ Bucket. لكن يمكنك إضافة المتغيرات التالية يدوياً إذا لزم الأمر:

1. اذهب إلى **Laravel Cloud** → **Your App** → **Environment** → **Settings**
2. اضغط على **Environment Variables**
3. أضف المتغيرات التالية (إذا لم تكن موجودة تلقائياً):

```
CLOUD_STORAGE_KEY=your-access-key
CLOUD_STORAGE_SECRET=your-secret-key
CLOUD_STORAGE_REGION=us-east-1
CLOUD_STORAGE_BUCKET=your-bucket-name
CLOUD_STORAGE_URL=https://your-bucket-url.s3.amazonaws.com
CLOUD_STORAGE_ENDPOINT=https://s3.amazonaws.com
CLOUD_STORAGE_USE_PATH_STYLE=false
```

**ملاحظة**: في Laravel Cloud، عادة ما يتم توفير هذه المتغيرات تلقائياً. تحقق من **Environment Variables** في Settings.

### 3. تثبيت AWS SDK (إذا لم يكن مثبتاً)

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

### 4. التحقق من الكود

الكود تم تعديله لاستخدام Bucket تلقائياً إذا كان متاحاً:

- **ProductController**: يستخدم `cloud` disk إذا كان `CLOUD_STORAGE_BUCKET` موجوداً
- **ChatController**: يستخدم `cloud` disk للرسائل أيضاً
- **ProductImage Model**: يستخدم `Storage::url()` لعرض الصور
- **Message Model**: يستخدم `Storage::url()` لعرض الصور

### 5. اختبار النظام

1. ارفع صورة منتج جديدة
2. تحقق من أن الصورة تم حفظها في Bucket
3. تحقق من أن الصورة تظهر في الواجهة

## الملفات المعدلة

- `config/filesystems.php`: إضافة `cloud` disk
- `app/Http/Controllers/Admin/ProductController.php`: استخدام `cloud` disk
- `app/Http/Controllers/ChatController.php`: استخدام `cloud` disk
- `app/Models/ProductImage.php`: استخدام `Storage::url()`
- `app/Models/Message.php`: استخدام `Storage::url()`

## ملاحظات مهمة

1. **الصور القديمة**: الصور القديمة المحفوظة في `storage/app/public` ستبقى كما هي. الصور الجديدة فقط ستُحفظ في Bucket.

2. **Migration**: إذا أردت نقل الصور القديمة إلى Bucket، يمكنك إنشاء command:

```php
php artisan make:command MigrateImagesToCloud
```

3. **Fallback**: النظام يستخدم `public` disk كـ fallback إذا لم يكن Bucket متاحاً (مثلاً في التطوير المحلي).

4. **Performance**: استخدام Bucket يحسن الأداء ويقلل الحمل على الخادم.

## حل المشاكل

### خطأ 500 عند رفع الصور

1. تحقق من أن Bucket موجود في Laravel Cloud
2. تحقق من أن Environment Variables موجودة وصحيحة
3. تحقق من logs في Laravel Cloud → Logs
4. تأكد من أن `league/flysystem-aws-s3-v3` مثبت

### الصور لا تظهر

1. تحقق من أن Bucket visibility هو **Public** (أو أن URLs صحيحة)
2. تحقق من أن `CLOUD_STORAGE_URL` صحيح
3. تحقق من أن الصور موجودة في Bucket

### الصور القديمة لا تظهر

الصور القديمة محفوظة في `storage/app/public`. يمكنك:
- نقلها إلى Bucket يدوياً
- أو استخدام `storage:link` للوصول إليها

