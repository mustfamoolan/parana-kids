// Import Workbox
importScripts('https://storage.googleapis.com/workbox-cdn/releases/7.0.0/workbox-sw.js');

// Import Firebase Messaging
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js');

// Set cache name
const CACHE_NAME = 'parana-kids-v1';

// Precaching - Cache critical assets on install
// ملاحظة: لا نضيف صفحات تسجيل الدخول إلى precache
workbox.precaching.precacheAndRoute([
  {
    url: '/assets/css/fonts.css',
    revision: null
  },
  {
    url: '/assets/images/ParanaKids.png',
    revision: null
  },
  {
    url: '/assets/images/icons/icon-192x192.png',
    revision: null
  },
  {
    url: '/assets/images/icons/icon-512x512.png',
    revision: null
  },
  {
    url: '/manifest.json',
    revision: null
  }
]);

// استثناء صفحات تسجيل الدخول من cache - استخدام NetworkOnly
workbox.routing.registerRoute(
  ({request, url}) => {
    const pathname = new URL(url).pathname;
    return request.destination === 'document' &&
           (pathname.includes('/admin/login') || pathname.includes('/delegate/login'));
  },
  new workbox.strategies.NetworkOnly()
);

// Cache strategy: Network First, then Cache for HTML pages (باستثناء صفحات تسجيل الدخول)
workbox.routing.registerRoute(
  ({request, url}) => {
    const pathname = new URL(url).pathname;
    return request.destination === 'document' &&
           !pathname.includes('/admin/login') &&
           !pathname.includes('/delegate/login');
  },
  new workbox.strategies.NetworkFirst({
    cacheName: CACHE_NAME,
    plugins: [
      {
        cacheableResponse: {
          statuses: [200],
        },
      },
    ],
  })
);

// Cache strategy: Cache First for static assets
workbox.routing.registerRoute(
  ({request}) => request.destination === 'style' ||
                 request.destination === 'script' ||
                 request.destination === 'image' ||
                 request.destination === 'font',
  new workbox.strategies.CacheFirst({
    cacheName: CACHE_NAME,
    plugins: [
      {
        cacheableResponse: {
          statuses: [0, 200],
        },
      },
    ],
  })
);

// Clean up old caches
workbox.precaching.cleanupOutdatedCaches();

// Firebase Messaging - Background Message Handler
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  // استقبال Firebase config من الصفحة الرئيسية
  if (event.data && event.data.type === 'FIREBASE_CONFIG') {
    const firebaseConfig = event.data.config;
    if (typeof firebase !== 'undefined' && !firebase.apps.length) {
      firebase.initializeApp(firebaseConfig);

      const messaging = firebase.messaging();

      // معالجة الرسائل في الخلفية
      messaging.onBackgroundMessage((payload) => {
        console.log('Background message received:', payload);

        const notification = payload.notification || {};
        const data = payload.data || {};

        const notificationTitle = notification.title || 'رسالة جديدة';
        const notificationOptions = {
          body: notification.body || 'لديك رسالة جديدة',
          icon: notification.icon || '/assets/images/icons/icon-192x192.png',
          badge: '/assets/images/icons/icon-192x192.png',
          data: data,
          tag: `chat-${data.conversation_id || 'new'}`,
          requireInteraction: false,
        };

        return self.registration.showNotification(notificationTitle, notificationOptions);
      });
    }
  }
});

// معالجة الإشعارات في الخلفية (عندما يكون الموقع مغلق)
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  const data = event.notification.data || {};
  const conversationId = data.conversation_id;

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      // إذا كان الموقع مفتوحاً، افتح المحادثة
      if (clientList.length > 0) {
        const url = conversationId ? `/apps/chat?conversation=${conversationId}` : '/apps/chat';
        return clientList[0].focus().then((client) => {
          return client.navigate(url);
        });
      }
      // إذا كان الموقع مغلقاً، افتح نافذة جديدة
      return clients.openWindow(conversationId ? `/apps/chat?conversation=${conversationId}` : '/apps/chat');
    })
  );
});
