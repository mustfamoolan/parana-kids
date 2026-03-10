<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader\PageBoundaries;

class AlWaseetService
{
    protected $baseUrl = 'https://api.alwaseet-iq.net/v1/merchant';
    protected $tokenCacheKey = 'alwaseet_token';
    protected $tokenExpiryCacheKey = 'alwaseet_token_expires_at';
    protected $orderStatusCacheKeyPrefix = 'alwaseet_order_status_';

    /**
     * Encode token for URL - preserve @@ prefix
     */
    protected function encodeTokenForUrl($token): string
    {
        // إذا كان token يبدأ بـ @@، نحافظ عليه كما هو في URL
        // لأن API قد لا يقبل %40%40
        if (strpos($token, '@@') === 0) {
            $tokenWithoutPrefix = substr($token, 2);
            return '@@' . urlencode($tokenWithoutPrefix);
        }
        return urlencode($token);
    }

    /**
     * Encode token for URL - full encoding (test method)
     */
    protected function encodeTokenForUrlFull($token): string
    {
        // تجربة encoding كامل بما في ذلك @@
        return urlencode($token);
    }

    /**
     * Log request details for debugging
     */
    protected function logRequestDetails($method, $url, $headers = [], $data = [], $token = null)
    {
        $logData = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'headers_count' => count($headers),
        ];

        if ($token) {
            $logData['token_full'] = $token;
            $logData['token_length'] = strlen($token);
            $logData['token_starts_with'] = substr($token, 0, 2);
        }

        if (!empty($data)) {
            // Log data keys only (not values for security)
            $logData['data_keys'] = array_keys($data);
            $logData['data_count'] = count($data);
        }

        $logData['server_remote_addr'] = $_SERVER['REMOTE_ADDR'] ?? 'not_set';

