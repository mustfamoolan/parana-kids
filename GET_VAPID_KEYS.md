# كيفية الحصول على VAPID Keys

## الطريقة 1: من Firebase Console (إذا كنت تستخدم Firebase)

1. اذهب إلى [Firebase Console](https://console.firebase.google.com/)
2. اختر مشروعك `parana-kids`
3. اذهب إلى **Project Settings** (⚙️) > **Cloud Messaging**
4. في قسم **Web Push certificates** ستجد:
   - **Key pair**: هذا هو VAPID key pair
   - إذا لم يكن موجوداً، اضغط **Generate key pair**
5. انسخ:
   - **Public key** → هذا هو `FIREBASE_VAPID_KEY` (موجود لديك بالفعل)
   - **Private key** → هذا هو `VAPID_PRIVATE_KEY` (تحتاج إليه)

## الطريقة 2: إنشاء VAPID Keys يدوياً

### باستخدام Node.js (web-push):

```bash
# تثبيت المكتبة
npm install -g web-push

# توليد المفاتيح
web-push generate-vapid-keys
```

ستحصل على:
```
Public Key: BGtkbcjrO12YMoDuq2sCQeHlu47uPx3SHTgFKZFYiBW8Qr0D9vgyZSZPdw6_4ZFEI9Snk1VEAj2qTYI1I1YxBXE
Private Key: I0_d0vnesxbBSUmlDdOKibGo6vEXRO-Vu88QlSlm5j0
```

### باستخدام PHP (web-push-php):

يمكنك إنشاء ملف PHP بسيط:

```php
<?php
require 'vendor/autoload.php';

use Minishlink\WebPush\VAPID;

$keys = VAPID::createVapidKeys();

echo "Public Key: " . $keys['publicKey'] . "\n";
echo "Private Key: " . $keys['privateKey'] . "\n";
```

## الطريقة 3: استخدام أداة Online

يمكنك استخدام:
- https://web-push-codelab.glitch.me/
- https://vapidkeys.com/

## إضافة المفاتيح في .env

بعد الحصول على المفاتيح، أضفها في ملف `.env`:

```env
# VAPID Public Key (موجود بالفعل)
FIREBASE_VAPID_KEY=BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0

# VAPID Private Key (يجب إضافته)
VAPID_PRIVATE_KEY=your_private_key_here
```

## ملاحظات مهمة:

1. **المفتاح الخاص (Private Key) سري جداً** - لا تشاركه أبداً أو ترفعه على GitHub
2. **استخدم نفس الزوج من المفاتيح** - إذا كان لديك VAPID public key من Firebase، يجب استخدام private key المقابل له
3. **إذا لم تجد private key في Firebase**، يمكنك إنشاء زوج جديد من المفاتيح واستبدال public key أيضاً

