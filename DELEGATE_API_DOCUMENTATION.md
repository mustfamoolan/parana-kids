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

---

## 6. جلب قائمة الطلبات (Get Orders List)

### Endpoint
```
GET /delegate/orders
```

### الوصف
جلب قائمة جميع طلبات المندوب مع إمكانية الفلترة والبحث.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | فلتر حسب الحالة: `pending`, `confirmed`, `deleted` |
| `search` | string | No | بحث في order_number, customer_name, customer_phone, customer_social_link, customer_address, delivery_code, notes, items |
| `date_from` | string | No | تاريخ البداية (YYYY-MM-DD) |
| `date_to` | string | No | تاريخ النهاية (YYYY-MM-DD) |
| `time_from` | string | No | وقت البداية (HH:MM) |
| `time_to` | string | No | وقت النهاية (HH:MM) |
| `per_page` | integer | No | عدد الطلبات في الصفحة (افتراضي: 15، حد أقصى: 100) |
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
      "order_number": "ORD-20250115-0001",
      "customer_name": "أحمد محمد",
      "customer_phone": "07701234567",
      "customer_phone2": null,
      "customer_address": "بغداد - الكرادة",
      "customer_social_link": "https://facebook.com/...",
      "status": "pending",
      "total_amount": 150.00,
      "items_count": 3,
      "delivery_code": null,
      "created_at": "2025-01-15T10:30:00+00:00",
      "confirmed_at": null,
      "deleted_at": null,
      "deleted_by": null,
      "deletion_reason": null,
      "deleted_by_user": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
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

### ملاحظات
- عند استخدام `status=deleted` يتم عرض فقط الطلبات المحذوفة
- عند عدم تحديد `status` يتم عرض جميع الطلبات (النشطة والمحذوفة)
- البحث يدعم تطبيع أرقام الهواتف تلقائياً
- ترتيب الطلبات: المحذوفة حسب `deleted_at`، النشطة حسب `created_at`

---

## 7. جلب تفاصيل طلب واحد (Get Single Order)

### Endpoint
```
GET /delegate/orders/{id}
```

### الوصف
جلب تفاصيل طلب واحد كاملة مع جميع العناصر ومعلومات الشحنة.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `id` | integer | Yes | معرف الطلب |

### Request Body
لا يوجد

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-20250115-0001",
      "customer_name": "أحمد محمد",
      "customer_phone": "07701234567",
      "customer_phone2": null,
      "customer_address": "بغداد - الكرادة",
      "customer_social_link": "https://facebook.com/...",
      "notes": "ملاحظات إضافية",
      "status": "pending",
      "total_amount": 150.00,
      "delivery_code": null,
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "قميص أطفال",
          "product_code": "SHIRT001",
          "size_id": 1,
          "size_name": "S",
          "quantity": 2,
          "unit_price": 50.00,
          "subtotal": 100.00,
          "product": {
            "id": 1,
            "name": "قميص أطفال",
            "code": "SHIRT001",
            "primary_image": "https://your-domain.com/storage/products/1_primary.jpg"
          }
        },
        {
          "id": 2,
          "product_id": 2,
          "product_name": "بنطلون أطفال",
          "product_code": "PANT001",
          "size_id": 3,
          "size_name": "M",
          "quantity": 1,
          "unit_price": 50.00,
          "subtotal": 50.00,
          "product": {
            "id": 2,
            "name": "بنطلون أطفال",
            "code": "PANT001",
            "primary_image": "https://your-domain.com/storage/products/2_primary.jpg"
          }
        }
      ],
      "alwaseet_shipment": {
        "id": 1,
        "alwaseet_order_id": "12345",
        "client_name": "أحمد محمد",
        "client_mobile": "07701234567",
        "client_mobile2": null,
        "city_id": "1",
        "city_name": "بغداد",
        "region_id": "10",
        "region_name": "الكرادة",
        "location": "بغداد - الكرادة",
        "price": 150.00,
        "delivery_price": 5.00,
        "package_size": "medium",
        "type_name": "ملابس",
        "status_id": "1",
        "status": "جديد",
        "items_number": "3",
        "merchant_notes": null,
        "issue_notes": null,
        "replacement": false,
        "qr_id": null,
        "qr_link": null,
        "alwaseet_created_at": "2025-01-15T10:35:00+00:00",
        "alwaseet_updated_at": "2025-01-15T10:35:00+00:00",
        "synced_at": "2025-01-15T10:35:00+00:00"
      },
      "created_at": "2025-01-15T10:30:00+00:00",
      "confirmed_at": null,
      "deleted_at": null,
      "deleted_by": null,
      "deletion_reason": null,
      "deleted_by_user": null
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

### Response Error - طلب غير موجود (404 Not Found)
```json
{
  "success": false,
  "message": "الطلب غير موجود",
  "error_code": "ORDER_NOT_FOUND"
}
```

### Response Error - طلب غير مصرح (403 Forbidden)
```json
{
  "success": false,
  "message": "ليس لديك صلاحية للوصول إلى هذا الطلب",
  "error_code": "FORBIDDEN_ORDER"
}
```

### ملاحظات
- يمكن جلب الطلبات المحذوفة (soft deleted) أيضاً
- يتم إرجاع معلومات الشحنة (`alwaseet_shipment`) فقط إذا كانت موجودة
- كل عنصر في الطلب يحتوي على معلومات المنتج والقياس

---

## 4. تحديث الطلب

### Endpoint
```
PUT /api/mobile/delegate/orders/{id}
```

### الوصف
تحديث طلب موجود. يمكن تحديث معلومات الزبون وعناصر الطلب. يجب أن يكون الطلب في حالة `pending` فقط.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون الطلب يخص المندوب (`delegate_id`)
- يجب أن يكون الطلب في حالة `pending` فقط (غير مقيد)
- يتم إرجاع المنتجات القديمة للمخزن تلقائياً
- يتم خصم المنتجات الجديدة من المخزن
- يتم التحقق من توفر الكميات قبل الخصم
- يتم استخدام `effective_price` لحساب الأسعار (يشمل التخفيضات النشطة)

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "customer_name": "string (required) - اسم العميل",
  "customer_phone": "string (required) - رقم هاتف العميل (11 رقم)",
  "customer_phone2": "string|null - رقم هاتف العميل الثاني (11 رقم)",
  "customer_address": "string (required) - عنوان العميل",
  "customer_social_link": "string (required) - رابط التواصل الاجتماعي",
  "notes": "string|null - ملاحظات",
  "items": [
    {
      "product_id": "integer (required) - معرف المنتج",
      "size_id": "integer (required) - معرف القياس",
      "quantity": "integer (required, min:1) - الكمية"
    }
  ]
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم تحديث الطلب بنجاح",
  "data": {
    "order": {
      // نفس هيكل Order Details من القسم السابق
    }
  }
}
```

### Response Error (400 Bad Request) - طلب مقيد
```json
{
  "success": false,
  "message": "لا يمكن تعديل الطلبات المقيدة",
  "error_code": "ORDER_CONFIRMED"
}
```

### Response Error (400 Bad Request) - كمية غير متوفرة
```json
{
  "success": false,
  "message": "الكمية المتوفرة من {product_name} - {size_name} غير كافية. المتوفر: {quantity}",
  "error_code": "UPDATE_ERROR"
}
```

### Response Error (422 Unprocessable Entity) - خطأ في التحقق
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "customer_name": ["The customer name field is required."],
    "customer_phone": ["The customer phone must be 11 digits."],
    "items": ["The items field is required."]
  }
}
```

### مثال على الاستخدام
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": "07701234568",
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات محدثة",
    "items": [
      {
        "product_id": 1,
        "size_id": 1,
        "quantity": 3
      },
      {
        "product_id": 2,
        "size_id": 3,
        "quantity": 2
      }
    ]
  }'
```

---

## كيفية تعديل الطلب - دليل شامل

### كيف تعمل عملية التعديل

عند تحديث طلب، يعمل النظام بالطريقة التالية:

1. **إرجاع المنتجات القديمة للمخزن**: يتم إرجاع جميع المنتجات الموجودة في الطلب الحالي إلى المخزن (زيادة الكمية المتوفرة)

2. **حذف جميع العناصر القديمة**: يتم حذف جميع عناصر الطلب الحالية من قاعدة البيانات

3. **إضافة العناصر الجديدة**: يتم إضافة جميع العناصر المرسلة في الـ request كعناصر جديدة للطلب

4. **خصم المنتجات الجديدة من المخزن**: يتم خصم الكميات المطلوبة من المخزن للمنتجات الجديدة

**ملاحظات مهمة:**
- يجب أن تكون جميع الكميات المطلوبة متوفرة في المخزن قبل التعديل
- إذا كانت كمية أي منتج غير متوفرة، سيفشل التعديل بالكامل ويتم إرجاع جميع المنتجات للمخزن
- يمكنك حذف منتج ببساطة بعدم تضمينه في `items` array
- يمكنك إضافة منتج جديد بإضافته في `items` array
- يمكنك تغيير كمية منتج موجود بتغيير قيمة `quantity`
- يمكنك تغيير قياس منتج موجود بتغيير قيمة `size_id`

### أمثلة عملية

#### مثال 1: حذف منتج من الطلب

**الحالة الأولية:**
الطلب يحتوي على:
- منتج 1 (product_id: 1, size_id: 1, quantity: 2)
- منتج 2 (product_id: 2, size_id: 3, quantity: 1)
- منتج 3 (product_id: 3, size_id: 5, quantity: 3)

**الهدف:** حذف منتج 2 من الطلب

**Request:**
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": null,
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات",
    "items": [
      {
        "product_id": 1,
        "size_id": 1,
        "quantity": 2
      },
      {
        "product_id": 3,
        "size_id": 5,
        "quantity": 3
      }
    ]
  }'
```

**النتيجة:** تم حذف منتج 2 من الطلب، وتم إرجاعه للمخزن تلقائياً.

---

#### مثال 2: إضافة منتج جديد للطلب

**الحالة الأولية:**
الطلب يحتوي على:
- منتج 1 (product_id: 1, size_id: 1, quantity: 2)

**الهدف:** إضافة منتج جديد (product_id: 5, size_id: 10, quantity: 1)

**Request:**
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": null,
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات",
    "items": [
      {
        "product_id": 1,
        "size_id": 1,
        "quantity": 2
      },
      {
        "product_id": 5,
        "size_id": 10,
        "quantity": 1
      }
    ]
  }'
