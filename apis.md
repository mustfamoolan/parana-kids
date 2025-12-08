اعمل لي دوكمنت لكل الاند بوينت وطريقة استخدامها بوضوح لاستخدمها في laravl

هذا توثيق لنقاط الوصول (Endpoints) الخاصة بواجهة التاجر (Merchant APIs) لتسهيل استخدامها في بيئة Laravel، مع الأخذ في الاعتبار أن المصادر توفر تفاصيل واجهة API القياسية وليس كود Laravel محددًا.
ملاحظات عامة حول الاستخدام
1. الإصدار وحدود الاستخدام: هذا التوثيق خاص بالإصدار V2.3. تم تطبيق حد للمعدل (Rate Limit) يبلغ 30 طلبًا لكل 30 ثانية لكل مستخدم، ويتم تطبيقه على جميع نقاط الوصول.
2. التوثيق والرمز المميز (Token): جميع الاتصالات تعتمد على رمز المصادقة (auth-token) الذي يتم الحصول عليه من نقطة وصول تسجيل الدخول.
3. متطلبات الفواتير: تتطلب نقاط وصول إدارة الفواتير (Invoice APIs) بشكل صارم رمز التاجر (Merchant token)، وإذا تم استخدام رمز مستخدم التاجر (merchant user token)، فستُرجع خطأ في المصادقة.
4. الاستجابة: في حالة النجاح، عادةً ما تكون الاستجابة بالشكل: {"status": true, "errNum": "S000", "msg": "ok", "data": [...]}. في حالة الفشل أو الخطأ، تكون الاستجابة: {"status": false, "errNum": "999", "msg": "error message"}.

