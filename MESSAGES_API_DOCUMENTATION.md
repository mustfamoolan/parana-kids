# توثيق API للرسائل والمحادثات

## Base URL
```
https://your-domain.com/api
```

**ملاحظة:** جميع المسارات تتطلب PWA token في header:
```
Authorization: Bearer {token}
أو
X-PWA-Token: {token}
```

---

## 1. جلب قائمة المحادثات

**Endpoint:** `GET /api/messages/conversations`

**الوصف:** جلب قائمة جميع المحادثات للمستخدم الحالي

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "conversations": [
    {
      "id": 1,
      "type": "direct",
      "userId": 2,
      "name": "محمد علي",
      "code": "DELEGATE001",
      "path": "https://domain.com/storage/profile.jpg",
      "preview": "مرحباً، كيف الحال؟",
      "time": "10:30 AM",
      "active": true,
      "unread_count": 3
    },
    {
      "id": 2,
      "type": "group",
      "userId": null,
      "name": "فريق العمل",
      "code": null,
      "path": "group-icon.svg",
      "preview": "رسالة في المجموعة",
      "time": "09:15 AM",
      "active": true,
      "unread_count": 5,
      "participants_count": 5
    }
  ]
}
```

---

## 2. الحصول على أو إنشاء محادثة

**Endpoint:** `POST /api/messages/conversation`

**الوصف:** الحصول على محادثة موجودة أو إنشاء محادثة جديدة مع مستخدم

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 2
}
```

**Response Success (200):**
```json
{
  "success": true,
  "conversation_id": 1,
  "other_user": {
    "id": 2,
    "name": "محمد علي",
    "path": "https://domain.com/storage/profile.jpg"
  }
}
```

**Response Error (403):**
```json
{
  "success": false,
  "message": "لا يمكنك المراسلة مع هذا المستخدم"
}
```

---

## 3. جلب الرسائل

**Endpoint:** `GET /api/messages/{conversation_id}`

**الوصف:** جلب جميع الرسائل في محادثة معينة

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "messages": [
    {
      "id": 1,
      "fromUserId": 1,
      "toUserId": 2,
      "text": "مرحباً، كيف الحال؟",
      "type": "text",
      "time": "10:30 AM"
    },
    {
      "id": 2,
      "fromUserId": 2,
      "toUserId": 1,
      "text": "أهلاً، بخير والحمد لله",
      "type": "text",
      "time": "10:31 AM"
    },
    {
      "id": 3,
      "fromUserId": 1,
      "toUserId": 2,
      "text": "",
      "type": "image",
      "image_url": "https://domain.com/storage/messages/image.jpg",
      "time": "10:32 AM"
    },
    {
      "id": 4,
      "fromUserId": 1,
      "toUserId": 2,
      "text": "طلب: ORD-12345",
      "type": "order",
      "order": {
        "id": 10,
        "order_number": "ORD-12345",
        "customer_name": "أحمد محمد",
        "customer_phone": "0123456789",
        "customer_social_link": "https://facebook.com/ahmed",
        "total_amount": 500.00,
        "status": "pending",
        "delegate_name": "محمد علي",
        "created_at": "2024-01-15 10:00"
      },
      "time": "10:33 AM"
    },
    {
      "id": 5,
      "fromUserId": 1,
      "toUserId": 2,
      "text": "منتج: قميص أزرق",
      "type": "product",
      "product": {
        "id": 5,
        "name": "قميص أزرق",
        "code": "SHIRT-001",
        "selling_price": 150.00,
        "gender_type": "unisex",
        "warehouse_name": "مخزن رئيسي",
        "image_url": "https://domain.com/storage/products/shirt.jpg",
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
      },
      "time": "10:34 AM"
    }
  ]
}
```

**ملاحظات:**
- الرسائل مرتبة حسب الوقت (من الأقدم للأحدث)
- عند فتح المحادثة، يتم تحديث `last_read_at` تلقائياً
- للمجموعات، يتم إضافة `sender_name` و `sender_id` لكل رسالة

---

## 4. إرسال رسالة

**Endpoint:** `POST /api/messages`

**الوصف:** إرسال رسالة نصية أو مع صورة

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
```
conversation_id: 1
message: "مرحباً، هذه رسالة نصية"
image: (file) - اختياري
```

**ملاحظات:**
- يمكن إرسال رسالة نصية فقط
- يمكن إرسال صورة فقط
- يمكن إرسال نص وصورة معاً
- الحد الأقصى لحجم الصورة: 5MB
- الصيغ المدعومة: jpeg, jpg, png, gif, webp

**Response Success (200):**
```json
{
  "success": true,
  "message": {
    "id": 6,
    "fromUserId": 1,
    "text": "مرحباً، هذه رسالة نصية",
    "type": "text",
    "time": "10:35 AM",
    "image_url": null
  }
}
```

**Response Success (200) - مع صورة:**
```json
{
  "success": true,
  "message": {
    "id": 7,
    "fromUserId": 1,
    "text": "",
    "type": "image",
    "time": "10:36 AM",
    "image_url": "https://domain.com/storage/messages/image.jpg"
  }
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "يجب إدخال رسالة أو رفع صورة"
}
```

---

## 5. إرسال رسالة لمستخدم

**Endpoint:** `POST /api/messages/send-to-user`

**الوصف:** إرسال رسالة لمستخدم مع إنشاء محادثة تلقائياً إذا لم تكن موجودة

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body (Form Data):**
```
user_id: 2
message: "مرحباً"
image: (file) - اختياري
```

**Response Success (200):**
```json
{
  "success": true,
  "message": {
    "id": 8,
    "fromUserId": 1,
    "text": "مرحباً",
    "type": "text",
    "time": "10:37 AM"
  }
}
```

---

## 6. تحديد الرسائل كمقروءة

**Endpoint:** `POST /api/messages/mark-read`

**الوصف:** تحديد جميع الرسائل في محادثة كمقروءة

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "conversation_id": 1
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "تم تحديد الرسائل كمقروءة"
}
```