```

**النتيجة:** تم إضافة المنتج الجديد للطلب، وتم خصمه من المخزن تلقائياً.

---

#### مثال 3: تغيير كمية منتج موجود

**الحالة الأولية:**
الطلب يحتوي على:
- منتج 1 (product_id: 1, size_id: 1, quantity: 2)
- منتج 2 (product_id: 2, size_id: 3, quantity: 1)

**الهدف:** زيادة كمية منتج 1 من 2 إلى 5

**Request:**
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": null,
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات",
    "items": [
      {
        "product_id": 1,
        "size_id": 1,
        "quantity": 5
      },
      {
        "product_id": 2,
        "size_id": 3,
        "quantity": 1
      }
    ]
  }'
```

**النتيجة:** تم تغيير كمية منتج 1 من 2 إلى 5. تم إرجاع الكمية القديمة (2) للمخزن وخصم الكمية الجديدة (5) من المخزن.

**ملاحظة:** تأكد من أن الكمية الجديدة (5) متوفرة في المخزن قبل التعديل.

---

#### مثال 4: تغيير قياس منتج موجود

**الحالة الأولية:**
الطلب يحتوي على:
- منتج 1 (product_id: 1, size_id: 1, quantity: 2) - القياس: S
- منتج 2 (product_id: 2, size_id: 3, quantity: 1) - القياس: M

**الهدف:** تغيير قياس منتج 1 من S (size_id: 1) إلى M (size_id: 2)

**Request:**
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": null,
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات",
    "items": [
      {
        "product_id": 1,
        "size_id": 2,
        "quantity": 2
      },
      {
        "product_id": 2,
        "size_id": 3,
        "quantity": 1
      }
    ]
  }'
```

**النتيجة:** تم تغيير قياس منتج 1 من S إلى M. تم إرجاع الكمية القديمة (2) من القياس S للمخزن وخصم الكمية الجديدة (2) من القياس M.

**ملاحظة:** تأكد من أن القياس الجديد (M) متوفر بالكمية المطلوبة في المخزن.

---

#### مثال 5: تعديل معلومات الزبون فقط

**الحالة الأولية:**
الطلب يحتوي على:
- منتج 1 (product_id: 1, size_id: 1, quantity: 2)
- منتج 2 (product_id: 2, size_id: 3, quantity: 1)

**الهدف:** تغيير رقم هاتف العميل فقط دون تغيير المنتجات

**Request:**
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07709999999",
    "customer_phone2": "07708888888",
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات محدثة",
    "items": [
      {
        "product_id": 1,
        "size_id": 1,
        "quantity": 2
      },
      {
        "product_id": 2,
        "size_id": 3,
        "quantity": 1
      }
    ]
  }'
```

**النتيجة:** تم تحديث معلومات الزبون فقط. المنتجات لم تتغير (تم إرجاعها وإعادة خصمها من المخزن، لكن الكميات نفسها).

---

#### مثال 6: سيناريو متعدد (حذف + إضافة + تعديل)

**الحالة الأولية:**
الطلب يحتوي على:
- منتج 1 (product_id: 1, size_id: 1, quantity: 2)
- منتج 2 (product_id: 2, size_id: 3, quantity: 1)
- منتج 3 (product_id: 3, size_id: 5, quantity: 3)

**الهدف:**
- حذف منتج 2
- زيادة كمية منتج 1 من 2 إلى 4
- تغيير قياس منتج 3 من size_id: 5 إلى size_id: 6
- إضافة منتج جديد (product_id: 7, size_id: 10, quantity: 1)

**Request:**
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": null,
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات محدثة",
    "items": [
      {
        "product_id": 1,
        "size_id": 1,
        "quantity": 4
      },
      {
        "product_id": 3,
        "size_id": 6,
        "quantity": 3
      },
      {
        "product_id": 7,
        "size_id": 10,
        "quantity": 1
      }
    ]
  }'
```

**النتيجة:**
- تم حذف منتج 2 وإرجاعه للمخزن
- تم زيادة كمية منتج 1 من 2 إلى 4 (إرجاع 2 وخصم 4)
- تم تغيير قياس منتج 3 (إرجاع من size_id: 5 وخصم من size_id: 6)
- تم إضافة منتج 7 الجديد وخصمه من المخزن

---

#### مثال 7: استخدام JavaScript (Fetch API)

```javascript
/**
 * تحديث طلب مع دعم جميع العمليات
 * @param {number} orderId - معرف الطلب
 * @param {Object} orderData - بيانات الطلب المحدثة
 * @returns {Promise<Object>} - بيانات الطلب المحدث
 */
async function updateOrder(orderId, orderData) {
  const token = localStorage.getItem('token');
  
  const response = await fetch(`https://your-domain.com/api/mobile/delegate/orders/${orderId}`, {
    method: 'PUT',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(orderData)
  });
  
  const data = await response.json();
  
  if (!response.ok) {
    throw new Error(data.message || 'فشل تحديث الطلب');
  }
  
  return data.data.order;
}

// مثال: حذف منتج من الطلب
async function removeProductFromOrder(orderId, productIdToRemove) {
  // جلب الطلب الحالي
  const currentOrder = await getOrder(orderId);
  
  // تصفية المنتجات (إزالة المنتج المطلوب)
  const updatedItems = currentOrder.items.filter(
    item => item.product_id !== productIdToRemove
  );
  
  // تحديث الطلب
  const orderData = {
    customer_name: currentOrder.customer_name,
    customer_phone: currentOrder.customer_phone,
    customer_phone2: currentOrder.customer_phone2,
    customer_address: currentOrder.customer_address,
    customer_social_link: currentOrder.customer_social_link,
    notes: currentOrder.notes,
    items: updatedItems.map(item => ({
      product_id: item.product_id,
      size_id: item.size_id,
      quantity: item.quantity
    }))
  };
  
  return await updateOrder(orderId, orderData);
}

// مثال: إضافة منتج جديد للطلب
async function addProductToOrder(orderId, productId, sizeId, quantity) {
  // جلب الطلب الحالي
  const currentOrder = await getOrder(orderId);
  
  // إضافة المنتج الجديد
  const updatedItems = [
    ...currentOrder.items.map(item => ({
      product_id: item.product_id,
      size_id: item.size_id,
      quantity: item.quantity
    })),
    {
      product_id: productId,
      size_id: sizeId,
      quantity: quantity
    }
  ];
  
  // تحديث الطلب
  const orderData = {
    customer_name: currentOrder.customer_name,
    customer_phone: currentOrder.customer_phone,
    customer_phone2: currentOrder.customer_phone2,
    customer_address: currentOrder.customer_address,
    customer_social_link: currentOrder.customer_social_link,
    notes: currentOrder.notes,
    items: updatedItems
  };
  
  return await updateOrder(orderId, orderData);
}

// مثال: تغيير كمية منتج
async function updateProductQuantity(orderId, productId, sizeId, newQuantity) {
  // جلب الطلب الحالي
  const currentOrder = await getOrder(orderId);
  
  // تحديث كمية المنتج
  const updatedItems = currentOrder.items.map(item => {
    if (item.product_id === productId && item.size_id === sizeId) {
      return {
        product_id: item.product_id,
        size_id: item.size_id,
        quantity: newQuantity
      };
    }
    return {
      product_id: item.product_id,
      size_id: item.size_id,
      quantity: item.quantity
    };
  });
  
  // تحديث الطلب
  const orderData = {
    customer_name: currentOrder.customer_name,
    customer_phone: currentOrder.customer_phone,
    customer_phone2: currentOrder.customer_phone2,
    customer_address: currentOrder.customer_address,
    customer_social_link: currentOrder.customer_social_link,
    notes: currentOrder.notes,
    items: updatedItems
  };
  
  return await updateOrder(orderId, orderData);
}

// مثال: تغيير قياس منتج
async function changeProductSize(orderId, productId, oldSizeId, newSizeId) {
  // جلب الطلب الحالي
  const currentOrder = await getOrder(orderId);
  
  // تحديث قياس المنتج
  const updatedItems = currentOrder.items.map(item => {
    if (item.product_id === productId && item.size_id === oldSizeId) {
      return {
        product_id: item.product_id,
        size_id: newSizeId,
        quantity: item.quantity
      };
    }
    return {
      product_id: item.product_id,
      size_id: item.size_id,
      quantity: item.quantity
    };
  });
  
  // تحديث الطلب
  const orderData = {
    customer_name: currentOrder.customer_name,
    customer_phone: currentOrder.customer_phone,
    customer_phone2: currentOrder.customer_phone2,
    customer_address: currentOrder.customer_address,
    customer_social_link: currentOrder.customer_social_link,
    notes: currentOrder.notes,
    items: updatedItems
  };
  
  return await updateOrder(orderId, orderData);
}

