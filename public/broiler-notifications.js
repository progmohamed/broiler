self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url ?? '/', self.location.origin);
    if (target.origin !== self.location.origin) return;
    event.waitUntil(self.clients.openWindow(target.href));
});
