// Service Worker لإشعارات PWA
const CACHE_NAME = 'parana-kids-v1';
const NOTIFICATION_POLL_INTERVAL = 30000; // 30 ثانية
let csrfToken = null;
let pwaToken = null; // PWA token للوصول إلى API
let autoCheckInterval = null;
let lastCheckTime = null;
let shownNotificationIds = new Set(); // تتبع الإشعارات التي تم عرضها

// تثبيت Service Worker
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    self.skipWaiting();
});

// تفعيل Service Worker
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    event.waitUntil(self.clients.claim());

    // بدء التحقق التلقائي عند تفعيل Service Worker
    startAutoCheck();
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
    } else if (event.data && event.data.type === 'SET_PWA_TOKEN') {
        pwaToken = event.data.token;
        console.log('Service Worker: PWA token received');
    } else if (event.data && event.data.type === 'START_AUTO_CHECK') {
        startAutoCheck();
    } else if (event.data && event.data.type === 'REQUEST_PWA_TOKEN') {
        // طلب PWA token من الصفحة
        self.clients.matchAll().then(clients => {
            if (clients.length > 0) {
                clients[0].postMessage({ type: 'REQUEST_PWA_TOKEN' });
            }
        });
    }
});

// بدء التحقق التلقائي
function startAutoCheck() {
    // إيقاف أي interval موجود
    if (autoCheckInterval) {
        clearInterval(autoCheckInterval);
    }

    // التحقق الفوري
    checkForNotifications();

    // التحقق الدوري كل 30 ثانية
    autoCheckInterval = setInterval(() => {
        checkForNotifications();
    }, NOTIFICATION_POLL_INTERVAL);

    console.log('Service Worker: Auto-check started at', new Date().toISOString());
}

// التحقق من الإشعارات الجديدة
async function checkForNotifications() {
    try {
        console.log('Service Worker: Checking notifications at', new Date().toISOString());

        // استخدام PWA token أولاً (يعمل حتى لو كان التطبيق مغلق)
        // إذا لم يكن موجوداً، استخدم CSRF token
        let authHeader = null;

        if (pwaToken) {
            authHeader = { 'X-PWA-Token': pwaToken };
            console.log('Service Worker: Using PWA token');
        } else if (csrfToken) {
            authHeader = { 'X-CSRF-TOKEN': csrfToken };
            console.log('Service Worker: Using CSRF token');
        } else {
            console.log('Service Worker: No token available, requesting from page...');
            // محاولة الحصول على token من الصفحة
            const clients = await self.clients.matchAll();
            if (clients.length > 0) {
                clients[0].postMessage({ type: 'REQUEST_PWA_TOKEN' });
                clients[0].postMessage({ type: 'REQUEST_CSRF_TOKEN' });
            } else {
                console.log('Service Worker: No clients available to request token');
            }
            return;
        }

        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...authHeader,
        };

        const response = await fetch('/api/sweet-alerts/unread', {
            method: 'GET',
            headers: headers,
            credentials: 'include',
        });

        if (!response.ok) {
            console.log('Service Worker: Failed to fetch notifications:', response.status, response.statusText);
            // إذا كان الخطأ 401، طلب token جديد
            if (response.status === 401) {
                const clients = await self.clients.matchAll();
                if (clients.length > 0) {
                    clients[0].postMessage({ type: 'REQUEST_PWA_TOKEN' });
                    clients[0].postMessage({ type: 'REQUEST_CSRF_TOKEN' });
                }
                // مسح token القديم
                pwaToken = null;
                csrfToken = null;
            }
            return;
        }

        const data = await response.json();
        console.log('Service Worker: Fetched', data.alerts?.length || 0, 'alerts');
        if (data.success && data.alerts && data.alerts.length > 0) {
            // عرض إشعار للإشعارات الجديدة فقط التي لم يتم عرضها من قبل
            const newAlerts = data.alerts.filter(alert => {
                // تخطي الإشعارات التي تم عرضها بالفعل
                if (shownNotificationIds.has(alert.id)) {
                    return false;
                }

                // تخطي الإشعارات القديمة (إذا كان lastCheckTime موجود)
                if (lastCheckTime) {
                    const alertTime = new Date(alert.created_at);
                    if (alertTime <= lastCheckTime) {
                        return false;
                    }
                }

                return true;
            });

            if (newAlerts.length > 0) {
                lastCheckTime = new Date();

                // عرض الإشعارات وتحديدها كمقروءة
                for (const alert of newAlerts) {
                    // إضافة ID الإشعار إلى القائمة
                    shownNotificationIds.add(alert.id);

                    // عرض الإشعار
                    showNotification(alert);

                    // تحديد الإشعار كمقروء في قاعدة البيانات
                    markAlertAsRead(alert.id);
                }
            }
        }
    } catch (error) {
        console.error('Service Worker: Error checking notifications:', error);
    }
}

// عرض إشعار المتصفح
function showNotification(alert) {
    // التحقق من أن الإشعار لم يُعرض من قبل
    if (shownNotificationIds.has(alert.id)) {
        console.log('Service Worker: Notification already shown, skipping:', alert.id);
        return;
    }

      const notificationOptions = {
        body: alert.message,
        icon: '/assets/images/icons/icon-192x192.png',
        badge: '/assets/images/icons/icon-192x192.png',
        tag: `sweet-alert-${alert.id}`, // استخدام ID فريد لمنع التكرار
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
            // إضافة ID الإشعار إلى القائمة بعد عرضه بنجاح
            shownNotificationIds.add(alert.id);
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

// تحديد الإشعار كمقروء في قاعدة البيانات
async function markAlertAsRead(alertId) {
    try {
        // استخدام PWA token أولاً، ثم CSRF token
        let authHeader = null;

        if (pwaToken) {
            authHeader = { 'X-PWA-Token': pwaToken };
        } else if (csrfToken) {
            authHeader = { 'X-CSRF-TOKEN': csrfToken };
        } else {
            console.log('Service Worker: Cannot mark alert as read - no token available');
            return;
        }

        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...authHeader,
        };

        const response = await fetch(`/api/sweet-alerts/${alertId}/read`, {
            method: 'POST',
            headers: headers,
            credentials: 'include',
        });

        if (response.ok) {
            console.log('Service Worker: Alert marked as read:', alertId);
        } else {
            console.log('Service Worker: Failed to mark alert as read:', response.status);
        }
      } catch (error) {
        console.error('Service Worker: Error marking alert as read:', error);
    }
}