// استخدام الأمثلة
// removeProductFromOrder(1, 2); // حذف منتج 2 من الطلب 1
// addProductToOrder(1, 5, 10, 1); // إضافة منتج 5 بقياس 10 وكمية 1 للطلب 1
// updateProductQuantity(1, 1, 1, 5); // تغيير كمية منتج 1 بقياس 1 إلى 5 في الطلب 1
// changeProductSize(1, 1, 1, 2); // تغيير قياس منتج 1 من 1 إلى 2 في الطلب 1
```

---

#### مثال 8: استخدام Flutter/Dart

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class OrderUpdateService {
  final String baseUrl = 'https://your-domain.com/api/mobile';
  String? token;

  // تحديث الطلب
  Future<Map<String, dynamic>> updateOrder({
    required int orderId,
    required String customerName,
    required String customerPhone,
    String? customerPhone2,
    required String customerAddress,
    required String customerSocialLink,
    String? notes,
    required List<Map<String, dynamic>> items,
  }) async {
    if (token == null) {
      throw Exception('لم يتم تسجيل الدخول');
    }

    final response = await http.put(
      Uri.parse('$baseUrl/delegate/orders/$orderId'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: jsonEncode({
        'customer_name': customerName,
        'customer_phone': customerPhone,
        'customer_phone2': customerPhone2,
        'customer_address': customerAddress,
        'customer_social_link': customerSocialLink,
        'notes': notes,
        'items': items,
      }),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        return data['data']['order'];
      }
    }

    throw Exception('فشل تحديث الطلب');
  }

  // حذف منتج من الطلب
  Future<Map<String, dynamic>> removeProductFromOrder({
    required int orderId,
    required int productIdToRemove,
    required Map<String, dynamic> currentOrder,
  }) async {
    final updatedItems = (currentOrder['items'] as List)
        .where((item) => item['product_id'] != productIdToRemove)
        .map((item) => {
          return {
            'product_id': item['product_id'],
            'size_id': item['size_id'],
            'quantity': item['quantity'],
          };
        })
        .toList();

    return await updateOrder(
      orderId: orderId,
      customerName: currentOrder['customer_name'],
      customerPhone: currentOrder['customer_phone'],
      customerPhone2: currentOrder['customer_phone2'],
      customerAddress: currentOrder['customer_address'],
      customerSocialLink: currentOrder['customer_social_link'],
      notes: currentOrder['notes'],
      items: updatedItems,
    );
  }

  // إضافة منتج جديد للطلب
  Future<Map<String, dynamic>> addProductToOrder({
    required int orderId,
    required int productId,
    required int sizeId,
    required int quantity,
    required Map<String, dynamic> currentOrder,
  }) async {
    final updatedItems = [
      ...(currentOrder['items'] as List).map((item) {
        return {
          'product_id': item['product_id'],
          'size_id': item['size_id'],
          'quantity': item['quantity'],
        };
      }),
      {
        'product_id': productId,
        'size_id': sizeId,
        'quantity': quantity,
      },
    ];

    return await updateOrder(
      orderId: orderId,
      customerName: currentOrder['customer_name'],
      customerPhone: currentOrder['customer_phone'],
      customerPhone2: currentOrder['customer_phone2'],
      customerAddress: currentOrder['customer_address'],
      customerSocialLink: currentOrder['customer_social_link'],
      notes: currentOrder['notes'],
      items: updatedItems,
    );
  }

  // تغيير كمية منتج
  Future<Map<String, dynamic>> updateProductQuantity({
    required int orderId,
    required int productId,
    required int sizeId,
    required int newQuantity,
    required Map<String, dynamic> currentOrder,
  }) async {
    final updatedItems = (currentOrder['items'] as List).map((item) {
      if (item['product_id'] == productId && item['size_id'] == sizeId) {
        return {
          'product_id': item['product_id'],
          'size_id': item['size_id'],
          'quantity': newQuantity,
        };
      }
      return {
        'product_id': item['product_id'],
        'size_id': item['size_id'],
        'quantity': item['quantity'],
      };
    }).toList();

    return await updateOrder(
      orderId: orderId,
      customerName: currentOrder['customer_name'],
      customerPhone: currentOrder['customer_phone'],
      customerPhone2: currentOrder['customer_phone2'],
      customerAddress: currentOrder['customer_address'],
      customerSocialLink: currentOrder['customer_social_link'],
      notes: currentOrder['notes'],
      items: updatedItems,
    );
  }

  // تغيير قياس منتج
  Future<Map<String, dynamic>> changeProductSize({
    required int orderId,
    required int productId,
    required int oldSizeId,
    required int newSizeId,
    required Map<String, dynamic> currentOrder,
  }) async {
    final updatedItems = (currentOrder['items'] as List).map((item) {
      if (item['product_id'] == productId && item['size_id'] == oldSizeId) {
        return {
          'product_id': item['product_id'],
          'size_id': newSizeId,
          'quantity': item['quantity'],
        };
      }
      return {
        'product_id': item['product_id'],
        'size_id': item['size_id'],
        'quantity': item['quantity'],
      };
    }).toList();

    return await updateOrder(
      orderId: orderId,
      customerName: currentOrder['customer_name'],
      customerPhone: currentOrder['customer_phone'],
      customerPhone2: currentOrder['customer_phone2'],
      customerAddress: currentOrder['customer_address'],
      customerSocialLink: currentOrder['customer_social_link'],
      notes: currentOrder['notes'],
      items: updatedItems,
    );
  }
}
```

---

### ملاحظات مهمة

1. **التحقق من الكميات المتوفرة**: قبل إرسال طلب التعديل، تأكد من أن جميع الكميات المطلوبة متوفرة في المخزن. يمكنك استخدام API جلب المنتجات للتحقق من الكميات المتوفرة.

2. **Transaction Safety**: جميع العمليات تتم داخل transaction، مما يعني أنه إذا فشل أي جزء من التعديل، سيتم التراجع عن جميع التغييرات تلقائياً.

3. **إعادة الحساب التلقائي**: يتم إعادة حساب المبلغ الإجمالي للطلب تلقائياً بناءً على الأسعار الحالية للمنتجات (بما في ذلك التخفيضات النشطة).

4. **الطلبات المقيدة**: لا يمكن تعديل الطلبات في حالة `confirmed` أو أي حالة أخرى غير `pending`.

5. **الأداء**: عند تعديل طلب كبير، قد تستغرق العملية بعض الوقت لأن النظام يقوم بإرجاع جميع المنتجات القديمة وخصم الجديدة.

---

## 5. حذف الطلب (Soft Delete)

### Endpoint
```
DELETE /api/mobile/delegate/orders/{id}
```

### الوصف
حذف طلب (soft delete) مع إرجاع جميع المنتجات للمخزن. يمكن حذف الطلبات في حالة `pending` أو `confirmed` فقط.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون الطلب يخص المندوب (`delegate_id`)
- يجب أن يكون الطلب في حالة `pending` أو `confirmed` فقط
- يتم إرجاع جميع المنتجات للمخزن تلقائياً
- يتم تسجيل حركة الحذف في `ProductMovement`
- يتم تسجيل `deleted_by` و `deletion_reason`
- يتم إرسال إشعار SweetAlert للمجهز/المدير

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "deletion_reason": "string (required, max:500) - سبب الحذف"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم حذف الطلب بنجاح وإرجاع جميع المنتجات للمخزن"
}
```

### Response Error (400 Bad Request) - لا يمكن حذف الطلب
```json
{
  "success": false,
  "message": "لا يمكن حذف هذا الطلب",
  "error_code": "ORDER_CANNOT_BE_DELETED"
}
```

### Response Error (422 Unprocessable Entity) - خطأ في التحقق
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "deletion_reason": ["يجب إدخال سبب الحذف"]
  }
}
```

### مثال على الاستخدام
```bash
curl -X DELETE "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "deletion_reason": "العميل ألغى الطلب"
  }'
```

---

## 6. استرجاع الطلب (Restore)

### Endpoint
```
POST /api/mobile/delegate/orders/{id}/restore
```

