# Admin & Supplier AlWaseet Track Orders API Documentation

## نظرة عامة

هذا التوثيق يشرح APIs تتبع طلبات الوسيط (AlWaseet) للمدير والمجهز في تطبيق الموبايل.

**Base URL:** `https://your-domain.com/api/mobile/admin/alwaseet`

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

## Endpoints

### 1. جلب بطاقات الحالات (Status Cards)

جلب إحصائيات حالات طلبات الوسيط (عدد الطلبات والمبالغ لكل حالة).

**Endpoint:** `GET /api/mobile/admin/alwaseet/status-cards`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `warehouse_id` (optional): فلتر حسب المخزن
- `confirmed_by` (optional): فلتر حسب المجهز الذي قيد الطلب (user_id)
- `delegate_id` (optional): فلتر حسب المندوب
- `date_from` (optional): تاريخ البداية (Y-m-d)
- `date_to` (optional): تاريخ النهاية (Y-m-d)
- `time_from` (optional): وقت البداية (H:i)
- `time_to` (optional): وقت النهاية (H:i)
- `hours_ago` (optional): آخر X ساعة (2, 4, 6, 8... حتى 30)

**ملاحظات مهمة:**
- **للمدير**: يعرض جميع الطلبات مع المبالغ الكلية لكل حالة
- **للمجهز**: يعرض فقط الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها (بدون المبالغ)
- **للمورد الخاص**: يعرض فقط الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها (بدون المبالغ)

**Response (Success - للمدير):**
```json
{
  "success": true,
  "data": {
    "status_cards": [
      {
        "status_id": "1",
        "status_text": "جديد",
        "count": 25,
        "total_amount": 3500000.00,
        "color": "info"
      },
      {
        "status_id": "2",
        "status_text": "قيد المعالجة",
        "count": 12,
        "total_amount": 1800000.00,
        "color": "primary"
      },
      {
        "status_id": "3",
        "status_text": "جاهز للتسليم",
        "count": 8,
        "total_amount": 1200000.00,
        "color": "warning"
      },
      {
        "status_id": "4",
        "status_text": "تم التسليم",
        "count": 65,
        "total_amount": 9750000.00,
        "color": "success"
      },
      {
        "status_id": "5",
        "status_text": "ملغي",
        "count": 3,
        "total_amount": 450000.00,
        "color": "danger"
      }
    ],
    "total_orders": 113,
    "total_amount": 16700000.00
  }
}
```

**Response (Success - للمجهز/المورد):**
```json
{
  "success": true,
  "data": {
    "status_cards": [
      {
        "status_id": "1",
        "status_text": "جديد",
        "count": 15,
        "total_amount": null,
        "color": "info"
      },
      {
        "status_id": "2",
        "status_text": "قيد المعالجة",
        "count": 8,
        "total_amount": null,
        "color": "primary"
      },
      {
        "status_id": "4",
        "status_text": "تم التسليم",
        "count": 45,
        "total_amount": null,
        "color": "success"
      }
    ],
    "total_orders": 68,
    "total_amount": null
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "غير مصرح. يجب أن تكون مديراً أو مجهزاً للوصول إلى هذه البيانات.",
  "error_code": "FORBIDDEN"
}
```

**ملاحظات:**
- يتم عرض الحالات النشطة فقط (من `AlWaseetOrderStatus`)
- الحالات مرتبة حسب `display_order`
- `color` يمكن استخدامه في واجهة المستخدم (info, primary, success, warning, danger, secondary)
- يتم استخدام Cache (2 دقيقة للفلاتر، 10 دقائق بدون فلاتر)
- `total_amount` يكون `null` للمجهز والمورد الخاص (فقط للمدير)

---

### 2. جلب قائمة الطلبات

جلب قائمة طلبات الوسيط للمدير والمجهز مع فلترة وpagination.

