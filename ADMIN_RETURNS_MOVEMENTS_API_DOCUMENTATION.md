# Admin & Supplier Returns & Movements Mobile API Documentation

## نظرة عامة

هذا التوثيق يشرح APIs حركات الطلبات والإرجاعات للمدير والمجهز في تطبيق الموبايل. يتضمن حركات الطلبات، الإرجاعات الجزئية، والإرجاعات/الاستبدالات الجماعية.

**Base URL:** `https://your-domain.com/api/mobile/admin`

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

## 1. حركات الطلبات (Order Movements)

### 1.1. جلب قائمة حركات الطلبات

**Endpoint:** `GET /api/mobile/admin/order-movements`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `warehouse_id` (optional): فلتر حسب المخزن
- `product_id` (optional): فلتر حسب المنتج
- `size_id` (optional): فلتر حسب القياس
- `movement_type` (optional): فلتر حسب نوع الحركة (add, sale, confirm, cancel, return, delete, restore, return_bulk, return_exchange_bulk, partial_return)
- `user_id` (optional): فلتر حسب المستخدم
- `order_status` (optional): فلتر حسب حالة الطلب (pending, confirmed, cancelled, returned, exchanged)
- `date_from` (optional): تاريخ البداية (Y-m-d)
- `date_to` (optional): تاريخ النهاية (Y-m-d)
- `time_from` (optional): وقت البداية (H:i)
- `time_to` (optional): وقت النهاية (H:i)
- `group_by_order` (optional): تجميع حسب الطلب (1 = نعم، 0 أو بدون = لا)
- `page` (optional): رقم الصفحة (افتراضي: 1)
- `per_page` (optional): عدد الحركات في الصفحة (افتراضي: 20، حد أقصى: 100)

**Response (Success):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "product": {
        "id": 456,
        "name": "منتج 1",
        "code": "P001",
        "primary_image_url": "https://..."
      },
      "size": {
        "id": 789,
        "size_name": "M"
      },
      "warehouse": {
        "id": 1,
        "name": "مخزن الشمال"
      },
      "order": {
        "id": 10,
        "order_number": "ORD-20250130-001",
        "customer_name": "أحمد محمد",
        "status": "confirmed"
      },
      "user": {
        "id": 2,
        "name": "مجهز 1",
        "code": "SUP001"
      },
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "movement_type": "sale",
      "movement_type_text": "بيع",
      "quantity": -2,
      "balance_after": 15,
      "order_status": "confirmed",
      "order_status_text": "مقيد",
      "notes": "بيع من طلب #ORD-20250130-001",
      "created_at": "2025-01-30T10:00:00Z"
    }
  ],
  "grouped_by_order": null,
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "has_more": true
  }
}
```

**Response (Success - مع group_by_order=1):**
```json
{
  "success": true,
  "data": [...],
  "grouped_by_order": [
    {
      "order_id": 10,
      "order": {
        "id": 10,
        "order_number": "ORD-20250130-001",
        "customer_name": "أحمد محمد",
        "status": "confirmed"
      },
      "order_status": "confirmed",
      "order_status_text": "مقيد",
      "user": {
        "id": 2,
        "name": "مجهز 1",
        "code": "SUP001"
      },
      "created_at": "2025-01-30T10:00:00Z",
      "total_quantity": -5,
      "movements_count": 3,
      "movements": [
        {
          "id": 123,
          "product": {
            "id": 456,
            "name": "منتج 1",
            "code": "P001"
          },
          "size": {
            "id": 789,
            "size_name": "M"
          },
          "movement_type": "sale",
          "movement_type_text": "بيع",
          "quantity": -2,
          "created_at": "2025-01-30T10:00:00Z"
        }
      ]
    }
  ],
  "pagination": {...}
}
```

**ملاحظات:**
- `quantity` سالب للبيع/الخصم، موجب للإضافة/الإرجاع
- `grouped_by_order` موجود فقط إذا كان `group_by_order=1`
- للمجهز: فقط حركات المخازن المسموح له بها

---

### 1.2. جلب إحصائيات حركات الطلبات

**Endpoint:** `GET /api/mobile/admin/order-movements/statistics`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- نفس الفلاتر في `getOrderMovements` (warehouse_id, product_id, size_id, movement_type, user_id, order_status, date_from, date_to, time_from, time_to)

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_movements": 150,
      "total_additions": 500,
      "total_sales": 300,
      "total_confirms": 250,
      "total_returns": 100,
      "total_cancels": 20,
      "total_deletes": 10
    },
    "by_movement_type": {
      "add": {
        "count": 25,
        "total_quantity": 500
      },
      "sale": {
        "count": 50,
        "total_quantity": 300
      },
      "confirm": {
        "count": 30,
        "total_quantity": 250
      },
      "return": {
        "count": 15,
        "total_quantity": 50
      },
      "cancel": {
        "count": 5,
        "total_quantity": 20
      },
      "delete": {
        "count": 3,
        "total_quantity": 10
      },
      "restore": {
        "count": 0,
        "total_quantity": 0
      },
      "return_bulk": {
        "count": 10,
        "total_quantity": 30
      },
      "return_exchange_bulk": {
        "count": 8,
        "total_quantity": 20
      },
      "partial_return": {
        "count": 12,
        "total_quantity": 40
      }
    },
    "by_order_status": {
      "pending": {
        "count": 40,
        "total_quantity": 200
      },
      "confirmed": {
        "count": 80,
        "total_quantity": 400
      },
      "cancelled": {
        "count": 10,
        "total_quantity": 50
      },
      "returned": {
        "count": 15,
        "total_quantity": 75
      },
      "exchanged": {
        "count": 5,
        "total_quantity": 25
      }
    }
  }
}
```

