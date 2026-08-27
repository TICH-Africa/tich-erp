/**
 * TICH Platform - UI State Detection
 * Offline, slow network, and session expiry toasts (bottom-right).
 */
(function () {
    'use strict';

    var offlineBanner = document.getElementById('tich-offline-banner');
    var slowBanner = document.getElementById('tich-slow-banner');
    var sessionBanner = document.getElementById('tich-session-banner');

    var dismissed = {
        offline: false,
        slow: false,
        session: false,
    };

    function setToastVisible(toast, visible, dismissKey) {
        if (!toast) return;

        if (visible && dismissKey && dismissed[dismissKey]) {
            return;
        }

        if (visible) {
            toast.hidden = false;
            toast.classList.remove('is-leaving');
            toast.classList.add('is-visible');
            return;
        }

        if (!toast.classList.contains('is-visible') && toast.hidden) {
            return;
        }

        toast.classList.add('is-leaving');
        toast.classList.remove('is-visible');

        window.setTimeout(function () {
            toast.hidden = true;
            toast.classList.remove('is-leaving');
        }, 220);
    }

    function dismissToast(toast) {
        if (!toast) return;

        if (toast === offlineBanner) dismissed.offline = true;
        if (toast === slowBanner) dismissed.slow = true;
        if (toast === sessionBanner) dismissed.session = true;

        setToastVisible(toast, false);
    }

    document.querySelectorAll('[data-system-toast-dismiss]').forEach(function (button) {
        button.addEventListener('click', function () {
            dismissToast(button.closest('.tich-system-toast'));
        });
    });

    // --- Offline detection ---
    function setOffline(isOffline) {
        if (!isOffline) {
            dismissed.offline = false;
        }
        setToastVisible(offlineBanner, isOffline, 'offline');
    }

    window.addEventListener('offline', function () { setOffline(true); });
    window.addEventListener('online', function () { setOffline(false); });
    if (!navigator.onLine) setOffline(true);

    // --- Slow network detection ---
    function checkSlowConnection() {
        if (!slowBanner) return;

        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (!conn) return;

        var isSlow = conn.effectiveType === 'slow-2g' || conn.effectiveType === '2g' || conn.downlink < 0.5;

        if (!isSlow) {
            dismissed.slow = false;
        }

        setToastVisible(slowBanner, isSlow, 'slow');
    }

    checkSlowConnection();
    if (navigator.connection || navigator.mozConnection || navigator.webkitConnection) {
        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        conn.addEventListener('change', checkSlowConnection);
    }

    // --- Session expiry detection ---
    var sessionCheckInterval = 60000; // 1 minute
    var sessionCheckUrl = '/api/session-check';

    function checkSession() {
        if (!sessionBanner || !navigator.onLine) return;
        fetch(sessionCheckUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).then(function (response) {
            if (response.status === 401 || response.status === 419) {
                sessionBanner.classList.add('is-visible');
            }
        }).catch(function () {
            // Network error, offline banner handles this
        });
    }

    setInterval(checkSession, sessionCheckInterval);
})();
