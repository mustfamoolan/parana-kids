# توثيق API للمستخدمين - تطبيق الموبايل

## Base URL
```
https://your-domain.com/api
```

---

## 1. تسجيل دخول المدير/المجهز

**Endpoint:** `POST /api/admin/login`

**الوصف:** تسجيل دخول للمدير أو المجهز أو المورد

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "login_field": "phone_or_code",
  "password": "password123"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "token": "64-character-token-string",
  "expires_at": "2024-12-31T23:59:59+00:00",
  "user": {
    "id": 1,
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "phone": "0123456789",
    "code": "ADMIN001",
    "role": "admin",
    "page_name": null,
    "profile_image": "path/to/image.jpg",
    "profile_image_url": "https://domain.com/storage/path/to/image.jpg",
    "private_warehouse_id": null,
    "telegram_chat_id": null,
    "warehouses": [],
    "private_warehouse": null,
    "created_at": "2024-01-01T00:00:00+00:00",
    "updated_at": "2024-01-01T00:00:00+00:00"
  }
}
```

**Response Error (401):**
```json
{
  "success": false,
  "message": "بيانات الدخول غير صحيحة."
}
```

---

## 2. تسجيل دخول المندوب

**Endpoint:** `POST /api/delegate/login`

**الوصف:** تسجيل دخول للمندوب

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
  "code": "DELEGATE001",
  "password": "password123"
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "token": "64-character-token-string",
  "expires_at": "2024-12-31T23:59:59+00:00",
  "user": {
    "id": 2,
    "name": "محمد علي",
    "email": null,
    "phone": "0987654321",
    "code": "DELEGATE001",
    "role": "delegate",
    "page_name": "صفحة المندوب",
    "profile_image": null,
    "profile_image_url": "https://domain.com/assets/images/profile-1.jpeg",
    "private_warehouse_id": null,
    "telegram_chat_id": null,
    "warehouses": [
      {
        "id": 1,
        "name": "مخزن رئيسي",
        "can_manage": true
      }
    ],
    "private_warehouse": null,
    "created_at": "2024-01-01T00:00:00+00:00",
    "updated_at": "2024-01-01T00:00:00+00:00"
  }
}
```

**Response Error (401):**
```json
{
  "success": false,
  "message": "بيانات الدخول غير صحيحة."
}
```

---

## 3. معلومات المستخدم الحالي

**Endpoint:** `GET /api/user`

**الوصف:** الحصول على معلومات المستخدم المسجل دخوله

**Headers:**
```
Authorization: Bearer {token}
أو
X-PWA-Token: {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "أحمد محمد",
    "email": "ahmed@example.com",
    "phone": "0123456789",
    "code": "ADMIN001",
    "role": "admin",
    "page_name": null,
    "profile_image": "path/to/image.jpg",
    "profile_image_url": "https://domain.com/storage/path/to/image.jpg",
    "private_warehouse_id": null,
    "telegram_chat_id": null,
    "warehouses": [],
    "private_warehouse": null,
    "created_at": "2024-01-01T00:00:00+00:00",
    "updated_at": "2024-01-01T00:00:00+00:00"
  }
}
```

**Response Error (401):**
```json
{
  "success": false,
  "message": "غير مصرح."
}
```

---

## 4. تحديث بيانات المستخدم

**Endpoint:** `PUT /api/user`