### الوصف
استرجاع طلب محذوف (soft deleted) مع خصم المنتجات من المخزن. يجب التحقق من توفر الكميات قبل الاسترجاع.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون الطلب يخص المندوب (`delegate_id`)
- يجب أن يكون الطلب محذوف (soft deleted)
- يتم التحقق من توفر الكميات قبل الاسترجاع
- يتم خصم المنتجات من المخزن
- يتم تسجيل حركة الاسترجاع في `ProductMovement`
- يتم استرجاع الطلب ووضع `status = 'pending'`
- يتم مسح `deleted_by` و `deletion_reason`

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Request Body
لا يوجد (فارغ)

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم استرجاع الطلب بنجاح وخصم المنتجات من المخزن",
  "data": {
    "order": {
      // نفس هيكل Order Details من القسم السابق
    }
  }
}
```

### Response Error (400 Bad Request) - كمية غير متوفرة
```json
{
  "success": false,
  "message": "لا يمكن استرجاع الطلب - المنتجات التالية غير متوفرة بالكمية المطلوبة: {product_name} ({size_name}): المطلوب {quantity}، المتوفر {available} | ...",
  "error_code": "INSUFFICIENT_STOCK",
  "shortages": [
    "{product_name} ({size_name}): المطلوب {quantity}، المتوفر {available}"
  ]
}
```

### Response Error (404 Not Found) - طلب غير موجود أو غير محذوف
```json
{
  "success": false,
  "message": "الطلب غير موجود أو غير محذوف",
  "error_code": "ORDER_NOT_FOUND"
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/orders/1/restore" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 7. حذف الطلب نهائياً (Force Delete)

### Endpoint
```
POST /api/mobile/delegate/orders/{id}/force-delete
```

### الوصف
حذف طلب نهائياً من قاعدة البيانات (hard delete). هذه العملية لا يمكن التراجع عنها.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون الطلب يخص المندوب (`delegate_id`)
- يجب أن يكون الطلب محذوف (soft deleted) مسبقاً
- **تحذير:** هذه العملية لا يمكن التراجع عنها

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Request Body
لا يوجد (فارغ)

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم حذف الطلب نهائياً"
}
```

### Response Error (404 Not Found) - طلب غير موجود
```json
{
  "success": false,
  "message": "الطلب غير موجود",
  "error_code": "ORDER_NOT_FOUND"
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/orders/1/force-delete" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### ملاحظات
- هذه العملية حذف نهائي ولا يمكن التراجع عنها
- يجب استخدامها بحذر
- المنتجات لا يتم إرجاعها للمخزن لأنها سبق إرجاعها عند الحذف الأول (soft delete)

---

## هيكل بيانات الطلب (Order Object Structure)

### قائمة الطلبات (Order List Item)
```json
{
  "id": "integer - معرف الطلب",
  "order_number": "string - رقم الطلب",
  "customer_name": "string - اسم العميل",
  "customer_phone": "string - رقم هاتف العميل",
  "customer_phone2": "string|null - رقم هاتف العميل الثاني",
  "customer_address": "string - عنوان العميل",
  "customer_social_link": "string - رابط التواصل الاجتماعي",
  "status": "string - حالة الطلب: pending, confirmed",
  "total_amount": "float - المبلغ الإجمالي",
  "items_count": "integer - عدد العناصر",
  "delivery_code": "string|null - كود التوصيل",
  "created_at": "string - تاريخ الإنشاء (ISO 8601)",
  "confirmed_at": "string|null - تاريخ التقييد (ISO 8601)",
  "deleted_at": "string|null - تاريخ الحذف (ISO 8601)",
  "deleted_by": "integer|null - معرف المستخدم الذي حذف الطلب",
  "deletion_reason": "string|null - سبب الحذف",
  "deleted_by_user": {
    "id": "integer - معرف المستخدم",
    "name": "string - اسم المستخدم"
  } | null
}
```

### تفاصيل الطلب (Order Details)
```json
{
  "id": "integer - معرف الطلب",
  "order_number": "string - رقم الطلب",
  "customer_name": "string - اسم العميل",
  "customer_phone": "string - رقم هاتف العميل",
  "customer_phone2": "string|null - رقم هاتف العميل الثاني",
  "customer_address": "string - عنوان العميل",
  "customer_social_link": "string - رابط التواصل الاجتماعي",
  "notes": "string|null - ملاحظات",
  "status": "string - حالة الطلب: pending, confirmed",
  "total_amount": "float - المبلغ الإجمالي",
  "delivery_code": "string|null - كود التوصيل",
  "items": [
    {
      "id": "integer - معرف العنصر",
      "product_id": "integer - معرف المنتج",
      "product_name": "string - اسم المنتج",
      "product_code": "string - كود المنتج",
      "size_id": "integer - معرف القياس",
      "size_name": "string - اسم القياس",
      "quantity": "integer - الكمية",
      "unit_price": "float - سعر الوحدة",
      "subtotal": "float - المجموع الفرعي",
      "product": {
        "id": "integer - معرف المنتج",
        "name": "string - اسم المنتج",
        "code": "string - كود المنتج",
        "primary_image": "string|null - رابط الصورة الرئيسية"
      }
    }
  ],
  "alwaseet_shipment": {
    "id": "integer - معرف الشحنة",
    "alwaseet_order_id": "string - معرف الطلب في الواسط",
    "client_name": "string - اسم العميل",
    "client_mobile": "string - رقم هاتف العميل",
    "client_mobile2": "string|null - رقم هاتف العميل الثاني",
    "city_id": "string - معرف المدينة",
    "city_name": "string - اسم المدينة",
    "region_id": "string - معرف المنطقة",
    "region_name": "string - اسم المنطقة",
    "location": "string - العنوان",
    "price": "float - السعر",
    "delivery_price": "float - سعر التوصيل",
    "package_size": "string - حجم الطرد",
    "type_name": "string - نوع الطرد",
    "status_id": "string - معرف الحالة",
    "status": "string - حالة الشحنة",
    "items_number": "string - عدد العناصر",
    "merchant_notes": "string|null - ملاحظات التاجر",
    "issue_notes": "string|null - ملاحظات المشكلة",
    "replacement": "boolean - هل هو استبدال",
    "qr_id": "string|null - معرف QR",
    "qr_link": "string|null - رابط QR",
    "alwaseet_created_at": "string|null - تاريخ الإنشاء في الواسط (ISO 8601)",
    "alwaseet_updated_at": "string|null - تاريخ التحديث في الواسط (ISO 8601)",
    "synced_at": "string|null - تاريخ المزامنة (ISO 8601)"
  } | null,
  "created_at": "string - تاريخ الإنشاء (ISO 8601)",
  "confirmed_at": "string|null - تاريخ التقييد (ISO 8601)",
  "deleted_at": "string|null - تاريخ الحذف (ISO 8601)",
  "deleted_by": "integer|null - معرف المستخدم الذي حذف الطلب",
  "deletion_reason": "string|null - سبب الحذف",
  "deleted_by_user": {
    "id": "integer - معرف المستخدم",
    "name": "string - اسم المستخدم"
  } | null
}
```

---

## أمثلة على الاستخدام - الطلبات

### مثال 1: جلب قائمة الطلبات باستخدام cURL

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/orders?status=pending&per_page=20&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 2: البحث عن طلب

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/orders?search=ORD-20250115-0001" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 3: فلترة حسب التاريخ

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/orders?date_from=2025-01-01&date_to=2025-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 4: جلب طلب واحد

```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/orders/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### مثال 5: استخدام JavaScript (Fetch API)

```javascript
// جلب قائمة الطلبات
async function getOrders(filters = {}) {
  const token = localStorage.getItem('token');
  const queryParams = new URLSearchParams(filters).toString();
  
  const response = await fetch(`https://your-domain.com/api/mobile/delegate/orders?${queryParams}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    return {
      orders: data.data,
      pagination: data.pagination
    };
  } else {
    throw new Error(data.message);
  }
}

// جلب طلب واحد
async function getOrder(orderId) {
  const token = localStorage.getItem('token');
  
  const response = await fetch(`https://your-domain.com/api/mobile/delegate/orders/${orderId}`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const data = await response.json();
  
  if (data.success) {
    return data.data.order;
  } else {
    throw new Error(data.message);
  }
}

// استخدام الأمثلة
getOrders({ status: 'pending', per_page: 20 })
  .then(result => {
    console.log('الطلبات:', result.orders);
    console.log('معلومات الصفحات:', result.pagination);
  })
  .catch(error => console.error('خطأ:', error));

getOrder(1)
  .then(order => console.log('الطلب:', order))
  .catch(error => console.error('خطأ:', error));
```

### مثال 6: استخدام Flutter/Dart

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

class DelegateOrderApiService {
  final String baseUrl = 'https://your-domain.com/api/mobile';
  String? token;

  // جلب قائمة الطلبات
  Future<Map<String, dynamic>> getOrders({
    String? status,
    String? search,
    String? dateFrom,
    String? dateTo,
    String? timeFrom,
    String? timeTo,
    int perPage = 15,
    int page = 1,
  }) async {
    if (token == null) {
      throw Exception('لم يتم تسجيل الدخول');
    }

    final queryParams = <String, String>{};
    if (status != null) queryParams['status'] = status;
    if (search != null) queryParams['search'] = search;
    if (dateFrom != null) queryParams['date_from'] = dateFrom;
    if (dateTo != null) queryParams['date_to'] = dateTo;
    if (timeFrom != null) queryParams['time_from'] = timeFrom;
    if (timeTo != null) queryParams['time_to'] = timeTo;
    queryParams['per_page'] = perPage.toString();
    queryParams['page'] = page.toString();

    final uri = Uri.parse('$baseUrl/delegate/orders').replace(
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
          'orders': data['data'],
          'pagination': data['pagination'],
        };
      }
    }
    
    throw Exception('فشل جلب الطلبات');
  }

  // جلب طلب واحد
  Future<Map<String, dynamic>> getOrder(int orderId) async {
    if (token == null) {
      throw Exception('لم يتم تسجيل الدخول');
    }

    final response = await http.get(
      Uri.parse('$baseUrl/delegate/orders/$orderId'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
      },
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success']) {
        return data['data']['order'];
      }
    }
    
    throw Exception('فشل جلب الطلب');
  }
}
```

---

## أكواد الأخطاء - الطلبات

| Error Code | الوصف | HTTP Status |
|------------|-------|-------------|
| `FORBIDDEN` | غير مصرح - المستخدم ليس مندوب | 403 |
| `FORBIDDEN_ORDER` | ليس لديك صلاحية للوصول إلى هذا الطلب | 403 |
| `ORDER_NOT_FOUND` | الطلب غير موجود | 404 |
| `ORDER_CONFIRMED` | لا يمكن تعديل الطلبات المقيدة | 400 |
| `ORDER_CANNOT_BE_DELETED` | لا يمكن حذف هذا الطلب | 400 |
| `INSUFFICIENT_STOCK` | الكمية غير متوفرة للاسترجاع | 400 |
| `UPDATE_ERROR` | خطأ أثناء تحديث الطلب | 500 |
| `DELETE_ERROR` | خطأ أثناء حذف الطلب | 500 |
| `RESTORE_ERROR` | خطأ أثناء استرجاع الطلب | 500 |
| `INVALID_PHONE` | رقم الهاتف غير صحيح | 422 |
| `CART_NOT_FOUND` | لا توجد سلة نشطة | 404 |
| `FORBIDDEN_CART` | ليس لديك صلاحية للوصول إلى هذه السلة | 403 |
| `CART_NOT_ACTIVE` | السلة غير نشطة | 400 |
| `EMPTY_CART` | السلة فارغة | 400 |
| `MISSING_CUSTOMER_DATA` | بيانات الزبون غير موجودة | 400 |
| `INITIALIZE_ERROR` | خطأ أثناء بدء الطلب | 500 |
| `ADD_ITEMS_ERROR` | خطأ أثناء إضافة المنتجات | 400 |
| `UPDATE_ITEM_ERROR` | خطأ أثناء تحديث الكمية | 500 |
| `REMOVE_ITEM_ERROR` | خطأ أثناء حذف المنتج | 500 |
| `SUBMIT_ERROR` | خطأ أثناء إرسال الطلب | 500 |
| `FORBIDDEN_ITEM` | ليس لديك صلاحية للوصول إلى هذا العنصر | 403 |

---

## إنشاء طلب جديد

### نظرة عامة
إنشاء طلب جديد يتطلب عدة خطوات:
1. **بدء الطلب**: إنشاء سلة جديدة مع بيانات الزبون
2. **إضافة المنتجات**: إضافة المنتجات والقياسات للسلة
3. **إدارة السلة**: عرض، تعديل، أو حذف المنتجات
4. **إرسال الطلب**: تحويل السلة إلى طلب نهائي

### التدفق الكامل

```
1. POST /orders/initialize → إنشاء سلة جديدة
2. POST /carts/items → إضافة منتجات (متكرر)
3. GET /carts/current → عرض السلة
4. PUT /carts/items/{id} → تعديل الكميات (اختياري)
5. DELETE /carts/items/{id} → حذف منتجات (اختياري)
6. POST /orders/submit → إرسال الطلب
```

---

## 1. بدء طلب جديد (Initialize Order)

### Endpoint
```
POST /api/mobile/delegate/orders/initialize
```

### الوصف
إنشاء سلة جديدة (Cart) مع بيانات الزبون. يتم حذف أي سلة نشطة قديمة للمندوب تلقائياً.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يتم حذف أي سلة نشطة قديمة للمندوب تلقائياً
- يتم تنسيق أرقام الهواتف إلى 11 رقم
- يتم إنشاء StockReservation عند إضافة المنتجات (ليس هنا)

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "customer_name": "string (required) - اسم العميل",
  "customer_phone": "string (required) - رقم هاتف العميل (11 رقم)",
  "customer_phone2": "string|null - رقم هاتف العميل الثاني (11 رقم)",
  "customer_address": "string (required) - عنوان العميل",
  "customer_social_link": "string (required) - رابط التواصل الاجتماعي",
  "notes": "string|null - ملاحظات"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم بدء الطلب بنجاح",
  "data": {
    "cart": {
      "id": 1,
      "customer_name": "أحمد محمد",
      "customer_phone": "07701234567",
      "customer_phone2": null,
      "customer_address": "بغداد - الكرادة",
      "customer_social_link": "https://facebook.com/...",
      "notes": "ملاحظات",
      "status": "active",
      "total_amount": 0.00,
      "items_count": 0,
      "items": [],
      "created_at": "2025-01-15T10:30:00+00:00",
      "expires_at": "2025-01-16T10:30:00+00:00"
    }
  }
}
```

### Response Error (422 Unprocessable Entity) - رقم هاتف غير صحيح
```json
{
  "success": false,
  "message": "رقم الهاتف يجب أن يكون بالضبط 11 رقم بعد التنسيق",
  "error_code": "INVALID_PHONE"
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/orders/initialize" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_phone2": "07701234568",
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات إضافية"
  }'
```

---

## 2. جلب السلة الحالية (Get Current Cart)

### Endpoint
```
GET /api/mobile/delegate/carts/current
```

### الوصف
جلب السلة الحالية النشطة للمندوب مع جميع المنتجات والتفاصيل.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `cart_id` | integer | No | معرف السلة (إذا لم يتم تحديده، يتم البحث عن السلة النشطة) |

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "cart": {
      "id": 1,
      "customer_name": "أحمد محمد",
      "customer_phone": "07701234567",
      "customer_phone2": null,
      "customer_address": "بغداد - الكرادة",
      "customer_social_link": "https://facebook.com/...",
      "notes": "ملاحظات",
      "status": "active",
      "total_amount": 150.00,
      "items_count": 2,
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "قميص أطفال",
          "product_code": "SHIRT001",
          "size_id": 1,
          "size_name": "S",
          "quantity": 2,
          "price": 50.00,
          "subtotal": 100.00,
          "product": {
            "id": 1,
            "name": "قميص أطفال",
            "code": "SHIRT001",
            "primary_image": "https://your-domain.com/storage/products/1_primary.jpg"
          }
        },
        {
          "id": 2,
          "product_id": 2,
          "product_name": "بنطلون أطفال",
          "product_code": "PANT001",
          "size_id": 3,
          "size_name": "M",
          "quantity": 1,
          "price": 50.00,
          "subtotal": 50.00,
          "product": {
            "id": 2,
            "name": "بنطلون أطفال",
            "code": "PANT001",
            "primary_image": "https://your-domain.com/storage/products/2_primary.jpg"
          }
        }
      ],
      "created_at": "2025-01-15T10:30:00+00:00",
      "expires_at": "2025-01-16T10:30:00+00:00"
    }
  }
}
```

### Response Error (404 Not Found) - لا توجد سلة
```json
{
  "success": false,
  "message": "لا توجد سلة نشطة",
  "error_code": "CART_NOT_FOUND"
}
```

### مثال على الاستخدام
```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/carts/current?cart_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 3. إضافة منتجات للسلة (Add Items to Cart)

### Endpoint
```
POST /api/mobile/delegate/carts/items
```

### الوصف
إضافة منتجات للسلة. يمكن إضافة عدة قياسات لنفس المنتج في طلب واحد. يتم إنشاء StockReservation تلقائياً.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن تكون السلة نشطة (`status = 'active'`)
- يجب أن تكون السلة تخص المندوب
- يتم التحقق من توفر الكميات (available_quantity)
- إذا كان المنتج موجود في السلة بنفس القياس، يتم تحديث الكمية
- يتم استخدام `effective_price` للسعر (يشمل التخفيضات النشطة)
- يتم إنشاء StockReservation لكل منتج (حجز للمخزن)

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "cart_id": "integer (required) - معرف السلة",
  "product_id": "integer (required) - معرف المنتج",
  "items": [
    {
      "size_id": "integer (required) - معرف القياس",
      "quantity": "integer (required, min:1) - الكمية"
    }
  ]
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم إضافة المنتجات إلى السلة بنجاح",
  "data": {
    "cart": {
      // نفس هيكل Cart من getCurrent
    }
  }
}
```

### Response Error (400 Bad Request) - كمية غير متوفرة
```json
{
  "success": false,
  "message": "الكمية المطلوبة غير متوفرة للقياس S. المتوفر: 5",
  "error_code": "ADD_ITEMS_ERROR"
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/carts/items" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "cart_id": 1,
    "product_id": 1,
    "items": [
      {
        "size_id": 1,
        "quantity": 2
      },
      {
        "size_id": 2,
        "quantity": 1
      }
    ]
  }'
```

---

## 4. تحديث كمية منتج في السلة (Update Cart Item)

### Endpoint
```
PUT /api/mobile/delegate/carts/items/{id}
```

### الوصف
تحديث كمية منتج موجود في السلة. يتم تحديث StockReservation تلقائياً.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون العنصر يخص المندوب
- يتم التحقق من توفر الكمية الجديدة
- حساب available_quantity = size.quantity + current_cart_item.quantity

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "quantity": "integer (required, min:1) - الكمية الجديدة"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم تحديث الكمية بنجاح",
  "data": {
    "cart": {
      // نفس هيكل Cart من getCurrent
    }
  }
}
```

### Response Error (400 Bad Request) - كمية غير متوفرة
```json
{
  "success": false,
  "message": "الكمية المطلوبة غير متوفرة. المتوفر: 10",
  "error_code": "INSUFFICIENT_STOCK"
}
```

### مثال على الاستخدام
```bash
curl -X PUT "https://your-domain.com/api/mobile/delegate/carts/items/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "quantity": 5
  }'