        Log::info('AlWaseetService: Request details', $logData);
    }

    /**
     * التحقق من أن الـ token هو Merchant token (يبدأ بـ @@)
     * بعض APIs تتطلب Merchant token وليس Merchant User token
     */
    protected function ensureMerchantToken($token, $apiName = 'API'): void
    {
        if (strpos($token, '@@') !== 0) {
            $errorMessage = "❌ هذا الـ API ({$apiName}) يتطلب Merchant Account وليس Merchant User Account.\n\n" .
                "📝 الحل:\n" .
                "1. اذهب إلى صفحة الإعدادات: /admin/alwaseet/settings\n" .
                "2. تأكد من استخدام بيانات Merchant Account الرئيسي (وليس Merchant User)\n" .
                "3. اضغط على 'إعادة تسجيل الدخول' أو 'اختبار الاتصال'\n\n" .
                "💡 ملاحظة: Merchant token يبدأ بـ '@@' بينما Merchant User token لا يبدأ بهذا الرمز.\n" .
                "   الـ token الحالي يبدأ بـ: '" . substr($token, 0, 2) . "'";

            Log::error('AlWaseetService: Merchant token required but merchant user token provided', [
                'api_name' => $apiName,
                'token_preview' => substr($token, 0, 15) . '...',
                'token_starts_with' => substr($token, 0, 2),
                'token_length' => strlen($token),
                'token_source' => 'direct_check',
                'note' => 'تم اكتشاف Merchant User token. يرجى استخدام Merchant Account للحصول على صلاحيات كاملة.',
            ]);

            throw new \Exception($errorMessage);
        }
    }

    /**
     * التحقق من نوع الحساب (Merchant أو Merchant User)
     * @return array ['is_merchant' => bool, 'token_preview' => string, 'message' => string]
     */
    public function getAccountType(): array
    {
        try {
            $token = $this->getToken();
            $isMerchant = strpos($token, '@@') === 0;

            return [
                'is_merchant' => $isMerchant,
                'is_merchant_user' => !$isMerchant,
                'token_preview' => substr($token, 0, 15) . '...',
                'token_starts_with' => substr($token, 0, 2),
                'message' => $isMerchant
                    ? '✅ أنت تستخدم Merchant Account (صلاحيات كاملة)'
                    : '⚠️ أنت تستخدم Merchant User Account (صلاحيات محدودة)',
                'warning' => !$isMerchant
                    ? 'بعض APIs مثل Get Orders و Get Order Statuses تتطلب Merchant Account'
                    : null,
            ];
        } catch (\Exception $e) {
            return [
                'is_merchant' => false,
                'is_merchant_user' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get authentication token
     */
    public function getToken($forceRefresh = false): string
    {
        // Check cache first
        if (!$forceRefresh && Cache::has($this->tokenCacheKey)) {
            $token = Cache::get($this->tokenCacheKey);
            // تنظيف الـ token من المسافات فقط
            $token = trim($token);

            // التحقق من نوع الـ token في Cache
            if (!empty($token)) {
                $isMerchantToken = strpos($token, '@@') === 0;

                Log::info('AlWaseetService: Token retrieved from cache', [
                    'token_preview' => substr($token, 0, 15) . '...',
                    'token_starts_with' => substr($token, 0, 2),
                    'token_length' => strlen($token),
                    'is_merchant_token' => $isMerchantToken,
                ]);

                // إرجاع الـ token الموجود في Cache (سواء كان Merchant أو Merchant User)
                return $token;
            }
        }

        // Get credentials from settings
        $username = Setting::getValue('alwaseet_username');
        $password = Setting::getValue('alwaseet_password');

        if (empty($username)) {
            throw new \Exception('اسم المستخدم غير محدد. يرجى إعداد الربط أولاً.');
        }

        if (empty($password)) {
            throw new \Exception('كلمة المرور غير محددة. يرجى إعداد الربط أولاً.');
        }

        try {
            Log::info('AlWaseetService: Attempting login', [
                'username_preview' => substr($username, 0, 3) . '...',
                'url' => "{$this->baseUrl}/login",
            ]);

            $loginUrl = "{$this->baseUrl}/login";
            $loginData = [
                'username' => $username,
                'password' => $password,
            ];

            // استخدام application/x-www-form-urlencoded بدلاً من multipart/form-data
            $postFields = http_build_query($loginData);

            $headers = [
                'Accept: application/json, text/plain, */*',
                'Accept-Language: ar,en;q=0.9',
                'Accept-Encoding: gzip, deflate, br',
                'Content-Type: application/x-www-form-urlencoded',
                'Content-Length: ' . strlen($postFields),
                'Connection: keep-alive',
                'Cache-Control: no-cache',
                'Origin: https://api.alwaseet-iq.net',
                'Referer: https://api.alwaseet-iq.net/v1/merchant/login',
                'Sec-Fetch-Dest: empty',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Site: same-origin',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'sec-ch-ua: "Chromium";v="122", "Not(A:Brand";v="24", "Google Chrome";v="122"',
                'sec-ch-ua-mobile: ?0',
                'sec-ch-ua-platform: "Windows"',
            ];

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('POST', $loginUrl, $headers, ['username' => '***', 'password' => '***'], null);

            // مسار مؤقت لـ cookie jar
            $cookieFile = tempnam(sys_get_temp_dir(), 'alwaseet_cookie_');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $loginUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, ''); // يرسل Accept-Encoding تلقائياً
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

            $responseBody = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // حذف ملف الكوكي المؤقت
            if (file_exists($cookieFile)) {
                @unlink($cookieFile);
            }

            if ($curlError) {
                throw new \Exception('خطأ في الاتصال بالواسط: ' . $curlError);
            }

            $data = json_decode($responseBody, true);

            Log::info('AlWaseetService: Login response received', [
                'status_code' => $statusCode,
                'response_status' => $data['status'] ?? null,
                'errNum' => $data['errNum'] ?? null,
                'msg' => $data['msg'] ?? null,
                'has_token' => isset($data['data']['token']),
                'body_preview' => substr($responseBody, 0, 500),
            ]);

            // التحقق من response status
            if ($statusCode !== 200) {
                // فحص ما إذا كان الـ response من Cloudflare (HTML بدلاً من JSON)
                $isCloudflareBlock = (
                    strpos($responseBody, 'error code:') !== false ||
                    strpos($responseBody, 'Cloudflare') !== false ||
                    strpos($responseBody, 'Access denied') !== false ||
                    (strpos($responseBody, '<html') !== false && $statusCode === 403)
                );

                if ($isCloudflareBlock) {
                    // استخراج رقم الخطأ من Cloudflare
                    preg_match('/error code:\s*(\d+)/i', $responseBody, $matches);
                    $cfErrorCode = $matches[1] ?? '1106';

                    $errorMsg = "تم حجب الاتصال بواجهة الواسط من قِبَل Cloudflare (خطأ: {$cfErrorCode}).\n" .
                        "يرجى التواصل مع دعم الواسط لإضافة عنوان IP الخادم إلى القائمة البيضاء.\n" .
                        "عنوان IP الخادم الحالي: " . (gethostbyname(gethostname()) ?: 'غير معروف');

                    Log::error('AlWaseetService: Login blocked by Cloudflare', [
                        'status_code' => $statusCode,
                        'cf_error_code' => $cfErrorCode,
                        'body_preview' => substr($responseBody, 0, 300),
                    ]);

                    throw new \Exception($errorMsg);
                }

                $errorMsg = $data['msg'] ?? 'فشل تسجيل الدخول: ' . $statusCode;

                Log::error('AlWaseetService: Login failed - HTTP error', [
                    'status_code' => $statusCode,
                    'msg' => $errorMsg,
                    'response_body' => $responseBody,
                ]);

                throw new \Exception($errorMsg);
            }

            // التحقق من response structure
            if (!isset($data['status'])) {
                Log::error('AlWaseetService: Login failed - Invalid response format', [
                    'response_body' => $responseBody,
                ]);
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            // التحقق من success status
            if ($data['status'] !== true) {
                $errorMsg = $data['msg'] ?? 'فشل تسجيل الدخول';
                $errNum = $data['errNum'] ?? null;

                Log::error('AlWaseetService: Login failed - API returned false', [
                    'errNum' => $errNum,
                    'msg' => $errorMsg,
                    'response' => $data,
                ]);

                throw new \Exception($errorMsg);
            }

            // التحقق من وجود token
            if (!isset($data['data']['token'])) {
                Log::error('AlWaseetService: Login failed - Token not found in response', [
                    'response' => $data,
                ]);
                throw new \Exception('لم يتم العثور على token في الاستجابة');
            }

            $token = trim($data['data']['token']);

            // التحقق من أن token يبدأ بـ @@ (merchant token)
            if (strpos($token, '@@') !== 0) {
                Log::warning('AlWaseetService: Token does not start with @@', [
                    'token_preview' => substr($token, 0, 15) . '...',
                    'token_starts_with' => substr($token, 0, 2),
                    'note' => 'قد يكون هذا merchant user token وليس merchant token. بعض APIs قد لا تعمل مع هذا النوع من الـ token.',
                ]);

                // حفظ نوع الحساب في Settings
                Setting::setValue('alwaseet_account_type', 'merchant_user');
            } else {
                // حفظ نوع الحساب في Settings
                Setting::setValue('alwaseet_account_type', 'merchant');
            }

            // Cache token for 24 hours (token resets on password change)
            Cache::put($this->tokenCacheKey, $token, now()->addHours(24));
            Setting::setValue('alwaseet_token', $token);

            Log::info('AlWaseetService: Token refreshed successfully', [
                'token_length' => strlen($token),
                'token_preview' => substr($token, 0, 15) . '...',
                'token_starts_with' => substr($token, 0, 2),
                'token_full' => $token, // تسجيل token كامل للتحقق
                'errNum' => $data['errNum'] ?? null,
                'account_type' => strpos($token, '@@') === 0 ? 'merchant' : 'merchant_user',
            ]);

            return $token;
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Login exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'username_preview' => isset($username) ? substr($username, 0, 3) . '...' : 'N/A',
            ]);
            throw $e;
        }
    }

    /**
     * Refresh token if needed (when getting 400 with errNum: 21)
     */
    protected function refreshTokenIfNeeded(): string
    {
        // Clear cache and force refresh
        Cache::forget($this->tokenCacheKey);
        Setting::setValue('alwaseet_token', null);

        // Get new token
        return $this->getToken(true);
    }

    /**
     * Test connection
     */
    public function testConnection()
    {
        try {
            $token = $this->getToken();
            return ['success' => true, 'message' => 'تم الاتصال بنجاح'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all orders
     */
    public function getOrders($statusId = null, $dateFrom = null, $dateTo = null, $retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token
        $this->ensureMerchantToken($token, 'Get Orders');

        try {
            // بناء URL مع token في query parameter (كما في الوثائق الرسمية)
            // تجربة encoding كامل أيضاً
            $encodedToken = $this->encodeTokenForUrl($token);

            // Log للتحقق من encoding
            Log::info('AlWaseetService: Token encoding test', [
                'original_token' => substr($token, 0, 20) . '...',
                'encoded_token' => substr($encodedToken, 0, 20) . '...',
                'full_encoded' => substr($this->encodeTokenForUrlFull($token), 0, 20) . '...',
            ]);

            $url = "{$this->baseUrl}/merchant-orders?token=" . $encodedToken;
            if ($statusId) {
                $url .= "&status_id=" . urlencode($statusId);
            }
            if ($dateFrom) {
                $url .= "&date_from=" . urlencode($dateFrom);
            }
            if ($dateTo) {
                $url .= "&date_to=" . urlencode($dateTo);
            }

            Log::info('AlWaseetService: getOrders request', [
                'url_preview' => str_replace($token, substr($token, 0, 15) . '...', $url),
                'token_starts_with' => substr($token, 0, 2),
                'token_length' => strlen($token),
            ]);

            // استخدام GET method (بدون Content-Type header)
            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];

            // إضافة X-Forwarded-For إذا كان متوفر
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
            }

            // إضافة token في header أيضاً للاختبار
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'X-Auth-Token: ' . $token;

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('GET', $url, $headers, [], $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // الحصول على معلومات curl بعد التنفيذ
            $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
            $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            // فصل response headers من body
            $responseHeaders = '';
            if ($responseHeadersSize > 0) {
                $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                $responseBody = substr($responseBody, $responseHeadersSize);
            }

            curl_close($ch);

            // Logging شامل بعد التنفيذ
            Log::info('AlWaseetService: getOrders response details', [
                'http_code' => $httpCode,
                'local_ip' => $localIp,
                'primary_ip' => $primaryIp,
                'response_headers_size' => $responseHeadersSize,
                'response_headers_preview' => substr($responseHeaders, 0, 200),
                'response_body_preview' => substr($responseBody, 0, 200),
                'error' => $error ?: null,
            ]);

            if ($error) {
                throw new \Exception('خطأ في الاتصال: ' . $error);
            }

            if ($httpCode !== 200) {
                $data = json_decode($responseBody, true);
                $errNum = $data['errNum'] ?? null;

                // معالجة خطأ الصلاحية (errNum: 21)
                if ($httpCode === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                        'method' => 'getOrders',
                        'token_starts_with' => substr($token, 0, 2),
                    ]);

                    // قبل إعادة المحاولة، نتحقق من نوع الـ token الجديد
                    $newToken = $this->refreshTokenIfNeeded();
                    if (strpos($newToken, '@@') !== 0) {
                        // إذا كان الـ token الجديد أيضاً لا يبدأ بـ @@، المشكلة في الحساب
                        $errorMsg = "❌ فشل الوصول: أنت تستخدم Merchant User Account\n\n" .
                            "📝 هذا الـ API يتطلب Merchant Account الرئيسي.\n" .
                            "يرجى تسجيل الدخول بحساب Merchant في: /admin/alwaseet/settings";
                        throw new \Exception($errorMsg);
                    }

                    // إعادة المحاولة مرة واحدة فقط
                    return $this->getOrders($statusId, $dateFrom, $dateTo, false);
                }

                Log::error('AlWaseetService: HTTP request failed', [
                    'url' => $url,
                    'status' => $httpCode,
                    'errNum' => $errNum,
                    'body' => $responseBody,
                    'token_starts_with' => substr($token, 0, 2),
                    'token_is_merchant' => strpos($token, '@@') === 0,
                ]);

                // استخدام الرسالة الأصلية من API الواسط
                $errorMsg = $data['msg'] ?? 'فشل الاتصال بخادم الواسط: ' . $httpCode;

                throw new \Exception($errorMsg);
            }

            $data = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('AlWaseetService: JSON decode failed', [
                    'response' => $responseBody,
                    'error' => json_last_error_msg(),
                ]);
                throw new \Exception('فشل تحليل الاستجابة من الواسط');
            }

            if (!isset($data['status'])) {
                Log::error('AlWaseetService: Invalid response format', ['response' => $data]);
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                $orders = $data['data'] ?? [];
                Log::info('AlWaseetService: Orders retrieved', ['count' => count($orders)]);
                return $orders;
            }

            throw new \Exception($data['msg'] ?? 'فشل جلب الطلبات');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Get orders failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get order statuses
     */
    public function getOrderStatuses($retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token
        $this->ensureMerchantToken($token, 'Get Order Statuses');

        try {
            $encodedToken = $this->encodeTokenForUrl($token);
            $url = "{$this->baseUrl}/statuses?token=" . $encodedToken;

            Log::info('AlWaseetService: getOrderStatuses request', [
                'url_preview' => str_replace($token, substr($token, 0, 15) . '...', $url),
                'token_starts_with' => substr($token, 0, 2),
            ]);

            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];

            // إضافة X-Forwarded-For إذا كان متوفر
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
            }

            // إضافة token في header
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'X-Auth-Token: ' . $token;

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('GET', $url, $headers, [], $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // الحصول على معلومات curl بعد التنفيذ
            $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
            $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            // فصل response headers من body
            $responseHeaders = '';
            if ($responseHeadersSize > 0) {
                $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                $responseBody = substr($responseBody, $responseHeadersSize);
            }

            curl_close($ch);

            // Logging شامل بعد التنفيذ
            Log::info('AlWaseetService: getOrderStatuses response details', [
                'http_code' => $httpCode,
                'local_ip' => $localIp,
                'primary_ip' => $primaryIp,
                'response_headers_size' => $responseHeadersSize,
                'response_headers_preview' => substr($responseHeaders, 0, 200),
                'response_body_preview' => substr($responseBody, 0, 200),
                'error' => $error ?: null,
            ]);

            if ($error) {
                throw new \Exception('خطأ في الاتصال: ' . $error);
            }

            if ($httpCode !== 200) {
                $data = json_decode($responseBody, true);
                $errNum = $data['errNum'] ?? null;

                // معالجة خطأ الصلاحية (errNum: 21)
                if ($httpCode === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                        'method' => 'getOrderStatuses',
                    ]);

                    $newToken = $this->refreshTokenIfNeeded();
                    if (strpos($newToken, '@@') !== 0) {
                        $errorMsg = "❌ فشل الوصول: أنت تستخدم Merchant User Account\n\n" .
                            "📝 هذا الـ API يتطلب Merchant Account الرئيسي.\n" .
                            "يرجى تسجيل الدخول بحساب Merchant في: /admin/alwaseet/settings";
                        throw new \Exception($errorMsg);
                    }

                    return $this->getOrderStatuses(false);
                }

                // استخدام الرسالة الأصلية من API الواسط
                $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                throw new \Exception($errorMsg);
            }

            $data = json_decode($responseBody, true);

            if (!isset($data['status'])) {
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                return $data['data'] ?? [];
            }

            throw new \Exception($data['msg'] ?? 'فشل جلب حالات الطلبات');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Get order statuses failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get orders by IDs
     */
    public function getOrdersByIds(array $orderIds, $retry = true)
    {
        if (empty($orderIds))
            return [];

        $results = [];
        $idsToFetch = [];

        // Check cache for each ID
        foreach ($orderIds as $id) {
            $cached = Cache::get($this->orderStatusCacheKeyPrefix . $id);
            if ($cached) {
                $results[] = $cached;
            } else {
                $idsToFetch[] = $id;
            }
        }

        if (empty($idsToFetch)) {
            Log::info('AlWaseetService: All order IDs found in cache', ['count' => count($orderIds)]);
            return $results;
        }

        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token
        $this->ensureMerchantToken($token, 'Get Orders By IDs');

        try {
            $url = "{$this->baseUrl}/get-orders-by-ids-bulk?token=" . $this->encodeTokenForUrl($token);

            $headers = [
                'User-Agent' => 'Laravel-AlWaseet-Integration/1.0',
            ];

            $postData = [
                'ids' => implode(',', $idsToFetch),
            ];

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('POST', $url, $headers, $postData, $token);

            // استخدام POST مع multipart/form-data
            $response = Http::asMultipart()
                ->withHeaders($headers)
                ->post($url, $postData);

            if (!$response->successful()) {
                $responseData = $response->json();
                $errNum = $responseData['errNum'] ?? null;

                // معالجة خطأ الصلاحية (errNum: 21)
                if ($response->status() === 400 && $errNum == 21 && $retry) {
                    $this->refreshTokenIfNeeded();
                    return $this->getOrdersByIds($orderIds, false);
                }

                Log::error('AlWaseetService: HTTP request failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'errNum' => $errNum,
                    'body' => $response->body(),
                ]);

                throw new \Exception($responseData['msg'] ?? 'فشل الاتصال: ' . $response->status());
            }

            $data = $response->json();

            if ($data['status'] === true) {
                $fetchedOrders = $data['data'] ?? [];

                // Update cache for fetched orders
                foreach ($fetchedOrders as $apiOrder) {
                    if (isset($apiOrder['id'])) {
                        // Cache status for 5 minutes
                        Cache::put($this->orderStatusCacheKeyPrefix . $apiOrder['id'], $apiOrder, now()->addMinutes(5));
                        $results[] = $apiOrder;
                    }
                }

                return $results;
            }

            throw new \Exception($data['msg'] ?? 'فشل جلب الطلبات');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Get orders by IDs failed', [
                'error' => $e->getMessage(),
                'order_ids' => $idsToFetch,
            ]);
            throw $e;
        }
    }

    /**
     * Get cities
     */
    public function getCities($retry = true)
    {
        return Cache::remember('alwaseet_cities', now()->addHours(24), function () use ($retry) {
            $token = $this->getToken();

            try {
                $url = "{$this->baseUrl}/citys?token=" . $this->encodeTokenForUrl($token);

                $headers = [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ];

                // إضافة X-Forwarded-For إذا كان متوفر
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
                }

                // إضافة token في header
                $headers[] = 'Authorization: Bearer ' . $token;
                $headers[] = 'X-Auth-Token: ' . $token;

                // Logging شامل قبل الإرسال
                $this->logRequestDetails('GET', $url, $headers, [], $token);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);

                // الحصول على معلومات curl بعد التنفيذ
                $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
                $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
                $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

                // فصل response headers من body
                $responseHeaders = '';
                if ($responseHeadersSize > 0) {
                    $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                    $responseBody = substr($responseBody, $responseHeadersSize);
                }

                curl_close($ch);

                // Logging شامل بعد التنفيذ
                Log::info('AlWaseetService: getCities response details', [
                    'http_code' => $httpCode,
                    'local_ip' => $localIp,
                    'primary_ip' => $primaryIp,
                    'response_headers_size' => $responseHeadersSize,
                    'response_headers_preview' => substr($responseHeaders, 0, 200),
                    'response_body_preview' => substr($responseBody, 0, 200),
                    'error' => $error ?: null,
                ]);

                if ($error) {
                    throw new \Exception('خطأ في الاتصال: ' . $error);
                }

                if ($httpCode !== 200) {
                    $data = json_decode($responseBody, true);
                    $errNum = $data['errNum'] ?? null;

                    // معالجة خطأ الصلاحية (errNum: 21)
                    if ($httpCode === 400 && $errNum == 21 && $retry) {
                        Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                            'method' => 'getCities',
                        ]);
                        $this->refreshTokenIfNeeded();
                        // إعادة المحاولة مرة واحدة فقط
                        Cache::forget('alwaseet_cities');
                        return $this->getCities(false);
                    }

                    Log::error('AlWaseetService: HTTP request failed', [
                        'url' => $url,
                        'status' => $httpCode,
                        'errNum' => $errNum,
                        'body' => $responseBody,
                    ]);

                    // استخدام الرسالة الأصلية من API الواسط
                    $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                    throw new \Exception($errorMsg);
                }

                $data = json_decode($responseBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('فشل تحليل الاستجابة: ' . json_last_error_msg());
                }

                if (!isset($data['status'])) {
                    throw new \Exception('استجابة غير صحيحة من الواسط');
                }

                if ($data['status'] === true) {
                    return $data['data'] ?? [];
                }

                throw new \Exception($data['msg'] ?? 'فشل جلب المدن');
            } catch (\Exception $e) {
                Log::error('AlWaseetService: Get cities failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get regions by city ID
     */
    public function getRegions($cityId, $retry = true)
    {
        return Cache::remember("alwaseet_regions_{$cityId}", now()->addHours(24), function () use ($cityId, $retry) {
            $token = $this->getToken();

            try {
                $url = "{$this->baseUrl}/regions?token=" . $this->encodeTokenForUrl($token) . "&city_id=" . urlencode($cityId);

                $headers = [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ];

                // إضافة X-Forwarded-For إذا كان متوفر
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
                }

                // إضافة token في header
                $headers[] = 'Authorization: Bearer ' . $token;
                $headers[] = 'X-Auth-Token: ' . $token;

                // Logging شامل قبل الإرسال
                $this->logRequestDetails('GET', $url, $headers, [], $token);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);

                // الحصول على معلومات curl بعد التنفيذ
                $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
                $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
                $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

                // فصل response headers من body
                $responseHeaders = '';
                if ($responseHeadersSize > 0) {
                    $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                    $responseBody = substr($responseBody, $responseHeadersSize);
                }

                curl_close($ch);

                // Logging شامل بعد التنفيذ
                Log::info('AlWaseetService: getRegions response details', [
                    'http_code' => $httpCode,
                    'local_ip' => $localIp,
                    'primary_ip' => $primaryIp,
                    'response_headers_size' => $responseHeadersSize,
                    'response_headers_preview' => substr($responseHeaders, 0, 200),
                    'response_body_preview' => substr($responseBody, 0, 200),
                    'error' => $error ?: null,
                ]);

                if ($error) {
                    throw new \Exception('خطأ في الاتصال: ' . $error);
                }

                if ($httpCode !== 200) {
                    $data = json_decode($responseBody, true);
                    $errNum = $data['errNum'] ?? null;

                    // معالجة خطأ الصلاحية (errNum: 21)
                    if ($httpCode === 400 && $errNum == 21 && $retry) {
                        Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                            'method' => 'getRegions',
                        ]);
                        $this->refreshTokenIfNeeded();
                        // إعادة المحاولة مرة واحدة فقط
                        Cache::forget("alwaseet_regions_{$cityId}");
                        return $this->getRegions($cityId, false);
                    }

                    Log::error('AlWaseetService: HTTP request failed', [
                        'url' => $url,
                        'status' => $httpCode,
                        'errNum' => $errNum,
                        'body' => $responseBody,
                    ]);

                    // استخدام الرسالة الأصلية من API الواسط
                    $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                    throw new \Exception($errorMsg);
                }

                $data = json_decode($responseBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('فشل تحليل الاستجابة: ' . json_last_error_msg());
                }

                if (!isset($data['status'])) {
                    throw new \Exception('استجابة غير صحيحة من الواسط');
                }

                if ($data['status'] === true) {
                    return $data['data'] ?? [];
                }

                throw new \Exception($data['msg'] ?? 'فشل جلب المناطق');
            } catch (\Exception $e) {
                Log::error('AlWaseetService: Get regions failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Get package sizes
     */
    public function getPackageSizes($retry = true)
    {
        return Cache::remember('alwaseet_package_sizes', now()->addHours(24), function () use ($retry) {
            $token = $this->getToken();

            try {
                $url = "{$this->baseUrl}/package-sizes?token=" . $this->encodeTokenForUrl($token);

                $headers = [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ];

                // إضافة X-Forwarded-For إذا كان متوفر
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
                }

                // إضافة token في header
                $headers[] = 'Authorization: Bearer ' . $token;
                $headers[] = 'X-Auth-Token: ' . $token;

                // Logging شامل قبل الإرسال
                $this->logRequestDetails('GET', $url, $headers, [], $token);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);

                // الحصول على معلومات curl بعد التنفيذ
                $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
                $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
                $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

                // فصل response headers من body
                $responseHeaders = '';
                if ($responseHeadersSize > 0) {
                    $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                    $responseBody = substr($responseBody, $responseHeadersSize);
                }

                curl_close($ch);

                // Logging شامل بعد التنفيذ
                Log::info('AlWaseetService: getPackageSizes response details', [
                    'http_code' => $httpCode,
                    'local_ip' => $localIp,
                    'primary_ip' => $primaryIp,
                    'response_headers_size' => $responseHeadersSize,
                    'response_headers_preview' => substr($responseHeaders, 0, 200),
                    'response_body_preview' => substr($responseBody, 0, 200),
                    'error' => $error ?: null,
                ]);

                if ($error) {
                    throw new \Exception('خطأ في الاتصال: ' . $error);
                }

                if ($httpCode !== 200) {
                    $data = json_decode($responseBody, true);
                    $errNum = $data['errNum'] ?? null;

                    // معالجة خطأ الصلاحية (errNum: 21)
                    if ($httpCode === 400 && $errNum == 21 && $retry) {
                        Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                            'method' => 'getPackageSizes',
                        ]);
                        $this->refreshTokenIfNeeded();
                        // إعادة المحاولة مرة واحدة فقط
                        Cache::forget('alwaseet_package_sizes');
                        return $this->getPackageSizes(false);
                    }

                    Log::error('AlWaseetService: HTTP request failed', [
                        'url' => $url,
                        'status' => $httpCode,
                        'errNum' => $errNum,
                        'body' => $responseBody,
                    ]);

                    // استخدام الرسالة الأصلية من API الواسط
                    $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                    throw new \Exception($errorMsg);
                }

                $data = json_decode($responseBody, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('فشل تحليل الاستجابة: ' . json_last_error_msg());
                }

                if (!isset($data['status'])) {
                    throw new \Exception('استجابة غير صحيحة من الواسط');
                }

                if ($data['status'] === true) {
                    return $data['data'] ?? [];
                }

                throw new \Exception($data['msg'] ?? 'فشل جلب أحجام الطرود');
            } catch (\Exception $e) {
                Log::error('AlWaseetService: Get package sizes failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
    }

    /**
     * Create a new order
     */
    public function createOrder(array $orderData, $retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token
        $this->ensureMerchantToken($token, 'Create Order');

        try {
            // تنظيف الـ token من المسافات فقط (يجب أن يبقى @@ في البداية)
            $token = trim($token);

            $url = "{$this->baseUrl}/create-order?token=" . $this->encodeTokenForUrl($token);

            // إضافة token في form data أيضاً (بعض APIs تتطلب ذلك)
            $orderDataWithToken = array_merge($orderData, ['token' => $token]);

            $headers = [
                'User-Agent' => 'Laravel-AlWaseet-Integration/1.0',
            ];

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('POST', $url, $headers, $orderDataWithToken, $token);

            $response = Http::asMultipart()
                ->withHeaders($headers)
                ->post($url, $orderDataWithToken);

            // Logging response headers
            $responseHeaders = $response->headers();
            Log::info('AlWaseetService: Create order response headers', [
                'headers' => $responseHeaders,
            ]);

            $responseBody = $response->body();
            $responseData = $response->json();

            Log::info('AlWaseetService: Create order response', [
                'status' => $response->status(),
                'errNum' => $responseData['errNum'] ?? null,
                'msg' => $responseData['msg'] ?? null,
                'body_preview' => substr($responseBody, 0, 200),
            ]);

            if (!$response->successful()) {
                $errNum = $responseData['errNum'] ?? null;
                $errorMsg = $responseData['msg'] ?? 'فشل إنشاء الطلب: ' . $response->status();

                // تسجيل تفاصيل أكثر في logs (مع token كامل للتحقق)
                Log::error('AlWaseetService: Create order failed', [
                    'url' => str_replace($token, substr($token, 0, 15) . '...', $url),
                    'status' => $response->status(),
                    'errNum' => $errNum,
                    'msg' => $errorMsg,
                    'token_full' => $token, // تسجيل token كامل في logs للتحقق
                    'token_length' => strlen($token),
                    'token_starts_with' => substr($token, 0, 2),
                    'body' => $responseBody,
                    'order_data' => $orderData,
                ]);

                // إذا كان الخطأ بسبب صلاحية (errNum: 21) ويمكن إعادة المحاولة
                if ($response->status() === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                        'old_token_preview' => substr($token, 0, 15) . '...',
                        'old_token_full' => $token, // تسجيل token كامل
                    ]);
                    // إعادة تسجيل الدخول
                    $newToken = $this->refreshTokenIfNeeded();
                    Log::info('AlWaseetService: Token refreshed, retrying...', [
                        'new_token_preview' => substr($newToken, 0, 15) . '...',
                        'new_token_full' => $newToken, // تسجيل token كامل
                    ]);
                    // إعادة المحاولة مرة واحدة فقط
                    return $this->createOrder($orderData, false);
                }

                // استخدام الرسالة الأصلية من API الواسط
                throw new \Exception($errorMsg);
            }

            $data = $response->json();

            if (!isset($data['status'])) {
                Log::error('AlWaseetService: Invalid response format', [
                    'response' => $responseBody,
                ]);
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                Log::info('AlWaseetService: Order created successfully', [
                    'order_id' => $data['data']['id'] ?? null,
                    'qr_id' => $data['data']['qr_id'] ?? null,
                    'qr_link' => $data['data']['qr_link'] ?? null,
                ]);
                return $data['data'] ?? [];
            }

            // استخدام الرسالة الأصلية من API الواسط
            $errorMsg = $data['msg'] ?? 'فشل إنشاء الطلب';
            $errNum = $data['errNum'] ?? null;

            Log::error('AlWaseetService: Create order failed - API returned false', [
                'errNum' => $errNum,
                'msg' => $errorMsg,
                'response' => $data,
            ]);

            throw new \Exception($errorMsg);
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Create order exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'order_data' => $orderData,
            ]);
            throw $e;
        }
    }

    /**
     * Edit an existing order
     * @param string $qrId The QR ID of the order (required in body according to API docs)
     * @param array $orderData Order data to update
     * @param bool $retry Whether to retry on token expiration
     */
    public function editOrder($qrId, array $orderData, $retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token
        $this->ensureMerchantToken($token, 'Edit Order');

        try {
            // تنظيف الـ token من المسافات فقط (يجب أن يبقى @@ في البداية)
            $token = trim($token);

            // حسب الدوكمنت: qr_id يجب أن يكون في body وليس في URL
            $url = "{$this->baseUrl}/edit-order?token=" . $this->encodeTokenForUrl($token);

            // إضافة qr_id إلى body
            $orderData['qr_id'] = (string) $qrId;

            $headers = [
                'User-Agent' => 'Laravel-AlWaseet-Integration/1.0',
            ];

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('POST', $url, $headers, $orderData, $token);

            $response = Http::asMultipart()
                ->withHeaders($headers)
                ->post($url, $orderData);

            // Logging response headers
            $responseHeaders = $response->headers();
            Log::info('AlWaseetService: Edit order response headers', [
                'headers' => $responseHeaders,
            ]);

            if (!$response->successful()) {
                $responseData = $response->json();
                $errNum = $responseData['errNum'] ?? null;

                Log::error('AlWaseetService: Edit order failed', [
                    'url' => str_replace($token, substr($token, 0, 10) . '...', $url),
                    'status' => $response->status(),
                    'errNum' => $errNum,
                    'body' => $response->body(),
                ]);

                // إذا كان الخطأ بسبب صلاحية (errNum: 21) ويمكن إعادة المحاولة
                if ($response->status() === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired, refreshing...');
                    // إعادة تسجيل الدخول
                    $this->refreshTokenIfNeeded();
                    // إعادة المحاولة مرة واحدة فقط
                    return $this->editOrder($qrId, $orderData, false);
                }

                $errorMsg = $responseData['msg'] ?? 'فشل تعديل الطلب: ' . $response->status();
                throw new \Exception($errorMsg);
            }

            $data = $response->json();

            if (!isset($data['status'])) {
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                Log::info('AlWaseetService: Order edited successfully', [
                    'qr_id' => $qrId,
                ]);
                return $data['data'] ?? [];
            }

            throw new \Exception($data['msg'] ?? 'فشل تعديل الطلب');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Edit order failed', [
                'error' => $e->getMessage(),
                'qr_id' => $qrId,
                'order_data' => $orderData,
            ]);
            throw $e;
        }
    }

    /**
     * Get merchant invoices
     */
    public function getMerchantInvoices($retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token (مطلوب حسب الدوكمنت)
        $this->ensureMerchantToken($token, 'Get Merchant Invoices');

        try {
            $url = "{$this->baseUrl}/get_merchant_invoices?token=" . $this->encodeTokenForUrl($token);

            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];

            // إضافة X-Forwarded-For إذا كان متوفر
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
            }

            // إضافة token في header
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'X-Auth-Token: ' . $token;

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('GET', $url, $headers, [], $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // الحصول على معلومات curl بعد التنفيذ
            $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
            $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            // فصل response headers من body
            $responseHeaders = '';
            if ($responseHeadersSize > 0) {
                $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                $responseBody = substr($responseBody, $responseHeadersSize);
            }

            curl_close($ch);

            // Logging شامل بعد التنفيذ
            Log::info('AlWaseetService: getMerchantInvoices response details', [
                'http_code' => $httpCode,
                'local_ip' => $localIp,
                'primary_ip' => $primaryIp,
                'response_headers_size' => $responseHeadersSize,
                'response_headers_preview' => substr($responseHeaders, 0, 200),
                'response_body_preview' => substr($responseBody, 0, 200),
                'error' => $error ?: null,
            ]);

            if ($error) {
                throw new \Exception('خطأ في الاتصال: ' . $error);
            }

            if ($httpCode !== 200) {
                $data = json_decode($responseBody, true);
                $errNum = $data['errNum'] ?? null;

                // معالجة خطأ الصلاحية (errNum: 21)
                if ($httpCode === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                        'method' => 'getMerchantInvoices',
                    ]);
                    $this->refreshTokenIfNeeded();
                    // إعادة المحاولة مرة واحدة فقط
                    return $this->getMerchantInvoices(false);
                }

                Log::error('AlWaseetService: HTTP request failed', [
                    'url' => $url,
                    'status' => $httpCode,
                    'errNum' => $errNum,
                    'body' => $responseBody,
                ]);

                // استخدام الرسالة الأصلية من API الواسط
                $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                throw new \Exception($errorMsg);
            }

            $data = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('فشل تحليل الاستجابة: ' . json_last_error_msg());
            }

            if (!isset($data['status'])) {
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                return $data['data'] ?? [];
            }

            throw new \Exception($data['msg'] ?? 'فشل جلب الفواتير');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Get merchant invoices failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get orders for an invoice
     */
    public function getInvoiceOrders($invoiceId, $retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token (مطلوب حسب الدوكمنت)
        $this->ensureMerchantToken($token, 'Get Invoice Orders');

        try {
            $url = "{$this->baseUrl}/get_merchant_invoice_orders?token=" . $this->encodeTokenForUrl($token) . "&invoice_id=" . urlencode($invoiceId);

            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];

            // إضافة X-Forwarded-For إذا كان متوفر
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
            }

            // إضافة token في header
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'X-Auth-Token: ' . $token;

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('GET', $url, $headers, [], $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // الحصول على معلومات curl بعد التنفيذ
            $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
            $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            // فصل response headers من body
            $responseHeaders = '';
            if ($responseHeadersSize > 0) {
                $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                $responseBody = substr($responseBody, $responseHeadersSize);
            }

            curl_close($ch);

            // Logging شامل بعد التنفيذ
            Log::info('AlWaseetService: getInvoiceOrders response details', [
                'http_code' => $httpCode,
                'local_ip' => $localIp,
                'primary_ip' => $primaryIp,
                'response_headers_size' => $responseHeadersSize,
                'response_headers_preview' => substr($responseHeaders, 0, 200),
                'response_body_preview' => substr($responseBody, 0, 200),
                'error' => $error ?: null,
            ]);

            if ($error) {
                throw new \Exception('خطأ في الاتصال: ' . $error);
            }

            if ($httpCode !== 200) {
                $data = json_decode($responseBody, true);
                $errNum = $data['errNum'] ?? null;

                // معالجة خطأ الصلاحية (errNum: 21)
                if ($httpCode === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                        'method' => 'getInvoiceOrders',
                    ]);
                    $this->refreshTokenIfNeeded();
                    // إعادة المحاولة مرة واحدة فقط
                    return $this->getInvoiceOrders($invoiceId, false);
                }

                Log::error('AlWaseetService: HTTP request failed', [
                    'url' => $url,
                    'status' => $httpCode,
                    'errNum' => $errNum,
                    'body' => $responseBody,
                ]);

                // استخدام الرسالة الأصلية من API الواسط
                $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                throw new \Exception($errorMsg);
            }

            $data = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('فشل تحليل الاستجابة: ' . json_last_error_msg());
            }

            if (!isset($data['status'])) {
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                return $data['data'] ?? [];
            }

            throw new \Exception($data['msg'] ?? 'فشل جلب طلبات الفاتورة');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Get invoice orders failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoiceId,
            ]);
            throw $e;
        }
    }

    /**
     * Receive/confirm an invoice
     */
    public function receiveInvoice($invoiceId, $retry = true)
    {
        $token = $this->getToken();

        // التحقق من أن الـ token هو Merchant token (مطلوب حسب الدوكمنت)
        $this->ensureMerchantToken($token, 'Receive Invoice');

        try {
            $url = "{$this->baseUrl}/receive_merchant_invoice?token=" . $this->encodeTokenForUrl($token) . "&invoice_id=" . urlencode($invoiceId);

            $headers = [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ];

            // إضافة X-Forwarded-For إذا كان متوفر
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $headers[] = 'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR'];
            }

            // إضافة token في header
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'X-Auth-Token: ' . $token;

            // Logging شامل قبل الإرسال
            $this->logRequestDetails('GET', $url, $headers, [], $token);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على response headers

            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);

            // الحصول على معلومات curl بعد التنفيذ
            $localIp = curl_getinfo($ch, CURLINFO_LOCAL_IP) ?? 'unknown';
            $primaryIp = curl_getinfo($ch, CURLINFO_PRIMARY_IP) ?? 'unknown';
            $responseHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            // فصل response headers من body
            $responseHeaders = '';
            if ($responseHeadersSize > 0) {
                $responseHeaders = substr($responseBody, 0, $responseHeadersSize);
                $responseBody = substr($responseBody, $responseHeadersSize);
            }

            curl_close($ch);

            // Logging شامل بعد التنفيذ
            Log::info('AlWaseetService: receiveInvoice response details', [
                'http_code' => $httpCode,
                'local_ip' => $localIp,
                'primary_ip' => $primaryIp,
                'response_headers_size' => $responseHeadersSize,
                'response_headers_preview' => substr($responseHeaders, 0, 200),
                'response_body_preview' => substr($responseBody, 0, 200),
                'error' => $error ?: null,
            ]);

            if ($error) {
                throw new \Exception('خطأ في الاتصال: ' . $error);
            }

            if ($httpCode !== 200) {
                $data = json_decode($responseBody, true);
                $errNum = $data['errNum'] ?? null;

                // معالجة خطأ الصلاحية (errNum: 21)
                if ($httpCode === 400 && $errNum == 21 && $retry) {
                    Log::warning('AlWaseetService: Token expired (errNum: 21), refreshing...', [
                        'method' => 'receiveInvoice',
                    ]);
                    $this->refreshTokenIfNeeded();
                    // إعادة المحاولة مرة واحدة فقط
                    return $this->receiveInvoice($invoiceId, false);
                }

                Log::error('AlWaseetService: HTTP request failed', [
                    'url' => $url,
                    'status' => $httpCode,
                    'errNum' => $errNum,
                    'body' => $responseBody,
                ]);

                // استخدام الرسالة الأصلية من API الواسط
                $errorMsg = $data['msg'] ?? 'فشل الاتصال: ' . $httpCode;

                throw new \Exception($errorMsg);
            }

            $data = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('فشل تحليل الاستجابة: ' . json_last_error_msg());
            }

            if (!isset($data['status'])) {
                throw new \Exception('استجابة غير صحيحة من الواسط');
            }

            if ($data['status'] === true) {
                return true;
            }

            throw new \Exception($data['msg'] ?? 'فشل تأكيد استلام الفاتورة');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Receive invoice failed', [
                'error' => $e->getMessage(),
                'invoice_id' => $invoiceId,
            ]);
            throw $e;
        }
    }

    /**
     * Clear cached token
     */
    public function clearToken()
    {
        Cache::forget($this->tokenCacheKey);
        Cache::forget($this->tokenExpiryCacheKey);
        Setting::setValue('alwaseet_token', null);
    }

    /**
     * تحميل PDF للإيصال من qr_link
     */
    public function downloadReceiptPdf($qrLink)
    {
        try {
            // الحصول على token
            $token = $this->getToken();

            // إضافة token إلى URL إذا لم يكن موجوداً
            $url = $qrLink;
            if (strpos($qrLink, 'token=') === false) {
                $separator = strpos($qrLink, '?') !== false ? '&' : '?';
                $url = $qrLink . $separator . 'token=' . $this->encodeTokenForUrl($token);
            }

            // تحميل PDF من الرابط
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true); // للحصول على headers

            // إضافة headers
            $headers = [
                'User-Agent: Laravel-AlWaseet-Integration/1.0',
                'Accept: application/pdf',
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            if ($error) {
                throw new \Exception('خطأ في الاتصال: ' . $error);
            }

            if ($httpCode !== 200) {
                Log::error('AlWaseetService: PDF download failed', [
                    'http_code' => $httpCode,
                    'url' => $url,
                    'response_preview' => substr($response, 0, 200),
                ]);
                throw new \Exception('فشل تحميل PDF: ' . $httpCode);
            }

            // فصل headers من body
            $responseHeaders = substr($response, 0, $headerSize);
            $pdfContent = substr($response, $headerSize);

            if (empty($pdfContent)) {
                throw new \Exception('الملف فارغ');
            }

            // التحقق من أن المحتوى هو PDF وليس HTML
            $contentType = $this->extractContentType($responseHeaders);

            // إذا كان HTML، قد يكون خطأ أو صفحة login
            if (
                stripos($contentType, 'text/html') !== false ||
                stripos($pdfContent, '<html') !== false ||
                stripos($pdfContent, '<!DOCTYPE') !== false
            ) {

                Log::error('AlWaseetService: Received HTML instead of PDF', [
                    'url' => $url,
                    'content_type' => $contentType,
                    'content_preview' => substr($pdfContent, 0, 500),
                ]);

                throw new \Exception('تم استلام HTML بدلاً من PDF. قد يكون الرابط غير صحيح أو يحتاج مصادقة.');
            }

            // التحقق من أن المحتوى يبدأ بـ PDF signature
            if (substr($pdfContent, 0, 4) !== '%PDF') {
                Log::warning('AlWaseetService: PDF content does not start with PDF signature', [
                    'url' => $url,
                    'content_preview' => substr($pdfContent, 0, 100),
                ]);
                // قد يكون PDF صحيح لكن بدون signature، نتابع
            }

            // إرجاع response للتحميل
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="receipt-' . time() . '.pdf"')
                ->header('Content-Length', strlen($pdfContent))
                ->header('Cache-Control', 'private, max-age=0, must-revalidate')
                ->header('Pragma', 'public');
        } catch (\Exception $e) {
            Log::error('AlWaseetService: Download receipt PDF failed', [
                'error' => $e->getMessage(),
                'qr_link' => $qrLink,
            ]);
            throw $e;
        }
    }

    /**
     * استخراج Content-Type من headers
     */
    protected function extractContentType($headers): string
    {
        if (preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $matches)) {
            return trim($matches[1]);
        }
        return 'application/octet-stream';
    }

    /**
     * دمج عدة PDFs في ملف واحد
     */
    public function mergePdfs(array $qrLinks): string
    {
        try {
            $pdf = new Fpdi();
            $mergedCount = 0;

            Log::info('AlWaseetService: Starting PDF merge', [
                'qr_links_count' => count($qrLinks),
            ]);

            foreach ($qrLinks as $qrLinkIndex => $qrLink) {
                if (empty($qrLink)) {
                    Log::warning('AlWaseetService: Empty qr_link skipped', ['index' => $qrLinkIndex]);
                    continue;
                }

                try {
                    // تحميل PDF من qr_link
                    // تنظيف qr_link من المسافات الزائدة
                    $qrLink = trim($qrLink);

                    $token = $this->getToken();
                    $url = $qrLink;
                    if (strpos($qrLink, 'token=') === false) {
                        $separator = strpos($qrLink, '?') !== false ? '&' : '?';
                        $url = $qrLink . $separator . 'token=' . $this->encodeTokenForUrl($token);
                    } else {
                        // إذا كان token موجود، تأكد من تنظيف URL من المسافات
                        $url = trim($url);
                    }

                    Log::info('AlWaseetService: Downloading PDF for merge', [
                        'index' => $qrLinkIndex,
                        'url' => $url,
                    ]);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Accept: application/pdf',
                        'User-Agent: Laravel-AlWaseet-Integration/1.0',
                    ]);

                    $pdfContent = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($curlError) {
                        Log::warning('AlWaseetService: cURL error downloading PDF', [
                            'qr_link' => $qrLink,
                            'index' => $qrLinkIndex,
                            'error' => $curlError,
                        ]);
                        continue;
                    }

                    if ($httpCode !== 200) {
                        Log::warning('AlWaseetService: HTTP error downloading PDF', [
                            'qr_link' => $qrLink,
                            'index' => $qrLinkIndex,
                            'http_code' => $httpCode,
                        ]);
                        continue;
                    }

                    if (empty($pdfContent)) {
                        Log::warning('AlWaseetService: Empty PDF content', [
                            'qr_link' => $qrLink,
                            'index' => $qrLinkIndex,
                        ]);
                        continue;
                    }

                    if (substr($pdfContent, 0, 4) !== '%PDF') {
                        Log::warning('AlWaseetService: Invalid PDF content (not starting with %PDF)', [
                            'qr_link' => $qrLink,
                            'index' => $qrLinkIndex,
                            'content_preview' => substr($pdfContent, 0, 100),
                        ]);
                        continue;
                    }

                    // حفظ PDF مؤقتاً
                    $tempFile = tempnam(sys_get_temp_dir(), 'alwaseet_pdf_');
                    file_put_contents($tempFile, $pdfContent);

                    try {
                        // حساب عدد الصفحات
                        $pageCount = $pdf->setSourceFile($tempFile);
                        Log::info('AlWaseetService: PDF loaded for merge', [
                            'index' => $qrLinkIndex,
                            'page_count' => $pageCount,
                            'file_size' => filesize($tempFile),
                        ]);

                        // إضافة كل صفحة
                        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                            $templateId = $pdf->importPage($pageNo);
                            $size = $pdf->getTemplateSize($templateId);

                            // تحديد اتجاه الصفحة
                            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                            $pdf->useTemplate($templateId);
                        }

                        $mergedCount++;
                        Log::info('AlWaseetService: PDF merged successfully', [
                            'index' => $qrLinkIndex,
                            'merged_count' => $mergedCount,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('AlWaseetService: Error processing PDF pages', [
                            'qr_link' => $qrLink,
                            'index' => $qrLinkIndex,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    } finally {
                        // حذف الملف المؤقت
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('AlWaseetService: Error merging PDF', [
                        'qr_link' => $qrLink,
                        'index' => $qrLinkIndex,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    continue;
                }
            }

            if ($mergedCount === 0) {
                Log::warning('AlWaseetService: No PDFs were merged', [
                    'qr_links_count' => count($qrLinks),
                ]);
                throw new \Exception('لم يتم دمج أي PDFs. قد تكون جميع الروابط غير صحيحة أو فارغة.');
            }

            Log::info('AlWaseetService: PDF merge completed', [
                'merged_count' => $mergedCount,
                'total_links' => count($qrLinks),
            ]);

            // التحقق من أن PDF يحتوي على صفحات
            try {
                // إرجاع المحتوى المدمج - استخدام 'S' للحصول على string
                $output = $pdf->Output('', 'S');

                if (empty($output)) {
                    Log::error('AlWaseetService: Output() returned empty string', [
                        'merged_count' => $mergedCount,
                    ]);
                    throw new \Exception('فشل إنشاء PDF المدمج: المحتوى فارغ');
                }

                // التحقق من أن المحتوى يبدأ بـ PDF signature
                if (substr($output, 0, 4) !== '%PDF') {
                    Log::error('AlWaseetService: Output() did not return valid PDF', [
                        'output_preview' => substr($output, 0, 100),
                    ]);
                    throw new \Exception('المحتوى المُعاد ليس PDF صالحاً');
                }

                Log::info('AlWaseetService: PDF output generated successfully', [
                    'output_length' => strlen($output),
                    'merged_count' => $mergedCount,
                ]);

                return $output;
            } catch (\Exception $e) {
                Log::error('AlWaseetService: Merge PDFs failed', [
                    'error' => $e->getMessage(),
                    'qr_links_count' => count($qrLinks),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }
    }

    /**
     * Format phone number to +964 format
     */
    public static function formatPhone($phone): string
    {
        if (empty($phone))
            return '';

        // إزالة المسافات والأرقام غير الرقمية (مع الحفاظ على +)
        $phone = preg_replace('/[^0-9]/', '', (string) $phone);

        // إذا كان يبدأ بـ 0، استبدله بـ 964
        if (strpos($phone, '0') === 0) {
            $phone = '964' . substr($phone, 1);
        }

        // التأكد من وجود 964 في البداية
        if (strpos($phone, '964') !== 0) {
            $phone = '964' . $phone;
        }

        return '+' . $phone;
    }
}