**الوصف:** تحديث بيانات المستخدم الحالي

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "أحمد محمد الجديد",
  "phone": "0123456789",
  "email": "newemail@example.com",
  "role": "admin",
  "password": "newpassword123"
}
```

**ملاحظات:**
- `password` اختياري (إذا لم يتم إرساله، لن يتم تحديثه)
- `code` مطلوب إذا كان `role` هو `supplier` أو `delegate` أو `private_supplier`
- `page_name` اختياري للمندوب فقط
- `warehouses` (array) مطلوب للمجهز والمندوب
- `private_warehouse_id` مطلوب للمورد

**Response Success (200):**
```json
{
  "success": true,
  "message": "تم تحديث المستخدم بنجاح",
  "user": {
    "id": 1,
    "name": "أحمد محمد الجديد",
    "email": "newemail@example.com",
    "phone": "0123456789",
    "code": "ADMIN001",
    "role": "admin",
    "page_name": null,
    "profile_image": "path/to/image.jpg",
    "profile_image_url": "https://domain.com/storage/path/to/image.jpg",
    "private_warehouse_id": null,
    "telegram_chat_id": null,
    "warehouses": [],
    "private_warehouse": null,
    "created_at": "2024-01-01T00:00:00+00:00",
    "updated_at": "2024-01-15T10:30:00+00:00"
  }
}
```

**Response Error (401):**
```json
{
  "success": false,
  "message": "غير مصرح."
}
```

---

## 5. إنشاء مستخدم جديد (للمدير فقط)

**Endpoint:** `POST /api/admin/users`

**الوصف:** إنشاء مستخدم جديد (للمدير فقط)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "مستخدم جديد",
  "phone": "0111111111",
  "email": "newuser@example.com",
  "password": "password123",
  "role": "delegate",
  "code": "DELEGATE002",
  "page_name": "صفحة المندوب الجديد",
  "warehouses": [1, 2]
}
```

**ملاحظات:**
- `code` مطلوب إذا كان `role` هو `supplier` أو `delegate` أو `private_supplier`
- `page_name` اختياري للمندوب فقط
- `warehouses` (array) مطلوب للمجهز والمندوب
- `private_warehouse_id` مطلوب للمورد

**Response Success (201):**
```json
{
  "success": true,
  "message": "تم إضافة المستخدم بنجاح",
  "user": {
    "id": 3,
    "name": "مستخدم جديد",
    "email": "newuser@example.com",
    "phone": "0111111111",
    "code": "DELEGATE002",
    "role": "delegate",
    "page_name": "صفحة المندوب الجديد",
    "profile_image": null,
    "profile_image_url": "https://domain.com/assets/images/profile-3.jpeg",
    "private_warehouse_id": null,
    "telegram_chat_id": null,
    "warehouses": [
      {
        "id": 1,
        "name": "مخزن رئيسي",
        "can_manage": false
      },
      {
        "id": 2,
        "name": "مخزن فرعي",
        "can_manage": false
      }
    ],
    "private_warehouse": null,
    "created_at": "2024-01-15T10:30:00+00:00",
    "updated_at": "2024-01-15T10:30:00+00:00"
  }
}
```

**Response Error (403):**
```json
{
  "success": false,
  "message": "غير مصرح. فقط المدير يمكنه إنشاء مستخدمين."
}
```

---

## أنواع المستخدمين (Roles)

- `admin` - المدير
- `supplier` - المجهز
- `delegate` - المندوب
- `private_supplier` - المورد

---

## المصادقة (Authentication)

جميع المسارات المحمية تتطلب إرسال token في أحد الطرق التالية:

### طريقة 1: Authorization Header
```
Authorization: Bearer {token}
```

### طريقة 2: X-PWA-Token Header
```
X-PWA-Token: {token}
```

### طريقة 3: Query Parameter
```
GET /api/user?token={token}
```

---

## أمثلة الاستخدام

### مثال 1: تسجيل دخول في Flutter/Dart
```dart
Future<Map<String, dynamic>> loginAdmin(String loginField, String password) async {
  final response = await http.post(
    Uri.parse('https://your-domain.com/api/admin/login'),
    headers: {'Content-Type': 'application/json'},
    body: jsonEncode({
      'login_field': loginField,
      'password': password,
    }),
  );
  
  return jsonDecode(response.body);
}
```

### مثال 2: الحصول على معلومات المستخدم
```dart
Future<Map<String, dynamic>> getUserInfo(String token) async {
  final response = await http.get(
    Uri.parse('https://your-domain.com/api/user'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  return jsonDecode(response.body);
}
```

### مثال 3: تحديث بيانات المستخدم
```dart
Future<Map<String, dynamic>> updateUser(String token, Map<String, dynamic> data) async {
  final response = await http.put(
    Uri.parse('https://your-domain.com/api/user'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode(data),
  );
  
  return jsonDecode(response.body);
}
```