```

---

## 5. حذف منتج من السلة (Remove Cart Item)

### Endpoint
```
DELETE /api/mobile/delegate/carts/items/{id}
```

### الوصف
حذف منتج من السلة. يتم حذف StockReservation تلقائياً (إرجاع للمخزن).

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون العنصر يخص المندوب
- يتم حذف StockReservation (إرجاع للمخزن)

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم حذف المنتج بنجاح",
  "data": {
    "cart": {
      // نفس هيكل Cart من getCurrent
    }
  }
}
```

### مثال على الاستخدام
```bash
curl -X DELETE "https://your-domain.com/api/mobile/delegate/carts/items/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 6. إرسال الطلب (Submit Order)

### Endpoint
```
POST /api/mobile/delegate/orders/submit
```

### الوصف
تحويل السلة إلى طلب نهائي. يتم خصم المنتجات من المخزن وحذف StockReservations.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن تكون السلة نشطة (`status = 'active'`)
- يجب أن تحتوي السلة على منتجات
- يجب أن تكون بيانات الزبون موجودة
- يتم استخدام `lockForUpdate()` لمنع التكرار
- يتم خصم المنتجات من المخزن
- يتم حذف StockReservations
- يتم تغيير حالة السلة إلى 'completed'
- يتم تسجيل حركة المواد (ProductMovement)
- يتم إرسال Event لإنشاء شحنة في الواسط
- يتم إرسال إشعار SweetAlert للمجهز/المدير

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "cart_id": "integer (required) - معرف السلة"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم إنشاء الطلب بنجاح! رقم الطلب: ORD-20250115-0001",
  "data": {
    "order": {
      // نفس هيكل Order Details من قسم الطلبات
    }
  }
}
```

### Response Error (400 Bad Request) - سلة فارغة
```json
{
  "success": false,
  "message": "أضف منتجات أولاً",
  "error_code": "EMPTY_CART"
}
```

### Response Error (400 Bad Request) - بيانات الزبون مفقودة
```json
{
  "success": false,
  "message": "بيانات الزبون غير موجودة. يرجى إنشاء طلب جديد",
  "error_code": "MISSING_CUSTOMER_DATA"
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/orders/submit" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "cart_id": 1
  }'
```

---

## أمثلة على الاستخدام - إنشاء طلب جديد

### مثال 1: إنشاء طلب كامل باستخدام cURL

```bash
# 1. بدء الطلب
curl -X POST "https://your-domain.com/api/mobile/delegate/orders/initialize" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "أحمد محمد",
    "customer_phone": "07701234567",
    "customer_address": "بغداد - الكرادة",
    "customer_social_link": "https://facebook.com/...",
    "notes": "ملاحظات"
  }'

# 2. إضافة منتج للسلة
curl -X POST "https://your-domain.com/api/mobile/delegate/carts/items" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "cart_id": 1,
    "product_id": 1,
    "items": [
      {
        "size_id": 1,
        "quantity": 2
      }
    ]
  }'

# 3. عرض السلة
curl -X GET "https://your-domain.com/api/mobile/delegate/carts/current?cart_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"

# 4. إرسال الطلب
curl -X POST "https://your-domain.com/api/mobile/delegate/orders/submit" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "cart_id": 1
  }'
```

### مثال 2: استخدام JavaScript (Fetch API)

```javascript
class DelegateOrderService {
  constructor(token) {
    this.token = token;
    this.baseUrl = 'https://your-domain.com/api/mobile';
  }

  // بدء طلب جديد
  async initializeOrder(customerData) {
    const response = await fetch(`${this.baseUrl}/delegate/orders/initialize`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(customerData)
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل بدء الطلب');
    }

    return data.data.cart;
  }

  // جلب السلة الحالية
  async getCurrentCart(cartId = null) {
    const url = cartId 
      ? `${this.baseUrl}/delegate/carts/current?cart_id=${cartId}`
      : `${this.baseUrl}/delegate/carts/current`;

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل جلب السلة');
    }

    return data.data.cart;
  }

  // إضافة منتجات للسلة
  async addItemsToCart(cartId, productId, items) {
    const response = await fetch(`${this.baseUrl}/delegate/carts/items`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        cart_id: cartId,
        product_id: productId,
        items: items
      })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إضافة المنتجات');
    }

    return data.data.cart;
  }

  // تحديث كمية منتج
  async updateCartItem(itemId, quantity) {
    const response = await fetch(`${this.baseUrl}/delegate/carts/items/${itemId}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ quantity })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل تحديث الكمية');
    }

    return data.data.cart;
  }

  // حذف منتج من السلة
  async removeCartItem(itemId) {
    const response = await fetch(`${this.baseUrl}/delegate/carts/items/${itemId}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل حذف المنتج');
    }

    return data.data.cart;
  }

  // إرسال الطلب
  async submitOrder(cartId) {
    const response = await fetch(`${this.baseUrl}/delegate/orders/submit`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ cart_id: cartId })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إرسال الطلب');
    }

    return data.data.order;
  }
}

// استخدام الأمثلة
const orderService = new DelegateOrderService(localStorage.getItem('token'));

// إنشاء طلب كامل
async function createOrder() {
  try {
    // 1. بدء الطلب
    const cart = await orderService.initializeOrder({
      customer_name: 'أحمد محمد',
      customer_phone: '07701234567',
      customer_address: 'بغداد - الكرادة',
      customer_social_link: 'https://facebook.com/...',
      notes: 'ملاحظات'
    });

    console.log('تم بدء الطلب:', cart);

    // 2. إضافة منتجات
    const updatedCart = await orderService.addItemsToCart(cart.id, 1, [
      { size_id: 1, quantity: 2 },
      { size_id: 2, quantity: 1 }
    ]);

    console.log('تم إضافة المنتجات:', updatedCart);

    // 3. إرسال الطلب
    const order = await orderService.submitOrder(cart.id);
    console.log('تم إنشاء الطلب:', order);
  } catch (error) {
    console.error('خطأ:', error);
  }
}
```

### ملاحظات مهمة

1. **StockReservation**: يتم إنشاء حجز للمنتجات عند الإضافة للسلة، ولا يتم خصمها من المخزن إلا عند إرسال الطلب
2. **Transaction Safety**: جميع العمليات الحساسة تتم داخل transactions
3. **Locking**: يتم استخدام `lockForUpdate()` عند إرسال الطلب لمنع التكرار
4. **Phone Normalization**: يتم تنسيق أرقام الهواتف تلقائياً إلى 11 رقم
5. **Quantity Validation**: يتم التحقق من الكميات المتوفرة قبل أي عملية
6. **Cart Expiration**: السلة تنتهي بعد 24 ساعة من الإنشاء

---

## نظام الرسائل (Chat)

### نظرة عامة
نظام الرسائل يسمح للمندوب بالتواصل مع المستخدمين الآخرين (المجهزين، المديرين، المندوبين الآخرين) عبر محادثات مباشرة أو مجموعات. يدعم النظام إرسال رسائل نصية، صور، طلبات، ومنتجات.

### أنواع الرسائل
1. **نص (text)**: رسالة نصية عادية
2. **صورة (image)**: رسالة تحتوي على صورة
3. **طلب (order)**: رسالة تحتوي على تفاصيل طلب
4. **منتج (product)**: رسالة تحتوي على تفاصيل منتج

### أنواع المحادثات
1. **مباشرة (direct)**: محادثة بين مستخدمين اثنين
2. **مجموعة (group)**: محادثة بين عدة مستخدمين (للمدير فقط)

---

## 1. جلب قائمة المحادثات (Get Conversations)

### Endpoint
```
GET /api/mobile/delegate/chat/conversations
```

### الوصف
جلب قائمة جميع المحادثات للمندوب مع آخر رسالة وعدد الرسائل غير المقروءة.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "conversations": [
      {
        "id": 1,
        "type": "direct",
        "userId": 2,
        "name": "أحمد محمد",
        "code": "DEL001",
        "path": "https://your-domain.com/storage/profiles/2.jpg",
        "preview": "آخر رسالة في المحادثة",
        "time": "10:30 AM",
        "active": true,
        "unread_count": 2
      },
      {
        "id": 2,
        "type": "group",
        "userId": null,
        "name": "مجموعة المندوبين",
        "code": null,
        "path": "group-icon.svg",
        "preview": "آخر رسالة في المجموعة",
        "time": "09:15 AM",
        "active": true,
        "unread_count": 0,
        "participants_count": 5
      }
    ]
  }
}
```

### مثال على الاستخدام
```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/chat/conversations" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 2. جلب أو إنشاء محادثة (Get or Create Conversation)

