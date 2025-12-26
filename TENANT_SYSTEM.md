# نظام Multi-Tenancy - التوثيق الشامل

## نظرة عامة

تم إنشاء نظام Multi-Tenancy منفصل تماماً عن النظام الحالي للسماح بإدارة شركات متعددة. كل شركة لها بياناتها الخاصة (مخازن، منتجات، مستخدمين، طلبات) معزولة تماماً عن الشركات الأخرى.

## البنية المعمارية

### قاعدة البيانات

جميع الجداول الجديدة تبدأ بـ `tenant_`:

- `tenant_companies` - بيانات الشركات
- `tenant_users` - مستخدمي الشركات
- `tenant_warehouses` - مخازن الشركات
- `tenant_products` - منتجات الشركات
- `tenant_orders` - الطلبات
- ... وجداول أخرى

### المسارات

النظام الجديد يعمل على المسار: `/tenant/{company}/*`

مثال:
- `/tenant/company-a/login` - تسجيل دخول الشركة A
- `/tenant/company-a/admin/dashboard` - لوحة تحكم المدير
- `/tenant/company-a/delegate/dashboard` - لوحة تحكم المندوب

### Models

جميع Models الجديدة موجودة في `app/Models/Tenant/`:

- `Company.php` - Model الشركة
- `TenantUser.php` - Model المستخدم
- `Warehouse.php` - Model المخزن
- `Product.php` - Model المنتج
- ... وغيرها

### Trait للعزل التلقائي

تم إنشاء Trait `BelongsToTenantCompany` الذي يضيف:

1. **Global Scope**: فلترة تلقائية حسب `company_id`
2. **Auto-assignment**: إضافة `company_id` تلقائياً عند الإنشاء
3. **Relationship**: علاقة مع `Company`

### Middleware

1. **IdentifyTenant**: التعرف على الشركة من URL
2. **TenantAuth**: التحقق من تسجيل الدخول
3. **TenantAdmin**: التحقق من صلاحيات Admin/Supplier

### Authentication

تم إنشاء Guard جديد `tenant` في `config/auth.php`:

```php
'guards' => [
    'tenant' => [
        'driver' => 'session',
        'provider' => 'tenant_users',
    ],
],
```

## كيفية الاستخدام

### إنشاء شركة جديدة (للمدير الأساسي)

1. الدخول إلى `/admin/companies`
2. النقر على "إضافة شركة جديدة"
3. ملء البيانات:
   - اسم الشركة
   - بيانات المالك
   - بيانات المدير (admin) للشركة
4. سيتم إنشاء الشركة تلقائياً مع مستخدم admin

### تسجيل الدخول للشركة

1. الدخول إلى `/tenant/{company-slug}/login`
2. استخدام بيانات المدير التي تم إنشاؤها
3. سيتم التوجيه تلقائياً حسب الدور

### إدارة الشركات

المدير الأساسي يمكنه:
- عرض قائمة الشركات
- إنشاء شركات جديدة
- تعديل بيانات الشركات
- تفعيل/تعطيل الشركات
- حذف الشركات
- عرض إحصائيات عامة

## الأمان والعزل

### عزل البيانات

- كل Model يستخدم `BelongsToTenantCompany` trait
- Global Scope يضمن عرض بيانات الشركة الحالية فقط
- Middleware يتحقق من أن المستخدم ينتمي للشركة الصحيحة

### الصلاحيات

- **Admin (في الشركة)**: إدارة كاملة للشركة
- **Supplier**: إدارة المخازن والمنتجات
- **Delegate**: إنشاء الطلبات فقط

## الملفات الرئيسية

### Migrations
- `database/migrations/2025_12_18_000001_create_tenant_companies_table.php`
- `database/migrations/2025_12_18_000002_create_tenant_users_table.php`
- ... (29 migration إجمالاً)

### Models
- `app/Models/Tenant/Company.php`
- `app/Models/Tenant/TenantUser.php`
- `app/Models/Tenant/Traits/BelongsToTenantCompany.php`
- ... (20+ model)

### Controllers
- `app/Http/Controllers/Tenant/Auth/TenantLoginController.php`
- `app/Http/Controllers/Tenant/Admin/*`
- `app/Http/Controllers/Tenant/Delegate/*`
- `app/Http/Controllers/Admin/CompanyManagementController.php`

### Routes
- `routes/tenant.php` - مسارات النظام الجديد
- `routes/web.php` - مسارات إدارة الشركات (مضاف)

### Middleware
- `app/Http/Middleware/IdentifyTenant.php`
- `app/Http/Middleware/TenantAuth.php`
- `app/Http/Middleware/TenantAdmin.php`

## ملاحظات مهمة

1. **النظام الحالي**: لم يتم تعديله أبداً، يعمل بشكل طبيعي
2. **العزل التام**: كل شركة معزولة تماماً عن الأخرى
3. **سهولة الصيانة**: التعديلات المستقبلية واضحة ومنظمة
4. **قابلية التوسع**: يمكن إضافة ميزات جديدة بسهولة

## الخطوات التالية

1. إنشاء Views للواجهات (نسخ من admin/ و delegate/)
2. إنشاء Policies للصلاحيات
3. اختبار شامل للنظام
4. إضافة ميزات إضافية حسب الحاجة

## الدعم

لأي استفسارات أو مشاكل، يرجى مراجعة الكود أو التواصل مع فريق التطوير.
