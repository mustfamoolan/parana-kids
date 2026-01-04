# Admin & Supplier Orders Mobile API Documentation

## نظرة عامة

هذا التوثيق يشرح APIs إدارة الطلبات للمدير والمجهز في تطبيق الموبايل. يتضمن إدارة الطلبات غير المقيدة والمقيدة، التعديل، التجهيز، والمواد المطلوبة.

**Base URL:** `https://your-domain.com/api/mobile/admin/orders`

**Authentication:** جميع الـ endpoints تحتاج إلى PWA Token في header `Authorization: Bearer {pwa_token}`

**الأدوار المدعومة:**
- `admin` (المدير)
- `supplier` (المجهز العادي)
- `private_supplier` (المورد الخاص)

---

## Authentication

جميع الـ endpoints تحتاج إلى PWA Token. راجع [ADMIN_API_DOCUMENTATION.md](./ADMIN_API_DOCUMENTATION.md) للحصول على تفاصيل تسجيل الدخول والحصول على Token.

**Headers المطلوبة:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
Accept: application/json
```

---

## 1. الطلبات غير المقيدة (Pending Orders)

### 1.1. جلب قائمة الطلبات غير المقيدة

**Endpoint:** `GET /api/mobile/admin/orders/pending`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `warehouse_id` (optional): فلتر حسب المخزن
- `confirmed_by` (optional): فلتر حسب المجهز الذي قيد الطلب (user_id)
- `delegate_id` (optional): فلتر حسب المندوب
- `size_reviewed` (optional): فلتر حالة التدقيق (not_reviewed, reviewed)
- `message_confirmed` (optional): فلتر حالة تأكيد الرسالة (not_sent, waiting_response, not_confirmed, confirmed)
- `search` (optional): بحث شامل في (order_number, customer_name, customer_phone, customer_address, delivery_code, delegate name, product name/code)
- `date_from` (optional): تاريخ البداية (Y-m-d)
- `date_to` (optional): تاريخ النهاية (Y-m-d)
- `time_from` (optional): وقت البداية (H:i)
- `time_to` (optional): وقت النهاية (H:i)
- `hours_ago` (optional): آخر X ساعة (2, 4, 6, 8... حتى 30)
- `page` (optional): رقم الصفحة (افتراضي: 1)
- `per_page` (optional): عدد الطلبات في الصفحة (افتراضي: 15، حد أقصى: 50)

**Response (Success):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "order_number": "ORD-20250130-001",
      "customer_name": "أحمد محمد",
      "customer_phone": "07901234567",
      "customer_address": "بغداد - الكرادة",
      "status": "pending",
      "total_amount": 150000.00,
      "delivery_fee": 0.00,
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "confirmed_by": null,
      "created_at": "2025-01-30T10:00:00Z",
      "confirmed_at": null,
      "items_count": 3
    }
  ],
  "statistics": {
    "total_orders": 25,
    "total_amount": 3500000.00,
    "total_profit": 875000.00
  },
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 25,
    "last_page": 2,
    "has_more": true
  }
}
```

**ملاحظات:**
- `statistics` موجود فقط للمدير (`null` للمجهز)
- الطلبات مرتبة حسب تاريخ الإنشاء (الأحدث أولاً)
- الفلتر يعمل على `created_at` (تاريخ إنشاء الطلب)

---

## 2. الطلبات المقيدة (Confirmed Orders)

### 2.1. جلب قائمة الطلبات المقيدة

**Endpoint:** `GET /api/mobile/admin/orders/confirmed`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- نفس الفلاتر في الطلبات غير المقيدة
- **ملاحظة مهمة**: الفلتر يعمل على `confirmed_at` (تاريخ التقييد) وليس `created_at`

**Response (Success):**
```json
{
  "success": true,
  "data": [
    {
      "id": 124,
      "order_number": "ORD-20250130-002",
      "customer_name": "محمد علي",
      "customer_phone": "07901234568",
      "customer_address": "بغداد - المنصور",
      "status": "confirmed",
      "total_amount": 200000.00,
      "delivery_fee": 5000.00,
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "confirmed_by": {
        "id": 2,
        "name": "مجهز 1"
      },
      "created_at": "2025-01-30T09:00:00Z",
      "confirmed_at": "2025-01-30T10:30:00Z",
      "items_count": 4
    }
  ],
  "statistics": {
    "total_orders": 45,
    "total_amount": 8750000.00,
    "total_profit": 2187500.00
  },
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 45,
    "last_page": 3,
    "has_more": true
  }
}
```

