self.addEventListener('install', event => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const targetUrl = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
        for (const client of windows) {
            if ('focus' in client) {
                client.focus();
                client.postMessage({ type: 'notification_click', url: targetUrl });
                return;
            }
        }
        if (self.clients.openWindow) {
            await self.clients.openWindow(targetUrl);
        }
    })());
});

self.addEventListener('push', event => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (_) {
        payload = {};
    }
    const title = payload.title || 'Pulse';
    const body = payload.body || 'Новое уведомление';
    const options = {
        body,
        icon: '/data/avatars/default.png',
        badge: '/data/avatars/default.png',
        tag: payload.tag || `pulse-push-${Date.now()}`,
        data: { url: payload.url || '/' }
    };
    event.waitUntil(self.registration.showNotification(title, options));
});
