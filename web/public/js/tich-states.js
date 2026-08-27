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
    // Session banner is shown by the 419 error page / redirect feedback.
    // Proactive polling is removed so guests do not see false session-expired
    // banners on public pages.
})();
