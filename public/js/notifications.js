// نظام الإشعارات الشامل - يعمل من أي صفحة

class NotificationManager {
    constructor() {
        this.sseEventSource = null;
        this.notificationCallbacks = [];
        this.isInitialized = false;
        this.unreadCount = 0;
        this.pollingInterval = null;
        this.pollInterval = 3000; // 3 ثوان
    }

    /**
     * تهيئة SSE من أي صفحة
     */
    initSSE() {
        if (this.isInitialized) {
            return;
        }

        // التحقق من وجود المستخدم المسجل دخوله
        if (!window.authUserId) {
            console.log('NotificationManager: User not authenticated, skipping SSE initialization');
            return;
        }

        // التحقق من دعم EventSource
        if (typeof EventSource === 'undefined') {
            console.warn('NotificationManager: EventSource not supported');
            return;
        }

        try {
            const sseUrl = '/api/sse/stream';
            console.log('NotificationManager: Connecting to SSE stream...', sseUrl);

            this.sseEventSource = new EventSource(sseUrl, {
                withCredentials: true
            });

            this.sseEventSource.onopen = () => {
                console.log('NotificationManager: SSE connection opened');
                this.isInitialized = true;
            };

            this.sseEventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log('NotificationManager: SSE message received:', data);

                    if (data.type === 'notification' && data.data) {
                        this.handleNotification(data.data);
                    } else if (data.type === 'ping') {
                        // Ping - للحفاظ على الاتصال
                        console.log('NotificationManager: SSE ping received');
                    }
                } catch (error) {
                    console.error('NotificationManager: Error parsing SSE message:', error);
                }
            };

            this.sseEventSource.onerror = (error) => {
                console.error('NotificationManager: SSE connection error:', error);

                // إعادة الاتصال بعد 5 ثوان
                setTimeout(() => {
                    if (this.sseEventSource && this.sseEventSource.readyState === EventSource.CLOSED) {
                        console.log('NotificationManager: Reconnecting to SSE...');
                        this.isInitialized = false;
                        this.initSSE();
                    }
                }, 5000);
            };
        } catch (error) {
            console.error('NotificationManager: Error connecting to SSE:', error);
        }
    }

    /**
     * معالجة الإشعار الوارد
     */
    handleNotification(notification) {
        console.log('NotificationManager: Handling notification:', notification);

        // عرض Toast Notification فوراً
        this.showToastNotification(notification);

        // إرسال الإشعار إلى Service Worker (للعمل حتى لو كان الموقع مغلق)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(registration => {
                if (registration.active) {
                    registration.active.postMessage({
                        type: 'SSE_NOTIFICATION',
                        notification: notification,
                    });
                    console.log('NotificationManager: Notification sent to service worker');
                }
            });
        }

        // عرض Push Notification إذا كان المستخدم ليس في الصفحة
        if (document.visibilityState === 'hidden') {
            this.showPushNotification(notification);
        }

        // تحديث عدد الإشعارات غير المقروءة فوراً
        this.updateUnreadCount();

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
     */
    showToastNotification(notification) {
        if (typeof window.Swal === 'undefined') {
            console.warn('NotificationManager: SweetAlert not available');
            return;
        }

        const title = notification.title || 'رسالة جديدة';
        const body = notification.body || notification.message_text || 'لديك رسالة جديدة';
        const message = `${title}: ${body}`;

        const toast = window.Swal.mixin({
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 5000,
            showCloseButton: true,
            didOpen: (toast) => {
                toast.addEventListener('click', () => {
                    // الانتقال للمحادثة عند الضغط على Toast
                    if (notification.data?.conversation_id) {
                        window.location.href = `/apps/chat?conversation=${notification.data.conversation_id}`;
                    }
                });
            }
        });

        toast.fire({
            title: message,
            icon: 'info',
        });
    }

    /**
     * تحديث عدد الإشعارات غير المقروءة
     */
    async updateUnreadCount() {
        try {
            const response = await fetch('/api/notifications/unread-count?type=message', {
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
                    // إرسال event لتحديث Dashboard
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
     * إرسال custom event لتحديث Dashboard
     */
    dispatchNotificationEvent(notification) {
        window.dispatchEvent(new CustomEvent('newNotification', {
            detail: notification
        }));
    }

    /**
     * بدء polling لتحديث عدد الإشعارات
     */
    startPolling() {
        if (this.pollingInterval) {
            return; // Polling يعمل بالفعل
        }

        // تحديث فوري
        this.updateUnreadCount();

        // Polling كل 3 ثوان
        this.pollingInterval = setInterval(() => {
            this.updateUnreadCount();
        }, this.pollInterval);

        console.log('NotificationManager: Polling started');
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
     * عرض Push Notification
     */
    showPushNotification(notification) {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'granted') {
            const title = notification.title || 'رسالة جديدة';
            const body = notification.body || notification.message_text || 'لديك رسالة جديدة';
            const icon = notification.icon || '/assets/images/icons/icon-192x192.png';

            const notificationOptions = {
                body: body,
                icon: icon,
                badge: '/assets/images/icons/icon-192x192.png',
                vibrate: [200, 100, 200],
                silent: false,
                dir: 'rtl',
                lang: 'ar',
                tag: `notification-${notification.data?.conversation_id || notification.id || 'new'}`,
                requireInteraction: false,
                data: notification.data || {},
                timestamp: Date.now(),
            };

            const notif = new Notification(title, notificationOptions);

            notif.onclick = () => {
                window.focus();
                // الانتقال للمحادثة إذا كان نوع الإشعار message
                if (notification.data?.conversation_id) {
                    window.location.href = `/chat?conversation=${notification.data.conversation_id}`;
                }
                notif.close();
            };

            // إغلاق الإشعار تلقائياً بعد 5 ثوان
            setTimeout(() => notif.close(), 5000);
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
     * إغلاق SSE connection
     */
    close() {
        if (this.sseEventSource) {
            this.sseEventSource.close();
            this.sseEventSource = null;
            this.isInitialized = false;
        }
        this.stopPolling();
    }
}

// إنشاء instance عام
window.notificationManager = new NotificationManager();

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

    // تهيئة SSE
    window.notificationManager.initSSE();

    // بدء polling لتحديث عدد الإشعارات
    window.notificationManager.startPolling();
});

// إغلاق SSE عند إغلاق الصفحة
window.addEventListener('beforeunload', () => {
    if (window.notificationManager) {
        window.notificationManager.close();
    }
});

