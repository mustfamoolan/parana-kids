# توثيق API تطبيق موبايل المندوبين

## نظرة عامة

هذا التوثيق يشرح APIs المصادقة لتطبيق موبايل المندوبين. جميع APIs منفصلة تماماً عن النظام الحالي وتعمل تحت prefix `/api/mobile/delegate/auth/`.

## Base URL

```
http://your-domain.com/api/mobile/delegate/auth
```

## نظام المصادقة

يستخدم النظام **PWA Token** للمصادقة:
- Token يُرسل في header: `Authorization: Bearer {token}`
- Token صالح لمدة 30 يوم
- عند تسجيل دخول جديد، يتم حذف جميع الـ tokens القديمة تلقائياً

## APIs المتاحة

### 1. تسجيل الدخول

**Endpoint:** `POST /api/mobile/delegate/auth/login`

**Description:** تسجيل دخول المندوب باستخدام الكود وكلمة المرور

**Request Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "code": "D001",
  "password": "123456"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "token": "abc123def456...",
    "expires_at": "2025-01-18T10:30:00.000000Z",
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "code": "D001",
      "role": "delegate",
      "phone": "0501234567",
      "email": "ahmad@example.com",
      "page_name": "صفحة أحمد",
      "profile_image": "profiles/1_1234567890.jpg",
      "profile_image_url": "http://your-domain.com/storage/profiles/1_1234567890.jpg",
      "private_warehouse_id": null,
      "telegram_chat_id": null,
      "warehouses": [
        {
          "id": 1,
          "name": "مخزن الرئيسي",
          "can_manage": false
        }
      ],
      "private_warehouse": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Responses:**

**401 - بيانات خاطئة:**
```json
{
  "success": false,
  "message": "بيانات الدخول غير صحيحة أو المستخدم ليس مندوب",
  "error_code": "INVALID_CREDENTIALS"
}
```

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "خطأ في البيانات المدخلة",
  "errors": {
    "code": ["حقل الكود مطلوب"],
    "password": ["حقل كلمة المرور مطلوب"]
  }
}
```

---

### 2. معلومات المستخدم

**Endpoint:** `GET /api/mobile/delegate/auth/me`

**Description:** جلب معلومات المستخدم الحالي (يحتاج token)

**Request Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "code": "D001",
      "role": "delegate",
      "phone": "0501234567",
      "email": "ahmad@example.com",
      "page_name": "صفحة أحمد",
      "profile_image": "profiles/1_1234567890.jpg",
      "profile_image_url": "http://your-domain.com/storage/profiles/1_1234567890.jpg",
      "private_warehouse_id": null,
      "telegram_chat_id": null,
      "warehouses": [
        {
          "id": 1,
          "name": "مخزن الرئيسي",
          "can_manage": false
        }
      ],
      "private_warehouse": null,
      "created_at": "2024-01-01T00:00:00.000000Z",
      "updated_at": "2024-01-01T00:00:00.000000Z"
    }
  }
}
```

**Error Responses:**

**401 - غير مصرح:**
```json
{
  "success": false,
  "message": "غير مصرح",
  "error_code": "UNAUTHORIZED"
}
```

**403 - ليس مندوب:**
```json
{
  "success": false,
  "message": "هذا API مخصص للمندوبين فقط",
  "error_code": "FORBIDDEN"
}
```

---

### 3. تسجيل الخروج

**Endpoint:** `POST /api/mobile/delegate/auth/logout`

**Description:** تسجيل خروج المستخدم وإلغاء token الحالي

**Request Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

**Error Responses:**

**401 - غير مصرح:**
```json
{
  "success": false,
  "message": "غير مصرح",
  "error_code": "UNAUTHORIZED"
}
```

**403 - ليس مندوب:**
```json
{
  "success": false,
  "message": "هذا API مخصص للمندوبين فقط",
  "error_code": "FORBIDDEN"
}
```

