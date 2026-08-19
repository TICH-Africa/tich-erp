/**
 * TICH Platform - UI State Detection
 * Handles offline, slow network, and session expiry banners.
 */
(function () {
    'use strict';

    var offlineBanner = document.getElementById('tich-offline-banner');
    var slowBanner = document.getElementById('tich-slow-banner');
    var sessionBanner = document.getElementById('tich-session-banner');

    // --- Offline detection ---
    function setOffline(isOffline) {
        if (!offlineBanner) return;
        offlineBanner.classList.toggle('is-visible', isOffline);
    }

    window.addEventListener('offline', function () { setOffline(true); });
    window.addEventListener('online', function () { setOffline(false); });
    if (!navigator.onLine) setOffline(true);

    // --- Slow network detection ---
    function checkSlowConnection() {
        if (!slowBanner) return;
        var conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (conn) {
            var isSlow = conn.effectiveType === 'slow-2g' || conn.effectiveType === '2g' || conn.downlink < 0.5;
            slowBanner.classList.toggle('is-visible', isSlow);
            conn.addEventListener('change', checkSlowConnection);
        }
    }
    checkSlowConnection();

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