**ملاحظات:**
- `statistics` موجود فقط للمدير (`null` للمجهز)
- الطلبات مرتبة حسب `confirmed_at` (الأحدث أولاً)
- الفلتر يعمل على `confirmed_at` (تاريخ التقييد)

---

## 3. إدارة الطلبات (Management)

### 3.1. جلب قائمة موحدة للطلبات

**Endpoint:** `GET /api/mobile/admin/orders`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `status` (optional): فلتر الحالة (pending, confirmed, deleted)
  - `pending`: الطلبات غير المقيدة
  - `confirmed`: الطلبات المقيدة
  - `deleted`: الطلبات المحذوفة (soft deleted) التي حذفها المدير/المجهز
  - بدون فلتر: عرض pending و confirmed معاً
- نفس الفلاتر الأخرى من الطلبات غير المقيدة

**Response (Success):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "order_number": "ORD-20250130-001",
      "customer_name": "أحمد محمد",
      "customer_phone": "07901234567",
      "customer_address": "بغداد - الكرادة",
      "status": "pending",
      "total_amount": 150000.00,
      "delivery_fee": 0.00,
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "confirmed_by": null,
      "created_at": "2025-01-30T10:00:00Z",
      "confirmed_at": null,
      "items_count": 3
    },
    {
      "id": 124,
      "order_number": "ORD-20250130-002",
      "customer_name": "محمد علي",
      "customer_phone": "07901234568",
      "customer_address": "بغداد - المنصور",
      "status": "confirmed",
      "total_amount": 200000.00,
      "delivery_fee": 5000.00,
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "confirmed_by": {
        "id": 2,
        "name": "مجهز 1"
      },
      "created_at": "2025-01-30T09:00:00Z",
      "confirmed_at": "2025-01-30T10:30:00Z",
      "items_count": 4
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 70,
    "last_page": 5,
    "has_more": true
  }
}
```

**ملاحظات:**
- لا يوجد `statistics` في هذا الـ endpoint
- الطلبات مرتبة حسب `created_at` (الأحدث أولاً)

---

## 4. عرض وتعديل الطلب

### 4.1. جلب تفاصيل طلب واحد

**Endpoint:** `GET /api/mobile/admin/orders/{id}`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Path Parameters:**
- `id` (required): معرف الطلب

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      "customer_name": "أحمد محمد",
      "customer_phone": "07901234567",
      "customer_phone2": "07901234568",
      "customer_address": "بغداد - الكرادة",
      "customer_social_link": "@ahmadmohammad",
      "delivery_code": "D123",
      "notes": "يرجى التوصيل بعد الساعة 2 ظهراً",
      "status": "pending",
      "total_amount": 150000.00,
      "delivery_fee": 0.00,
      "size_reviewed": "not_reviewed",
      "message_confirmed": "not_sent",
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "confirmed_by": null,
      "processed_by": null,
      "created_at": "2025-01-30T10:00:00Z",
      "confirmed_at": null,
      "can_be_edited": true,
      "items": [
        {
          "id": 456,
          "product": {
            "id": 789,
            "name": "منتج 1",
            "code": "P001",
            "primary_image_url": "https://...",
            "warehouse": {
              "id": 1,
              "name": "مخزن الشمال"
            }
          },
          "size_id": 12,
          "size_name": "M",
          "quantity": 2,
          "unit_price": 75000.00,
          "subtotal": 150000.00
        }
      ]
    }
  }
}
```

**Response (Error - Not Found):**
```json
{
  "success": false,
  "message": "الطلب غير موجود أو ليس لديك صلاحية للوصول إليه",
  "error_code": "NOT_FOUND"
}
```

**ملاحظات:**
- يتم حذف إشعارات الطلب تلقائياً عند فتحه
- `can_be_edited`: `true` إذا كان الطلب `pending` أو `confirmed` خلال 5 ساعات من التقييد

---

### 4.2. جلب بيانات التعديل (المنتجات المتاحة)

