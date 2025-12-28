# تعليمات النشر على السيرفر

## جميع التعديلات تم رفعها بنجاح

تم رفع جميع التعديلات المتعلقة بنظام المستثمرين والأرباح على GitHub في التاريخ: 28/12/2025

### الـ Commits المهمة (بالترتيب من الأحدث):

1. **5acda26** - Cleanup: Remove old documentation files and update mobile routes
2. **d82fb8e** - Complete fix for investor profit filtering issue - All three files with all changes
3. **7d95b00** - Fix: Filter investor profits by investments in investor dashboard
4. **0299064** - Fix: Filter pending profits by investments in depositProfits
5. **7a93eb0** - Fix: Only distribute profits for products/warehouses with active investments
6. **d6dfd0b** - Fix investor profit filtering - Remove eager loading of unfiltered profits
7. **35e6b22** - Add cache clearing script and fix for old project transactions
8. **dd417d7** - Fix: Only record sale amounts for projects with active investments
9. **87f4147** - Reduce products per page from 50 to 25 for better performance
10. **c77b0da** - Optimize sales report: Remove Product::all() and improve queries

### الملفات الرئيسية التي تم تعديلها:

1. **app/Services/InvestorProfitCalculator.php**
   - إضافة دالة `checkHasActiveInvestment()` للتحقق من الاستثمارات النشطة
   - تعديل `distributeOrderProfits()` لتوزيع الأرباح فقط للمنتجات/المخازن التي لديها استثمارات نشطة
   - تعديل `recordOrderSaleAmounts()` لتسجيل المبيعات فقط للمشاريع التي لديها استثمارات نشطة
   - إزالة جميع عمليات التقريب (`roundToNearestCurrency`)

2. **app/Http/Controllers/Admin/InvestorController.php**
   - تعديل `show()` لجلب فقط أرباح الاستثمارات الفعلية للمستثمر
   - تعديل `depositProfits()` لفلترة الأرباح المعلقة حسب الاستثمارات الفعلية
   - إزالة eager loading للأرباح غير المفلترة

3. **app/Http/Controllers/Investor/InvestorController.php**
   - تعديل `dashboard()` لفلترة الأرباح حسب الاستثمارات الفعلية

4. **app/Http/Controllers/Admin/SalesReportController.php**
   - تحسين الأداء بإزالة `Product::all()`
   - حساب أرباح المدير فقط (باستثناء المشاريع بدون مستثمرين)

5. **app/Http/Controllers/Admin/ProjectController.php**
   - إزالة منطق خزنة المشروع الرئيسية
   - دمج صفحة المستثمرين مع المشاريع

### خطوات النشر على Laravel Cloud:

#### الخطوة 1: سحب التحديثات
```bash
cd /path/to/project
git fetch origin
git pull origin main
```

#### الخطوة 2: مسح الـ Cache (مهم جداً!)
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
```

#### الخطوة 3: إعادة تحسين التطبيق
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### الخطوة 4: (اختياري) تصليح البيانات القديمة
إذا كان هناك بيانات خاطئة من قبل، استخدم:
```bash
php fix_old_project_transactions.php
```

### ملاحظات مهمة:

1. **مشكلة الـ Cache**: إذا استمرت المشكلة، قد يكون السبب هو أن Laravel Cloud يستخدم Opcache أو Cache قوي آخر. في هذه الحالة:
   - قم بإعادة تشغيل الـ workers
   - أو انتظر بضع دقائق حتى ينتهي TTL للـ cache

2. **التحقق من التحديثات**: بعد النشر، تحقق من:
   - صفحة المستثمر: `admin/investors/{id}` يجب أن تعرض فقط أرباح استثماراته
   - كشف المبيعات: `admin/sales-report` يجب أن يعرض فقط ربح المدير
   - لوحة المستثمر: `investor/dashboard` يجب أن تعرض فقط أرباحه الفعلية

3. **البيانات القديمة**: إذا كان هناك transactions خاطئة في قاعدة البيانات من قبل، استخدم السكربت `fix_old_project_transactions.php` لتصحيحها.

### المشاكل التي تم حلها:

✅ الأرباح من مخازن غير مستثمر بها لا تظهر للمدير
✅ فلترة الأرباح حسب الاستثمارات الفعلية
✅ إزالة التقريب من الأرباح
✅ تحسين أداء صفحة كشف المبيعات
✅ حساب أرباح المدير بشكل صحيح في كشف المبيعات
✅ توزيع الأرباح فقط للمنتجات/المخازن التي لديها استثمارات نشطة
✅ تسجيل المبيعات فقط للمشاريع التي لديها استثمارات نشطة

---

**آخر تحديث**: 28/12/2025 الساعة 23:59
**Branch**: main
**Latest Commit**: 5acda26