**500 - خطأ في الخادم:**
```json
{
  "success": false,
  "message": "حدث خطأ أثناء تسجيل الخروج",
  "error_code": "LOGOUT_ERROR"
}
```

---

### 4. تحديث الملف الشخصي

**Endpoint:** `PUT /api/mobile/delegate/auth/profile`

**Description:** تحديث صورة البروفايل للمندوب

**Request Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**
```
profile_image: [file] (jpeg, jpg, png, max 2MB)
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "تم تحديث الملف الشخصي بنجاح",
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "code": "D001",
      "role": "delegate",
      "phone": "0501234567",
      "email": "ahmad@example.com",
      "page_name": "صفحة أحمد",
      "profile_image": "profiles/1_1234567890.jpg",
      "profile_image_url": "http://your-domain.com/storage/profiles/1_1234567890.jpg",
      ...
    }
  }
}
```

**Error Responses:**

**401 - غير مصرح:**
```json
{
  "success": false,
  "message": "غير مصرح",
  "error_code": "UNAUTHORIZED"
}
```

**403 - ليس مندوب:**
```json
{
  "success": false,
  "message": "هذا API مخصص للمندوبين فقط",
  "error_code": "FORBIDDEN"
}
```

**422 - Validation Error:**
```json
{
  "success": false,
  "message": "خطأ في البيانات المدخلة",
  "errors": {
    "profile_image": [
      "يجب إرسال صورة البروفايل",
      "الملف يجب أن يكون صورة",
      "نوع الصورة يجب أن يكون: jpeg, jpg, png",
      "حجم الصورة يجب أن يكون أقل من 2MB"
    ]
  }
}
```

**500 - خطأ في رفع الصورة:**
```json
{
  "success": false,
  "message": "فشل رفع الصورة: [error message]",
  "error_code": "UPLOAD_ERROR"
}
```

---

## أمثلة الاستخدام

### مثال 1: تسجيل الدخول (cURL)

```bash
curl -X POST http://localhost/api/mobile/delegate/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "code": "D001",
    "password": "123456"
  }'
```

### مثال 2: جلب معلومات المستخدم (cURL)

```bash
curl -X GET http://localhost/api/mobile/delegate/auth/me \
  -H "Authorization: Bearer your_token_here"
```

### مثال 3: تسجيل الخروج (cURL)

```bash
curl -X POST http://localhost/api/mobile/delegate/auth/logout \
  -H "Authorization: Bearer your_token_here"
```

### مثال 4: تحديث الصورة الشخصية (cURL)

```bash
curl -X PUT http://localhost/api/mobile/delegate/auth/profile \
  -H "Authorization: Bearer your_token_here" \
  -F "profile_image=@/path/to/image.jpg"
```

---

## أمثلة Flutter/Dart

### تسجيل الدخول

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> login(String code, String password) async {
  final response = await http.post(
    Uri.parse('http://your-domain.com/api/mobile/delegate/auth/login'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'code': code,
      'password': password,
    }),
  );
  
  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('فشل تسجيل الدخول: ${response.body}');
  }
}
```

### جلب معلومات المستخدم

```dart
Future<Map<String, dynamic>> getMe(String token) async {
  final response = await http.get(
    Uri.parse('http://your-domain.com/api/mobile/delegate/auth/me'),
    headers: {'Authorization': 'Bearer $token'},
  );
  
  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('فشل جلب المعلومات: ${response.body}');
  }
}
```

### تسجيل الخروج

```dart
Future<Map<String, dynamic>> logout(String token) async {
  final response = await http.post(
    Uri.parse('http://your-domain.com/api/mobile/delegate/auth/logout'),
    headers: {'Authorization': 'Bearer $token'},
  );
  
  if (response.statusCode == 200) {
    return jsonDecode(response.body);
  } else {
    throw Exception('فشل تسجيل الخروج: ${response.body}');
  }
}
```

### تحديث الصورة الشخصية

```dart
import 'package:http/http.dart' as http;
import 'dart:io';