**Endpoint:** `GET /api/mobile/admin/orders/{id}/edit`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Path Parameters:**
- `id` (required): معرف الطلب

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      ...
    },
    "products": [
      {
        "id": 789,
        "name": "منتج 1",
        "code": "P001",
        "purchase_price": 50000.00,
        "selling_price": 75000.00,
        "warehouse_id": 1,
        "warehouse": {
          "id": 1,
          "name": "مخزن الشمال"
        },
        "primary_image_url": "https://...",
        "sizes": [
          {
            "id": 12,
            "size_name": "M",
            "quantity": 15
          },
          {
            "id": 13,
            "size_name": "L",
            "quantity": 20
          }
        ]
      }
    ]
  }
}
```

**Response (Error - Cannot Edit):**
```json
{
  "success": false,
  "message": "لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)",
  "error_code": "CANNOT_EDIT"
}
```

**ملاحظات:**
- المنتجات المعروضة حسب صلاحيات المستخدم (للمجهز: فقط منتجات المخازن المسموح بها)
- يتم التحقق من إمكانية التعديل قبل إرجاع البيانات

---

### 4.3. تعديل الطلب

**Endpoint:** `PUT /api/mobile/admin/orders/{id}`

**Headers:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
```

**Path Parameters:**
- `id` (required): معرف الطلب

**Request Body:**
```json
{
  "delivery_code": "D123",
  "customer_name": "أحمد محمد",
  "customer_phone": "07901234567",
  "customer_phone2": "07901234568",
  "customer_address": "بغداد - الكرادة",
  "customer_social_link": "@ahmadmohammad",
  "notes": "يرجى التوصيل بعد الساعة 2 ظهراً",
  "items": [
    {
      "product_id": 789,
      "size_id": 12,
      "quantity": 3
    },
    {
      "product_id": 790,
      "size_id": 15,
      "quantity": 2
    }
  ]
}
```

**Validation Rules:**
- `customer_name`: required|string|max:255
- `customer_phone`: required|string|max:20
- `customer_phone2`: nullable|string|max:20
- `customer_address`: required|string
- `customer_social_link`: required|string|max:255
- `delivery_code`: nullable|string|max:255
- `notes`: nullable|string
- `items`: required|array|min:1
- `items.*.product_id`: required|exists:products,id
- `items.*.size_id`: required|exists:product_sizes,id
- `items.*.quantity`: required|integer|min:1

**Response (Success):**
```json
{
  "success": true,
  "message": "تم تعديل الطلب بنجاح",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      ...
    }
  }
}
```

**Response (Error - Cannot Edit):**
```json
{
  "success": false,
  "message": "لا يمكن تعديل هذا الطلب (مر أكثر من 5 ساعات على التقييد)",
  "error_code": "CANNOT_EDIT"
}
```

**Response (Error - Insufficient Quantity):**
```json
{
  "success": false,
  "message": "الكمية المتوفرة من منتج 1 - M غير كافية. المطلوب: 3، المتوفر: 2",
  "error_code": "UPDATE_ERROR"
}
```

**ملاحظات:**
- للطلبات المقيدة: يمكن التعديل فقط خلال 5 ساعات من التقييد
- يتم معالجة حركات المخزن تلقائياً (إرجاع/خصم)
- يتم تسجيل ProductMovement لكل تغيير
- يتم إعادة حساب `total_amount` تلقائياً

---

## 5. تجهيز الطلب

### 5.1. جلب بيانات تجهيز الطلب

**Endpoint:** `GET /api/mobile/admin/orders/{id}/process`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Path Parameters:**
- `id` (required): معرف الطلب

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      ...
    },
    "products": [
      {
        "id": 789,
        "name": "منتج 1",
        "code": "P001",
        "purchase_price": 50000.00,
        "selling_price": 75000.00,
        "warehouse_id": 1,
        "warehouse": {
          "id": 1,
          "name": "مخزن الشمال"
        },
        "primary_image_url": "https://...",
        "sizes": [
          {
            "id": 12,
            "size_name": "M",
            "quantity": 15
          }
        ]
      }
    ]
  }
}
```

**Response (Error - Cannot Process):**
```json
{
  "success": false,
  "message": "لا يمكن تجهيز الطلبات المقيدة",
  "error_code": "CANNOT_PROCESS"
}
```

**ملاحظات:**
- يمكن تجهيز الطلبات غير المقيدة فقط (`status = 'pending'`)
- المنتجات المعروضة حسب صلاحيات المستخدم
- يتم عرض فقط المنتجات التي لديها كميات متوفرة (`quantity > 0`)

---

### 5.2. تجهيز وتقييد الطلب

**Endpoint:** `POST /api/mobile/admin/orders/{id}/process`

**Headers:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
```

