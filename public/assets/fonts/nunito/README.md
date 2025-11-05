# ملفات خط Nunito

## ⚠️ مهم جداً

يجب تحميل ملفات خط Nunito ووضعها في هذا المجلد (`public/assets/fonts/nunito/`) لكي يعمل الخط بشكل صحيح.

## الملفات المطلوبة:

1. `nunito-v25-latin-regular.woff2` (وزن 400)
2. `nunito-v25-latin-500.woff2` (وزن 500)
3. `nunito-v25-latin-600.woff2` (وزن 600)
4. `nunito-v25-latin-700.woff2` (وزن 700)
5. `nunito-v25-latin-800.woff2` (وزن 800)

## طرق التحميل:

### الطريقة 1: Google Web Fonts Helper
1. اذهب إلى: https://google-webfonts-helper.herokuapp.com/fonts/nunito
2. اختر "latin" subset
3. اختر الأوزان: 400, 500, 600, 700, 800
4. اختر صيغة woff2 (أو woff كبديل)
5. حمّل الملفات وضعهما في هذا المجلد

### الطريقة 2: Google Fonts مباشرة
1. اذهب إلى: https://fonts.google.com/specimen/Nunito
2. انقر على "Download family"
3. استخرج الملفات وحدّث أسماء الملفات لتطابق الأسماء المطلوبة أعلاه

### الطريقة 3: استخدام npm
```bash
npm install @fontsource/nunito
```
ثم انسخ الملفات من `node_modules/@fontsource/nunito/files/` إلى هذا المجلد

## ملاحظة:
- بعد تحميل الملفات، تأكد من أن أسماء الملفات تطابق تماماً الأسماء المذكورة أعلاه
- إذا استخدمت صيغة woff بدلاً من woff2، تأكد من تحديث ملف `public/assets/css/fonts.css` ليشير إلى الملفات الصحيحة