### مثال 4: إنشاء مستخدم جديد (للمدير فقط)
```dart
Future<Map<String, dynamic>> createUser(String token, Map<String, dynamic> userData) async {
  final response = await http.post(
    Uri.parse('https://your-domain.com/api/admin/users'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
    body: jsonEncode(userData),
  );
  
  return jsonDecode(response.body);
}
```

---

## حفظ Token

بعد تسجيل الدخول، احفظ الـ `token` و `expires_at` في التطبيق (SharedPreferences في Flutter، أو AsyncStorage في React Native) واستخدمه في جميع الطلبات المحمية.

**مثال على حفظ Token في Flutter:**
```dart
// بعد تسجيل الدخول الناجح
await SharedPreferences.getInstance().then((prefs) {
  prefs.setString('token', response['token']);
  prefs.setString('expires_at', response['expires_at']);
});
```

**مثال على حفظ Token في React Native:**
```javascript
// بعد تسجيل الدخول الناجح
import AsyncStorage from '@react-native-async-storage/async-storage';

await AsyncStorage.setItem('token', response.token);
await AsyncStorage.setItem('expires_at', response.expires_at);
```

---

## معالجة الأخطاء

### أخطاء المصادقة (401)
عند الحصول على خطأ 401، يجب إعادة توجيه المستخدم إلى صفحة تسجيل الدخول.

### أخطاء الصلاحيات (403)
عند الحصول على خطأ 403، يعني أن المستخدم ليس لديه صلاحيات للوصول إلى هذا المورد.

### أخطاء التحقق (422)
عند الحصول على خطأ 422، يعني أن البيانات المرسلة غير صحيحة. تحقق من رسالة الخطأ لإصلاح المشكلة.

**مثال على معالجة الأخطاء:**
```dart
try {
  final response = await http.get(
    Uri.parse('https://your-domain.com/api/user'),
    headers: {'Authorization': 'Bearer $token'},
  );
  
  if (response.statusCode == 200) {
    // نجح الطلب
    final data = jsonDecode(response.body);
    return data;
  } else if (response.statusCode == 401) {
    // غير مصرح - إعادة توجيه لتسجيل الدخول
    // Navigate to login screen
    throw Exception('غير مصرح');
  } else {
    // خطأ آخر
    final error = jsonDecode(response.body);
    throw Exception(error['message'] ?? 'حدث خطأ');
  }
} catch (e) {
  // معالجة الخطأ
  print('Error: $e');
  rethrow;
}
```

---

## ملاحظات مهمة

1. **Token الصلاحية:** Token صالح لمدة 30 يوم من تاريخ الإنشاء
2. **تحديث Token:** يمكنك طلب token جديد عند انتهاء الصلاحية
3. **الأمان:** احرص على حفظ Token بشكل آمن في التطبيق
4. **HTTPS:** تأكد من استخدام HTTPS في الإنتاج
5. **Rate Limiting:** قد يكون هناك حد لعدد الطلبات في الدقيقة الواحدة

---

## هيكل بيانات المستخدم

```typescript
interface User {
  id: number;
  name: string;
  email: string | null;
  phone: string;
  code: string | null;
  role: 'admin' | 'supplier' | 'delegate' | 'private_supplier';
  page_name: string | null;
  profile_image: string | null;
  profile_image_url: string;
  private_warehouse_id: number | null;
  telegram_chat_id: string | null;
  warehouses: Warehouse[];
  private_warehouse: PrivateWarehouse | null;
  created_at: string; // ISO 8601 format
  updated_at: string; // ISO 8601 format
}

interface Warehouse {
  id: number;
  name: string;
  can_manage: boolean;
}

interface PrivateWarehouse {
  id: number;
  name: string;
}
```

---

## كودات الحالة (Status Codes)

- `200` - نجح الطلب
- `201` - تم الإنشاء بنجاح
- `401` - غير مصرح (مطلوب token صحيح)
- `403` - ممنوع (ليس لديك صلاحيات)
- `422` - خطأ في التحقق من البيانات
- `500` - خطأ في الخادم

---

## الدعم

للمزيد من المعلومات أو المساعدة، يرجى التواصل مع فريق التطوير.

