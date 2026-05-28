/// <reference lib="webworker" />
import { ExpirationPlugin } from 'workbox-expiration';
import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst } from 'workbox-strategies';

declare let self: ServiceWorkerGlobalScope;

cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

// Cache API responses for surveys
registerRoute(
  ({ url }) => url.pathname.startsWith('/surveys/json'),
  new NetworkFirst({
    cacheName: 'survey-data',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 10,
        maxAgeSeconds: 24 * 60 * 60, // 24 hours
      }),
    ],
  })
);

// Cache assets
registerRoute(
  ({ request }) => request.destination === 'image' || request.destination === 'font',
  new CacheFirst({
    cacheName: 'assets',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 50,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Days
      }),
    ],
  })
);

// Handle push notifications
self.addEventListener('push', (event: PushEvent) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'New Survey Available';
  const options = {
    body: data.body || 'Open the app to start earning rewards!',
    icon: '/favicon.svg',
    badge: '/favicon.svg',
    data: {
      url: data.url || '/surveys'
    }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event: NotificationEvent) => {
  event.notification.close();
  event.waitUntil(
    self.clients.openWindow(event.notification.data.url)
  );
});