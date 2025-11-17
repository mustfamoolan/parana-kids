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

// Firebase Messaging - تهيئة مباشرة في Service Worker
// وفقاً للوثائق الرسمية: يجب تهيئة Firebase مباشرة في Service Worker
console.log('[SW] Service Worker loaded, initializing Firebase...');

// Firebase Config - استخدام config افتراضي مباشرة (يمكن جلبها من API لاحقاً)
const firebaseConfig = {
  apiKey: 'AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU',
  authDomain: 'parana-kids.firebaseapp.com',
  projectId: 'parana-kids',
  storageBucket: 'parana-kids.firebasestorage.app',
  messagingSenderId: '130151352064',
  appId: '1:130151352064:web:42335c43d67f4ac49515e5',
  measurementId: 'G-HCTDLM0P9Y',
};

// تهيئة Firebase مباشرة (وفقاً للوثائق الرسمية)
try {
  if (typeof firebase !== 'undefined') {
    // تهيئة Firebase App
    if (!firebase.apps.length) {
      firebase.initializeApp(firebaseConfig);
      console.log('[SW] Firebase app initialized');
    } else {
      console.log('[SW] Firebase app already exists');
    }

    // الحصول على Firebase Messaging instance
    const messaging = firebase.messaging();
    console.log('[SW] Firebase messaging instance created');

    // معالجة الرسائل في الخلفية (عندما يكون التطبيق مغلقاً)
    // هذا يجب أن يكون مسجل قبل استقبال أي رسائل
    messaging.onBackgroundMessage((payload) => {
      console.log('[SW] ========== FIREBASE BACKGROUND MESSAGE ==========');
      console.log('[SW] Full payload:', payload);
      console.log('[SW] Payload notification:', payload.notification);
      console.log('[SW] Payload data:', payload.data);

      const notification = payload.notification || {};
      const data = payload.data || {};

      console.log('[SW] Raw notification object:', JSON.stringify(notification));
      console.log('[SW] Raw data object:', JSON.stringify(data));

      // تحديد نص الإشعار - أولوية: notification.body ثم data.notification_body ثم data.message_text
      let notificationTitle = 'رسالة جديدة';
      let notificationBody = 'لديك رسالة جديدة';
      
      // استخدام notification.body إذا كان موجوداً
      if (notification && notification.body && notification.body.trim() !== '' && notification.body !== 'لديك رسالة جديدة') {
        notificationBody = notification.body;
        console.log('[SW] Using notification.body:', notificationBody);
      } 
      // إذا لم يكن موجوداً، استخدم data.notification_body
      else if (data && data.notification_body && data.notification_body.trim() !== '' && data.notification_body !== 'لديك رسالة جديدة') {
        notificationBody = data.notification_body;
        console.log('[SW] Using data.notification_body:', notificationBody);
      }
      // إذا لم يكن موجوداً، استخدم data.message_text
      else if (data && data.message_text && data.message_text.trim() !== '' && data.message_text !== 'لديك رسالة جديدة') {
        notificationBody = data.message_text;
        console.log('[SW] Using data.message_text:', notificationBody);
      }
      
      // استخدام notification.title إذا كان موجوداً
      if (notification && notification.title && notification.title.trim() !== '') {
        notificationTitle = notification.title;
      } else if (data && data.notification_title && data.notification_title.trim() !== '') {
        notificationTitle = data.notification_title;
      }

      console.log('[SW] Final notification title:', notificationTitle);
      console.log('[SW] Final notification body:', notificationBody);

      const notificationOptions = {
        body: notificationBody,
        icon: notification.icon || '/assets/images/icons/icon-192x192.png',
        badge: '/assets/images/icons/icon-192x192.png',
        data: data,
        tag: `chat-${data.conversation_id || 'new'}`,
        requireInteraction: false,
        vibrate: [200, 100, 200],
        silent: false, // false = يستخدم صوت الجهاز الافتراضي
      };

      console.log('[SW] Showing Firebase notification:', notificationTitle);
      console.log('[SW] Notification options:', notificationOptions);

      return self.registration.showNotification(notificationTitle, notificationOptions)
        .then(() => {
          console.log('[SW] Firebase notification shown successfully');
        })
        .catch((error) => {
          console.error('[SW] Error showing Firebase notification:', error);
          console.error('[SW] Error stack:', error.stack);
        });
    });

    console.log('[SW] onBackgroundMessage registered successfully');
    console.log('[SW] Firebase initialized successfully in service worker');
  } else {
    console.error('[SW] Firebase SDK not loaded');
  }
} catch (error) {
  console.error('[SW] Error initializing Firebase in service worker:', error);
  console.error('[SW] Error stack:', error.stack);
}

// محاولة جلب config من API كـ backup (لكن Firebase مهيأ بالفعل)
async function updateFirebaseConfigFromAPI() {
  try {
    const response = await fetch('/api/firebase/config');
    if (response.ok) {
      const config = await response.json();
      console.log('[SW] Firebase config updated from API');
      // يمكن تحديث config هنا إذا لزم الأمر
    }
  } catch (error) {
    console.log('[SW] Could not load config from API, using default');
  }
}

// محاولة تحديث config من API (اختياري)
updateFirebaseConfigFromAPI();