--------------------------------------------------------------------------------
قائمة نقاط الوصول (Endpoints)
01. نقطة وصول تسجيل الدخول (Login Endpoint)
التفاصيل
القيمة
الغرض
مصادقة التاجر والحصول على الرمز المميز (Token).
URL
https://api.alwaseet-iq.net/v1/merchant/login.
الطريقة (Method)
POST.
Content-Type
multipart/form-data.
البارامترات المطلوبة
username (string), password (string).
ملاحظات الاستخدام
الرمز المميز يتم إرجاعه عند النجاح، ويتم استخدامه في جميع مكالمات API المستقبلية.
02. البيانات التكميلية لإنشاء الطلب (Supplementary Data)
تستخدم هذه النقاط للحصول على البيانات الضرورية (مثل معرف المنطقة والمدينة وحجم الطرد) قبل إنشاء الطلب.
النقطة
الغرض
URL
الطريقة (Method)
البارامترات المطلوبة
المدن (Cities)
ترجع قائمة بالمدن حسب الاسم والمعرف (ID).
https://api.alwaseet-iq.net/v1/merchant/citys.
GET.
لا يوجد.
المناطق (Regions)
ترجع قائمة بالمناطق حسب الاسم والمعرف لـ معرف مدينة معين.
https://api.alwaseet-iq.net/v1/merchant/regions?city_id=ID.
GET.
city_id (int).
أحجام الطرود (Package Sizes)
ترجع قائمة بالأحجام المدعومة حسب الاسم والمعرف.
https://api.alwaseet-iq.net/v1/merchant/package-sizes.
GET.
لا يوجد.
03. إنشاء الطلب (Order Creation)
التفاصيل
القيمة
الغرض
إرسال معلومات طلب جديد بعد تجهيز بياناته.
URL
https://api.alwaseet-iq.net/v1/merchant/create-order?token=loginToken.
الطريقة (Method)
POST.
Content-Type
multipart/form-data.
البارامترات المطلوبة (في الجسم)
client_name, client_mobile (بالصيغة "+9647..."), city_id, region_id, location, type_name, items_number, price, package_size, replacement (0 أو 1).
ملاحظات الاستخدام
عند النجاح، يتم إرجاع رابط (qr_link) لطباعة إيصال الطلب، ورمز QR الفريد للطلب (qr_id) الذي يعد المعرف الرئيسي له.
04. تعديل طلب (Edit an Order)
التفاصيل
القيمة
الغرض
تحديث معلومات الطلب عندما يكون الطلب لا يزال في حوزة التاجر.
URL
https://api.alwaseet-iq.net/v1/merchant/edit-order?token=loginToken.
الطريقة (Method)
POST.
Content-Type
multipart/form-data.
البارامترات المطلوبة (في الجسم)
جميع البارامترات المطلوبة لإنشاء الطلب بالإضافة إلى qr_id (رقم الطلب).
05. استرجاع الطلبات (Retrieve Orders)
التفاصيل
القيمة
الغرض
الاستعلام عن تفاصيل/حالة الطلبات (ترجع قائمة بطلبات التاجر).
URL
https://api.alwaseet-iq.net/v1/merchant/merchant-orders?token=loginToken.
الطريقة (Method)
GET.
Response
قائمة بالطلبات المتعلقة بالتاجر، تحتوي على تفاصيل مثل حالة الطلب (status) وتفاصيل التسليم.
06. الحصول على حالات الطلبات (Get Order Statuses)
التفاصيل
القيمة
الغرض
توفير قائمة بجميع حالات الطلبات الممكنة، كل حالة مرتبطة بمعرف فريد ونص وصفي.
URL
https://api.alwaseet-iq.net/v1/merchant/statuses?token=loginToken.
الطريقة (Method)
GET.
Response
قائمة بحالات الطلب بالشكل [{id: 1, status: 'status text'}].
07. استرجاع طلبات محددة بواسطة المعرفات (Retrieve Specific Orders by IDs) (Batch)
التفاصيل
القيمة
الغرض
استرجاع طلبات محددة عن طريق توفير قائمة من معرّفات الطلبات مفصولة بفواصل.
URL
https://api.alwaseet-iq.net/v1/merchant/get-orders-by-ids-bulk?token=loginToken.
الطريقة (Method)
POST.
Content-Type
multipart/form-data.
البارامتر المطلوب (في الجسم)
ids (string): سلسلة معرّفات مفصولة بفواصل، بحد أقصى 25 معرّفًا لكل طلب.
08. إدارة الفواتير (Invoice Management) (المالية)
ملاحظة هامة: هذه الواجهات تتطلب رمز التاجر (Merchant token) وليس رمز مستخدم التاجر.
النقطة
الغرض
URL
الطريقة (Method)
البارامترات المطلوبة
الحصول على فواتير التاجر
إرجاع جميع الفواتير المتعلقة بالتاجر.
https://api.alwaseet-iq.net/v1/merchant/get_merchant_invoices?token=loginToken.
GET.
لا يوجد.
الحصول على طلبات فاتورة معينة
إرجاع جميع الطلبات المرتبطة بفاتورة معينة.
https://api.alwaseet-iq.net/v1/merchant/get_merchant_invoice_orders?token=loginToken&invoice_id=invoiceID.
GET.
invoice_id (string).
تأكيد استلام فاتورة
وضع علامة على الفاتورة بأنها تم استلامها.
https://api.alwaseet-iq.net/v1/merchant/receive_merchant_invoice?token=loginToken&invoice_id=invoiceID.
GET.
invoice_id (string).

--------------------------------------------------------------------------------
توضيح لاستخدام Laravel:
عند استخدام هذه النقاط في Laravel، ستحتاج إلى استخدام HTTP Client (مثل Guzzle أو واجهة Illuminate\Support\Facades\Http) [معلومات من خارج المصدر]. يجب عليك التأكد من:
1. تمرير الرمز المميز إما في الـ Query String كما هو موضح في URLs (مثل ?token=loginToken) أو في الـ Header (إذا كانت API تسمح بذلك، لكن التوثيق يوضح استخدامه كـ Query Parameter).
2. تعيين Content-Type على multipart/form-data للطلبات من نوع POST.
3. تحويل مصفوفة الطلبات في Laravel إلى تنسيق multipart عند إرسال الطلبات التي تتطلب بارامترات في الجسم (Body)، مثل طلبات تسجيل الدخول وإنشاء الطلب وتعديله.
هذه النقاط والبارامترات المرتبطة بها هي الأساس لدمج واجهة API في تطبيقك باستخدام Laravel.