**Path Parameters:**
- `id` (required): معرف الطلب

**Request Body:**
```json
{
  "customer_name": "أحمد محمد",
  "customer_phone": "07901234567",
  "customer_phone2": "07901234568",
  "customer_address": "بغداد - الكرادة",
  "customer_social_link": "@ahmadmohammad",
  "delivery_code": "D123",
  "notes": "يرجى التوصيل بعد الساعة 2 ظهراً"
}
```

**Validation Rules:**
- `customer_name`: required|string|max:255
- `customer_phone`: required|string|max:20
- `customer_phone2`: nullable|string|max:20
- `customer_address`: required|string
- `customer_social_link`: required|string|max:255
- `delivery_code`: required|string|max:100
- `notes`: nullable|string

**Response (Success):**
```json
{
  "success": true,
  "message": "تم تجهيز وتقييد الطلب بنجاح",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      "status": "confirmed",
      "confirmed_at": "2025-01-30T10:30:00Z",
      "confirmed_by": {
        "id": 2,
        "name": "مجهز 1"
      },
      ...
    }
  }
}
```

**Response (Error - Cannot Process):**
```json
{
  "success": false,
  "message": "لا يمكن تجهيز الطلبات المقيدة",
  "error_code": "CANNOT_PROCESS"
}
```

**ملاحظات:**
- يتم تغيير الحالة إلى `confirmed`
- يتم حفظ `delivery_fee_at_confirmation` و `profit_margin_at_confirmation` من الإعدادات
- يتم إرسال إشعار للمندوب تلقائياً
- يتم تسجيل ProductMovement (confirm) لكل منتج
- يتم تسجيل الربح عند التقييد (للمدير فقط)
- **ملاحظة مهمة**: المنتجات لا يتم خصمها من المخزن هنا (تم خصمها عند رفع المندوب للطلب)

---

## 6. المواد المطلوبة (Materials)

### 6.1. جلب قائمة المواد (غير مجمعة)

**Endpoint:** `GET /api/mobile/admin/orders/materials`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `status` (optional): فلتر الحالة (pending افتراضي، confirmed, deleted)
- نفس الفلاتر الأخرى من الطلبات غير المقيدة

**Response (Success):**
```json
{
  "success": true,
  "data": [
    {
      "product": {
        "id": 789,
        "name": "منتج 1",
        "code": "P001",
        "primary_image_url": "https://...",
        "warehouse": {
          "id": 1,
          "name": "مخزن الشمال"
        }
      },
      "size_name": "M",
      "total_quantity": 15,
      "orders": [
        {
          "order_number": "ORD-20250130-001",
          "quantity": 5,
          "order_id": 123
        },
        {
          "order_number": "ORD-20250130-002",
          "quantity": 10,
          "order_id": 124
        }
      ]
    },
    {
      "product": {
        "id": 789,
        "name": "منتج 1",
        "code": "P001",
        "primary_image_url": "https://...",
        "warehouse": {
          "id": 1,
          "name": "مخزن الشمال"
        }
      },
      "size_name": "L",
      "total_quantity": 8,
      "orders": [
        {
          "order_number": "ORD-20250130-003",
          "quantity": 8,
          "order_id": 125
        }
      ]
    }
  ]
}
```

**ملاحظات:**
- يتم تجميع المواد حسب `product_id` و `size_name`
- يتم عرض كل منتج + حجم مع الكمية الإجمالية والطلبات التي تحتوي عليه
- يتم فلترة المواد حسب المخزن والصلاحيات

---

### 6.2. جلب قائمة المواد (مجمعة حسب كود المنتج)

**Endpoint:** `GET /api/mobile/admin/orders/materials/grouped`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- نفس الفلاتر في قائمة المواد غير المجمعة

**Response (Success):**
```json
{
  "success": true,
  "data": [
    {
      "product": {
        "id": 789,
        "name": "منتج 1",
        "code": "P001",
        "primary_image_url": "https://...",
        "warehouse": {
          "id": 1,
          "name": "مخزن الشمال"
        }
      },
      "product_code": "P001",
      "size_name": "M",
      "total_quantity": 15,
      "orders": [
        {
          "order_number": "ORD-20250130-001",
          "quantity": 5,
          "order_id": 123
        },
        {
          "order_number": "ORD-20250130-002",
          "quantity": 10,
          "order_id": 124
        }
      ]
    },
    {
      "product": {
        "id": 789,
        "name": "منتج 1",
        "code": "P001",
        "primary_image_url": "https://...",
        "warehouse": {
          "id": 1,
          "name": "مخزن الشمال"
        }
      },
      "product_code": "P001",
      "size_name": "L",
      "total_quantity": 8,
      "orders": [
        {
          "order_number": "ORD-20250130-003",
          "quantity": 8,
          "order_id": 125
        }
      ]
    }
  ]
}
```

