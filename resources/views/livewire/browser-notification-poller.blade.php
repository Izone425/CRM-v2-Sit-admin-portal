<div wire:poll.30s="checkNewNotifications">
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }

        // Listen for Livewire events
        Livewire.on('show-browser-notification', function(data) {
            console.log('[BrowserNotificationPoller] Notification event:', data);
            var notif = data[0] || data;

            if ('Notification' in window && Notification.permission === 'granted') {
                var n = new Notification(notif.title || 'New Notification', {
                    body: notif.body || '',
                    icon: '/favicon.ico',
                    tag: notif.tag || Date.now().toString(),
                });

                n.onclick = function() {
                    window.focus();
                    if (notif.url) {
                        window.open(window.location.origin + notif.url, '_blank');
                    }
                    n.close();
                };
            }
        });
    });
</script>
