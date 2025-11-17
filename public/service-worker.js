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
// هذا يضمن عمل الإشعارات حتى لو كان التطبيق مغلقاً تماماً
let firebaseInitialized = false;

// دالة تهيئة Firebase
function initializeFirebase(firebaseConfig) {
  if (firebaseInitialized || !firebaseConfig || !firebaseConfig.apiKey || typeof firebase === 'undefined') {
    if (firebaseInitialized) {
      console.log('[SW] Firebase already initialized');
    }
    return;
  }

  try {
    console.log('[SW] Initializing Firebase with config:', {
      apiKey: firebaseConfig.apiKey ? firebaseConfig.apiKey.substring(0, 10) + '...' : 'missing',
      projectId: firebaseConfig.projectId || 'missing',
    });

    if (!firebase.apps.length) {
      firebase.initializeApp(firebaseConfig);
      console.log('[SW] Firebase app initialized');
    } else {
      console.log('[SW] Firebase app already exists');
    }

    const messaging = firebase.messaging();
    console.log('[SW] Firebase messaging instance created');

    // معالجة الرسائل في الخلفية (عندما يكون التطبيق مغلقاً)
    messaging.onBackgroundMessage((payload) => {
      console.log('[SW] Firebase background message received:', payload);
      console.log('[SW] Payload notification:', payload.notification);
      console.log('[SW] Payload data:', payload.data);

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
        vibrate: [200, 100, 200],
        silent: false,
      };

      console.log('[SW] Showing Firebase notification:', notificationTitle);
      return self.registration.showNotification(notificationTitle, notificationOptions).then(() => {
        console.log('[SW] Firebase notification shown successfully');
      }).catch((error) => {
        console.error('[SW] Error showing Firebase notification:', error);
      });
    });

    console.log('[SW] onBackgroundMessage registered successfully');
    firebaseInitialized = true;
    console.log('[SW] Firebase initialized successfully in service worker');
  } catch (error) {
    console.error('[SW] Error initializing Firebase in service worker:', error);
    console.error('[SW] Error stack:', error.stack);
  }
}

// جلب config من API مباشرة
async function loadFirebaseConfigFromAPI() {
  if (firebaseInitialized) {
    console.log('[SW] Firebase already initialized, skipping config load');
    return;
  }

  console.log('[SW] Loading Firebase config from API...');
  try {
    const response = await fetch('/api/firebase/config');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const firebaseConfig = await response.json();
    console.log('[SW] Firebase config loaded from API');

    if (firebaseConfig && firebaseConfig.apiKey) {
      initializeFirebase(firebaseConfig);
    } else {
      console.error('[SW] Invalid Firebase config received');
    }
  } catch (error) {
    console.error('[SW] Error loading Firebase config from API:', error);
    // محاولة استخدام config افتراضي
    console.log('[SW] Trying default Firebase config...');
    const defaultConfig = {
      apiKey: 'AIzaSyAXv3VHE9P1L5i71y4Z20nB-N4tLiA-TrU',
      authDomain: 'parana-kids.firebaseapp.com',
      projectId: 'parana-kids',
      storageBucket: 'parana-kids.firebasestorage.app',
      messagingSenderId: '130151352064',
      appId: '1:130151352064:web:42335c43d67f4ac49515e5',
      measurementId: 'G-HCTDLM0P9Y',
    };
    initializeFirebase(defaultConfig);
  }
}

// تهيئة Firebase فوراً عند تحميل Service Worker
console.log('[SW] Service Worker loaded, initializing Firebase...');
loadFirebaseConfigFromAPI();

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
  console.log('[SW] Push event received:', event);
  
  let notificationData = {
    title: 'رسالة جديدة',
    body: 'لديك رسالة جديدة',
    icon: '/assets/images/icons/icon-192x192.png',
    data: {},
  };

  try {
    if (event.data) {
      const payload = event.data.json();
      console.log('[SW] Push payload:', payload);
      
      if (payload.notification) {
        notificationData.title = payload.notification.title || notificationData.title;
        notificationData.body = payload.notification.body || notificationData.body;
        notificationData.icon = payload.notification.icon || notificationData.icon;
      }
      
      if (payload.data) {
        notificationData.data = payload.data;
      }
    }
  } catch (error) {
    console.error('[SW] Error parsing push data:', error);
  }

  const notificationOptions = {
    body: notificationData.body,
    icon: notificationData.icon,
    badge: '/assets/images/icons/icon-192x192.png',
    data: notificationData.data,
    tag: `chat-${notificationData.data.conversation_id || 'new'}`,
    requireInteraction: false,
    vibrate: [200, 100, 200],
    silent: false,
  };

  console.log('[SW] Showing push notification:', notificationData.title);
  event.waitUntil(
    self.registration.showNotification(notificationData.title, notificationOptions)
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