### Endpoint
```
POST /api/mobile/delegate/chat/conversation
```

### الوصف
جلب محادثة موجودة مع مستخدم أو إنشاء محادثة جديدة إذا لم تكن موجودة.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "user_id": "integer (required) - معرف المستخدم"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "conversation_id": 1,
    "other_user": {
      "id": 2,
      "name": "أحمد محمد",
      "path": "https://your-domain.com/storage/profiles/2.jpg"
    }
  }
}
```

### Response Error (403 Forbidden) - مستخدم غير متاح
```json
{
  "success": false,
  "message": "لا يمكنك المراسلة مع هذا المستخدم",
  "error_code": "USER_NOT_AVAILABLE"
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/conversation" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2
  }'
```

---

## 3. جلب الرسائل (Get Messages)

### Endpoint
```
GET /api/mobile/delegate/chat/messages
```

### الوصف
جلب جميع رسائل محادثة معينة. يتم تحديث `last_read_at` تلقائياً وحذف إشعارات SweetAlert.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `conversation_id` | integer | Yes | معرف المحادثة |

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "messages": [
      {
        "id": 1,
        "fromUserId": 1,
        "toUserId": 2,
        "text": "مرحباً، كيف الحال؟",
        "type": "text",
        "time": "10:30 AM",
        "created_at": "2025-01-15T10:30:00+00:00",
        "image_url": null,
        "order": null,
        "product": null
      },
      {
        "id": 2,
        "fromUserId": 2,
        "toUserId": 1,
        "text": "",
        "type": "image",
        "time": "10:31 AM",
        "created_at": "2025-01-15T10:31:00+00:00",
        "image_url": "https://your-domain.com/storage/messages/abc123.jpg",
        "order": null,
        "product": null
      },
      {
        "id": 3,
        "fromUserId": 1,
        "toUserId": 2,
        "text": "طلب: ORD-20250115-0001",
        "type": "order",
        "time": "10:32 AM",
        "created_at": "2025-01-15T10:32:00+00:00",
        "image_url": null,
        "order": {
          "id": 1,
          "order_number": "ORD-20250115-0001",
          "customer_name": "محمد أحمد",
          "customer_phone": "07701234567",
          "customer_social_link": "https://facebook.com/...",
          "total_amount": 150.00,
          "status": "pending",
          "delegate_name": "أحمد محمد",
          "created_at": "2025-01-15 10:30"
        },
        "product": null
      },
      {
        "id": 4,
        "fromUserId": 1,
        "toUserId": 2,
        "text": "منتج: قميص أطفال",
        "type": "product",
        "time": "10:33 AM",
        "created_at": "2025-01-15T10:33:00+00:00",
        "image_url": null,
        "order": null,
        "product": {
          "id": 1,
          "name": "قميص أطفال",
          "code": "SHIRT001",
          "selling_price": 50.00,
          "gender_type": "unisex",
          "warehouse_name": "مخزن بغداد",
          "image_url": "https://your-domain.com/storage/products/1_primary.jpg",
          "sizes": [
            {
              "id": 1,
              "size_name": "S",
              "quantity": 10,
              "available_quantity": 8
            },
            {
              "id": 2,
              "size_name": "M",
              "quantity": 15,
              "available_quantity": 12
            }
          ]
        }
      }
    ]
  }
}
```

### مثال على الاستخدام
```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/chat/messages?conversation_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 4. إرسال رسالة (Send Message)

### Endpoint
```
POST /api/mobile/delegate/chat/send
```

### الوصف
إرسال رسالة نصية أو صورة في محادثة موجودة.

### القواعد والصلاحيات
- يجب أن يكون المستخدم مندوباً (`isDelegate()`)
- يجب أن يكون المستخدم مشاركاً في المحادثة
- يجب أن تحتوي الرسالة على نص أو صورة على الأقل
- يتم إرسال SweetAlert للمستلم تلقائياً

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data (للصور) أو application/json (للنص)
```

### Request Body (رسالة نصية)
```json
{
  "conversation_id": "integer (required)",
  "message": "string (nullable, max:5000)"
}
```

### Request Body (رسالة مع صورة)
- `conversation_id` (required, integer)
- `message` (optional, string, max:5000)
- `image` (file, max:5MB, types: jpeg,jpg,png,gif,webp)

### Response Success (200 OK)
```json
{
  "success": true,
  "message": {
    "id": 1,
    "fromUserId": 1,
    "text": "مرحباً، كيف الحال؟",
    "type": "text",
    "time": "10:30 AM",
    "created_at": "2025-01-15T10:30:00+00:00",
    "image_url": null
  }
}
```

### Response Success (200 OK) - رسالة مع صورة
```json
{
  "success": true,
  "message": {
    "id": 2,
    "fromUserId": 1,
    "text": "صورة المنتج",
    "type": "image",
    "time": "10:31 AM",
    "created_at": "2025-01-15T10:31:00+00:00",
    "image_url": "https://your-domain.com/storage/messages/abc123.jpg"
  }
}
```

### Response Error (400 Bad Request) - رسالة فارغة
```json
{
  "success": false,
  "message": "يجب إدخال رسالة أو رفع صورة",
  "error_code": "EMPTY_MESSAGE"
}
```

### مثال على الاستخدام - رسالة نصية
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/send" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "message": "مرحباً، كيف الحال؟"
  }'
```

### مثال على الاستخدام - رسالة مع صورة
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/send" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -F "conversation_id=1" \
  -F "message=صورة المنتج" \
  -F "image=@/path/to/image.jpg"
```

---

## 5. إرسال رسالة لمستخدم (Send Message to User)

### Endpoint
```
POST /api/mobile/delegate/chat/send-to-user
```

### الوصف
إرسال رسالة لمستخدم معين. يتم إنشاء محادثة تلقائياً إذا لم تكن موجودة.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data (للصور) أو application/json (للنص)
```

### Request Body
```json
{
  "user_id": "integer (required)",
  "message": "string (nullable, max:5000)",
  "image": "file (optional, max:5MB)"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": {
    "id": 1,
    "fromUserId": 1,
    "text": "مرحباً",
    "type": "text",
    "time": "10:30 AM",
    "created_at": "2025-01-15T10:30:00+00:00",
    "image_url": null
  }
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/send-to-user" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "message": "مرحباً، أريد التحدث معك"
  }'
```

---

## 6. تحديد الرسائل كمقروءة (Mark as Read)

### Endpoint
```
POST /api/mobile/delegate/chat/mark-read
```

### الوصف
تحديد جميع رسائل محادثة معينة كمقروءة. يتم تحديث `last_read_at` تلقائياً.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "conversation_id": "integer (required)"
}
```

### Response Success (200 OK)
```json
{
  "success": true
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/mark-read" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1
  }'
```

---

## 7. البحث عن طلب (Search Order)

### Endpoint
```
GET /api/mobile/delegate/chat/search-order
```

### الوصف
البحث عن طلبات بناءً على رقم الطلب، رقم الهاتف، الرابط، أو كود الوسيط.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `query` | string | Yes | نص البحث (min:3) |

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 1,
        "order_number": "ORD-20250115-0001",
        "customer_name": "محمد أحمد",
        "customer_phone": "07701234567",
        "customer_social_link": "https://facebook.com/...",
        "total_amount": 150.00,
        "status": "pending",
        "delegate_name": "أحمد محمد",
        "created_at": "2025-01-15 10:30"
      }
    ]
  }
}
```

### مثال على الاستخدام
```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/chat/search-order?query=ORD-20250115" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 8. إرسال رسالة تحتوي على طلب (Send Order Message)

### Endpoint
```
POST /api/mobile/delegate/chat/send-order
```

### الوصف
إرسال رسالة تحتوي على تفاصيل طلب في محادثة.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "conversation_id": "integer (required)",
  "order_id": "integer (required)"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": {
    "id": 1,
    "fromUserId": 1,
    "text": "طلب: ORD-20250115-0001",
    "type": "order",
    "time": "10:30 AM",
    "created_at": "2025-01-15T10:30:00+00:00",
    "order": {
      "id": 1,
      "order_number": "ORD-20250115-0001",
      "customer_name": "محمد أحمد",
      "customer_phone": "07701234567",
      "customer_social_link": "https://facebook.com/...",
      "total_amount": 150.00,
      "status": "pending",
      "delegate_name": "أحمد محمد",
      "created_at": "2025-01-15 10:30"
    }
  }
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/send-order" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "order_id": 1
  }'
```

---

## 9. البحث عن منتج (Search Product)

### Endpoint
```
GET /api/mobile/delegate/chat/search-product
```

### الوصف
البحث عن منتجات بناءً على اسم المنتج أو الكود.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
```

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `query` | string | Yes | نص البحث (min:2) |

