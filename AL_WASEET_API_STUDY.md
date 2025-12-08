# دراسة API شركة الواسط للتوصيل

## نظرة عامة

شركة الواسط (Al-Waseet) تقدم API للتكامل مع أنظمة التوصيل. هذه الدراسة تغطي جميع الـ endpoints المتاحة وكيفية استخدامها.

**Base URL:** `https://api.alwaseet-iq.net/v1/merchant`

**Rate Limit:** 30 طلب كل 30 ثانية لكل مستخدم

**Documentation:** [https://al-waseet.com/apis-main/index](https://al-waseet.com/apis-main/index)

---

## 1. Authentication (التوثيق)

### Login Endpoint

**URL:** `POST https://api.alwaseet-iq.net/v1/merchant/login`

**Content-Type:** `multipart/form-data`

**Parameters:**
- `username` (string, required): اسم المستخدم للتاجر
- `password` (string, required): كلمة المرور

**Response Success:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": {
    "token": "@@d71480ycdmp9...."
  }
}
```

**Response Error:**
```json
{
  "status": false,
  "errNum": "999",
  "msg": "error message"
}
```

**ملاحظات مهمة:**
- Token يتم إعادة تعيينه عند تغيير كلمة المرور
- يمكن تسجيل الدخول بحساب التاجر (merchant) أو حساب مستخدم التاجر (merchant user)
- APIs الفواتير تتطلب Merchant token فقط (وليس merchant user token)

---

## 2. Supplementary Data (البيانات المساعدة)

### 2.1 Cities (المدن)

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/citys`

**Method:** GET

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": [
    {
      "id": "1",
      "city_name": "بغداد"
    }
  ]
}
```

### 2.2 Regions (المناطق)

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/regions?city_id=ID`

**Method:** GET

**Parameters:**
- `city_id` (int, required): معرف المدينة

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": [
    {
      "id": "1",
      "region_name": "المنصور"
    }
  ]
}
```

### 2.3 Package Sizes (أحجام الطرود)

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/package-sizes`

**Method:** GET

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": [
    {
      "id": "1",
      "size": "عادي"
    }
  ]
}
```

---

## 3. Order Creation (إنشاء الطلب)

**URL:** `POST https://api.alwaseet-iq.net/v1/merchant/create-order?token=loginToken`

**Method:** POST

**Content-Type:** `multipart/form-data`

### Required Parameters:

- `client_name` (string): اسم العميل
- `client_mobile` (string): رقم هاتف العميل (يجب أن يبدأ بـ +964)
- `city_id` (string): معرف المدينة
- `region_id` (string): معرف المنطقة
- `location` (string): وصف موقع العميل
- `price` (string): سعر الطلب (يشمل رسوم التوصيل)
- `package_size` (string): حجم الطرد (ID من package-sizes API)
- `type_name` (string): نوع البضاعة (مثل: "ملابس")

### Optional Parameters:

- `client_mobile2` (string): رقم هاتف ثاني للعميل
- `merchant_notes` (string): ملاحظات التاجر
- `replacement` (string): "1" إذا كان طلب استبدال، "0" إذا كان طلب عادي

### Response Success:

```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": {
    "id": "103",
    "qr_id": "QR123456",
    "qr_link": "https://api.alwaseet-iq.net/v1/merchant/qr/QR123456"
  }
}
```

**ملاحظات:**
- `qr_link`: رابط لطباعة PDF يحتوي على إيصال الطلب (sticker)
- `qr_id`: رقم QR يمكن استخدامه في تصميم إيصال مخصص

---

## 4. Edit Order (تعديل الطلب)

**URL:** `POST https://api.alwaseet-iq.net/v1/merchant/edit-order?token=loginToken&order_id=orderID`

**Method:** POST

**Parameters:**
- `order_id` (string, required): معرف الطلب
- نفس parameters إنشاء الطلب (كلها optional للتعديل)

**ملاحظات:**
- يمكن تعديل الطلب قبل أن يتم استلامه من قبل المندوب
- بعد الاستلام، لا يمكن التعديل

---

## 5. Retrieve Orders (جلب الطلبات)

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/get-orders?token=loginToken`

**Method:** GET

**Optional Parameters:**
- `status_id` (string): فلترة حسب حالة الطلب
- `date_from` (string): تاريخ البداية (YYYY-MM-DD)
- `date_to` (string): تاريخ النهاية (YYYY-MM-DD)

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": [
    {
      "client_name": "محمد",
      "client_mobile": "+9647704723599",
      "items_number": "1",
      "created_at": "2023-08-13 22:12:18",
      "city_name": "بغداد",
      "region_name": "شارع فلسطين",
      "status_id": "2",
      "status": "تم استلام الطلب من قبل المندوب",
      "price": "20000",
      "location": "market",
      "id": "103",
      "delivery_price": "9000",
      "package_size": "عادي",
      "merchant_invoice_id": "-1"
    }
  ]
}
```

---

## 6. Get Orders Statuses (حالات الطلبات)

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/get-orders-statuses?token=loginToken`

**Method:** GET

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": [
    {
      "id": "1",
      "status": "تم استلام الطلب من قبل المندوب"
    }
  ]
}
```

