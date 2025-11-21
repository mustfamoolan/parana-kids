// نظام الإشعارات الشامل - يعتمد على Firebase FCM فقط (أخف على النظام)

class NotificationManager {
    constructor() {
        this.notificationCallbacks = [];
        this.unreadCount = 0;
        this.pollingInterval = null;
        this.pollInterval = 30000; // 30 ثانية (محسّن للأداء - FCM يعمل push)
        this.firebaseMessaging = null;
    }

    /**
     * تهيئة Firebase Messaging
     */
    async initFirebase() {
        console.log('NotificationManager: initFirebase() called');

        // التحقق من وجود المستخدم المسجل دخوله
        if (!window.authUserId) {
            console.log('NotificationManager: User not authenticated, skipping Firebase initialization');
            return;
        }

        // التحقق من أن Firebase متاح
        if (typeof firebase === 'undefined' || !firebase.messaging) {
            console.log('NotificationManager: Firebase not available (will use Web Push only)');
            return;
        }

        try {
            // طلب إذن الإشعارات قبل محاولة الحصول على FCM token (مهم جداً!)
            console.log('NotificationManager: Requesting notification permission...');
            const permission = await this.requestPermission();

            if (!permission) {
                console.warn('NotificationManager: Notification permission not granted, FCM token may not be available');
                // نستمر في المحاولة لأن بعض المتصفحات قد تعطي token حتى بدون إذن
            } else {
                console.log('NotificationManager: Notification permission granted');
            }

            // تهيئة Firebase App إذا لم يكن مهيأ
            if (!firebase.apps || firebase.apps.length === 0) {
                console.log('NotificationManager: Initializing Firebase app...');
                const firebaseConfig = {
                    apiKey: 'AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU',
                    authDomain: 'parana-kids.firebaseapp.com',
                    projectId: 'parana-kids',
                    storageBucket: 'parana-kids.firebasestorage.app',
                    messagingSenderId: '130151352064',
                    appId: '1:130151352064:web:42335c43d67f4ac49515e5',
                    measurementId: 'G-HCTDLM0P9Y',
                };
                firebase.initializeApp(firebaseConfig);
                console.log('NotificationManager: Firebase app initialized');
            }

            // تهيئة Firebase Messaging
            console.log('NotificationManager: Initializing Firebase Messaging...');
            this.firebaseMessaging = firebase.messaging();

            // الحصول على FCM token مع retry mechanism
            let token = null;
            let retryCount = 0;
            const maxRetries = 3;

            while (!token && retryCount < maxRetries) {
                try {
                    console.log(`NotificationManager: Attempting to get FCM token (attempt ${retryCount + 1}/${maxRetries})...`);
                    token = await this.firebaseMessaging.getToken({
                        vapidKey: 'BET5Odck6WkOyun9SwgVCQjxpVcCi7o0WMCyu1vJbsX9K8kdNV-DGM-THOdKWBcXIYvo5rTH4E3cKX2LNmLGYX0'
                    });

                    if (token) {
                        console.log('NotificationManager: FCM token obtained successfully');
                        break;
                    } else {
                        console.warn('NotificationManager: FCM token is null');
                    }
                } catch (tokenError) {
                    console.error(`NotificationManager: Error getting FCM token (attempt ${retryCount + 1}):`, tokenError);
                    retryCount++;

                    if (retryCount < maxRetries) {
                        console.log(`NotificationManager: Retrying in 3 seconds...`);
                        await new Promise(resolve => setTimeout(resolve, 3000));
                    }
                }
            }

            if (token) {
                // حفظ token في قاعدة البيانات
                console.log('NotificationManager: Saving FCM token to database...');
                await this.saveFcmToken(token);
                console.log('NotificationManager: FCM token obtained and saved successfully');
            } else {
                console.error('NotificationManager: Failed to get FCM token after all retries');
            }

            // معالجة الإشعارات عند فتح التطبيق (foreground messages)
            // ملاحظة: onBackgroundMessage موجود في Service Worker
            this.firebaseMessaging.onMessage((payload) => {
                console.log('NotificationManager: Foreground message received:', payload);
                // معالجة الإشعار في المقدمة
                const notification = payload.data || payload.notification || payload;
                this.handleNotification(notification);
            });

            console.log('NotificationManager: Firebase initialized successfully');
        } catch (error) {
            console.error('NotificationManager: Error initializing Firebase:', error);
            console.error('NotificationManager: Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
        }
    }

    /**
     * حفظ FCM token في قاعدة البيانات
     */
    async saveFcmToken(token) {
        // التحقق من صحة token
        if (!token || typeof token !== 'string' || token.length < 10) {
            console.error('NotificationManager: Invalid FCM token provided');
            return false;
        }

        // التحقق من CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!csrfToken) {
            console.warn('NotificationManager: CSRF token not found, request may fail');
        }

        let retryCount = 0;
        const maxRetries = 3;

        while (retryCount < maxRetries) {
            try {
                console.log(`NotificationManager: Attempting to save FCM token (attempt ${retryCount + 1}/${maxRetries})...`);

                const response = await fetch('/api/fcm/token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ token: token })
                });

                console.log('NotificationManager: Response status:', response.status);

                if (response.ok) {
                    const data = await response.json();
                    console.log('NotificationManager: FCM token saved successfully', data);
                    return true;
                } else {
                    const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
                    console.error('NotificationManager: Failed to save FCM token', {
                        status: response.status,
                        statusText: response.statusText,
                        error: errorData
                    });

                    retryCount++;
                    if (retryCount < maxRetries) {
                        console.log(`NotificationManager: Retrying in 2 seconds...`);
                        await new Promise(resolve => setTimeout(resolve, 2000));
                    }
                }
            } catch (error) {
                console.error(`NotificationManager: Error saving FCM token (attempt ${retryCount + 1}):`, error);
                retryCount++;

                if (retryCount < maxRetries) {
                    console.log(`NotificationManager: Retrying in 2 seconds...`);
                    await new Promise(resolve => setTimeout(resolve, 2000));
                }
            }
        }

