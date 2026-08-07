(function () {
    const configEl = document.getElementById('sidebar-realtime-config');
    if (!configEl) {
        return;
    }

    let config;
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch {
        return;
    }

    const sidebar = document.getElementById(config.sidebarId);
    if (!sidebar) {
        return;
    }

    const menuLabels = config.menuLabels || {};
    const badgeSelector = config.badgeSelector || '[data-sidebar-badge]';

    const formatCount = (count) => {
        const value = Number(count) || 0;
        if (value <= 0) {
            return null;
        }

        return value > 99 ? '99+' : String(value);
    };

    const badgeLabel = (key, label) => {
        const itemName = menuLabels[key] || key;
        return `${label} pending ${itemName}`;
    };

    const pulseBadge = (badge) => {
        badge.classList.remove('is-updated');
        void badge.offsetWidth;
        badge.classList.add('is-updated');
    };

    const applyLabels = (labels) => {
        if (!labels || typeof labels !== 'object') {
            return;
        }

        sidebar.querySelectorAll(badgeSelector).forEach((badge) => {
            const key = badge.getAttribute('data-sidebar-badge');
            const nextLabel = labels[key] ?? formatCount(config.initialCounts?.[key]);
            const previousLabel = badge.textContent.trim();

            if (!nextLabel) {
                badge.textContent = '';
                badge.hidden = true;
                badge.removeAttribute('aria-label');
                return;
            }

            badge.textContent = nextLabel;
            badge.hidden = false;
            badge.setAttribute('aria-label', badgeLabel(key, nextLabel));

            if (previousLabel && previousLabel !== nextLabel) {
                pulseBadge(badge);
            }
        });
    };

    applyLabels(config.initialLabels || {});

    const poll = async () => {
        if (!config.pollUrl) {
            return;
        }

        try {
            const response = await fetch(config.pollUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            applyLabels(payload.labels || {});
        } catch {
            // Ignore network errors; websocket or next poll will retry.
        }
    };

    if (config.pollUrl) {
        window.setInterval(poll, 60000);
    }

    const broadcast = config.broadcast || {};
    if (!broadcast.enabled || typeof window.Pusher === 'undefined' || typeof window.Echo === 'undefined' || !broadcast.channel) {
        return;
    }

    window.Pusher = window.Pusher;

    const echo = new window.Echo({
        broadcaster: 'reverb',
        key: broadcast.key,
        wsHost: broadcast.host,
        wsPort: broadcast.port,
        wssPort: broadcast.port,
        forceTLS: broadcast.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: broadcast.authEndpoint,
        auth: {
            headers: {
                'X-CSRF-TOKEN': broadcast.csrfToken,
            },
        },
    });

    echo.private(broadcast.channel)
        .listen(broadcast.event || '.sidebar.counts.updated', (event) => {
            applyLabels(event.labels || {});
        })
        .error(() => {
            poll();
        });
})();
