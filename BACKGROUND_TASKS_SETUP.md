# إعداد المهام التي تعمل في الخلفية (Background Tasks)

هذا الملف يوضح جميع المهام التي تحتاج تشغيل في الخلفية لتطبيق Laravel.

## 1. Queue Worker (عامل قائمة الانتظار)

### الوصف
يجب تشغيل Queue Worker لتشغيل Jobs في الخلفية. بدون هذا، لن تعمل أي Jobs.

### Jobs الموجودة:
- **CreateAlWaseetShipmentJob**: إنشاء شحنة الواسط تلقائياً عند إنشاء طلب جديد
- **SyncAlWaseetOrdersJob**: مزامنة طلبات الواسط من API
- **ProcessAlWaseetQueueJob**: معالجة قائمة انتظار الواسط

### طريقة التشغيل:

#### للتطوير المحلي (Development):
```bash
php artisan queue:work
```

#### للإنتاج (Production):
يجب تشغيله كخدمة دائمة. يمكن استخدام Supervisor أو systemd.

**مثال Supervisor:**
```ini
[program:parana-kids-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/queue-worker.log
stopwaitsecs=3600
```

### التحقق من حالة Queue:
```bash
# عرض عدد Jobs المعلقة
php artisan queue:monitor

# عرض Jobs الفاشلة
php artisan queue:failed
```

---

## 2. Task Scheduler (جدولة المهام)

### الوصف
يجب تشغيل Scheduler لتشغيل المهام المجدولة تلقائياً.

### المهام المجدولة:

1. **carts:expire** - كل دقيقة
   - انتهاء صلاحية السلات المنتهية

2. **carts:archive-expired** - كل 5 دقائق
   - أرشفة السلات المنتهية

3. **orders:delete-old-trashed** - يومياً الساعة 2 صباحاً
   - حذف الطلبات المحذوفة القديمة

4. **promotions:expire** - كل ساعة
   - إيقاف التخفيضات المنتهية

5. **product-links:delete-expired** - كل دقيقة
   - حذف روابط المنتجات المنتهية (بعد ساعتين من الإنشاء)

6. **مزامنة طلبات الواسط** - كل دقيقة (إذا كانت مفعلة)
   - مزامنة تلقائية لطلبات الواسط من API
   - يتم التحقق من الفترة المحددة في الإعدادات

### طريقة التشغيل:

#### للتطوير المحلي (Development):
```bash
php artisan schedule:work
```

#### للإنتاج (Production):
يجب إضافة سطر في crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**أو استخدام Supervisor:**
```ini
[program:parana-kids-scheduler]
command=php /path/to/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/scheduler.log
```

### التحقق من حالة Scheduler:
```bash
# عرض المهام المجدولة
php artisan schedule:list

# تشغيل المهام يدوياً (للتجربة)
php artisan schedule:run
```

---

## 3. إعدادات الواسط (AlWaseet)

### المزامنة التلقائية:
- يمكن تفعيل/تعطيل المزامنة التلقائية من الإعدادات
- يمكن تحديد الفترة الزمنية للمزامنة (بالدقائق)
- يمكن تحديد حالات الطلبات المراد مزامنتها

### الإعدادات المطلوبة:
- `alwaseet_auto_sync_enabled`: تفعيل/تعطيل المزامنة التلقائية (0 أو 1)
- `alwaseet_auto_sync_interval`: الفترة الزمنية بين المزامنات (بالدقائق)
- `alwaseet_auto_sync_status_ids`: حالات الطلبات المراد مزامنتها (مفصولة بفواصل)
- `alwaseet_auto_create_shipment`: إنشاء شحنة تلقائياً عند إنشاء طلب (0 أو 1)

---

## 4. الأوامر المتاحة

### أوامر Queue:
```bash
# تشغيل Queue Worker
php artisan queue:work

# معالجة Jobs محددة
php artisan queue:work --queue=high,default

# إعادة محاولة Jobs الفاشلة
php artisan queue:retry all

# حذف Jobs الفاشلة
php artisan queue:flush
```

### أوامر Scheduler:
```bash
# عرض المهام المجدولة
php artisan schedule:list

# تشغيل المهام يدوياً
php artisan schedule:run

# تشغيل Scheduler في وضع المراقبة (للتطوير)
php artisan schedule:work
```

### أوامر مخصصة:
```bash
# مزامنة طلبات الواسط يدوياً
php artisan alwaseet:sync [--status-id=] [--date-from=] [--date-to=]

# انتهاء صلاحية السلات
php artisan carts:expire

# أرشفة السلات المنتهية
php artisan carts:archive-expired

# حذف الطلبات المحذوفة القديمة
php artisan orders:delete-old-trashed

# إيقاف التخفيضات المنتهية
php artisan promotions:expire

# حذف روابط المنتجات المنتهية
php artisan product-links:delete-expired
```

---

## 5. مراقبة الأداء

### فحص حالة Queue:
```bash
# عدد Jobs المعلقة
php artisan queue:monitor

# عرض Jobs الفاشلة
php artisan queue:failed

# عرض تفاصيل Job فاشل
php artisan queue:failed:show {id}
```

### فحص Logs:
```bash
# عرض logs المهام
tail -f storage/logs/laravel.log

# عرض logs Queue Worker
tail -f storage/logs/queue-worker.log

# عرض logs Scheduler
tail -f storage/logs/scheduler.log
```

---

## 6. استكشاف الأخطاء

### المشكلة: Jobs لا تعمل
**الحل:**
1. تأكد من تشغيل `php artisan queue:work`
2. تحقق من إعدادات Queue في `config/queue.php`
3. تأكد من أن جدول `jobs` موجود في قاعدة البيانات

### المشكلة: المهام المجدولة لا تعمل
**الحل:**
1. تأكد من إضافة crontab أو تشغيل `php artisan schedule:work`
2. تحقق من logs في `storage/logs/scheduler.log`
3. جرب تشغيل `php artisan schedule:run` يدوياً

### المشكلة: المزامنة التلقائية لا تعمل
**الحل:**
1. تأكد من تفعيل `alwaseet_auto_sync_enabled` في الإعدادات
2. تحقق من أن Scheduler يعمل
3. راجع logs في `storage/logs/laravel.log`

---

## 7. ملاحظات مهمة

1. **Queue Worker** يجب أن يعمل دائماً في الإنتاج
2. **Scheduler** يحتاج crontab أو Supervisor في الإنتاج
3. في التطوير المحلي، يمكن استخدام `php artisan schedule:work` و `php artisan queue:work` في نوافذ منفصلة
4. تأكد من أن صلاحيات الملفات صحيحة (`storage/logs` يجب أن يكون قابل للكتابة)
5. في حالة استخدام Supervisor، تأكد من إعادة تشغيله بعد أي تحديثات

---

## 8. مثال إعداد كامل للإنتاج

### Supervisor Configuration (`/etc/supervisor/conf.d/parana-kids.conf`):
```ini
[program:parana-kids-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/parana-kids/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/parana-kids/storage/logs/queue-worker.log
stopwaitsecs=3600

[program:parana-kids-scheduler]
command=php /var/www/parana-kids/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/parana-kids/storage/logs/scheduler.log
```

### Crontab (بديل لـ Supervisor للـ Scheduler):
```bash
* * * * * cd /var/www/parana-kids && php artisan schedule:run >> /dev/null 2>&1
```

### بعد التعديل:
```bash
# إعادة تحميل Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start parana-kids-queue-worker:*
sudo supervisorctl start parana-kids-scheduler
```

---

**آخر تحديث:** 2025-01-27