---

## 7. البحث عن طلب

**Endpoint:** `GET /api/messages/search/order?query={search_term}`

**الوصف:** البحث عن طلبات (رقم الطلب، رقم الهاتف، الرابط، أو كود الوسيط)

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `query` (required) - نص البحث (الحد الأدنى: 3 أحرف)

**Response Success (200):**
```json
{
  "success": true,
  "orders": [
    {
      "id": 10,
      "order_number": "ORD-12345",
      "customer_name": "أحمد محمد",
      "customer_phone": "0123456789",
      "customer_social_link": "https://facebook.com/ahmed",
      "total_amount": 500.00,
      "status": "pending",
      "delegate_name": "محمد علي",
      "created_at": "2024-01-15 10:00"
    }
  ]
}
```

---

## 8. إرسال رسالة مع طلب

**Endpoint:** `POST /api/messages/order`

**الوصف:** إرسال رسالة تحتوي على معلومات طلب

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "conversation_id": 1,
  "order_id": 10
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": {
    "id": 9,
    "fromUserId": 1,
    "text": "طلب: ORD-12345",
    "type": "order",
    "order": {
      "id": 10,
      "order_number": "ORD-12345",
      "customer_name": "أحمد محمد",
      "customer_phone": "0123456789",
      "customer_social_link": "https://facebook.com/ahmed",
      "total_amount": 500.00,
      "status": "pending",
      "delegate_name": "محمد علي",
      "created_at": "2024-01-15 10:00"
    },
    "time": "10:38 AM"
  }
}
```

---

## 9. البحث عن منتج

**Endpoint:** `GET /api/messages/search/product?query={search_term}`

**الوصف:** البحث عن منتجات (اسم المنتج أو الكود)

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `query` (required) - نص البحث (الحد الأدنى: 2 أحرف)

**Response Success (200):**
```json
{
  "success": true,
  "products": [
    {
      "id": 5,
      "name": "قميص أزرق",
      "code": "SHIRT-001",
      "selling_price": 150.00,
      "gender_type": "unisex",
      "warehouse_name": "مخزن رئيسي",
      "image_url": "https://domain.com/storage/products/shirt.jpg",
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
```

---

## 10. إرسال رسالة مع منتج

**Endpoint:** `POST /api/messages/product`

**الوصف:** إرسال رسالة تحتوي على معلومات منتج

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "conversation_id": 1,
  "product_id": 5
}
```

**Response Success (200):**
```json
{
  "success": true,
  "message": {
    "id": 10,
    "fromUserId": 1,
    "text": "منتج: قميص أزرق",
    "type": "product",
    "product": {
      "id": 5,
      "name": "قميص أزرق",
      "code": "SHIRT-001",
      "selling_price": 150.00,
      "gender_type": "unisex",
      "warehouse_name": "مخزن رئيسي",
      "image_url": "https://domain.com/storage/products/shirt.jpg",
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
    },
    "time": "10:39 AM"
  }
}
```

---

## 11. إنشاء مجموعة (للمدير فقط)

**Endpoint:** `POST /api/messages/groups`

**الوصف:** إنشاء محادثة جماعية جديدة

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "فريق العمل",
  "user_ids": [2, 3, 4]
}
```

**Response Success (200):**
```json
{
  "success": true,
  "conversation_id": 3,
  "conversation": {
    "id": 3,
    "title": "فريق العمل",
    "type": "group",
    "participants_count": 4,
    "participants": [
      {
        "id": 1,
        "name": "أحمد محمد",
        "code": "ADMIN001",
        "role": "admin"
      },
      {
        "id": 2,
        "name": "محمد علي",
        "code": "DELEGATE001",
        "role": "delegate"
      },
      {
        "id": 3,
        "name": "خالد أحمد",
        "code": "SUPPLIER001",
        "role": "supplier"
      },
      {
        "id": 4,
        "name": "سارة محمد",
        "code": "DELEGATE002",
        "role": "delegate"
      }
    ]
  }
}
```

**Response Error (403):**
```json
{
  "success": false,
  "message": "غير مصرح - المدير فقط يمكنه إنشاء المجموعات"
}
```

---

## 12. إضافة مشاركين للمجموعة (للمدير فقط)

**Endpoint:** `POST /api/messages/groups/{id}/participants`

**الوصف:** إضافة مستخدمين جدد لمجموعة موجودة

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_ids": [5, 6]
}
```

**Response Success (200):**
```json
{
  "success": true,
  "added_count": 2,
  "participants_count": 6
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "هذه المحادثة ليست مجموعة"
}
```

---

## 13. إزالة مشارك من المجموعة (للمدير فقط)

**Endpoint:** `DELETE /api/messages/groups/{id}/participants/{user_id}`

**الوصف:** إزالة مستخدم من مجموعة

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "participants_count": 5
}
```

**Response Error (400):**
```json
{
  "success": false,
  "message": "لا يمكنك إزالة نفسك من المجموعة"
}
```

---

## 14. جلب قائمة المشاركين في المجموعة

**Endpoint:** `GET /api/messages/groups/{id}/participants`

**الوصف:** جلب قائمة جميع المشاركين في مجموعة

**Headers:**
```
Authorization: Bearer {token}
```

**Response Success (200):**
```json
{
  "success": true,
  "conversation_id": 3,
  "title": "فريق العمل",
  "participants": [
    {
      "id": 1,
      "name": "أحمد محمد",
      "code": "ADMIN001",
      "role": "admin",
      "path": "https://domain.com/storage/profile.jpg"
    },
    {
      "id": 2,
      "name": "محمد علي",
      "code": "DELEGATE001",
      "role": "delegate",
      "path": "https://domain.com/storage/profile2.jpg"
    }
  ],
  "participants_count": 2,
  "is_admin": true
}
```

---

## أنواع الرسائل

### 1. رسالة نصية (`text`)
```json
{
  "id": 1,
  "fromUserId": 1,
  "toUserId": 2,
  "text": "مرحباً",
  "type": "text",
  "time": "10:30 AM"
}
```

### 2. رسالة مع صورة (`image`)
```json
{
  "id": 2,
  "fromUserId": 1,
  "toUserId": 2,
  "text": "",
  "type": "image",
  "image_url": "https://domain.com/storage/messages/image.jpg",
  "time": "10:31 AM"
}
```

### 3. رسالة مع طلب (`order`)
```json
{
  "id": 3,
  "fromUserId": 1,
  "toUserId": 2,
  "text": "طلب: ORD-12345",
  "type": "order",
  "order": {
    "id": 10,
    "order_number": "ORD-12345",
    "customer_name": "أحمد محمد",
    "customer_phone": "0123456789",
    "customer_social_link": "https://facebook.com/ahmed",
    "total_amount": 500.00,
    "status": "pending",
    "delegate_name": "محمد علي",
    "created_at": "2024-01-15 10:00"
  },
  "time": "10:32 AM"
}
```

### 4. رسالة مع منتج (`product`)
```json
{
  "id": 4,
  "fromUserId": 1,
  "toUserId": 2,
  "text": "منتج: قميص أزرق",
  "type": "product",
  "product": {
    "id": 5,
    "name": "قميص أزرق",
    "code": "SHIRT-001",
    "selling_price": 150.00,
    "gender_type": "unisex",
    "warehouse_name": "مخزن رئيسي",
    "image_url": "https://domain.com/storage/products/shirt.jpg",
    "sizes": [
      {
        "id": 1,
        "size_name": "S",
        "quantity": 10,
        "available_quantity": 8
      }
    ]
  },
  "time": "10:33 AM"
}
```

---

## أنواع المحادثات

### 1. محادثة مباشرة (`direct`)
محادثة بين مستخدمين فقط

### 2. محادثة جماعية (`group`)
محادثة مع عدة مستخدمين (للمدير فقط)

---

## أمثلة الاستخدام

### مثال 1: جلب المحادثات في Flutter/Dart
```dart
Future<Map<String, dynamic>> getConversations(String token) async {
  final response = await http.get(
    Uri.parse('https://your-domain.com/api/messages/conversations'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  return jsonDecode(response.body);
}
```

### مثال 2: إرسال رسالة نصية
```dart
Future<Map<String, dynamic>> sendMessage(
  String token,
  int conversationId,
  String message,
) async {
  final response = await http.post(
    Uri.parse('https://your-domain.com/api/messages'),
    headers: {
      'Authorization': 'Bearer $token',
    },
    body: {
      'conversation_id': conversationId.toString(),
      'message': message,
    },
  );
  
  return jsonDecode(response.body);
}
```

### مثال 3: إرسال رسالة مع صورة
```dart
Future<Map<String, dynamic>> sendMessageWithImage(
  String token,
  int conversationId,
  String message,
  File imageFile,
) async {
  var request = http.MultipartRequest(
    'POST',
    Uri.parse('https://your-domain.com/api/messages'),
  );
  
  request.headers['Authorization'] = 'Bearer $token';
  request.fields['conversation_id'] = conversationId.toString();
  request.fields['message'] = message;
  request.files.add(
    await http.MultipartFile.fromPath('image', imageFile.path),
  );
  
  var response = await request.send();
  var responseBody = await response.stream.bytesToString();
  
  return jsonDecode(responseBody);
}
```

### مثال 4: جلب الرسائل
```dart
Future<Map<String, dynamic>> getMessages(
  String token,
  int conversationId,
) async {
  final response = await http.get(
    Uri.parse('https://your-domain.com/api/messages/$conversationId'),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  return jsonDecode(response.body);
}
```

### مثال 5: البحث عن منتج
```dart
Future<Map<String, dynamic>> searchProduct(
  String token,
  String query,
) async {
  final response = await http.get(
    Uri.parse('https://your-domain.com/api/messages/search/product')
        .replace(queryParameters: {'query': query}),
    headers: {
      'Authorization': 'Bearer $token',
      'Content-Type': 'application/json',
    },
  );
  
  return jsonDecode(response.body);
}
```

---

## معالجة الأخطاء

### أخطاء المصادقة (401)
```json
{
  "success": false,
  "message": "غير مصرح."
}
```

### أخطاء الصلاحيات (403)
```json
{
  "success": false,
  "message": "غير مصرح - المدير فقط يمكنه إنشاء المجموعات"
}
```

### أخطاء التحقق (422)
```json
{
  "success": false,
  "message": "خطأ في التحقق من البيانات",
  "errors": {
    "conversation_id": ["The conversation id field is required."]
  }
}
```

### أخطاء الخادم (500)
```json
{
  "success": false,
  "message": "حدث خطأ في إرسال الرسالة: ..."
}
```

---

## ملاحظات مهمة

1. **رفع الصور:** استخدم `multipart/form-data` عند إرسال صور
2. **حجم الصورة:** الحد الأقصى 5MB
3. **صيغ الصور:** jpeg, jpg, png, gif, webp
4. **الرسائل غير المقروءة:** يتم حسابها تلقائياً بناءً على `last_read_at`
5. **الإشعارات:** يتم إرسال إشعارات SweetAlert تلقائياً عند استلام رسالة جديدة
6. **المجموعات:** فقط المدير يمكنه إنشاء وإدارة المجموعات
7. **الصلاحيات:** يجب أن يكون المستخدم مشاركاً في المحادثة للوصول إليها

---

## كودات الحالة (Status Codes)

- `200` - نجح الطلب
- `400` - خطأ في الطلب (مثل: يجب إدخال رسالة أو صورة)
- `401` - غير مصرح (مطلوب token صحيح)
- `403` - ممنوع (ليس لديك صلاحيات)
- `422` - خطأ في التحقق من البيانات
- `500` - خطأ في الخادم

---

## الدعم

للمزيد من المعلومات أو المساعدة، يرجى التواصل مع فريق التطوير.