**ملاحظات:**
- الإحصائيات تعمل على نفس الفلاتر المطبقة في `getOrderMovements`
- `total_quantity` دائماً موجب (يتم استخدام `abs()`)

---

## 2. الإرجاعات الجزئية (Partial Returns)

### 2.1. جلب قائمة الطلبات المقيدة للإرجاع الجزئي

**Endpoint:** `GET /api/mobile/admin/orders/partial-returns`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `delegate_id` (optional): فلتر حسب المندوب
- `confirmed_by` (optional): فلتر حسب المجهز الذي قيد الطلب (user_id)
- `search` (optional): بحث مطابقة تامة في (order_number, customer_name, customer_phone, customer_social_link, customer_address, delivery_code, delegate name/code, confirmedBy name)
- `date_from` (optional): تاريخ البداية (Y-m-d)
- `date_to` (optional): تاريخ النهاية (Y-m-d)
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
      "delivery_code": "D123",
      "status": "confirmed",
      "total_amount": 150000.00,
      "delegate": {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      "confirmed_by": {
        "id": 2,
        "name": "مجهز 1"
      },
      "created_at": "2025-01-30T10:00:00Z",
      "items_count": 3,
      "items_with_remaining": 2
    }
  ],
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
- يعرض فقط الطلبات المقيدة (`status = 'confirmed'`) التي تحتوي على منتجات قابلة للإرجاع (`quantity > 0`)
- `items_with_remaining`: عدد المنتجات التي لديها كمية متبقية للإرجاع
- البحث مطابقة تامة (exact match) وليس LIKE

---

### 2.2. جلب تفاصيل طلب للإرجاع الجزئي

