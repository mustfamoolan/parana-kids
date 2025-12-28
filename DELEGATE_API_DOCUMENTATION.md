# توثيق API تطبيق المندوب (Delegate Mobile App API)

## نظرة عامة

هذا التوثيق يشرح كيفية استخدام API لتطبيق المندوب. جميع المسارات تستخدم نظام PWA Token للمصادقة.

**Base URL:** `https://your-domain.com/api/mobile`

---

## المصادقة (Authentication)

جميع المسارات المحمية تتطلب إرسال Token في Header. يمكن إرسال Token بإحدى الطرق التالية:

1. **Authorization Header (مُفضل):**
   ```
   Authorization: Bearer {token}
   ```

2. **X-PWA-Token Header:**
   ```
   X-PWA-Token: {token}
   ```

3. **Query Parameter:**
   ```
   ?token={token}
   ```

---

## 1. تسجيل الدخول (Login)

### Endpoint
```
POST /delegate/auth/login
```

### الوصف
تسجيل دخول المندوب باستخدام الكود وكلمة المرور.

### Headers
```
Content-Type: application/json
Accept: application/json
```

### Request Body
```json
{
  "code": "string (required)",
  "password": "string (required)"
}
```

### مثال Request
```json
{
  "code": "DELEGATE001",
  "password": "password123"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "token": "64-character-random-token",
    "expires_at": "2025-02-15T10:30:00+00:00",
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "phone": "0912345678",
      "code": "DELEGATE001",
      "role": "delegate",
      "page_name": "صفحة أحمد",
      "profile_image": "profiles/1_1234567890.jpg",
      "profile_image_url": "https://your-domain.com/storage/profiles/1_1234567890.jpg",
      "private_warehouse_id": null,
      "telegram_chat_id": null,
      "warehouses": [
        {
          "id": 1,
          "name": "مخزن الشمال",
          "can_manage": true
        },
        {
          "id": 2,
          "name": "مخزن الجنوب",
          "can_manage": false
        }
      ],
      "private_warehouse": null,
      "created_at": "2025-01-01T00:00:00+00:00",
      "updated_at": "2025-01-15T10:30:00+00:00"
    }
  }
}
```

### Response Error (401 Unauthorized)
```json
{
  "success": false,
  "message": "بيانات الدخول غير صحيحة أو المستخدم ليس مندوب",
  "error_code": "INVALID_CREDENTIALS"
}
```

### Response Validation Error (422 Unprocessable Entity)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "code": ["حقل الكود مطلوب"],
    "password": ["حقل كلمة المرور مطلوب"]
  }
}
```

### ملاحظات
- Token صالح لمدة 30 يوم من تاريخ الإنشاء
- عند تسجيل دخول جديد، يتم حذف جميع Tokens السابقة للمستخدم
- يجب حفظ Token في التطبيق لاستخدامه في الطلبات اللاحقة

---

## 2. جلب معلومات المندوب (Get Current User)

### Endpoint
```
GET /delegate/auth/me
```

### الوصف
جلب معلومات المندوب الحالي المسجل دخوله.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

أو

```
X-PWA-Token: {token}
Accept: application/json
```

### Request Body
لا يوجد

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmed@example.com",
      "phone": "0912345678",
      "code": "DELEGATE001",
      "role": "delegate",
      "page_name": "صفحة أحمد",
      "profile_image": "profiles/1_1234567890.jpg",
      "profile_image_url": "https://your-domain.com/storage/profiles/1_1234567890.jpg",
      "private_warehouse_id": null,
      "telegram_chat_id": null,
      "warehouses": [
        {
          "id": 1,
          "name": "مخزن الشمال",
          "can_manage": true
        }
      ],
      "private_warehouse": null,
      "created_at": "2025-01-01T00:00:00+00:00",
      "updated_at": "2025-01-15T10:30:00+00:00"
    }
  }
}
```

### Response Error (401 Unauthorized)
```json
{
  "success": false,
  "message": "غير مصرح",
  "error_code": "UNAUTHORIZED"
}
```

### Response Error (403 Forbidden)
```json
{
  "success": false,
  "message": "هذا API مخصص للمندوبين فقط",
  "error_code": "FORBIDDEN"
}
```