// Test notification بعد 3 ثواني للتأكد من أن Service Worker يعمل
setTimeout(() => {
  if (self.registration) {
    console.log('[SW] Testing notification system...');
    self.registration.showNotification('Service Worker Test', {
      body: 'Service Worker is working correctly',
      icon: '/assets/images/icons/icon-192x192.png',
      badge: '/assets/images/icons/icon-192x192.png',
      tag: 'test-notification',
      requireInteraction: false,
      vibrate: [200, 100, 200],
    }).then(() => {
      console.log('[SW] Test notification shown successfully');
    }).catch((error) => {
      console.error('[SW] Error showing test notification:', error);
    });
  }
}, 3000);

// معالجة رسائل من الصفحة الرئيسية
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  // استقبال Firebase config من الصفحة الرئيسية (backup)
  if (event.data && event.data.type === 'FIREBASE_CONFIG') {
    console.log('[SW] Received Firebase config from main page');
    initializeFirebase(event.data.config);
  }
});

// تهيئة Firebase عند تحميل Service Worker (install event)
self.addEventListener('install', (event) => {
  console.log('[SW] Service Worker installing...');
  event.waitUntil(
    loadFirebaseConfigFromAPI().then(() => {
      self.skipWaiting();
    })
  );
});

// تهيئة Firebase عند تفعيل Service Worker (activate event)
self.addEventListener('activate', (event) => {
  console.log('[SW] Service Worker activating...');
  event.waitUntil(
    loadFirebaseConfigFromAPI().then(() => {
      return self.clients.claim();
    })
  );
});

// معالجة push events مباشرة (fallback إذا لم يعمل Firebase)
self.addEventListener('push', (event) => {
  console.log('[SW] ========== PUSH EVENT RECEIVED ==========');
  console.log('[SW] Push event:', event);
  console.log('[SW] Event data:', event.data);

  let notificationData = {
    title: 'رسالة جديدة',
    body: 'لديك رسالة جديدة',
    icon: '/assets/images/icons/icon-192x192.png',
    data: {},
  };

  try {
    if (event.data) {
      let payload;
      try {
        payload = event.data.json();
        console.log('[SW] Push payload (JSON):', payload);
      } catch (e) {
        // إذا لم يكن JSON، حاول text
        const text = event.data.text();
        console.log('[SW] Push payload (text):', text);
        try {
          payload = JSON.parse(text);
        } catch (e2) {
          console.error('[SW] Cannot parse push data as JSON or text');
          payload = null;
        }
      }

      if (payload) {
        if (payload.notification) {
          notificationData.title = payload.notification.title || notificationData.title;
          notificationData.body = payload.notification.body || payload.data?.message_text || notificationData.body;
          notificationData.icon = payload.notification.icon || notificationData.icon;
          console.log('[SW] Notification data from payload:', payload.notification);
        }

        if (payload.data) {
          notificationData.data = payload.data;
          // استخدام message_text من data إذا لم يكن في notification
          if (!notificationData.body || notificationData.body === 'لديك رسالة جديدة') {
            notificationData.body = payload.data.message_text || notificationData.body;
          }
          console.log('[SW] Data from payload:', payload.data);
        }
      }
    } else {
      console.log('[SW] No data in push event');
    }
  } catch (error) {
    console.error('[SW] Error parsing push data:', error);
    console.error('[SW] Error stack:', error.stack);
  }

  // تحديد نص الإشعار بشكل أفضل - أولوية: notification.body ثم data.notification_body ثم data.message_text
  let finalBody = 'لديك رسالة جديدة';
  
  // استخدام notification.body إذا كان موجوداً
  if (notificationData.body && notificationData.body.trim() !== '' && notificationData.body !== 'لديك رسالة جديدة') {
    finalBody = notificationData.body;
    console.log('[SW] Push - Using notification.body:', finalBody);
  } 
  // إذا لم يكن موجوداً، استخدم data.notification_body
  else if (notificationData.data && notificationData.data.notification_body && notificationData.data.notification_body.trim() !== '' && notificationData.data.notification_body !== 'لديك رسالة جديدة') {
    finalBody = notificationData.data.notification_body;
    console.log('[SW] Push - Using data.notification_body:', finalBody);
  }
  // إذا لم يكن موجوداً، استخدم data.message_text
  else if (notificationData.data && notificationData.data.message_text && notificationData.data.message_text.trim() !== '' && notificationData.data.message_text !== 'لديك رسالة جديدة') {
    finalBody = notificationData.data.message_text;
    console.log('[SW] Push - Using data.message_text:', finalBody);
  }

  console.log('[SW] Push - Final notification body:', finalBody);

  const notificationOptions = {
    body: finalBody,
    icon: notificationData.icon,
    badge: '/assets/images/icons/icon-192x192.png',
    data: notificationData.data,
    tag: `chat-${notificationData.data.conversation_id || 'new'}`,
    requireInteraction: false,
    vibrate: [200, 100, 200],
    silent: false, // false = يستخدم صوت الجهاز الافتراضي
  };

  console.log('[SW] Notification options:', notificationOptions);
  console.log('[SW] Showing push notification:', notificationData.title);

  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationOptions)
      .then(() => {
        console.log('[SW] Push notification shown successfully');
      })
      .catch((error) => {
        console.error('[SW] Error showing push notification:', error);
        console.error('[SW] Error stack:', error.stack);
      })
  );
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
