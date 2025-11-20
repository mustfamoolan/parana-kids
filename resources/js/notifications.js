// نظام الإشعارات الشامل - يعمل من أي صفحة

class NotificationManager {
    constructor() {
        this.sseEventSource = null;
        this.notificationCallbacks = [];
        this.isInitialized = false;
        this.unreadCount = 0;
        this.pollingInterval = null;
        this.pollInterval = 5000; // 5 ثوان (تم تحسينه من 3 ثوان)
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
                    console.log('NotificationManager: SSE raw message:', event.data);
                    const data = JSON.parse(event.data);
                    console.log('NotificationManager: SSE parsed message:', data);

                    if (data.type === 'notification') {
                        console.log('NotificationManager: Notification received via SSE:', data);
                        const notification = data.data || data;
                        this.handleNotification(notification);
                    } else if (data.type === 'ping') {
                        // Ping - للحفاظ على الاتصال
                        console.log('NotificationManager: SSE ping received');
                    } else {
                        console.log('NotificationManager: Unknown message type:', data.type);
                    }
                } catch (error) {
                    console.error('NotificationManager: Error parsing SSE message:', error);
                    console.error('NotificationManager: Raw event data:', event.data);
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
        console.log('NotificationManager: showToastNotification called', notification);

        // انتظار حتى يكون SweetAlert متاحاً
        if (typeof window.Swal === 'undefined') {
            console.warn('NotificationManager: SweetAlert not available, waiting...');
            // محاولة مرة أخرى بعد 500ms
            setTimeout(() => {
                if (typeof window.Swal !== 'undefined') {
                    this.showToastNotification(notification);
                } else {
                    console.error('NotificationManager: SweetAlert still not available after wait');
                }
            }, 500);
            return;
        }

        const title = notification.title || 'رسالة جديدة';
        const body = notification.body || notification.message_text || notification.data?.message_text || 'لديك رسالة جديدة';
        const message = `${title}: ${body}`;

        console.log('NotificationManager: Showing toast:', message);

        try {
            const toast = window.Swal.mixin({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 5000,
                showCloseButton: true,
                didOpen: (toastEl) => {
                    console.log('NotificationManager: Toast opened');
                    toastEl.addEventListener('click', () => {
                        // الانتقال للمحادثة عند الضغط على Toast
                        if (notification.data?.conversation_id || notification.conversation_id) {
                            const convId = notification.data?.conversation_id || notification.conversation_id;
                            window.location.href = `/apps/chat?conversation=${convId}`;
                        }
                    });
                }
            });

            toast.fire({
                title: message,
                icon: 'info',
            }).then(() => {
                console.log('NotificationManager: Toast shown successfully');
            }).catch((error) => {
                console.error('NotificationManager: Error showing toast:', error);
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
            console.log('NotificationManager: Updating unread count...');
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
                console.log('NotificationManager: Unread count:', newCount, '(previous:', this.unreadCount, ')');

                if (this.unreadCount !== newCount) {
                    this.unreadCount = newCount;

                    // تحديث Badge counter
                    this.updateBadge(newCount);

                    // إرسال event لتحديث Dashboard
                    console.log('NotificationManager: Dispatching unreadCountUpdated event with count:', newCount);
                    window.dispatchEvent(new CustomEvent('unreadCountUpdated', {
                        detail: { count: newCount }
                    }));
                }
            } else {
                console.error('NotificationManager: Failed to fetch unread count, status:', response.status);
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
                    console.log('NotificationManager: Badge updated to', count);
                } else {
                    await navigator.clearAppBadge();
                    console.log('NotificationManager: Badge cleared');
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

    // Test notification بعد 2 ثوان للتحقق من عمل النظام
    setTimeout(() => {
        console.log('NotificationManager: Testing notification system...');
        if (typeof window.Swal !== 'undefined') {
            console.log('NotificationManager: SweetAlert is available');
            // Test toast
            window.showMessage('نظام الإشعارات يعمل!', 'top');
        } else {
            console.error('NotificationManager: SweetAlert is NOT available');
        }
    }, 2000);
});

// إغلاق SSE عند إغلاق الصفحة
window.addEventListener('beforeunload', () => {
    if (window.notificationManager) {
        window.notificationManager.close();
    }
});