### Response Success (200 OK)
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "قميص أطفال",
        "code": "SHIRT001",
        "selling_price": 50.00,
        "gender_type": "unisex",
        "warehouse_name": "مخزن بغداد",
        "image_url": "https://your-domain.com/storage/products/1_primary.jpg",
        "sizes": [
          {
            "id": 1,
            "size_name": "S",
            "quantity": 10,
            "available_quantity": 8
          },
          {
            "id": 2,
            "size_name": "M",
            "quantity": 15,
            "available_quantity": 12
          }
        ]
      }
    ]
  }
}
```

### مثال على الاستخدام
```bash
curl -X GET "https://your-domain.com/api/mobile/delegate/chat/search-product?query=قميص" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

---

## 10. إرسال رسالة تحتوي على منتج (Send Product Message)

### Endpoint
```
POST /api/mobile/delegate/chat/send-product
```

### الوصف
إرسال رسالة تحتوي على تفاصيل منتج في محادثة.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Body
```json
{
  "conversation_id": "integer (required)",
  "product_id": "integer (required)"
}
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": {
    "id": 1,
    "fromUserId": 1,
    "text": "منتج: قميص أطفال",
    "type": "product",
    "time": "10:30 AM",
    "created_at": "2025-01-15T10:30:00+00:00",
    "product": {
      "id": 1,
      "name": "قميص أطفال",
      "code": "SHIRT001",
      "selling_price": 50.00,
      "gender_type": "unisex",
      "warehouse_name": "مخزن بغداد",
      "image_url": "https://your-domain.com/storage/products/1_primary.jpg",
      "sizes": [
        {
          "id": 1,
          "size_name": "S",
          "quantity": 10,
          "available_quantity": 8
        },
        {
          "id": 2,
          "size_name": "M",
          "quantity": 15,
          "available_quantity": 12
        }
      ]
    }
  }
}
```

### مثال على الاستخدام
```bash
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/send-product" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "product_id": 1
  }'
```

---

## أمثلة على الاستخدام - الرسائل

### مثال 1: إنشاء محادثة وإرسال رسالة

```bash
# 1. إنشاء محادثة
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/conversation" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2
  }'

# 2. إرسال رسالة
curl -X POST "https://your-domain.com/api/mobile/delegate/chat/send" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "conversation_id": 1,
    "message": "مرحباً، أريد التحدث معك"
  }'
```

### مثال 2: استخدام JavaScript (Fetch API)

```javascript
class DelegateChatService {
  constructor(token) {
    this.token = token;
    this.baseUrl = 'https://your-domain.com/api/mobile';
  }

  // جلب المحادثات
  async getConversations() {
    const response = await fetch(`${this.baseUrl}/delegate/chat/conversations`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل جلب المحادثات');
    }

    return data.data.conversations;
  }

  // إنشاء محادثة
  async getOrCreateConversation(userId) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/conversation`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ user_id: userId })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إنشاء المحادثة');
    }

    return data.data;
  }

  // جلب الرسائل
  async getMessages(conversationId) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/messages?conversation_id=${conversationId}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل جلب الرسائل');
    }

    return data.data.messages;
  }

  // إرسال رسالة نصية
  async sendMessage(conversationId, message) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/send`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        conversation_id: conversationId,
        message: message
      })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إرسال الرسالة');
    }

    return data.message;
  }

  // إرسال رسالة مع صورة
  async sendImageMessage(conversationId, message, imageFile) {
    const formData = new FormData();
    formData.append('conversation_id', conversationId);
    if (message) {
      formData.append('message', message);
    }
    formData.append('image', imageFile);

    const response = await fetch(`${this.baseUrl}/delegate/chat/send`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      },
      body: formData
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إرسال الصورة');
    }

    return data.message;
  }

  // البحث عن طلب
  async searchOrder(query) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/search-order?query=${encodeURIComponent(query)}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل البحث عن الطلب');
    }

    return data.data.orders;
  }

  // إرسال طلب
  async sendOrder(conversationId, orderId) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/send-order`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        conversation_id: conversationId,
        order_id: orderId
      })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إرسال الطلب');
    }

    return data.message;
  }

  // البحث عن منتج
  async searchProduct(query) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/search-product?query=${encodeURIComponent(query)}`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل البحث عن المنتج');
    }

    return data.data.products;
  }

  // إرسال منتج
  async sendProduct(conversationId, productId) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/send-product`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        conversation_id: conversationId,
        product_id: productId
      })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل إرسال المنتج');
    }

    return data.message;
  }

  // تحديد كمقروءة
  async markAsRead(conversationId) {
    const response = await fetch(`${this.baseUrl}/delegate/chat/mark-read`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        conversation_id: conversationId
      })
    });

    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'فشل تحديد الرسائل كمقروءة');
    }

    return data;
  }
}

// استخدام الأمثلة
const chatService = new DelegateChatService(localStorage.getItem('token'));

// جلب المحادثات
const conversations = await chatService.getConversations();
console.log('المحادثات:', conversations);

// إنشاء محادثة جديدة
const conversation = await chatService.getOrCreateConversation(2);
console.log('المحادثة:', conversation);

// جلب الرسائل
const messages = await chatService.getMessages(conversation.conversation_id);
console.log('الرسائل:', messages);

// إرسال رسالة نصية
const sentMessage = await chatService.sendMessage(conversation.conversation_id, 'مرحباً');
console.log('تم إرسال الرسالة:', sentMessage);

// إرسال صورة
const imageInput = document.querySelector('input[type="file"]');
if (imageInput.files[0]) {
  const imageMessage = await chatService.sendImageMessage(
    conversation.conversation_id,
    'صورة المنتج',
    imageInput.files[0]
  );
  console.log('تم إرسال الصورة:', imageMessage);
}

// البحث عن طلب وإرساله
const orders = await chatService.searchOrder('ORD-20250115');
if (orders.length > 0) {
  const orderMessage = await chatService.sendOrder(conversation.conversation_id, orders[0].id);
  console.log('تم إرسال الطلب:', orderMessage);
}

// البحث عن منتج وإرساله
const products = await chatService.searchProduct('قميص');
if (products.length > 0) {
  const productMessage = await chatService.sendProduct(conversation.conversation_id, products[0].id);
  console.log('تم إرسال المنتج:', productMessage);
}
```

### ملاحظات مهمة

1. **تحديث last_read_at**: عند جلب الرسائل، يتم تحديث `last_read_at` تلقائياً وحذف إشعارات SweetAlert
2. **إرسال إشعارات**: عند إرسال رسالة، يتم إرسال SweetAlert للمستلم تلقائياً
3. **رفع الصور**: يتم حفظ الصور في `storage/app/public/messages/` بحد أقصى 5MB
4. **أنواع الرسائل**: text, image, order, product
5. **أنواع المحادثات**: direct (مباشرة) أو group (مجموعة)
6. **Unread Count**: يتم حساب الرسائل غير المقروءة بناءً على `last_read_at`
7. **المستخدمين المتاحين**: يمكن للمندوب المراسلة مع جميع المستخدمين (لا توجد قيود)

---

## Notifications API - نظام الإشعارات

نظام إشعارات Firebase Cloud Messaging (FCM) للمندوبين. يتيح النظام للمندوبين استقبال إشعارات فورية على أجهزتهم المحمولة عند حدوث أحداث مهمة مثل إنشاء طلبات جديدة، تغيير حالة الشحنة، أو استلام رسائل.

### نظرة عامة

- **نوع الإشعارات**: Push Notifications عبر Firebase Cloud Messaging
- **التخزين**: يتم حفظ جميع الإشعارات في جدول `notifications` في قاعدة البيانات
- **التكامل**: يعمل النظام بجانب نظام Telegram الموجود (لا يحل محله)
- **الأنواع المدعومة**: طلبات (order_created, order_confirmed, order_deleted)، رسائل (message)، تغيير حالة الشحنة (shipment_status_changed)

### 1. تسجيل FCM Token

تسجيل FCM token للجهاز لاستقبال الإشعارات.

**Endpoint:** `POST /api/mobile/delegate/notifications/register-token`

**Headers:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "fcm_token_string_from_firebase",
  "device_type": "android",  // android, ios, web
  "device_info": {
    "model": "Samsung Galaxy S21",
    "os_version": "14",
    "app_version": "1.0.0"
  }
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "تم تسجيل الجهاز بنجاح"
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "خطأ في التحقق من البيانات",
  "errors": {
    "token": ["حقل token مطلوب"]
  },
  "error_code": "VALIDATION_ERROR"
}
```

**ملاحظات:**
- يجب استدعاء هذا API عند تسجيل الدخول أو عند تحديث FCM token
- إذا كان token موجوداً، سيتم تحديثه بدلاً من إنشاء واحد جديد
- يتم تعطيل tokens القديمة تلقائياً عند اكتشاف أنها غير صالحة

### 2. جلب قائمة الإشعارات

جلب قائمة الإشعارات للمندوب مع إمكانية التصفية والترقيم.

**Endpoint:** `GET /api/mobile/delegate/notifications`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `page` (optional): رقم الصفحة (افتراضي: 1)
- `per_page` (optional): عدد الإشعارات في الصفحة (افتراضي: 20)
- `type` (optional): نوع الإشعار (order_created, message, shipment_status_changed, etc.)

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "type": "order_created",
        "title": "طلب جديد",
        "message": "تم إنشاء طلب جديد: ORD-20250115-001",
        "data": {
          "order_id": 123,
          "order_number": "ORD-20250115-001",
          "screen": "order_details"
        },
        "is_read": false,
        "read_at": null,
        "created_at": "2025-01-15T10:30:00Z"
      },
      {
        "id": 2,
        "type": "message",
        "title": "رسالة جديدة",
        "message": "رسالة من أحمد: مرحباً، هل المنتج متوفر؟",
        "data": {
          "conversation_id": 45,
          "sender_id": 2,
          "screen": "chat"
        },
        "is_read": true,
        "read_at": "2025-01-15T10:35:00Z",
        "created_at": "2025-01-15T10:32:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 3,
      "per_page": 20,
      "total": 45
    }
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "حدث خطأ أثناء جلب الإشعارات",
  "error_code": "FETCH_NOTIFICATIONS_ERROR"
}
```

**ملاحظات:**
- يتم ترتيب الإشعارات حسب تاريخ الإنشاء (الأحدث أولاً)
- يتم استبعاد الإشعارات المنتهية الصلاحية تلقائياً
- يمكن تصفية الإشعارات حسب النوع باستخدام `type` parameter

### 3. جلب عدد الإشعارات غير المقروءة

جلب عدد الإشعارات غير المقروءة للمندوب.

**Endpoint:** `GET /api/mobile/delegate/notifications/unread-count`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "unread_count": 5
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "حدث خطأ أثناء جلب عدد الإشعارات",
  "error_code": "FETCH_UNREAD_COUNT_ERROR"
}
```