### Response Error - Token غير صحيح (401 Unauthorized)
```json
{
  "success": false,
  "message": "Token غير صحيح أو منتهي الصلاحية."
}
```

### ملاحظات
- يجب إرسال Token صالح في Header
- إذا كان Token منتهي الصلاحية، سيتم إرجاع خطأ 401
- يمكن استخدام هذا الـ endpoint للتحقق من صحة Token

---

## 3. تسجيل الخروج (Logout)

### Endpoint
```
POST /delegate/auth/logout
```

### الوصف
تسجيل خروج المندوب وإلغاء Token الحالي.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

أو

```
X-PWA-Token: {token}
Accept: application/json
```

### Request Body
لا يوجد

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم تسجيل الخروج بنجاح"
}
```

### Response Error (401 Unauthorized)
```json
{
  "success": false,
  "message": "غير مصرح",
  "error_code": "UNAUTHORIZED"
}
```

### Response Error (403 Forbidden)
```json
{
  "success": false,
  "message": "هذا API مخصص للمندوبين فقط",
  "error_code": "FORBIDDEN"
}
```

### Response Error (500 Internal Server Error)
```json
{
  "success": false,
  "message": "حدث خطأ أثناء تسجيل الخروج",
  "error_code": "LOGOUT_ERROR"
}
```

### ملاحظات
- بعد تسجيل الخروج، يصبح Token غير صالح ولا يمكن استخدامه
- يجب حذف Token من التطبيق بعد نجاح تسجيل الخروج
- يمكن إرسال Token في `Authorization` header أو `X-PWA-Token` header

---

## هيكل بيانات المستخدم (User Object Structure)

جميع الـ endpoints ترجع بيانات المستخدم بنفس الهيكل:

```json
{
  "id": "integer - معرف المستخدم",
  "name": "string - اسم المستخدم",
  "email": "string|null - البريد الإلكتروني",
  "phone": "string|null - رقم الهاتف",
  "code": "string|null - كود المندوب",
  "role": "string - دور المستخدم (delegate)",
  "page_name": "string|null - اسم الصفحة",
  "profile_image": "string|null - مسار صورة البروفايل",
  "profile_image_url": "string - رابط كامل لصورة البروفايل",
  "private_warehouse_id": "integer|null - معرف المخزن الخاص",
  "telegram_chat_id": "integer|null - معرف Telegram Chat",
  "warehouses": [
    {
      "id": "integer - معرف المخزن",
      "name": "string - اسم المخزن",
      "can_manage": "boolean - هل يمكن إدارة المخزن"
    }
  ],
  "private_warehouse": {
    "id": "integer - معرف المخزن الخاص",
    "name": "string - اسم المخزن الخاص"
  } | null,
  "created_at": "string - تاريخ الإنشاء (ISO 8601)",
  "updated_at": "string - تاريخ آخر تحديث (ISO 8601)"
}
```

---

## أكواد الأخطاء (Error Codes)

| Error Code | الوصف | HTTP Status |
|------------|-------|-------------|
| `INVALID_CREDENTIALS` | بيانات الدخول غير صحيحة | 401 |
| `UNAUTHORIZED` | غير مصرح - Token مفقود أو غير صحيح | 401 |
| `FORBIDDEN` | محظور - المستخدم ليس مندوب | 403 |
| `LOGOUT_ERROR` | خطأ في تسجيل الخروج | 500 |

---

## أمثلة على الاستخدام

### مثال 1: تسجيل الدخول باستخدام cURL

```bash
curl -X POST https://your-domain.com/api/mobile/delegate/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "code": "DELEGATE001",
    "password": "password123"
  }'
```

### مثال 2: جلب معلومات المستخدم

```bash
curl -X GET https://your-domain.com/api/mobile/delegate/auth/me \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 3: تسجيل الخروج

```bash
curl -X POST https://your-domain.com/api/mobile/delegate/auth/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 4: استخدام JavaScript (Fetch API)

```javascript
// تسجيل الدخول
async function login(code, password) {
  const response = await fetch('https://your-domain.com/api/mobile/delegate/auth/login', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify({ code, password })
  });
  
  const data = await response.json();
  
  if (data.success) {
    // حفظ Token
    localStorage.setItem('token', data.data.token);
    return data.data;
  } else {
    throw new Error(data.message);
  }
}