**Endpoint:** `GET /api/mobile/admin/orders/{id}/partial-return`

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
      "notes": "ملاحظات",
      "status": "confirmed",
      "total_amount": 150000.00,
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
      "created_at": "2025-01-30T10:00:00Z",
      "confirmed_at": "2025-01-30T10:30:00Z",
      "items": [
        {
          "id": 456,
          "product": {
            "id": 789,
            "name": "منتج 1",
            "code": "P001",
            "primary_image_url": "https://..."
          },
          "size": {
            "id": 12,
            "size_name": "M"
          },
          "size_id": 12,
          "size_name": "M",
          "quantity": 2,
          "original_quantity": 5,
          "remaining_quantity": 2,
          "returned_quantity": 3,
          "unit_price": 75000.00,
          "subtotal": 150000.00,
          "return_items": [
            {
              "id": 100,
              "quantity_returned": 2,
              "return_reason": "إرجاع جزئي",
              "created_at": "2025-01-30T11:00:00Z"
            },
            {
              "id": 101,
              "quantity_returned": 1,
              "return_reason": "إرجاع جزئي",
              "created_at": "2025-01-30T12:00:00Z"
            }
          ]
        }
      ]
    }
  }
}
```

**Response (Error - Invalid Status):**
```json
{
  "success": false,
  "message": "لا يمكن إرجاع منتجات من طلب غير مقيد",
  "error_code": "INVALID_STATUS"
}
```

**ملاحظات:**
- `original_quantity`: الكمية الأصلية عند إنشاء الطلب
- `remaining_quantity`: الكمية المتبقية بعد الإرجاعات
- `returned_quantity`: مجموع الكميات المرجعة
- `return_items`: قائمة الإرجاعات السابقة لهذا العنصر

---

### 2.3. معالجة الإرجاع الجزئي

**Endpoint:** `POST /api/mobile/admin/orders/{id}/partial-return`

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
  "return_items": [
    {
      "order_item_id": 456,
      "product_id": 789,
      "size_id": 12,
      "quantity": 2
    },
    {
      "order_item_id": 457,
      "product_id": 790,
      "size_id": 15,
      "quantity": 1
    }
  ],
  "notes": "إرجاع جزئي - منتجات معيبة"
}
```

**Validation Rules:**
- `return_items`: required|array|min:1
- `return_items.*.order_item_id`: required|exists:order_items,id
- `return_items.*.product_id`: required|exists:products,id
- `return_items.*.size_id`: nullable
- `return_items.*.quantity`: required|integer|min:1
- `notes`: nullable|string|max:1000

**Response (Success):**
```json
{
  "success": true,
  "message": "تم إرجاع المنتجات بنجاح",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      "status": "confirmed",
      "total_amount": 100000.00,
      ...
    },
    "total_amount_reduction": 50000.00,
    "all_items_returned": false
  }
}
```

**Response (Success - All Items Returned):**
```json
{
  "success": true,
  "message": "تم إرجاع جميع المنتجات بنجاح وتم حذف الطلب تلقائياً",
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-20250130-001",
      "status": "confirmed",
      "deleted_at": "2025-01-30T12:00:00Z",
      ...
    },
    "total_amount_reduction": 150000.00,
    "all_items_returned": true
  }
}
```

**Response (Error - Insufficient Quantity):**
```json
{
  "success": false,
  "message": "الكمية المراد إرجاعها (3) أكبر من الكمية المتبقية (2) للمنتج: منتج 1",
  "error_code": "PROCESS_ERROR"
}
```

**ملاحظات:**
- يتم التحقق من الكمية المتبقية قبل الإرجاع
- يتم إرجاع الكمية للمخزن تلقائياً
- يتم تسجيل ProductMovement بنوع `partial_return`
- يتم تسجيل ReturnItem لكل عنصر مرجع
- يتم تحديث `total_amount` تلقائياً
- إذا تم إرجاع جميع المنتجات، يتم حذف الطلب تلقائياً (soft delete)
- يتم معالجة تأثير الإرجاع على المستثمرين (إذا كان مفعلاً)

---

## 3. الإرجاعات الجماعية (Bulk Returns)

### 3.1. جلب قوائم الفلاتر

