# دليل إعداد Webhook لتليجرام

## الخطوات التفصيلية

### 1. الحصول على BOT_TOKEN

1. افتح تليجرام وابحث عن `@BotFather`
2. أرسل `/newbot` أو `/mybots` إذا كان لديك بوت موجود
3. إذا كنت تنشئ بوت جديد:
   - اختر اسماً للبوت (مثال: "Parana Kids Orders Bot")
   - اختر username (يجب أن ينتهي بـ `bot` مثل: `parana_orders_bot`)
   - ستحصل على token مثل: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`

### 2. إعداد ملف .env

افتح ملف `.env` وأضف:

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_WEBHOOK_URL=https://yourdomain.com/telegram/webhook
```

**مهم**: استبدل:
- `123456789:ABCdefGHIjklMNOpqrsTUVwxyz` بـ BOT_TOKEN الخاص بك
- `https://yourdomain.com` بـ رابط موقعك الفعلي

### 3. تحديد رابط Webhook

رابط webhook يجب أن يكون:
```
https://yourdomain.com/telegram/webhook
```

**مثال:**
- إذا كان موقعك: `https://parana-kids.com`
- رابط webhook: `https://parana-kids.com/telegram/webhook`

### 4. إعداد Webhook عبر Terminal/Command Line

#### الطريقة الأولى: استخدام curl (Windows PowerShell)

```powershell
# استبدل YOUR_BOT_TOKEN و YOUR_WEBHOOK_URL
$botToken = "YOUR_BOT_TOKEN"
$webhookUrl = "https://yourdomain.com/telegram/webhook"

curl -X POST "https://api.telegram.org/bot$botToken/setWebhook" -d "url=$webhookUrl"
```

#### الطريقة الثانية: استخدام curl (Linux/Mac)

```bash
# استبدل YOUR_BOT_TOKEN و YOUR_WEBHOOK_URL
curl -X POST "https://api.telegram.org/botYOUR_BOT_TOKEN/setWebhook" \
  -d "url=https://yourdomain.com/telegram/webhook"
```

#### الطريقة الثالثة: استخدام المتصفح

افتح هذا الرابط في المتصفح (استبدل القيم):
```
https://api.telegram.org/botYOUR_BOT_TOKEN/setWebhook?url=https://yourdomain.com/telegram/webhook
```

### 5. التحقق من إعداد Webhook

#### عبر Terminal:

```bash
curl "https://api.telegram.org/botYOUR_BOT_TOKEN/getWebhookInfo"
```

#### عبر المتصفح:

افتح:
```
https://api.telegram.org/botYOUR_BOT_TOKEN/getWebhookInfo
```

**النتيجة المتوقعة:**
```json
{
  "ok": true,
  "result": {
    "url": "https://yourdomain.com/telegram/webhook",
    "has_custom_certificate": false,
    "pending_update_count": 0
  }
}
```

### 6. اختبار البوت

1. افتح البوت في تليجرام
2. أرسل `/start`
3. يجب أن يستجيب البوت ويرسل رسالة ترحيب

## استكشاف الأخطاء

### خطأ: "Bad Request: HTTPS url must be provided"

**الحل**: تأكد من أن رابط webhook يبدأ بـ `https://` وليس `http://`

### خطأ: "Bad Request: url is empty"

**الحل**: تأكد من كتابة رابط webhook بشكل صحيح

### خطأ: "Bad Request: Failed to resolve host"

**الحل**: 
- تأكد من أن النطاق (domain) صحيح
- تأكد من أن الموقع متاح على الإنترنت
- تأكد من أن route `/telegram/webhook` يعمل

### البوت لا يستجيب

1. تحقق من أن webhook تم إعداده:
   ```bash
   curl "https://api.telegram.org/botYOUR_BOT_TOKEN/getWebhookInfo"
   ```

2. تحقق من ملفات السجلات:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. اختبر route مباشرة:
   - افتح: `https://yourdomain.com/telegram/webhook` في المتصفح
   - يجب أن ترى رسالة خطأ (هذا طبيعي لأن تليجرام يرسل POST)

### اختبار Webhook محلياً (Development)

إذا كنت تعمل على localhost، يمكنك استخدام ngrok:

1. تثبيت ngrok:
   ```bash
   # Windows: تحميل من https://ngrok.com
   # أو عبر chocolatey: choco install ngrok
   ```

2. تشغيل ngrok:
   ```bash
   ngrok http 8000
   ```

3. استخدام رابط ngrok في webhook:
   ```
   https://abc123.ngrok.io/telegram/webhook
   ```

## مثال كامل

```bash
# 1. BOT_TOKEN من BotFather
BOT_TOKEN="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"

# 2. رابط موقعك
WEBHOOK_URL="https://parana-kids.com/telegram/webhook"

# 3. إعداد webhook
curl -X POST "https://api.telegram.org/bot$BOT_TOKEN/setWebhook" -d "url=$WEBHOOK_URL"

# 4. التحقق
curl "https://api.telegram.org/bot$BOT_TOKEN/getWebhookInfo"
```

## ملاحظات مهمة

1. **HTTPS مطلوب**: تليجرام يتطلب HTTPS لـ webhook
2. **الرابط يجب أن يكون عاماً**: لا يمكن استخدام localhost مباشرة
3. **Port 443**: تأكد من أن البورت 443 (HTTPS) مفتوح
4. **SSL Certificate**: تأكد من أن شهادة SSL صالحة

## إلغاء Webhook

إذا أردت إلغاء webhook:

```bash
curl -X POST "https://api.telegram.org/botYOUR_BOT_TOKEN/deleteWebhook"
```