// جلب معلومات المستخدم
async function getCurrentUser() {
  const token = localStorage.getItem('token');
  
  const response = await fetch('https://your-domain.com/api/mobile/delegate/auth/me', {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    return data.data.user;
  } else {
    throw new Error(data.message);
  }
}

// تسجيل الخروج
async function logout() {
  const token = localStorage.getItem('token');
  
  const response = await fetch('https://your-domain.com/api/mobile/delegate/auth/logout', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    // حذف Token
    localStorage.removeItem('token');
    return true;
  } else {
    throw new Error(data.message);
  }
}
```

### مثال 5: استخدام Flutter/Dart

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class DelegateApiService {
  final String baseUrl = 'https://your-domain.com/api/mobile';
  String? token;

  // تسجيل الدخول
  Future<Map<String, dynamic>> login(String code, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/delegate/auth/login'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: jsonEncode({
        'code': code,
        'password': password,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        token = data['data']['token'];
        return data['data'];
      }
    }
    
    throw Exception('فشل تسجيل الدخول');
  }

  // جلب معلومات المستخدم
  Future<Map<String, dynamic>> getCurrentUser() async {
    if (token == null) {
      throw Exception('لم يتم تسجيل الدخول');
    }

    final response = await http.get(
      Uri.parse('$baseUrl/delegate/auth/me'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        return data['data']['user'];
      }
    }
    
    throw Exception('فشل جلب معلومات المستخدم');
  }

  // تسجيل الخروج
  Future<bool> logout() async {
    if (token == null) {
      return true;
    }

    final response = await http.post(
      Uri.parse('$baseUrl/delegate/auth/logout'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        token = null;
        return true;
      }
    }
    
    throw Exception('فشل تسجيل الخروج');
  }
}
```

---

## 4. جلب قائمة المنتجات (Get Products List)

### Endpoint
```
GET /delegate/products
```

### الوصف
جلب قائمة جميع المنتجات من المخازن المصرح بها للمندوب مع إمكانية الفلترة والبحث.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

أو

```
X-PWA-Token: {token}
Accept: application/json
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `warehouse_id` | integer | No | فلتر حسب مخزن معين |
| `gender_type` | string | No | فلتر حسب النوع: `boys`, `girls`, `boys_girls`, `accessories` |
| `has_discount` | string | No | عرض المنتجات التي لديها تخفيض فقط: `1` |
| `search` | string | No | بحث في القياسات، الكود، النوع، والاسم |
| `per_page` | integer | No | عدد المنتجات في الصفحة (افتراضي: 30، حد أقصى: 100) |
| `page` | integer | No | رقم الصفحة (افتراضي: 1) |

### Request Body
لا يوجد

### Response Success (200 OK)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "قميص أطفال",
      "code": "SHIRT001",
      "gender_type": "boys",
      "selling_price": 50.00,
      "effective_price": 45.00,
      "purchase_price": 30.00,
      "description": "قميص قطني عالي الجودة",
      "warehouse": {
        "id": 1,
        "name": "مخزن الشمال"
      },
      "primary_image": "https://your-domain.com/storage/products/1_primary.jpg",
      "images": [
        "https://your-domain.com/storage/products/1_1.jpg",
        "https://your-domain.com/storage/products/1_2.jpg"
      ],
      "sizes": [
        {
          "id": 1,
          "size_name": "S",
          "quantity": 10,
          "available_quantity": 8,
          "reserved_quantity": 2
        },
        {
          "id": 2,
          "size_name": "M",
          "quantity": 15,
          "available_quantity": 15,
          "reserved_quantity": 0
        }
      ],
      "discount": {
        "has_discount": true,
        "type": "percentage",
        "value": 10.00,
        "original_price": 50.00,
        "discount_price": 45.00,
        "discount_amount": 5.00,
        "percentage": 10.00,
        "start_date": "2025-01-01T00:00:00+00:00",
        "end_date": "2025-12-31T23:59:59+00:00"
      },
      "warehouse_promotion": {
        "has_promotion": false
      },
      "created_at": "2025-01-01T00:00:00+00:00",
      "updated_at": "2025-01-15T10:30:00+00:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 30,
    "total": 150,
    "last_page": 5,
    "has_more": true
  }
}
```

### Response Error (401 Unauthorized)
```json
{
  "success": false,
  "message": "Token غير صحيح أو منتهي الصلاحية."
}
```

### Response Error (403 Forbidden)
```json
{
  "success": false,
  "message": "غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.",
  "error_code": "FORBIDDEN"
}
```

### Response Error - مخزن غير مصرح (403 Forbidden)
```json
{
  "success": false,
  "message": "ليس لديك صلاحية للوصول إلى هذا المخزن",
  "error_code": "FORBIDDEN_WAREHOUSE"
}
```

### ملاحظات
- يتم إخفاء المنتجات المحجوبة (`is_hidden = true`) تلقائياً
- يتم إخفاء المنتجات التي جميع قياساتها غير متوفرة (available_quantity = 0)
- عند استخدام `gender_type=boys` يتم عرض المنتجات من نوع "ولادي" و "ولادي بناتي"
- عند استخدام `gender_type=girls` يتم عرض المنتجات من نوع "بناتي" و "ولادي بناتي"
- البحث يعطي أولوية للقياسات، ثم الكود، ثم النوع، ثم الاسم

---

## 5. جلب منتج واحد (Get Single Product)

### Endpoint
```
GET /delegate/products/{id}
```

### الوصف
جلب منتج واحد بالتفاصيل الكاملة من المخازن المصرح بها للمندوب.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

أو

```
X-PWA-Token: {token}
Accept: application/json
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | معرف المنتج |

### Request Body
لا يوجد

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "قميص أطفال",
      "code": "SHIRT001",
      "gender_type": "boys",
      "selling_price": 50.00,
      "effective_price": 45.00,
      "purchase_price": 30.00,
      "description": "قميص قطني عالي الجودة",
      "warehouse": {
        "id": 1,
        "name": "مخزن الشمال"
      },
      "primary_image": "https://your-domain.com/storage/products/1_primary.jpg",
      "images": [
        "https://your-domain.com/storage/products/1_1.jpg",
        "https://your-domain.com/storage/products/1_2.jpg"
      ],
      "sizes": [
        {
          "id": 1,
          "size_name": "S",
          "quantity": 10,
          "available_quantity": 8,
          "reserved_quantity": 2
        },
        {
          "id": 2,
          "size_name": "M",
          "quantity": 15,
          "available_quantity": 15,
          "reserved_quantity": 0
        }
      ],
      "discount": {
        "has_discount": true,
        "type": "percentage",
        "value": 10.00,
        "original_price": 50.00,
        "discount_price": 45.00,
        "discount_amount": 5.00,
        "percentage": 10.00,
        "start_date": "2025-01-01T00:00:00+00:00",
        "end_date": "2025-12-31T23:59:59+00:00"
      },
      "warehouse_promotion": {
        "has_promotion": false
      },
      "created_at": "2025-01-01T00:00:00+00:00",
      "updated_at": "2025-01-15T10:30:00+00:00"
    }
  }
}
```

### Response Error (401 Unauthorized)
```json
{
  "success": false,
  "message": "Token غير صحيح أو منتهي الصلاحية."
}
```

### Response Error (403 Forbidden)
```json
{
  "success": false,
  "message": "غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.",
  "error_code": "FORBIDDEN"
}
```

### Response Error - منتج غير موجود (404 Not Found)
```json
{
  "success": false,
  "message": "المنتج غير موجود",
  "error_code": "PRODUCT_NOT_FOUND"
}
```

### Response Error - منتج غير مصرح (403 Forbidden)
```json
{
  "success": false,
  "message": "ليس لديك صلاحية للوصول إلى هذا المنتج",
  "error_code": "FORBIDDEN_PRODUCT"
}
```

### ملاحظات
- يجب أن ينتمي المنتج لمخزن من المخازن المصرح بها للمندوب
- يتم إرجاع نفس هيكل البيانات كما في قائمة المنتجات

---

## هيكل بيانات المنتج (Product Object Structure)

جميع الـ endpoints ترجع بيانات المنتج بنفس الهيكل:

```json
{
  "id": "integer - معرف المنتج",
  "name": "string - اسم المنتج",
  "code": "string - كود المنتج",
  "gender_type": "string - نوع المنتج: boys, girls, boys_girls, accessories",
  "selling_price": "float - سعر البيع الأصلي",
  "effective_price": "float - السعر الفعلي (بعد التخفيضات)",
  "purchase_price": "float - سعر الشراء",
  "description": "string|null - وصف المنتج",
  "warehouse": {
    "id": "integer - معرف المخزن",
    "name": "string - اسم المخزن"
  },
  "primary_image": "string|null - رابط الصورة الرئيسية",
  "images": [
    "string - روابط جميع صور المنتج"
  ],
  "sizes": [
    {
      "id": "integer - معرف القياس",
      "size_name": "string - اسم القياس",
      "quantity": "integer - الكمية الإجمالية",
      "available_quantity": "integer - الكمية المتاحة (بعد حجز الكميات)",
      "reserved_quantity": "integer - الكمية المحجوزة"
    }
  ],
  "discount": {
    "has_discount": "boolean - هل يوجد تخفيض",
    "type": "string|null - نوع التخفيض: percentage, amount",
    "value": "float|null - قيمة التخفيض",
    "original_price": "float|null - السعر الأصلي",
    "discount_price": "float|null - السعر بعد التخفيض",
    "discount_amount": "float|null - مبلغ التخفيض",
    "percentage": "float|null - نسبة التخفيض",
    "start_date": "string|null - تاريخ بداية التخفيض (ISO 8601)",
    "end_date": "string|null - تاريخ نهاية التخفيض (ISO 8601)"
  },
  "warehouse_promotion": {
    "has_promotion": "boolean - هل يوجد تخفيض عام للمخزن",
    "discount_type": "string|null - نوع التخفيض: percentage, fixed_price",
    "discount_percentage": "float|null - نسبة التخفيض",
    "promotion_price": "float|null - سعر التخفيض الثابت",
    "start_date": "string|null - تاريخ بداية التخفيض (ISO 8601)",
    "end_date": "string|null - تاريخ نهاية التخفيض (ISO 8601)"
  },
  "created_at": "string - تاريخ الإنشاء (ISO 8601)",
  "updated_at": "string - تاريخ آخر تحديث (ISO 8601)"
}
```

### ملاحظات على البيانات
- **effective_price**: يتم حسابه تلقائياً مع مراعاة تخفيض المنتج الواحد أولاً، ثم تخفيض المخزن العام
- **available_quantity**: الكمية المتاحة = الكمية الإجمالية - الكمية المحجوزة
- **discount**: يتم إرجاع معلومات التخفيض فقط إذا كان التخفيض نشطاً في الوقت الحالي
- **warehouse_promotion**: يتم إرجاع معلومات التخفيض العام للمخزن فقط إذا كان نشطاً

---

## أمثلة على الاستخدام - المنتجات

### مثال 1: جلب قائمة المنتجات باستخدام cURL

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/products?gender_type=boys&per_page=20&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 2: البحث عن منتج

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/products?search=SHIRT001" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 3: جلب منتجات من مخزن معين

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/products?warehouse_id=1&has_discount=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 4: جلب منتج واحد

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/products/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 5: استخدام JavaScript (Fetch API)

```javascript
// جلب قائمة المنتجات
async function getProducts(filters = {}) {
  const token = localStorage.getItem('token');
  const queryParams = new URLSearchParams(filters).toString();
  
  const response = await fetch(`https://your-domain.com/api/mobile/delegate/products?${queryParams}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    return {
      products: data.data,
      pagination: data.pagination
    };
  } else {
    throw new Error(data.message);
  }
}