**Endpoint:** `GET /api/mobile/admin/alwaseet/orders`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Query Parameters:**
- `status_id` (optional): فلتر حسب حالة الوسيط
- `search` (optional): البحث في (order_number, customer_name, customer_phone, customer_address, delivery_code, delegate name, product name/code, alwaseet_order_id)
- `warehouse_id` (optional): فلتر حسب المخزن
- `confirmed_by` (optional): فلتر حسب المجهز الذي قيد الطلب (user_id)
- `delegate_id` (optional): فلتر حسب المندوب
- `date_from`, `date_to` (optional): فلتر التاريخ
- `time_from`, `time_to` (optional): فلتر الوقت
- `hours_ago` (optional): آخر X ساعة
- `page` (optional): رقم الصفحة (افتراضي: 1)
- `per_page` (optional): عدد الطلبات في الصفحة (افتراضي: 20، حد أقصى: 50)

**ملاحظات مهمة:**
- **للمدير**: يعرض جميع الطلبات
- **للمجهز**: يعرض فقط الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
- **للمورد الخاص**: يعرض فقط الطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها

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
      "alwaseet_shipment": {
        "id": 45,
        "alwaseet_order_id": "12345",
        "status_id": "2",
        "status_text": "قيد المعالجة",
        "city_name": "بغداد",
        "region_name": "الكرادة",
        "delivery_price": 5000.00,
        "qr_link": "https://...",
        "synced_at": "2025-01-30T10:05:00Z"
      },
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
          "size_name": "M",
          "quantity": 2,
          "unit_price": 75000.00
        }
      ]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 60,
    "last_page": 3,
    "has_more": true
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "غير مصرح. يجب أن تكون مديراً أو مجهزاً للوصول إلى هذه البيانات.",
  "error_code": "FORBIDDEN"
}
```

**ملاحظات:**
- الطلبات مرتبة حسب تاريخ الإنشاء (الأحدث أولاً)
- يتم عرض جميع الطلبات (ليس فقط التي لديها `alwaseetShipment`)
- البحث يشمل اسم المندوب واسم/كود المنتج

---

### 3. جلب تفاصيل طلب واحد مع Timeline

جلب تفاصيل كاملة لطلب واحد مع Timeline الحالات.

**Endpoint:** `GET /api/mobile/admin/alwaseet/orders/{id}`

**Headers:**
```
Authorization: Bearer {pwa_token}
```

**Path Parameters:**
- `id` (required): معرف الطلب

**ملاحظات مهمة:**
- **للمدير**: يمكن الوصول إلى أي طلب
- **للمجهز**: يمكن الوصول فقط للطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها
- **للمورد الخاص**: يمكن الوصول فقط للطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها

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
          "size_name": "M",
          "quantity": 2,
          "unit_price": 75000.00
        }
      ],
      "alwaseet_shipment": {
        "id": 45,
        "alwaseet_order_id": "12345",
        "status_id": "2",
        "status_text": "قيد المعالجة",
        "city_name": "بغداد",
        "region_name": "الكرادة",
        "location": "تفاصيل العنوان الدقيق",
        "price": 155000.00,
        "delivery_price": 5000.00,
        "merchant_notes": "ملاحظات التاجر",
        "issue_notes": null,
        "qr_link": "https://...",
        "alwaseet_created_at": "2025-01-30T10:05:00Z",
        "synced_at": "2025-01-30T10:05:00Z",
        "status_timeline": [
          {
            "status_id": "1",
            "status_text": "جديد",
            "changed_at": "2025-01-30T10:05:00Z",
            "is_current": false,
            "display_order": 1
          },
          {
            "status_id": "2",
            "status_text": "قيد المعالجة",
            "changed_at": "2025-01-30T11:00:00Z",
            "is_current": true,
            "display_order": 2
          }
        ]
      }
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

**Response (Error - Forbidden):**
```json
{
  "success": false,
  "message": "غير مصرح. يجب أن تكون مديراً أو مجهزاً للوصول إلى هذه البيانات.",
  "error_code": "FORBIDDEN"
}
```

**ملاحظات:**
- Timeline مرتب حسب `changed_at ASC` (من الأقدم للأحدث)
- `is_current` يشير إلى الحالة الحالية
- `display_order` يمكن استخدامه لترتيب الحالات في واجهة المستخدم
- إذا لم يكن للطلب `alwaseet_shipment`، سيكون `alwaseet_shipment: null`

---

## أمثلة على الاستخدام

### مثال 1: جلب بطاقات الحالات بدون فلاتر

```javascript
async function getStatusCards() {
  const response = await fetch(
    'https://api.example.com/api/mobile/admin/alwaseet/status-cards',
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

### مثال 2: جلب بطاقات الحالات مع فلاتر

```javascript
async function getStatusCardsWithFilters(filters) {
  const params = new URLSearchParams();
  if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id);
  if (filters.confirmed_by) params.append('confirmed_by', filters.confirmed_by);
  if (filters.delegate_id) params.append('delegate_id', filters.delegate_id);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  if (filters.hours_ago) params.append('hours_ago', filters.hours_ago);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/alwaseet/status-cards?${params.toString()}`,
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

### مثال 3: جلب قائمة الطلبات مع فلترة وبحث

```javascript
async function getAlWaseetOrders(filters = {}, page = 1, perPage = 20) {
  const params = new URLSearchParams({
    page: page.toString(),
    per_page: perPage.toString(),
  });
  
  if (filters.status_id) params.append('status_id', filters.status_id);
  if (filters.search) params.append('search', filters.search);
  if (filters.warehouse_id) params.append('warehouse_id', filters.warehouse_id);
  if (filters.confirmed_by) params.append('confirmed_by', filters.confirmed_by);
  if (filters.delegate_id) params.append('delegate_id', filters.delegate_id);
  if (filters.date_from) params.append('date_from', filters.date_from);
  if (filters.date_to) params.append('date_to', filters.date_to);
  if (filters.hours_ago) params.append('hours_ago', filters.hours_ago);
  
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/alwaseet/orders?${params.toString()}`,
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

### مثال 4: جلب تفاصيل طلب مع Timeline

```javascript
async function getAlWaseetOrderDetails(orderId) {
  const response = await fetch(
    `https://api.example.com/api/mobile/admin/alwaseet/orders/${orderId}`,
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

### مثال 5: عرض Dashboard كامل

```javascript
async function loadAlWaseetDashboard(filters = {}) {
  try {
    // 1. جلب بطاقات الحالات
    const statusCardsResponse = await getStatusCardsWithFilters(filters);
    if (statusCardsResponse.success) {
      const statusCards = statusCardsResponse.data.status_cards;
      const totalOrders = statusCardsResponse.data.total_orders;
      const totalAmount = statusCardsResponse.data.total_amount;
      
      console.log('Status Cards:', statusCards);
      console.log('Total Orders:', totalOrders);
      console.log('Total Amount:', totalAmount);
      
      // عرض البطاقات في واجهة المستخدم
      displayStatusCards(statusCards);
    }
    
    // 2. جلب قائمة الطلبات
    const ordersResponse = await getAlWaseetOrders(filters, 1, 20);
    if (ordersResponse.success) {
      const orders = ordersResponse.data;
      const pagination = ordersResponse.pagination;
      
      console.log('Orders:', orders);
      console.log('Pagination:', pagination);
      
      // عرض الطلبات في واجهة المستخدم
      displayOrders(orders, pagination);
    }
  } catch (error) {
    console.error('Error loading dashboard:', error);
  }
}
```

### مثال 6: عرض Timeline لطلب معين

```javascript
async function showOrderTimeline(orderId) {
  const orderResponse = await getAlWaseetOrderDetails(orderId);
  if (orderResponse.success) {
    const order = orderResponse.data.order;
    const timeline = order.alwaseet_shipment?.status_timeline || [];
    
    console.log('Order:', order.order_number);
    console.log('Current Status:', order.alwaseet_shipment?.status_text);
    console.log('Timeline:');
    
    // عرض Timeline في واجهة المستخدم
    timeline.forEach((status, index) => {
      const isCurrent = status.is_current ? ' (حالي)' : '';
      console.log(`${index + 1}. ${status.status_text} - ${status.changed_at}${isCurrent}`);
    });
    
    displayTimeline(timeline);
  } else {
    console.error('Error:', orderResponse.message);
  }
}
```

---

## Error Codes

| Error Code | الوصف |
|------------|-------|
| `FORBIDDEN` | المستخدم ليس مدير أو مجهز أو لا يملك صلاحية |
| `NOT_FOUND` | الطلب غير موجود أو ليس لديك صلاحية للوصول إليه |
| `STATUS_CARDS_ERROR` | خطأ في جلب بطاقات الحالات |

---

## ملاحظات مهمة

### 1. الصلاحيات

- **المدير (`admin`)**: يمكنه الوصول إلى جميع الطلبات والمبالغ الكلية
- **المجهز (`supplier`)**: يمكنه الوصول فقط للطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها (بدون المبالغ)
- **المورد الخاص (`private_supplier`)**: يمكنه الوصول فقط للطلبات التي تحتوي على منتجات من مخازن له صلاحية الوصول إليها (بدون المبالغ)

### 2. الأداء

- يتم استخدام Cache لتحسين الأداء:
  - **2 دقيقة** للبيانات المفلترة
  - **10 دقائق** للبيانات الكاملة (بدون فلاتر)
- يتم استخدام Eager Loading لتحميل العلاقات بشكل فعال

### 3. البيانات

- البيانات من قاعدة البيانات (محدثة من Jobs كل 10 دقائق)
- الحالات من `AlWaseetOrderStatus` (محدثة من Job كل ساعة)
- Timeline من `alwaseet_order_status_history`

### 4. الفلاتر

- جميع الفلاتر اختيارية
- يمكن دمج عدة فلاتر معاً
- البحث (`search`) يبحث في عدة حقول (order_number, customer_name, customer_phone, customer_address, delivery_code, delegate name, product name/code, alwaseet_order_id)

### 5. Pagination

- افتراضي: 20 طلب في الصفحة
- الحد الأقصى: 50 طلب في الصفحة
- Response يحتوي على معلومات Pagination كاملة

### 6. Timeline

- Timeline مرتب حسب `changed_at ASC` (من الأقدم للأحدث)
- `is_current` يشير إلى الحالة الحالية
- `display_order` يمكن استخدامه لترتيب الحالات في واجهة المستخدم

---

## الفروقات بين API المندوب و API المدير/المجهز

| الميزة | API المندوب | API المدير/المجهز |
|--------|-------------|-------------------|
| **الصلاحيات** | طلبات المندوب فقط | جميع الطلبات (للمدير) أو طلبات المخازن المسموح بها (للمجهز) |
| **الفلاتر** | `delegate_id` ثابت (المندوب الحالي) | `delegate_id` قابل للفلترة |
| **الفلاتر الإضافية** | لا يوجد | `confirmed_by`, `warehouse_id` |
| **المبالغ** | دائماً موجودة | فقط للمدير (null للمجهز) |
| **البحث** | محدود | شامل (يشمل delegate name, product name/code) |
| **البيانات** | `delegate` غير موجود | `delegate` و `confirmed_by` موجودان |

---

## ملخص Endpoints

| Method | Endpoint | الوصف |
|--------|----------|-------|
| `GET` | `/api/mobile/admin/alwaseet/status-cards` | جلب بطاقات الحالات |
| `GET` | `/api/mobile/admin/alwaseet/orders` | جلب قائمة الطلبات |
| `GET` | `/api/mobile/admin/alwaseet/orders/{id}` | جلب تفاصيل طلب مع Timeline |

---

## الدعم

للمزيد من المعلومات أو المساعدة، يرجى التواصل مع فريق التطوير.