**ملاحظات:**
- يتم تجميع المواد حسب كود المنتج (`product_code`)
- المواد مرتبة حسب كود المنتج (أبجدي)
- الأحجام مرتبة داخل كل منتج (أبجدي)
- نفس البيانات لكن مع ترتيب أفضل للتجميع

---

## 7. خيارات الحالات (Status Options)

### 7.1. حالة التدقيق (size_reviewed)

حقل `size_reviewed` يحدد حالة تدقيق القياسات في الطلب.

**القيم الممكنة:**
- `not_reviewed` (افتراضي): لم يتم التدقيق
- `reviewed`: تم تدقيق القياس

**الاستخدام:**
- يمكن استخدامه كفلتر في جميع endpoints الخاصة بالطلبات
- مثال: `?size_reviewed=reviewed` لعرض فقط الطلبات التي تم تدقيق قياساتها

### 7.2. حالة تأكيد الرسالة (message_confirmed)

حقل `message_confirmed` يحدد حالة تأكيد الرسالة المرسلة للعميل.

**القيم الممكنة:**
- `not_sent` (افتراضي): لم يرسل الرسالة
- `waiting_response`: تم إرسال الرسالة وبالانتظار الرد
- `not_confirmed`: لم يتم تأكيد الرسالة
- `confirmed`: تم تأكيد الرسالة

**الاستخدام:**
- يمكن استخدامه كفلتر في جميع endpoints الخاصة بالطلبات
- مثال: `?message_confirmed=confirmed` لعرض فقط الطلبات التي تم تأكيد رسائلها

**مثال على الاستخدام:**
```javascript
// جلب الطلبات التي تم تدقيق قياساتها وتم تأكيد رسائلها
const response = await fetch(
  'https://api.example.com/api/mobile/admin/orders/pending?size_reviewed=reviewed&message_confirmed=confirmed',
  {
    headers: {
      'Authorization': `Bearer ${pwaToken}`,
    },
  }
);
```

---

## Error Codes

| Error Code | الوصف | HTTP Status |
|------------|-------|-------------|
| `FORBIDDEN` | المستخدم ليس مدير أو مجهز أو لا يملك صلاحية | 403 |
| `NOT_FOUND` | الطلب غير موجود أو ليس لديك صلاحية للوصول إليه | 404 |
| `CANNOT_EDIT` | لا يمكن تعديل الطلب (مر أكثر من 5 ساعات على التقييد) | 400 |
| `CANNOT_PROCESS` | لا يمكن تجهيز الطلبات المقيدة | 400 |
| `UPDATE_ERROR` | خطأ في تحديث الطلب (مثل: كمية غير كافية) | 500 |
| `PROCESS_ERROR` | خطأ في تجهيز الطلب | 500 |
| `FETCH_ERROR` | خطأ في جلب البيانات | 500 |

---

## أمثلة على الاستخدام

### مثال 1: جلب قائمة الطلبات غير المقيدة

```javascript
async function getPendingOrders(filters = {}, page = 1, perPage = 15) {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
  });
  
  if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id);
  if (filters.delegate_id) params.append('delegate_id', filters.delegate_id);
  if (filters.search) params.append('search', filters.search);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/pending?${params.toString()}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Accept': 'application/json',
      },
    }
  );
  
  const data = await response.json();
  return data;
}
```

### مثال 2: جلب تفاصيل طلب

```javascript
async function getOrderDetails(orderId) {
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/${orderId}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Accept': 'application/json',
      },
    }
  );
  
  const data = await response.json();
  return data;
}
```

### مثال 3: تعديل طلب

