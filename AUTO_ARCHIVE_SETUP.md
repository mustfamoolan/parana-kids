# نظام الأرشفة التلقائية للطلبات النشطة

## الوصف
يقوم النظام بأرشفة الطلبات النشطة (السلات) التي مر عليها **أكثر من ساعة** بدون إتمام تلقائياً.

---

## كيف يعمل؟

### 1. عند فتح طلب جديد
- المندوب يدخل معلومات الزبون
- يتم إنشاء سلة نشطة (`status = 'active'`)
- يبدأ العد التنازلي (ساعة واحدة)

### 2. خلال الساعة
- المندوب يضيف منتجات
- المخزون محجوز (reservations)
- الطلب نشط ومرئي

### 3. بعد مرور ساعة
إذا لم يتم إرسال الطلب:
- ✅ يتم أرشفة الطلب تلقائياً
- ✅ إرجاع المنتجات إلى المخزون
- ✅ تسجيل حركة في `product_movements` (نوع: `archive`)
- ✅ نقل البيانات إلى `archived_orders`
- ✅ حذف السلة النشطة

### 4. الاسترجاع
المندوب يمكنه:
- عرض الطلبات المؤرشفة من صفحة الأرشيف
- استرجاع الطلب (يخصم من المخزون مرة أخرى)
- حذف الطلب نهائياً

---

## تشغيل النظام

### الطريقة 1: Scheduler (للإنتاج)

في السيرفر، قم بإضافة هذا السطر إلى `cron`:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**ملاحظة:** 
- استبدل `/path-to-your-project` بمسار المشروع الفعلي
- هذا السطر يجب أن يُضاف مرة واحدة فقط
- سيعمل تلقائياً كل دقيقة ويتحقق من الجدولة

### الطريقة 2: التشغيل اليدوي (للتطوير)

```bash
php artisan carts:archive-expired
```

---

## الإعدادات

### تغيير المدة الزمنية

في `app/Console/Commands/ArchiveExpiredCarts.php`:

```php
// سطر 37 - لتغيير من ساعة إلى نصف ساعة مثلاً:
->where('created_at', '<=', Carbon::now()->subMinutes(30))

// أو ساعتين:
->where('created_at', '<=', Carbon::now()->subHours(2))
```

### تغيير تكرار الفحص

في `app/Console/Kernel.php`:

```php
// كل 5 دقائق (الافتراضي):
$schedule->command('carts:archive-expired')->everyFiveMinutes();

// أو كل دقيقة:
$schedule->command('carts:archive-expired')->everyMinute();

// أو كل 10 دقائق:
$schedule->command('carts:archive-expired')->everyTenMinutes();
```

---

## التحقق من التشغيل

### 1. قائمة الـ Scheduled Commands:
```bash
php artisan schedule:list
```

يجب أن ترى:
```
0 * * * * php artisan carts:archive-expired .... Next Due: 5 minutes from now
```

### 2. تشغيل يدوي للاختبار:
```bash
php artisan carts:archive-expired
```

### 3. مراقبة اللوج:
```bash
tail -f storage/logs/laravel.log
```

---

## الملفات المعدلة

1. **app/Console/Commands/ArchiveExpiredCarts.php** - الـ Command الرئيسي
2. **app/Console/Kernel.php** - جدولة الـ Command
3. **database/migrations/xxxx_create_archived_orders_table.php** - جدول الأرشيف
4. **app/Models/ArchivedOrder.php** - Model الأرشيف

---

## استكشاف الأخطاء

### المشكلة: Scheduler لا يعمل
**الحل:**
- تأكد من إضافة cron job في السيرفر
- تحقق من صلاحيات التنفيذ: `chmod +x artisan`

### المشكلة: لا يتم الأرشفة
**الحل:**
- تحقق من وجود سلات منتهية: `php artisan tinker`
  ```php
  Cart::where('status', 'active')->where('created_at', '<=', now()->subHour())->count();
  ```

### المشكلة: خطأ في الأرشفة
**الحل:**
- افحص اللوج: `storage/logs/laravel.log`
- شغل Command يدوياً لرؤية الأخطاء: `php artisan carts:archive-expired`

---

## ملاحظات مهمة

⚠️ **تأكد من تشغيل Scheduler في السيرفر للإنتاج**
⚠️ **في التطوير (localhost)، شغل Command يدوياً للاختبار**
✅ **النظام يعمل تلقائياً بدون تدخل بعد الإعداد**
✅ **المخزون يُرجع تلقائياً عند الأرشفة**
✅ **يمكن استرجاع الطلبات المؤرشفة**

---

تم إنشاء هذا الملف بواسطة AI Assistant في 2025-10-28