// جلب منتج واحد
async function getProduct(productId) {
  const token = localStorage.getItem('token');
  
  const response = await fetch(`https://your-domain.com/api/mobile/delegate/products/${productId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    return data.data.product;
  } else {
    throw new Error(data.message);
  }
}

// استخدام الأمثلة
getProducts({ gender_type: 'boys', per_page: 20 })
  .then(result => {
    console.log('المنتجات:', result.products);
    console.log('معلومات الصفحات:', result.pagination);
  })
  .catch(error => console.error('خطأ:', error));

getProduct(1)
  .then(product => console.log('المنتج:', product))
  .catch(error => console.error('خطأ:', error));
```

### مثال 6: استخدام Flutter/Dart

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class DelegateProductApiService {
  final String baseUrl = 'https://your-domain.com/api/mobile';
  String? token;

  // جلب قائمة المنتجات
  Future<Map<String, dynamic>> getProducts({
    int? warehouseId,
    String? genderType,
    bool? hasDiscount,
    String? search,
    int perPage = 30,
    int page = 1,
  }) async {
    if (token == null) {
      throw Exception('لم يتم تسجيل الدخول');
    }

    final queryParams = <String, String>{};
    if (warehouseId != null) queryParams['warehouse_id'] = warehouseId.toString();
    if (genderType != null) queryParams['gender_type'] = genderType;
    if (hasDiscount == true) queryParams['has_discount'] = '1';
    if (search != null) queryParams['search'] = search;
    queryParams['per_page'] = perPage.toString();
    queryParams['page'] = page.toString();

    final uri = Uri.parse('$baseUrl/delegate/products').replace(
      queryParameters: queryParams,
    );

    final response = await http.get(
      uri,
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        return {
          'products': data['data'],
          'pagination': data['pagination'],
        };
      }
    }
    
    throw Exception('فشل جلب المنتجات');
  }

  // جلب منتج واحد
  Future<Map<String, dynamic>> getProduct(int productId) async {
    if (token == null) {
      throw Exception('لم يتم تسجيل الدخول');
    }

    final response = await http.get(
      Uri.parse('$baseUrl/delegate/products/$productId'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        return data['data']['product'];
      }
    }
    
    throw Exception('فشل جلب المنتج');
  }
}
```

---

## أكواد الأخطاء - المنتجات

| Error Code | الوصف | HTTP Status |
|------------|-------|-------------|
| `FORBIDDEN` | غير مصرح - المستخدم ليس مندوب | 403 |
| `FORBIDDEN_WAREHOUSE` | ليس لديك صلاحية للوصول إلى هذا المخزن | 403 |
| `FORBIDDEN_PRODUCT` | ليس لديك صلاحية للوصول إلى هذا المنتج | 403 |
| `PRODUCT_NOT_FOUND` | المنتج غير موجود | 404 |
| `NO_WAREHOUSES` | ليس لديك صلاحية للوصول إلى أي مخازن | 403 |

---

## ملاحظات مهمة

1. **Base URL:** تأكد من استبدال `https://your-domain.com` بـ URL الفعلي لخادمك
2. **Token Expiration:** Token صالح لمدة 30 يوم، بعدها يجب تسجيل الدخول مرة أخرى
3. **Error Handling:** تأكد من معالجة جميع حالات الخطأ في التطبيق
4. **Security:** لا تقم بتخزين Token في مكان غير آمن، استخدم Secure Storage
5. **Network:** تأكد من إضافة معالجة لأخطاء الشبكة (timeout, no connection, etc.)

---

## الخطوات التالية

بعد إكمال تسجيل الدخول وجلب المعلومات وتسجيل الخروج وجلب المنتجات، يمكن إضافة المزيد من الـ APIs مثل:
- جلب الطلبات
- تحديث حالة الطلب
- إدارة السلة (Cart)
- إدارة الشحنات
- وغيرها...

---

**آخر تحديث:** 2025-01-15

---

## ملخص جميع المسارات المتاحة

### Authentication APIs
- `POST /api/mobile/delegate/auth/login` - تسجيل الدخول
- `GET /api/mobile/delegate/auth/me` - معلومات المندوب
- `POST /api/mobile/delegate/auth/logout` - تسجيل الخروج
- `PUT /api/mobile/delegate/auth/profile` - تحديث صورة البروفايل

### Products APIs
- `GET /api/mobile/delegate/products` - قائمة المنتجات
- `GET /api/mobile/delegate/products/{id}` - منتج واحد

