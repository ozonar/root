self.addEventListener('install', function(event) {
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    event.waitUntil(clients.claim());
});

self.addEventListener('push', function(event) {
    if (!event.data) {
        return;
    }

    try {
        var data = event.data.json();
    } catch (e) {
        return;
    }

    var title = data.title || 'Checker';
    var body = data.body || '';
    var icon = data.icon || '/favicon.ico';
    var badge = data.badge || '/favicon.ico';
    var tag = data.tag || 'checker-update';
    var dataPayload = data.data || {};

    var options = {
        body: body,
        icon: icon,
        badge: badge,
        tag: tag,
        vibrate: [200, 100, 200],
        data: dataPayload,
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    var data = event.notification.data || {};
    var projectId = data.projectId;
    var pageIds = data.pageIds || [];

    var url = '/';
    if (projectId) {
        url = '/?projectId=' + projectId;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Если уже есть открытое окно, фокусируем его
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url.indexOf(url) !== -1 && 'focus' in client) {
                    return client.focus();
                }
            }
            // Иначе открываем новое
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});