        console.error('NotificationManager: Failed to save FCM token after all retries');
        return false;
    }

    /**
     * معالجة الإشعار الوارد (من Firebase أو Web Push)
     */
    handleNotification(notification) {
        console.log('NotificationManager: Handling notification:', notification);

        // عرض Toast Notification فوراً (إذا كانت الصفحة مفتوحة)
        if (!document.hidden) {
            this.showToastNotification(notification);
        }

        // تحديث عدد الإشعارات غير المقروءة
        this.updateUnreadCount();

        // تحديث Badge counter
        this.updateBadge(this.unreadCount + 1);

        // إرسال custom event لتحديث Dashboard
        this.dispatchNotificationEvent(notification);

        // استدعاء callbacks
        this.notificationCallbacks.forEach(callback => {
            try {
                callback(notification);
            } catch (error) {
                console.error('NotificationManager: Error in notification callback:', error);
            }
        });
    }

    /**
     * عرض Toast Notification باستخدام SweetAlert
     * يعمل لجميع أنواع الإشعارات: رسائل، طلبات، تحديثات حالة
     */
    showToastNotification(notification) {
        console.log('NotificationManager: showToastNotification called', notification);

        // انتظار حتى يكون SweetAlert متاحاً
        if (typeof window.Swal === 'undefined') {
            console.warn('NotificationManager: SweetAlert not available, waiting...');
            setTimeout(() => {
                if (typeof window.Swal !== 'undefined') {
                    this.showToastNotification(notification);
                } else {
                    console.error('NotificationManager: SweetAlert still not available after wait');
                }
            }, 500);
            return;
        }

        // تحديد نوع الإشعار والرابط المناسب
        const notificationType = notification.type || notification.data?.type || 'message';
        const title = notification.title || notification.data?.title || 'إشعار جديد';
        const body = notification.body || notification.message || notification.message_text || notification.data?.message_text || notification.data?.body || 'لديك إشعار جديد';

        // تحديد الرابط حسب نوع الإشعار
        let clickUrl = null;
        let iconType = 'info';

        if (notificationType === 'message' || notification.data?.conversation_id || notification.conversation_id) {
            // رسالة جديدة
            const convId = notification.data?.conversation_id || notification.conversation_id;
            if (convId) {
                clickUrl = `/apps/chat?conversation=${convId}`;
            } else {
                clickUrl = '/apps/chat';
            }
            iconType = 'info';
        } else if (notificationType.startsWith('order_') || notification.data?.order_id || notification.order_id) {
            // جميع أنواع إشعارات الطلبات
            const orderId = notification.data?.order_id || notification.order_id;
            if (orderId) {
                if (window.location.pathname.includes('/admin/')) {
                    clickUrl = `/admin/orders/${orderId}/process`;
                } else if (window.location.pathname.includes('/delegate/')) {
                    clickUrl = `/delegate/orders/${orderId}`;
                } else {
                    clickUrl = `/delegate/orders/${orderId}`;
                }
            } else {
                if (window.location.pathname.includes('/admin/')) {
                    clickUrl = '/admin/orders';
                } else {
                    clickUrl = '/delegate/orders';
                }
            }
            // تحديد أيقونة حسب نوع الطلب
            if (notificationType === 'order_created') {
                iconType = 'success';
            } else if (notificationType === 'order_cancelled' || notificationType === 'order_returned') {
                iconType = 'error';
            } else {
                iconType = 'warning';
            }
        } else {
            // أنواع أخرى من الإشعارات
            iconType = 'info';
        }

        const message = body.length > 100 ? body.substring(0, 100) + '...' : body;

        try {
            const toast = window.Swal.mixin({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 6000,
                showCloseButton: true,
                didOpen: (toastEl) => {
                    if (clickUrl) {
                        toastEl.style.cursor = 'pointer';
                        toastEl.addEventListener('click', () => {
                            window.location.href = clickUrl;
                        });
                    }
                }
            });

            toast.fire({
                title: `<strong>${title}</strong><br><small>${message}</small>`,
                icon: iconType,
                html: true,
            });
        } catch (error) {
            console.error('NotificationManager: Exception in showToastNotification:', error);
        }
    }

    /**
     * تحديث عدد الإشعارات غير المقروءة
     */
    async updateUnreadCount() {
        try {
            const response = await fetch('/api/notifications/unread-count', {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                const newCount = data.count || 0;

                if (this.unreadCount !== newCount) {
                    this.unreadCount = newCount;
                    this.updateBadge(newCount);
                    window.dispatchEvent(new CustomEvent('unreadCountUpdated', {
                        detail: { count: newCount }
                    }));
                }
            }
        } catch (error) {
            console.error('NotificationManager: Error updating unread count:', error);
        }
    }

    /**
     * تحديث Badge counter
     */
    async updateBadge(count) {
        if ('setAppBadge' in navigator) {
            try {
                if (count > 0) {
                    await navigator.setAppBadge(count);
                } else {
                    await navigator.clearAppBadge();
                }

                // إرسال رسالة إلى Service Worker لتحديث Badge
                if ('serviceWorker' in navigator) {
                    navigator.serviceWorker.ready.then(registration => {
                        if (registration.active) {
                            registration.active.postMessage({
                                type: 'UPDATE_BADGE',
                                count: count
                            });
                        }
                    });
                }
            } catch (error) {
                console.error('NotificationManager: Error updating badge:', error);
            }
        }
    }

    /**
     * إرسال custom event لتحديث Dashboard
     */
    dispatchNotificationEvent(notification) {
        window.dispatchEvent(new CustomEvent('newNotification', {
            detail: notification
        }));
    }

    /**
     * بدء polling لتحديث عدد الإشعارات (خفيف - كل 30 ثانية)
     */
    startPolling() {
        if (this.pollingInterval) {
            return;
        }

        // تحديث فوري
        this.updateUnreadCount();

        // Polling كل 30 ثانية (خفيف - FCM يعمل push)
        this.pollingInterval = setInterval(() => {
            if (document.hidden) {
                return;
            }
            this.updateUnreadCount();
        }, this.pollInterval);

        console.log('NotificationManager: Polling started (lightweight)');
    }

    /**
     * إيقاف polling
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('NotificationManager: Polling stopped');
        }
    }

    /**
     * إضافة callback للإشعارات
     */
    onNotification(callback) {
        this.notificationCallbacks.push(callback);
    }

    /**
     * إزالة callback
     */
    offNotification(callback) {
        const index = this.notificationCallbacks.indexOf(callback);
        if (index > -1) {
            this.notificationCallbacks.splice(index, 1);
        }
    }

    /**
     * طلب إذن الإشعارات
     */
    async requestPermission() {
        if (!('Notification' in window)) {
            return false;
        }

        if (Notification.permission === 'granted') {
            return true;
        }

        if (Notification.permission === 'denied') {
            return false;
        }

        const permission = await Notification.requestPermission();
        return permission === 'granted';
    }

    /**
     * إغلاق (تنظيف)
     */
    close() {
        this.stopPolling();
    }
}