Future<Map<String, dynamic>> updateProfile(String token, File imageFile) async {
  var request = http.MultipartRequest(
    'PUT',
    Uri.parse('http://your-domain.com/api/mobile/delegate/auth/profile'),
  );
  
  request.headers['Authorization'] = 'Bearer $token';
  request.files.add(
    await http.MultipartFile.fromPath('profile_image', imageFile.path),
  );
  
  var response = await request.send();
  var responseBody = await response.stream.bytesToString();
  
  if (response.statusCode == 200) {
    return jsonDecode(responseBody);
  } else {
    throw Exception('فشل تحديث الصورة: $responseBody');
  }
}
```

---

## أمثلة JavaScript (Fetch API)

### تسجيل الدخول

```javascript
async function login(code, password) {
  const response = await fetch('http://your-domain.com/api/mobile/delegate/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      code: code,
      password: password,
    }),
  });
  
  const data = await response.json();
  return data;
}
```

### جلب معلومات المستخدم

```javascript
async function getMe(token) {
  const response = await fetch('http://your-domain.com/api/mobile/delegate/auth/me', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  
  const data = await response.json();
  return data;
}
```

### تسجيل الخروج

```javascript
async function logout(token) {
  const response = await fetch('http://your-domain.com/api/mobile/delegate/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  
  const data = await response.json();
  return data;
}
```

### تحديث الصورة الشخصية

```javascript
async function updateProfile(token, imageFile) {
  const formData = new FormData();
  formData.append('profile_image', imageFile);
  
  const response = await fetch('http://your-domain.com/api/mobile/delegate/auth/profile', {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
    },
    body: formData,
  });
  
  const data = await response.json();
  return data;
}
```

---

## Error Codes

| Error Code | HTTP Status | Description |
|------------|-------------|-------------|
| `INVALID_CREDENTIALS` | 401 | بيانات الدخول غير صحيحة أو المستخدم ليس مندوب |
| `UNAUTHORIZED` | 401 | غير مصرح - Token مفقود أو غير صحيح |
| `FORBIDDEN` | 403 | المستخدم ليس مندوب |
| `UPLOAD_ERROR` | 500 | خطأ في رفع الصورة |
| `LOGOUT_ERROR` | 500 | خطأ في تسجيل الخروج |

---

## ملاحظات مهمة

1. **الأمان:**
   - Token صالح لمدة 30 يوم
   - يتم حذف الـ tokens القديمة تلقائياً عند تسجيل دخول جديد
   - Token يُرسل في header فقط (ليس في URL)
   - كل token مرتبط بمستخدم واحد

2. **Role Validation:**
   - جميع APIs تتحقق من أن المستخدم مندوب فقط
   - أي محاولة من مدير/مجهز تُرفض بـ 403

3. **Error Handling:**
   - جميع الأخطاء تُرجع بصيغة JSON موحدة
   - HTTP status codes صحيحة (200, 401, 403, 422, 500)
   - رسائل خطأ واضحة بالعربية

4. **Validation:**
   - Laravel validation للتحقق من البيانات
   - رسائل خطأ مخصصة بالعربية

5. **الصور:**
   - أنواع الصور المدعومة: JPEG, JPG, PNG
   - الحد الأقصى لحجم الصورة: 2MB
   - الصور تُحفظ في `storage/app/public/profiles/`

---

## التوسع المستقبلي

عند الحاجة لإضافة APIs للمدير والمجهز:

1. إنشاء `app/Http/Controllers/Mobile/Admin/MobileAdminAuthController.php`
2. إضافة routes في `routes/mobile.php` تحت prefix `admin/auth`
3. نفس البنية والمنطق لكن للمدير/المجهز

---

## الدعم

لأي استفسارات أو مشاكل، يرجى مراجعة الكود أو التواصل مع فريق التطوير.
