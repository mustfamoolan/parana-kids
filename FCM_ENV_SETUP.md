# إعداد Firebase في ملف .env

## المتغيرات المطلوبة

أضف هذه المتغيرات إلى ملف `.env`:

```env
# Firebase Configuration
FIREBASE_CREDENTIALS=storage/app/parana-kids-firebase-adminsdk-fbsvc-aabd2ef994.json
FIREBASE_PROJECT_ID=parana-kids

# Firebase Web App Config
FIREBASE_API_KEY=AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU
FIREBASE_AUTH_DOMAIN=parana-kids.firebaseapp.com
FIREBASE_STORAGE_BUCKET=parana-kids.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=130151352064
FIREBASE_APP_ID=1:130151352064:web:42335c43d67f4ac49515e5
FIREBASE_MEASUREMENT_ID=G-HCTDLM0P9Y

# Firebase VAPID Key (للإشعارات في المتصفح)
FIREBASE_VAPID_KEY=BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0
```

## ملاحظات

- تأكد من أن ملف Service Account JSON موجود في المسار المحدد
- بعد إضافة المتغيرات، قم بتشغيل `php artisan config:clear` لتحديث الإعدادات