// إنشاء instance عام
window.notificationManager = new NotificationManager();

// إغلاق أي اتصالات SSE قديمة فوراً (للمتصفحات التي ما زالت تستخدم نسخة قديمة)
if (window.notificationManager && window.notificationManager.sseEventSource) {
    try {
        window.notificationManager.sseEventSource.close();
        window.notificationManager.sseEventSource = null;
        window.notificationManager.isInitialized = false;
        console.log('NotificationManager: Closed old SSE connection');
    } catch (e) {
        console.log('NotificationManager: Error closing old SSE connection:', e);
    }
}

// إغلاق أي EventSource مفتوحة على /api/sse/stream
if (typeof EventSource !== 'undefined') {
    // البحث عن أي EventSource مفتوحة وإغلاقها
    const closeAllSSE = () => {
        // لا يمكن الوصول مباشرة إلى EventSource instances، لكن يمكن محاولة إغلاقها
        // عبر إرسال رسالة إلى Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(registration => {
                if (registration.active) {
                    registration.active.postMessage({
                        type: 'CLOSE_SSE'
                    });
                }
            });
        }
    };
    closeAllSSE();
}

// تهيئة تلقائية عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', () => {
    // طلب إذن الإشعارات
    if ('Notification' in window && Notification.permission === 'default') {
        window.notificationManager.requestPermission().then(granted => {
            if (granted) {
                console.log('NotificationManager: Notification permission granted');
            }
        });
    }

    // تهيئة Firebase Messaging (إذا كان متاحاً)
    window.notificationManager.initFirebase();

    // بدء polling خفيف لتحديث عدد الإشعارات (كل 30 ثانية)
    window.notificationManager.startPolling();

    // إيقاف polling عند إخفاء الصفحة (تحسين الأداء)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            window.notificationManager.stopPolling();
        } else {
            window.notificationManager.startPolling();
        }
    });

    console.log('NotificationManager: Initialized successfully (Firebase-based)');
});

// تنظيف عند إغلاق الصفحة
window.addEventListener('beforeunload', () => {
    if (window.notificationManager) {
        window.notificationManager.close();
    }
});
