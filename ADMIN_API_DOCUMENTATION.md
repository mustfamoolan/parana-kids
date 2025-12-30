# توثيق API تطبيق المدير والمجهز (Admin & Supplier Mobile App API)

## نظرة عامة

هذا التوثيق يشرح كيفية استخدام API لتطبيق المدير والمجهز. جميع المسارات تستخدم نظام PWA Token للمصادقة.

**Base URL:** `https://your-domain.com/api/mobile`

**الأدوار المدعومة:**
- **المدير (Admin)**: مدير النظام مع صلاحيات كاملة
- **المجهز العادي (Supplier)**: مجهز بدون مخزن خاص
- **المورد الخاص (Private Supplier)**: مجهز له مخزن خاص

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
POST /admin/auth/login
```

### الوصف
تسجيل دخول المدير أو المجهز باستخدام الكود وكلمة المرور. يدعم الأدوار التالية:
- `admin` (المدير)
- `supplier` (المجهز العادي)
- `private_supplier` (المورد الخاص)

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

**ملاحظة:** حقل `code` يمكن أن يكون:
- **الكود (Code)**: للمجهزين والموردين (مثل: `SUP001`, `PRV001`)
- **رقم الهاتف (Phone)**: للمديرين الذين قد لا يملكون كود (مثل: `07736182383`)

### مثال Request (للمدير - باستخدام رقم الهاتف)
```json
{
  "code": "07736182383",
  "password": "mz07736182383"
}
```

### مثال Request (للمجهز - باستخدام الكود)
```json
{
  "code": "SUP001",
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
      "name": "المدير العام",
      "email": "admin@example.com",
      "phone": "0912345678",
      "code": "ADM001",
      "role": "admin",
      "page_name": "صفحة المدير",
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

### مثال Response للمجهز العادي (Supplier)
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "token": "64-character-random-token",
    "expires_at": "2025-02-15T10:30:00+00:00",
    "user": {
      "id": 2,
      "name": "المجهز الأول",
      "email": "supplier@example.com",
      "phone": "0912345679",
      "code": "SUP001",
      "role": "supplier",
      "page_name": "صفحة المجهز",
      "profile_image": null,
      "profile_image_url": "https://your-domain.com/assets/images/profile-2.jpeg",
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

### مثال Response للمورد الخاص (Private Supplier)
```json
{
  "success": true,
  "message": "تم تسجيل الدخول بنجاح",
  "data": {
    "token": "64-character-random-token",
    "expires_at": "2025-02-15T10:30:00+00:00",
    "user": {
      "id": 3,
      "name": "المورد الخاص",
      "email": "private@example.com",
      "phone": "0912345680",
      "code": "PRV001",
      "role": "private_supplier",
      "page_name": "صفحة المورد",
      "profile_image": null,
      "profile_image_url": "https://your-domain.com/assets/images/profile-3.jpeg",
      "private_warehouse_id": 5,
      "telegram_chat_id": null,
      "warehouses": [],
      "private_warehouse": {
        "id": 5,
        "name": "مخزن المورد الخاص"
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
  "message": "بيانات الدخول غير صحيحة أو المستخدم ليس مديراً أو مجهزاً",
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
- API يدعم الأدوار الثلاثة: `admin`, `supplier`, `private_supplier`
- المدير لديه صلاحيات كاملة على جميع المخازن
- المجهز العادي لديه صلاحيات على المخازن المرتبطة به فقط
- المورد الخاص لديه مخزن خاص مرتبط به

---

## 2. جلب معلومات المستخدم (Get Current User)

### Endpoint
```
GET /admin/auth/me
```

### الوصف
جلب معلومات المدير أو المجهز الحالي المسجل دخوله.

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
      "name": "المدير العام",
      "email": "admin@example.com",
      "phone": "0912345678",
      "code": "ADM001",
      "role": "admin",
      "page_name": "صفحة المدير",
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
  "message": "هذا API مخصص للمديرين والمجهزين فقط",
  "error_code": "FORBIDDEN"
}
```

### ملاحظات
- يجب إرسال Token صالح في Header
- API يعيد معلومات المستخدم الحالي المسجل دخوله
- البيانات تشمل المخازن المتاحة وصلاحيات الإدارة

---

## 3. تسجيل الخروج (Logout)

### Endpoint
```
POST /admin/auth/logout
```

### الوصف
تسجيل خروج المدير أو المجهز وإلغاء Token الحالي.

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
  "message": "هذا API مخصص للمديرين والمجهزين فقط",
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
- بعد تسجيل الخروج، Token يصبح غير صالح
- يجب حذف Token من التطبيق بعد تسجيل الخروج
- يمكن استخدام Token مرة أخرى قبل تسجيل الخروج

---

## 4. تحديث الملف الشخصي (Update Profile)

### Endpoint
```
PUT /admin/auth/profile
```

### الوصف
تحديث صورة البروفايل للمدير أو المجهز.

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: multipart/form-data
```

أو

```
X-PWA-Token: {token}
Accept: application/json
Content-Type: multipart/form-data
```

### Request Body (Form Data)
```
profile_image: file (required)
  - نوع الملف: jpeg, jpg, png
  - الحد الأقصى للحجم: 2MB
```

### مثال Request (JavaScript)
```javascript
const formData = new FormData();
formData.append('profile_image', fileInput.files[0]);

const response = await fetch('/api/mobile/admin/auth/profile', {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  },
  body: formData
});
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "تم تحديث الملف الشخصي بنجاح",
  "data": {
    "user": {
      "id": 1,
      "name": "المدير العام",
      "email": "admin@example.com",
      "phone": "0912345678",
      "code": "ADM001",
      "role": "admin",
      "page_name": "صفحة المدير",
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
  "message": "هذا API مخصص للمديرين والمجهزين فقط",
  "error_code": "FORBIDDEN"
}
```

### Response Validation Error (422 Unprocessable Entity)
```json
{
  "message": "The given data was invalid.",
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

### Response Error (500 Internal Server Error)
```json
{
  "success": false,
  "message": "فشل رفع الصورة: {error_message}",
  "error_code": "UPLOAD_ERROR"
}
```

### ملاحظات
- الصورة القديمة يتم حذفها تلقائياً عند رفع صورة جديدة
- الصورة تُحفظ في `storage/app/public/profiles/`
- اسم الملف: `{user_id}_{timestamp}.{extension}`
- إذا لم يكن للمستخدم صورة، سيتم استخدام صورة افتراضية بناءً على `user_id`

---

## أكواد الأخطاء (Error Codes)

| Error Code | الوصف | HTTP Status |
|------------|-------|-------------|
| `INVALID_CREDENTIALS` | بيانات الدخول غير صحيحة أو المستخدم ليس مديراً أو مجهزاً | 401 |
| `UNAUTHORIZED` | غير مصرح - Token غير موجود أو غير صالح | 401 |
| `FORBIDDEN` | محظور - المستخدم ليس مديراً أو مجهزاً | 403 |
| `LOGOUT_ERROR` | حدث خطأ أثناء تسجيل الخروج | 500 |
| `UPLOAD_ERROR` | فشل رفع الصورة | 500 |

---

## أمثلة JavaScript كاملة

### مثال 1: تسجيل الدخول
```javascript
async function loginAdmin(code, password) {
  try {
    const response = await fetch('/api/mobile/admin/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        code: code,
        password: password
      })
    });

    const data = await response.json();

    if (data.success) {
      // حفظ Token
      localStorage.setItem('admin_token', data.data.token);
      localStorage.setItem('admin_token_expires_at', data.data.expires_at);
      
      // حفظ معلومات المستخدم
      localStorage.setItem('admin_user', JSON.stringify(data.data.user));
      
      console.log('تم تسجيل الدخول بنجاح:', data.data.user);
      return data.data;
    } else {
      console.error('فشل تسجيل الدخول:', data.message);
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('خطأ في تسجيل الدخول:', error);
    throw error;
  }
}

// استخدام
loginAdmin('ADM001', 'password123')
  .then(data => {
    console.log('المستخدم:', data.user);
  })
  .catch(error => {
    console.error('خطأ:', error);
  });
```

### مثال 2: جلب معلومات المستخدم
```javascript
async function getCurrentAdmin() {
  const token = localStorage.getItem('admin_token');
  
  if (!token) {
    throw new Error('لا يوجد token. يرجى تسجيل الدخول أولاً.');
  }

  try {
    const response = await fetch('/api/mobile/admin/auth/me', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();

    if (data.success) {
      console.log('معلومات المستخدم:', data.data.user);
      return data.data.user;
    } else {
      if (data.error_code === 'UNAUTHORIZED') {
        // Token غير صالح، حذف Token وإعادة توجيه لتسجيل الدخول
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_token_expires_at');
        localStorage.removeItem('admin_user');
        window.location.href = '/login';
      }
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('خطأ في جلب المعلومات:', error);
    throw error;
  }
}

// استخدام
getCurrentAdmin()
  .then(user => {
    console.log('المستخدم الحالي:', user);
  })
  .catch(error => {
    console.error('خطأ:', error);
  });
```

### مثال 3: تسجيل الخروج
```javascript
async function logoutAdmin() {
  const token = localStorage.getItem('admin_token');
  
  if (!token) {
    console.log('لا يوجد token للخروج');
    return;
  }

  try {
    const response = await fetch('/api/mobile/admin/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();

    if (data.success) {
      // حذف Token من localStorage
      localStorage.removeItem('admin_token');
      localStorage.removeItem('admin_token_expires_at');
      localStorage.removeItem('admin_user');
      
      console.log('تم تسجيل الخروج بنجاح');
      
      // إعادة توجيه لصفحة تسجيل الدخول
      window.location.href = '/login';
    } else {
      console.error('فشل تسجيل الخروج:', data.message);
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('خطأ في تسجيل الخروج:', error);
    // حتى لو فشل الطلب، نحذف Token محلياً
    localStorage.removeItem('admin_token');
    localStorage.removeItem('admin_token_expires_at');
    localStorage.removeItem('admin_user');
    throw error;
  }
}

// استخدام
logoutAdmin()
  .then(() => {
    console.log('تم تسجيل الخروج');
  })
  .catch(error => {
    console.error('خطأ:', error);
  });
```

### مثال 4: تحديث صورة البروفايل
```javascript
async function updateAdminProfile(imageFile) {
  const token = localStorage.getItem('admin_token');
  
  if (!token) {
    throw new Error('لا يوجد token. يرجى تسجيل الدخول أولاً.');
  }

  // التحقق من نوع الملف
  const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
  if (!allowedTypes.includes(imageFile.type)) {
    throw new Error('نوع الملف غير مدعوم. يجب أن يكون: jpeg, jpg, png');
  }

  // التحقق من حجم الملف (2MB)
  const maxSize = 2 * 1024 * 1024; // 2MB
  if (imageFile.size > maxSize) {
    throw new Error('حجم الملف كبير جداً. الحد الأقصى: 2MB');
  }

  try {
    const formData = new FormData();
    formData.append('profile_image', imageFile);

    const response = await fetch('/api/mobile/admin/auth/profile', {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      },
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      // تحديث معلومات المستخدم في localStorage
      localStorage.setItem('admin_user', JSON.stringify(data.data.user));
      
      console.log('تم تحديث الملف الشخصي بنجاح:', data.data.user);
      return data.data.user;
    } else {
      console.error('فشل تحديث الملف الشخصي:', data.message);
      throw new Error(data.message);
    }
  } catch (error) {
    console.error('خطأ في تحديث الملف الشخصي:', error);
    throw error;
  }
}

// استخدام
const fileInput = document.getElementById('profile-image-input');
fileInput.addEventListener('change', async (event) => {
  const file = event.target.files[0];
  if (file) {
    try {
      const updatedUser = await updateAdminProfile(file);
      console.log('تم التحديث:', updatedUser);
      // تحديث واجهة المستخدم
      document.getElementById('profile-image').src = updatedUser.profile_image_url;
    } catch (error) {
      alert('فشل تحديث الصورة: ' + error.message);
    }
  }
});
```

### مثال 5: Helper Function للتحقق من Token
```javascript
function isAdminTokenValid() {
  const token = localStorage.getItem('admin_token');
  const expiresAt = localStorage.getItem('admin_token_expires_at');
  
  if (!token || !expiresAt) {
    return false;
  }
  
  const expirationDate = new Date(expiresAt);
  const now = new Date();
  
  return expirationDate > now;
}

// استخدام
if (!isAdminTokenValid()) {
  console.log('Token غير صالح أو منتهي الصلاحية');
  localStorage.removeItem('admin_token');
  localStorage.removeItem('admin_token_expires_at');
  localStorage.removeItem('admin_user');
  window.location.href = '/login';
}
```

---

## ملاحظات مهمة

### 1. الفروقات بين الأدوار

#### المدير (Admin)
- لديه صلاحيات كاملة على جميع المخازن
- يمكنه إدارة جميع المستخدمين والمنتجات
- `role` في Response: `"admin"`

#### المجهز العادي (Supplier)
- لديه صلاحيات على المخازن المرتبطة به فقط
- `role` في Response: `"supplier"`
- `private_warehouse` في Response: `null`

#### المورد الخاص (Private Supplier)
- لديه مخزن خاص مرتبط به
- `role` في Response: `"private_supplier"`
- `private_warehouse` في Response: يحتوي على معلومات المخزن الخاص

### 2. إدارة Tokens
- Token واحد فقط لكل مستخدم في كل وقت
- عند تسجيل دخول جديد، يتم حذف Token السابق تلقائياً
- Token صالح لمدة 30 يوم
- يجب التحقق من صلاحية Token قبل كل طلب محمي

### 3. صورة البروفايل
- إذا لم يكن للمستخدم صورة، سيتم استخدام صورة افتراضية
- الصورة الافتراضية تُختار بناءً على `user_id % 20 + 1`
- الصور تُحفظ في `storage/app/public/profiles/`
- URL الصورة: `https://your-domain.com/storage/profiles/{filename}`

### 4. الأمان
- جميع المسارات المحمية تتطلب Token صالح
- يجب استخدام HTTPS في الإنتاج
- يجب التحقق من صلاحية Token في كل طلب
- يجب حذف Token عند تسجيل الخروج

---

## الدعم والمساعدة

إذا واجهت أي مشاكل أو لديك أسئلة، يرجى التواصل مع فريق التطوير.

**ملاحظة:** هذا API مخصص للمديرين والمجهزين فقط. إذا كنت مندوباً، يرجى استخدام [DELEGATE_API_DOCUMENTATION.md](DELEGATE_API_DOCUMENTATION.md).