---

## 7. Retrieve Specific Orders by IDs (جلب طلبات محددة)

**URL:** `POST https://api.alwaseet-iq.net/v1/merchant/get-orders-by-ids?token=loginToken`

**Method:** POST

**Parameters:**
- `order_ids` (string): معرفات الطلبات مفصولة بفواصل (comma-separated)

**Response:** نفس شكل Retrieve Orders

---

## 8. Invoice Management (إدارة الفواتير)

### 8.1 Get Merchant Invoices

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/get_merchant_invoices?token=loginToken`

**Method:** GET

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": [
    {
      "id": "3",
      "merchant_price": "170000",
      "delivered_orders_count": "7",
      "status": "تم الاستلام من قبل التاجر",
      "updated_at": "2023-12-20 17:01:46"
    }
  ]
}
```

### 8.2 Get Orders for an Invoice

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/get_merchant_invoice_orders?token=loginToken&invoice_id=invoiceID`

**Method:** GET

**Parameters:**
- `invoice_id` (string, required): معرف الفاتورة

**Response:**
```json
{
  "status": true,
  "errNum": "S000",
  "msg": "ok",
  "data": {
    "invoice": [...],
    "orders": [...]
  }
}
```

### 8.3 Receive an Invoice

**URL:** `GET https://api.alwaseet-iq.net/v1/merchant/receive_merchant_invoice?token=loginToken&invoice_id=invoiceID`

**Method:** GET

**Parameters:**
- `invoice_id` (string, required): معرف الفاتورة

**Purpose:** تأكيد استلام الفاتورة

---

## تحليل التكامل مع النظام الحالي

### البيانات المتوفرة في النظام:

1. **Order Model:**
   - `customer_name` → `client_name`
   - `customer_phone` → `client_mobile` (يحتاج إضافة +964)
   - `customer_address` → `location`
   - `total_amount` → `price`
   - `notes` → `merchant_notes`

2. **البيانات المفقودة التي نحتاجها:**
   - `city_id`: معرف المدينة
   - `region_id`: معرف المنطقة
   - `package_size`: حجم الطرد (ID)
   - `type_name`: نوع البضاعة (مثل "ملابس")

### الخطوات المطلوبة للتكامل:

1. **إضافة حقول جديدة في جدول orders:**
   - `alwaseet_order_id` (string, nullable): معرف الطلب في الواسط
   - `alwaseet_qr_id` (string, nullable): QR code للطلب
   - `alwaseet_qr_link` (string, nullable): رابط طباعة الإيصال
   - `alwaseet_city_id` (string, nullable): معرف المدينة
   - `alwaseet_region_id` (string, nullable): معرف المنطقة
   - `alwaseet_package_size_id` (string, nullable): معرف حجم الطرد
   - `alwaseet_status_id` (string, nullable): حالة الطلب في الواسط
   - `alwaseet_status` (string, nullable): نص حالة الطلب

2. **إنشاء Service Class:**
   - `AlWaseetService`: للتعامل مع جميع API calls
   - تخزين token في cache
   - معالجة الأخطاء

3. **إضافة Settings:**
   - `ALWASEET_USERNAME`: اسم المستخدم
   - `ALWASEET_PASSWORD`: كلمة المرور
   - `ALWASEET_DEFAULT_CITY_ID`: المدينة الافتراضية
   - `ALWASEET_DEFAULT_REGION_ID`: المنطقة الافتراضية
   - `ALWASEET_DEFAULT_PACKAGE_SIZE_ID`: حجم الطرد الافتراضي
   - `ALWASEET_DEFAULT_TYPE_NAME`: نوع البضاعة الافتراضي

4. **تحديث Order Creation:**
   - بعد إنشاء الطلب في النظام، إنشاء طلب في الواسط تلقائياً
   - حفظ `alwaseet_order_id` و `qr_link` في قاعدة البيانات

5. **إضافة Sync Job:**
   - Job دوري لمزامنة حالات الطلبات من الواسط
   - تحديث `alwaseet_status_id` و `alwaseet_status`

---

## ملاحظات مهمة

1. **Rate Limiting:** 30 طلب كل 30 ثانية - يجب إضافة throttling
2. **Error Handling:** جميع الأخطاء تعيد نفس الصيغة
3. **Token Management:** Token يجب تخزينه في cache مع expiration
4. **Phone Format:** يجب التأكد من أن رقم الهاتف يبدأ بـ +964
5. **Package Size:** يجب اختيار حجم مناسب حسب عدد المنتجات
6. **City/Region:** يحتاج واجهة لاختيار المدينة والمنطقة عند إنشاء الطلب

---

## الخطوات التالية

1. ✅ دراسة API (تم)
2. ⏳ إنشاء Service Class
3. ⏳ إضافة Migration للحقول الجديدة
4. ⏳ إضافة Settings
5. ⏳ تحديث Order Creation
6. ⏳ إضافة Sync Job
7. ⏳ إضافة واجهة لاختيار المدينة والمنطقة

