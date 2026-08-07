(function () {
    const sidebar = document.getElementById('hr-admin-sidebar');
    if (!sidebar) {
        return;
    }

    const configEl = document.getElementById('hr-sidebar-realtime-config');
    if (!configEl) {
        return;
    }

    let config;
    try {
        config = JSON.parse(configEl.textContent || '{}');
    } catch {
        return;
    }

    const menuLabels = config.menuLabels || {};

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

        sidebar.querySelectorAll('[data-hr-sidebar-badge]').forEach((badge) => {
            const key = badge.getAttribute('data-hr-sidebar-badge');
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

    if (!config.enabled || typeof window.Pusher === 'undefined' || typeof window.Echo === 'undefined') {
        return;
    }

    window.Pusher = window.Pusher;

    const echo = new window.Echo({
        broadcaster: 'reverb',
        key: config.key,
        wsHost: config.host,
        wsPort: config.port,
        wssPort: config.port,
        forceTLS: config.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: config.authEndpoint,
        auth: {
            headers: {
                'X-CSRF-TOKEN': config.csrfToken,
            },
        },
    });

    echo.private('hr.sidebar')
        .listen('.sidebar.counts.updated', (event) => {
            applyLabels(event.labels || {});
        })
        .error(() => {
            poll();
        });
})();