```javascript
async function updateOrder(orderId, orderData) {
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/${orderId}`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        delivery_code: orderData.delivery_code,
        customer_name: orderData.customer_name,
        customer_phone: orderData.customer_phone,
        customer_phone2: orderData.customer_phone2,
        customer_address: orderData.customer_address,
        customer_social_link: orderData.customer_social_link,
        notes: orderData.notes,
        items: orderData.items,
      }),
    }
  );
  
  const data = await response.json();
  return data;
}
```

### مثال 4: تجهيز وتقييد طلب

```javascript
async function processOrder(orderId, orderData) {
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/${orderId}/process`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        customer_name: orderData.customer_name,
        customer_phone: orderData.customer_phone,
        customer_phone2: orderData.customer_phone2,
        customer_address: orderData.customer_address,
        customer_social_link: orderData.customer_social_link,
        delivery_code: orderData.delivery_code,
        notes: orderData.notes,
      }),
    }
  );
  
  const data = await response.json();
  return data;
}
```

### مثال 5: جلب قائمة المواد

```javascript
async function getMaterialsList(status = 'pending', filters = {}) {
  const params = new URLSearchParams({
    status: status,
  });
  
  if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id);
  if (filters.delegate_id) params.append('delegate_id', filters.delegate_id);
  if (filters.search) params.append('search', filters.search);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/materials?${params.toString()}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Accept': 'application/json',
      },
    }
  );
  
  const data = await response.json();
  return data;
}
```

---

## ملاحظات مهمة

### 1. الصلاحيات

- **المدير (`admin`)**: يمكنه الوصول إلى جميع الطلبات من جميع المخازن
- **المجهز (`supplier`)**: يمكنه الوصول فقط للطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
- **المورد الخاص (`private_supplier`)**: نفس المجهز

### 2. التعديل

- **الطلبات غير المقيدة (`pending`)**: يمكن التعديل الكامل (المنتجات + معلومات العميل)
- **الطلبات المقيدة (`confirmed`)**: يمكن التعديل فقط خلال 5 ساعات من التقييد
- **الطلبات المقيدة (أكثر من 5 ساعات)**: لا يمكن التعديل

### 3. التجهيز

- يمكن تجهيز الطلبات غير المقيدة فقط (`status = 'pending'`)
- عند التجهيز: يتم تغيير الحالة إلى `confirmed` وحفظ معلومات التقييد
- **ملاحظة مهمة**: المنتجات لا يتم خصمها من المخزن عند التجهيز (تم خصمها عند رفع المندوب للطلب)

### 4. حركات المخزن

- عند تعديل الطلب: يتم معالجة حركات المخزن تلقائياً (إرجاع/خصم)
- يتم تسجيل ProductMovement لكل تغيير
- يتم التحقق من توفر الكميات قبل الخصم

### 5. الإشعارات

- عند تجهيز الطلب: يتم إرسال إشعار للمندوب تلقائياً
- عند فتح تفاصيل الطلب: يتم حذف إشعارات الطلب تلقائياً

### 6. الإحصائيات

- الإحصائيات (عدد الطلبات، المبلغ الإجمالي، الربح) موجودة فقط للمدير
- للمجهز: `statistics` يكون `null`

### 7. الفلاتر

- للطلبات غير المقيدة: الفلتر يعمل على `created_at`
- للطلبات المقيدة: الفلتر يعمل على `confirmed_at`
- جميع الفلاتر اختيارية ويمكن دمجها

---

## ملخص Endpoints

| Method | Endpoint | الوصف |
|--------|----------|-------|
| `GET` | `/api/mobile/admin/orders/pending` | قائمة الطلبات غير المقيدة |
| `GET` | `/api/mobile/admin/orders/confirmed` | قائمة الطلبات المقيدة |
| `GET` | `/api/mobile/admin/orders` | قائمة موحدة (management) |
| `GET` | `/api/mobile/admin/orders/{id}` | تفاصيل الطلب |
| `GET` | `/api/mobile/admin/orders/{id}/edit` | بيانات التعديل |
| `PUT` | `/api/mobile/admin/orders/{id}` | تعديل الطلب |
| `GET` | `/api/mobile/admin/orders/{id}/process` | بيانات التجهيز |
| `POST` | `/api/mobile/admin/orders/{id}/process` | تجهيز وتقييد الطلب |
| `GET` | `/api/mobile/admin/orders/materials` | قائمة المواد (غير مجمعة) |
| `GET` | `/api/mobile/admin/orders/materials/grouped` | قائمة المواد (مجمعة) |

---

## الدعم

للمزيد من المعلومات أو المساعدة، يرجى التواصل مع فريق التطوير.

