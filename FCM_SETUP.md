# إعداد Firebase Cloud Messaging (FCM)

## المتطلبات

1. Firebase project
2. Firebase Web App
3. Service Account JSON file

## خطوات الإعداد

### 1. إنشاء Firebase Project

1. اذهب إلى [Firebase Console](https://console.firebase.google.com/)
2. أنشئ مشروع جديد أو استخدم مشروع موجود
3. أضف Web App إلى المشروع

### 2. الحصول على Firebase Config

من Firebase Console:
1. اذهب إلى Project Settings > General
2. في قسم "Your apps"، اختر Web App
3. انسخ Firebase configuration object

### 3. إعداد Service Account

1. اذهب إلى Project Settings > Service Accounts
2. انقر على "Generate new private key"
3. احفظ ملف JSON في `storage/app/firebase-credentials.json`

### 4. الحصول على VAPID Key

1. اذهب إلى Project Settings > Cloud Messaging
2. في قسم "Web configuration"، انسخ "Web Push certificates"
3. إذا لم يكن موجوداً، انقر على "Generate key pair"

### 5. تحديث ملف .env

أضف المتغيرات التالية إلى ملف `.env`:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
FIREBASE_PROJECT_ID=your-project-id

# Firebase Web App Config
FIREBASE_API_KEY=your-api-key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-sender-id
FIREBASE_APP_ID=your-app-id

# Firebase VAPID Key (للإشعارات في المتصفح)
FIREBASE_VAPID_KEY=your-vapid-key
```

### 6. تثبيت Dependencies

```bash
composer install
```

سيتم تثبيت `kreait/firebase-php` تلقائياً.

### 7. تشغيل Migration

```bash
php artisan migrate
```

سيتم إنشاء جدول `fcm_tokens`.

## كيفية العمل

### Backend

- عند إرسال رسالة جديدة، يتم إرسال إشعار FCM تلقائياً لجميع المشاركين في المحادثة (عدا المرسل)
- الإشعار يحتوي على:
  - العنوان: "رسالة جديدة"
  - النص: "لديك رسالة جديدة"
  - البيانات: `conversation_id`, `sender_id`, `type`

### Frontend

- عند فتح صفحة المحادثة، يتم:
  1. طلب إذن الإشعارات
  2. الحصول على FCM token
  3. تسجيل token في قاعدة البيانات
  4. معالجة الإشعارات الواردة

### Service Worker

- معالجة الإشعارات في الخلفية (عندما يكون الموقع مغلق)
- عند النقر على الإشعار، يتم فتح المحادثة

## الاختبار

1. تأكد من أن Firebase credentials موجودة في `storage/app/firebase-credentials.json`
2. تأكد من تحديث جميع متغيرات `.env`
3. افتح صفحة المحادثة
4. أرسل رسالة من مستخدم آخر
5. يجب أن تظهر إشعار FCM

## ملاحظات

- الإشعارات تعمل حتى لو كان الموقع مغلق (عبر Service Worker)
- الإشعارات تعمل على الموبايل والديسكتوب
- الصوت يعمل تلقائياً مع الإشعارات