**ملاحظات:**
- يمكن استدعاء هذا API بشكل دوري لتحديث badge العداد في التطبيق
- يتم حساب الإشعارات غير المقروءة فقط (حيث `read_at` = null)

### 4. تحديد إشعار كمقروء

تحديد إشعار معين كمقروء.

**Endpoint:** `POST /api/mobile/delegate/notifications/{id}/mark-read`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "تم تحديد الإشعار كمقروء"
}
```

**Response (Error - Not Found):**
```json
{
  "success": false,
  "message": "الإشعار غير موجود",
  "error_code": "NOTIFICATION_NOT_FOUND"
}
```

**Response (Error - Already Read):**
```json
{
  "success": true,
  "message": "الإشعار مقروء بالفعل"
}
```

**ملاحظات:**
- يمكن استدعاء هذا API عند فتح الإشعار في التطبيق
- إذا كان الإشعار مقروءاً بالفعل، سيتم إرجاع رسالة تأكيد

### 5. تحديد جميع الإشعارات كمقروءة

تحديد جميع الإشعارات غير المقروءة كمقروءة دفعة واحدة.

**Endpoint:** `POST /api/mobile/delegate/notifications/mark-all-read`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "تم تحديد 15 إشعار كمقروء",
  "data": {
    "updated_count": 15
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "حدث خطأ أثناء تحديد جميع الإشعارات كمقروءة",
  "error_code": "MARK_ALL_READ_ERROR"
}
```

**ملاحظات:**
- يمكن استدعاء هذا API من زر "قراءة الكل" في التطبيق
- يتم تحديث `read_at` لجميع الإشعارات غير المقروءة

### 6. إلغاء تسجيل FCM Token

إلغاء تسجيل FCM token (يستخدم عند تسجيل الخروج).

**Endpoint:** `DELETE /api/mobile/delegate/notifications/unregister-token`

**Headers:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "token": "fcm_token_string"
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "تم إلغاء تسجيل الجهاز"
}
```

**Response (Error - Not Found):**
```json
{
  "success": false,
  "message": "Token غير موجود",
  "error_code": "TOKEN_NOT_FOUND"
}
```

**ملاحظات:**
- يجب استدعاء هذا API عند تسجيل الخروج
- بعد إلغاء التسجيل، لن يتم إرسال إشعارات لهذا الجهاز

---

### أنواع الإشعارات

#### 1. order_created - طلب جديد

يتم إرسال هذا الإشعار عند إنشاء طلب جديد من قبل المندوب.

**Data Structure:**
```json
{
  "type": "order_created",
  "order_id": 123,
  "order_number": "ORD-20250115-001",
  "screen": "order_details"
}
```

**ملاحظات:**
- يتم إرسال الإشعار للمندوب الذي أنشأ الطلب
- عند الضغط على الإشعار، يجب فتح صفحة تفاصيل الطلب

#### 2. order_confirmed - تم تقييد الطلب

يتم إرسال هذا الإشعار عند تقييد الطلب (confirmation).

**Data Structure:**
```json
{
  "type": "order_confirmed",
  "order_id": 123,
  "order_number": "ORD-20250115-001",
  "screen": "order_details"
}
```

**ملاحظات:**
- يتم إرسال الإشعار للمندوب الذي أنشأ الطلب
- يشير إلى أن الطلب تم تأكيده وتقييده

#### 3. order_deleted - تم حذف الطلب

يتم إرسال هذا الإشعار عند حذف طلب.

**Data Structure:**
```json
{
  "type": "order_deleted",
  "order_id": 123,
  "order_number": "ORD-20250115-001",
  "screen": "order_details"
}
```

**ملاحظات:**
- يتم إرسال الإشعار للمندوب الذي أنشأ الطلب
- يشير إلى أن الطلب تم حذفه (soft delete)

#### 4. message - رسالة جديدة

يتم إرسال هذا الإشعار عند استلام رسالة جديدة في المحادثة.

**Data Structure:**
```json
{
  "type": "message",
  "conversation_id": 45,
  "sender_id": 2,
  "screen": "chat"
}
```

**ملاحظات:**
- يتم إرسال الإشعار للمستلم فقط
- عند الضغط على الإشعار، يجب فتح المحادثة

#### 5. shipment_status_changed - تغيير حالة الشحنة

يتم إرسال هذا الإشعار عند تغيير حالة شحنة الطلب.

**Data Structure:**
```json
{
  "type": "shipment_status_changed",
  "order_id": 123,
  "order_number": "ORD-20250115-001",
  "shipment_id": 78,
  "old_status": "pending",
  "new_status": "in_transit",
  "screen": "order_details"
}
```

**ملاحظات:**
- يتم إرسال الإشعار للمندوب الذي أنشأ الطلب
- يشير إلى تغيير حالة الشحنة في نظام Al Waseet

---

### مثال على استخدام API في JavaScript/Flutter

```javascript
// تسجيل FCM Token
async function registerFCMToken(token, deviceType = 'android') {
  const response = await fetch('https://api.example.com/api/mobile/delegate/notifications/register-token', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${pwaToken}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      token: token,
      device_type: deviceType,
      device_info: {
        model: 'Samsung Galaxy S21',
        os_version: '14',
        app_version: '1.0.0'
      }
    })
  });
  
  const data = await response.json();
  console.log('Token registered:', data);
}

// جلب الإشعارات
async function getNotifications(page = 1, type = null) {
  let url = `https://api.example.com/api/mobile/delegate/notifications?page=${page}&per_page=20`;
  if (type) {
    url += `&type=${type}`;
  }
  
  const response = await fetch(url, {
    headers: {
      'Authorization': `Bearer ${pwaToken}`
    }
  });
  
  const data = await response.json();
  return data.data.notifications;
}

// جلب عدد الإشعارات غير المقروءة
async function getUnreadCount() {
  const response = await fetch('https://api.example.com/api/mobile/delegate/notifications/unread-count', {
    headers: {
      'Authorization': `Bearer ${pwaToken}`
    }
  });
  
  const data = await response.json();
  return data.data.unread_count;
}

// تحديد إشعار كمقروء
async function markAsRead(notificationId) {
  const response = await fetch(`https://api.example.com/api/mobile/delegate/notifications/${notificationId}/mark-read`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${pwaToken}`
    }
  });
  
  const data = await response.json();
  console.log('Marked as read:', data);
}

// تحديد جميع الإشعارات كمقروءة
async function markAllAsRead() {
  const response = await fetch('https://api.example.com/api/mobile/delegate/notifications/mark-all-read', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${pwaToken}`
    }
  });
  
  const data = await response.json();
  console.log('Marked all as read:', data);
}

// إلغاء تسجيل Token
async function unregisterToken(token) {
  const response = await fetch('https://api.example.com/api/mobile/delegate/notifications/unregister-token', {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${pwaToken}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      token: token
    })
  });
  
  const data = await response.json();
  console.log('Token unregistered:', data);
}
```

### ملاحظات مهمة

1. **تسجيل Token**: يجب تسجيل FCM token فوراً بعد تسجيل الدخول الناجح
2. **إلغاء التسجيل**: يجب إلغاء تسجيل token عند تسجيل الخروج
3. **معالجة الإشعارات**: عند استقبال push notification، يجب فتح التطبيق والانتقال للشاشة المناسبة حسب `data.screen`
4. **تحديث العداد**: يجب تحديث عداد الإشعارات غير المقروءة بشكل دوري
5. **التكامل مع Firebase**: يجب تكوين Firebase في التطبيق باستخدام VAPID keys من `firebase-credentials-base64.txt`
6. **الأمان**: جميع endpoints محمية بـ `auth.pwa` middleware
7. **التخزين**: يتم حفظ جميع الإشعارات في قاعدة البيانات، حتى لو لم يتم استقبال push notification
8. **التكامل مع Telegram**: يعمل نظام FCM بجانب نظام Telegram الموجود (لا يحل محله)

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

### Orders APIs
- `GET /api/mobile/delegate/orders` - قائمة الطلبات
- `GET /api/mobile/delegate/orders/{id}` - تفاصيل طلب واحد
- `PUT /api/mobile/delegate/orders/{id}` - تحديث طلب
- `DELETE /api/mobile/delegate/orders/{id}` - حذف طلب (soft delete)
- `POST /api/mobile/delegate/orders/{id}/restore` - استرجاع طلب محذوف
- `POST /api/mobile/delegate/orders/{id}/force-delete` - حذف طلب نهائياً (hard delete)
- `POST /api/mobile/delegate/orders/initialize` - بدء طلب جديد (إنشاء سلة)
- `POST /api/mobile/delegate/orders/submit` - إرسال الطلب (تحويل سلة إلى طلب)

### Cart APIs
- `GET /api/mobile/delegate/carts/current` - جلب السلة الحالية
- `POST /api/mobile/delegate/carts/items` - إضافة منتجات للسلة
- `PUT /api/mobile/delegate/carts/items/{id}` - تحديث كمية منتج في السلة
- `DELETE /api/mobile/delegate/carts/items/{id}` - حذف منتج من السلة

### Chat APIs
- `GET /api/mobile/delegate/chat/conversations` - جلب قائمة المحادثات
- `POST /api/mobile/delegate/chat/conversation` - جلب أو إنشاء محادثة
- `GET /api/mobile/delegate/chat/messages` - جلب رسائل محادثة
- `POST /api/mobile/delegate/chat/send` - إرسال رسالة نصية أو صورة
- `POST /api/mobile/delegate/chat/send-to-user` - إرسال رسالة لمستخدم
- `POST /api/mobile/delegate/chat/mark-read` - تحديد الرسائل كمقروءة
- `GET /api/mobile/delegate/chat/search-order` - البحث عن طلب
- `POST /api/mobile/delegate/chat/send-order` - إرسال رسالة تحتوي على طلب
- `GET /api/mobile/delegate/chat/search-product` - البحث عن منتج
- `POST /api/mobile/delegate/chat/send-product` - إرسال رسالة تحتوي على منتج

### Notifications APIs
- `POST /api/mobile/delegate/notifications/register-token` - تسجيل FCM token
- `GET /api/mobile/delegate/notifications` - جلب قائمة الإشعارات
- `GET /api/mobile/delegate/notifications/unread-count` - عدد الإشعارات غير المقروءة
- `POST /api/mobile/delegate/notifications/{id}/mark-read` - تحديد إشعار كمقروء
- `POST /api/mobile/delegate/notifications/mark-all-read` - تحديد جميع الإشعارات كمقروءة
- `DELETE /api/mobile/delegate/notifications/unregister-token` - إلغاء تسجيل FCM token