**Endpoint:** `GET /api/mobile/admin/bulk-returns/filter-options`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "warehouses": [
      {
        "id": 1,
        "name": "مخزن الشمال"
      },
      {
        "id": 2,
        "name": "مخزن الجنوب"
      }
    ],
    "delegates": [
      {
        "id": 5,
        "name": "مندوب 1",
        "code": "DEL001"
      },
      {
        "id": 6,
        "name": "مندوب 2",
        "code": "DEL002"
      }
    ]
  }
}
```

**ملاحظات:**
- للمجهز: فقط المخازن المسموح له بها
- للمدير: جميع المخازن

---

### 3.2. البحث عن المنتجات

**Endpoint:** `GET /api/mobile/admin/bulk-returns/search-products`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `warehouse_id` (optional): فلتر حسب المخزن
- `search` (optional): بحث بالاسم أو الكود
- `limit` (optional): عدد النتائج (افتراضي: 10، حد أقصى: 50)

**Response (Success):**
```json
{
  "success": true,
  "data": [
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
```

**ملاحظات:**
- للمجهز: فقط منتجات المخازن المسموح له بها
- البحث يعمل على `name` و `code` (LIKE search)

---

### 3.3. إرجاع المنتجات بشكل جماعي

**Endpoint:** `POST /api/mobile/admin/bulk-returns`

**Headers:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "delegate_id": 5,
  "warehouse_id": 1,
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
- `delegate_id`: required|exists:users,id
- `warehouse_id`: required|exists:warehouses,id
- `items`: required|array|min:1
- `items.*.product_id`: required|exists:products,id
- `items.*.size_id`: required|exists:product_sizes,id
- `items.*.quantity`: required|integer|min:1

**Response (Success):**
```json
{
  "success": true,
  "message": "تم إرجاع المواد بنجاح",
  "data": {
    "delegate": {
      "id": 5,
      "name": "مندوب 1",
      "code": "DEL001"
    },
    "warehouse": {
      "id": 1,
      "name": "مخزن الشمال"
    },
    "items": [
      {
        "product_id": 789,
        "product_name": "منتج 1",
        "size_id": 12,
        "size_name": "M",
        "quantity": 3,
        "old_quantity": 12,
        "new_quantity": 15
      },
      {
        "product_id": 790,
        "product_name": "منتج 2",
        "size_id": 15,
        "size_name": "L",
        "quantity": 2,
        "old_quantity": 18,
        "new_quantity": 20
      }
    ],
    "total_items": 2
  }
}
```

**Response (Error - Invalid Warehouse):**
```json
{
  "success": false,
  "message": "المنتج لا يخص المخزن المحدد",
  "error_code": "PROCESS_ERROR"
}
```

**ملاحظات:**
- يتم إضافة الكمية للمخزن تلقائياً
- يتم تحديث `warehouse_id` للمنتج
- يتم تسجيل ProductMovement بنوع `return_bulk`
- يتم التحقق من أن المنتج يخص المخزن المحدد
- يتم التحقق من أن القياس يخص المنتج

---

## 4. الإرجاعات/الاستبدالات الجماعية (Bulk Exchange Returns)

### 4.1. جلب قوائم الفلاتر

**Endpoint:** `GET /api/mobile/admin/bulk-exchange-returns/filter-options`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Response:** نفس `bulk-returns/filter-options`

---

### 4.2. البحث عن المنتجات

**Endpoint:** `GET /api/mobile/admin/bulk-exchange-returns/search-products`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:** نفس `bulk-returns/search-products`

**Response:** نفس `bulk-returns/search-products`

---

### 4.3. إرجاع/استبدال المنتجات بشكل جماعي

**Endpoint:** `POST /api/mobile/admin/bulk-exchange-returns`

**Headers:**
```
Authorization: Bearer {pwa_token}
Content-Type: application/json
```

**Request Body:** نفس `bulk-returns`

**Response:** نفس `bulk-returns` لكن مع `movement_type: 'return_exchange_bulk'`

**ملاحظات:**
- نفس الوظيفة تماماً مثل `bulk-returns`
- الفرق الوحيد: `movement_type` = `return_exchange_bulk` بدلاً من `return_bulk`
- يستخدم لنفس الغرض لكن مع تمييز في نوع الحركة

---

## 5. أنواع الحركات (Movement Types)

| النوع | الوصف | الكمية |
|------|-------|--------|
| `add` | إضافة | موجب |
| `sale` | بيع | سالب |
| `confirm` | تقييد | سالب |
| `cancel` | إلغاء | موجب |
| `return` | استرجاع | موجب |
| `delete` | حذف | موجب |
| `restore` | استرجاع من الحذف | موجب |
| `return_bulk` | إرجاع جماعي | موجب |
| `return_exchange_bulk` | إرجاع/استبدال جماعي | موجب |
| `partial_return` | إرجاع جزئي | موجب |

**ملاحظات:**
- الكميات السالبة: خصم من المخزن (بيع، تقييد)
- الكميات الموجبة: إضافة للمخزن (إرجاع، إلغاء، إضافة)

---

## 6. حالات الطلبات (Order Statuses)

| الحالة | الوصف |
|--------|-------|
| `pending` | غير مقيد |
| `confirmed` | مقيد |
| `cancelled` | ملغي |
| `returned` | مسترجعة |
| `exchanged` | مستبدلة |

---

## Error Codes

| Error Code | الوصف | HTTP Status |
|------------|-------|-------------|
| `FORBIDDEN` | المستخدم ليس مدير أو مجهز أو لا يملك صلاحية | 403 |
| `NOT_FOUND` | الطلب/المنتج/القياس غير موجود | 404 |
| `INVALID_STATUS` | حالة الطلب غير صالحة للعملية | 400 |
| `VALIDATION_ERROR` | خطأ في البيانات المرسلة | 422 |
| `PROCESS_ERROR` | خطأ في معالجة العملية | 500 |
| `FETCH_ERROR` | خطأ في جلب البيانات | 500 |

---

## أمثلة على الاستخدام

### مثال 1: جلب حركات الطلبات

```javascript
async function getOrderMovements(filters = {}, page = 1, perPage = 20) {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
  });
  
  if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id);
  if (filters.movement_type) params.append('movement_type', filters.movement_type);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  if (filters.group_by_order) params.append('group_by_order', '1');
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/order-movements?${params.toString()}`,
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

### مثال 2: جلب إحصائيات حركات الطلبات

```javascript
async function getOrderMovementsStatistics(filters = {}) {
  const params = new URLSearchParams();
  
  if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/order-movements/statistics?${params.toString()}`,
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

### مثال 3: جلب قائمة الإرجاعات الجزئية

```javascript
async function getPartialReturns(filters = {}, page = 1, perPage = 15) {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
  });
  
  if (filters.delegate_id) params.append('delegate_id', filters.delegate_id);
  if (filters.search) params.append('search', filters.search);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/partial-returns?${params.toString()}`,
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

### مثال 4: جلب تفاصيل طلب للإرجاع الجزئي

```javascript
async function getPartialReturnOrder(orderId) {
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/${orderId}/partial-return`,
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

### مثال 5: معالجة الإرجاع الجزئي

```javascript
async function processPartialReturn(orderId, returnData) {
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/orders/${orderId}/partial-return`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        return_items: returnData.return_items,
        notes: returnData.notes,
      }),
    }
  );
  
  const data = await response.json();
  return data;
}
```

### مثال 6: البحث عن منتجات للإرجاع الجماعي

```javascript
async function searchProductsForBulkReturn(warehouseId, searchTerm) {
  const params = new URLSearchParams({
    limit: '10',
  });
  
  if (warehouseId) params.append('warehouse_id', warehouseId);
  if (searchTerm) params.append('search', searchTerm);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/bulk-returns/search-products?${params.toString()}`,
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

### مثال 7: إرجاع منتجات بشكل جماعي

```javascript
async function returnProductsBulk(returnData) {
  const response = await fetch(
    'https://api.example.com/api/mobile/admin/bulk-returns',
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        delegate_id: returnData.delegate_id,
        warehouse_id: returnData.warehouse_id,
        items: returnData.items,
      }),
    }
  );
  
  const data = await response.json();
  return data;
}
```

### مثال 8: إرجاع/استبدال منتجات بشكل جماعي

```javascript
async function returnExchangeProductsBulk(returnData) {
  const response = await fetch(
    'https://api.example.com/api/mobile/admin/bulk-exchange-returns',
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${pwaToken}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        delegate_id: returnData.delegate_id,
        warehouse_id: returnData.warehouse_id,
        items: returnData.items,
      }),
    }
  );
  
  const data = await response.json();
  return data;
}
```

---

## ملاحظات مهمة

### 1. الصلاحيات

- **المدير (`admin`)**: يمكنه الوصول إلى جميع الحركات والإرجاعات من جميع المخازن
- **المجهز (`supplier`)**: يمكنه الوصول فقط للحركات والإرجاعات التي تتعلق بمخازن له صلاحية الوصول إليها
- **المورد الخاص (`private_supplier`)**: نفس المجهز

### 2. الإرجاع الجزئي

- يمكن إرجاع منتجات من الطلبات المقيدة فقط (`status = 'confirmed'`)
- يتم التحقق من الكمية المتبقية قبل الإرجاع
- إذا تم إرجاع جميع المنتجات، يتم حذف الطلب تلقائياً (soft delete)
- يتم تحديث `total_amount` تلقائياً
- يتم معالجة تأثير الإرجاع على المستثمرين (إذا كان مفعلاً)

### 3. الإرجاعات الجماعية

- لا تحتاج إلى طلب محدد
- يتم إرجاع المنتجات مباشرة للمخزن
- يتم تحديث `warehouse_id` للمنتج
- يتم تسجيل ProductMovement لكل منتج

### 4. حركات المخزن

- جميع العمليات تسجل ProductMovement تلقائياً
- يتم تحديث `balance_after` تلقائياً
- يتم ربط الحركة بالطلب (إذا كان موجوداً) والمستخدم والمندوب

### 5. البحث

- **في حركات الطلبات**: البحث يعمل على جميع الحقول (LIKE search)
- **في الإرجاعات الجزئية**: البحث مطابقة تامة (exact match) لضمان الدقة
- **في الإرجاعات الجماعية**: البحث يعمل على اسم/كود المنتج (LIKE search)

### 6. التجميع

- في حركات الطلبات: يمكن تجميع الحركات حسب `order_id` باستخدام `group_by_order=1`
- التجميع يعرض معلومات الطلب والحركات المرتبطة به

---

## ملخص Endpoints

| Method | Endpoint | الوصف |
|--------|----------|-------|
| `GET` | `/api/mobile/admin/order-movements` | قائمة حركات الطلبات |
| `GET` | `/api/mobile/admin/order-movements/statistics` | إحصائيات حركات الطلبات |
| `GET` | `/api/mobile/admin/orders/partial-returns` | قائمة الطلبات للإرجاع الجزئي |
| `GET` | `/api/mobile/admin/orders/{id}/partial-return` | تفاصيل طلب للإرجاع الجزئي |
| `POST` | `/api/mobile/admin/orders/{id}/partial-return` | معالجة الإرجاع الجزئي |
| `GET` | `/api/mobile/admin/bulk-returns/filter-options` | قوائم الفلاتر (إرجاع جماعي) |
| `GET` | `/api/mobile/admin/bulk-returns/search-products` | البحث عن المنتجات (إرجاع جماعي) |
| `POST` | `/api/mobile/admin/bulk-returns` | إرجاع منتجات بشكل جماعي |
| `GET` | `/api/mobile/admin/bulk-exchange-returns/filter-options` | قوائم الفلاتر (إرجاع/استبدال جماعي) |
| `GET` | `/api/mobile/admin/bulk-exchange-returns/search-products` | البحث عن المنتجات (إرجاع/استبدال جماعي) |
| `POST` | `/api/mobile/admin/bulk-exchange-returns` | إرجاع/استبدال منتجات بشكل جماعي |

---

## الدعم

للمزيد من المعلومات أو المساعدة، يرجى التواصل مع فريق التطوير.

