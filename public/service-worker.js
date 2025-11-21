// Service Worker لإشعارات PWA
const CACHE_NAME = 'parana-kids-v1';
const NOTIFICATION_POLL_INTERVAL = 30000; // 30 ثانية
let csrfToken = null;

// تثبيت Service Worker
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    self.skipWaiting();
});

// تفعيل Service Worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    event.waitUntil(self.clients.claim());
});

// استقبال رسائل من الصفحة
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'CHECK_NOTIFICATIONS') {
        checkForNotifications();
    } else if (event.data && event.data.type === 'SHOW_NOTIFICATION') {
        showNotification(event.data.alert);
    } else if (event.data && event.data.type === 'SET_CSRF_TOKEN') {
        csrfToken = event.data.token;
        console.log('Service Worker: CSRF token received');
    }
});

// التحقق من الإشعارات الجديدة
async function checkForNotifications() {
    try {
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }

        const response = await fetch('/api/sweet-alerts/unread', {
            method: 'GET',
            headers: headers,
            credentials: 'include',
        });

        if (!response.ok) {
            console.log('Service Worker: Failed to fetch notifications:', response.status);
            return;
        }

        const data = await response.json();
        if (data.success && data.alerts && data.alerts.length > 0) {
            // عرض إشعار لكل تنبيه جديد
            data.alerts.forEach(alert => {
                showNotification(alert);
            });
        }
    } catch (error) {
        console.error('Service Worker: Error checking notifications:', error);
    }
}

// عرض إشعار المتصفح
function showNotification(alert) {
    const notificationOptions = {
        body: alert.message,
        icon: '/assets/images/icons/icon-192x192.png',
        badge: '/assets/images/icons/icon-192x192.png',
        tag: `sweet-alert-${alert.id}`,
        requireInteraction: false,
        silent: false, // تشغيل الصوت الافتراضي
        data: {
            url: getNotificationUrl(alert),
            alertId: alert.id,
        },
        actions: [
            {
                action: 'view',
                title: 'عرض',
            },
            {
                action: 'close',
                title: 'إغلاق',
            },
        ],
    };

    self.registration.showNotification(alert.title, notificationOptions)
        .then(() => {
            console.log('Service Worker: Notification shown:', alert.id);
        })
        .catch((error) => {
            console.error('Service Worker: Error showing notification:', error);
        });
}

// الحصول على رابط الإشعار
function getNotificationUrl(alert) {
    if (alert.data && alert.data.action === 'view_order' && alert.data.order_id) {
        return `/admin/orders/${alert.data.order_id}/show`;
    } else if (alert.data && alert.data.action === 'view_message' && alert.data.conversation_id) {
        return `/apps/chat?conversation_id=${alert.data.conversation_id}`;
    }
    return '/';
}

// معالجة النقر على الإشعار
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'view' || !event.action) {
        const url = event.notification.data.url || '/';
        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true })
                .then((clientList) => {
                    // إذا كان التطبيق مفتوحاً، افتح الرابط فيه
                    for (const client of clientList) {
                        if (client.url.includes(url.split('/')[1]) && 'focus' in client) {
                            return client.navigate(url).then(() => client.focus());
                        }
                    }
                    // إذا لم يكن مفتوحاً، افتح نافذة جديدة
                    if (clients.openWindow) {
                        return clients.openWindow(url);
                    }
                })
        );
    }
});

// Background Sync للتحقق من الإشعارات
self.addEventListener('sync', (event) => {
    if (event.tag === 'check-notifications') {
        event.waitUntil(checkForNotifications());
    }
});

// Periodic Background Sync (إذا كان مدعوماً)
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'check-notifications-periodic') {
        event.waitUntil(checkForNotifications());
    }
});

