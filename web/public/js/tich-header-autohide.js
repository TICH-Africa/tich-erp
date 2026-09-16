/**
 * Auto-hide the site header on dashboards/portals after idle activity.
 * Slides the bar upward via margin (smooth both ways) and expands main content.
 */
document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    if (!body.classList.contains('tich-body--autohide-header')) {
        return;
    }

    const header = document.getElementById('site-header');
    if (!header) {
        return;
    }

    const root = document.documentElement;
    const IDLE_MS = 2800;
    const TOP_REVEAL_PX = 16;
    let hideTimer = null;
    let pointerOverHeader = false;
    let cachedChromePx = 0;

    const isIdle = () => body.classList.contains('is-header-idle');

    const isLocked = () =>
        header.classList.contains('is-nav-open')
        || body.classList.contains('is-nav-open')
        || body.classList.contains('is-admin-sidebar-open')
        || pointerOverHeader
        || header.contains(document.activeElement);

    const measureChromeHeight = () => {
        // Only measure while fully visible — collapsed/animating heights are wrong.
        if (isIdle()) {
            return cachedChromePx;
        }

        const measured = Math.ceil(header.getBoundingClientRect().height || header.offsetHeight || 0);
        if (measured > 0) {
            cachedChromePx = measured;
            root.style.setProperty('--tich-header-chrome-height', `${measured}px`);
            return measured;
        }

        return cachedChromePx;
    };

    const setMainOffset = (px) => {
        root.style.setProperty('--tich-header-height', `${Math.max(0, px)}px`);
    };

    const hideHeader = () => {
        if (isLocked() || isIdle()) {
            return;
        }

        const chromePx = measureChromeHeight();
        if (chromePx <= 0) {
            return;
        }

        root.style.setProperty('--tich-header-chrome-height', `${chromePx}px`);
        body.classList.add('is-header-idle');
        setMainOffset(0);
    };

    const showHeader = () => {
        if (isIdle()) {
            const chromePx = cachedChromePx
                || Number.parseInt(root.style.getPropertyValue('--tich-header-chrome-height').trim(), 10)
                || 0;

            // Restore height first so max layout target is ready, then slide in.
            if (chromePx > 0) {
                root.style.setProperty('--tich-header-chrome-height', `${chromePx}px`);
                setMainOffset(chromePx);
            }

            body.classList.remove('is-header-idle');
        }
        scheduleHide();
    };

    const scheduleHide = () => {
        if (hideTimer) {
            clearTimeout(hideTimer);
        }
        hideTimer = window.setTimeout(hideHeader, IDLE_MS);
    };

    const onActivity = () => {
        showHeader();
    };

    header.addEventListener('pointerenter', () => {
        pointerOverHeader = true;
        showHeader();
    });
    header.addEventListener('pointerleave', () => {
        pointerOverHeader = false;
        scheduleHide();
    });

    ['pointerdown', 'keydown', 'touchstart', 'focusin'].forEach((type) => {
        document.addEventListener(type, onActivity, { passive: true, capture: true });
    });

    document.addEventListener('scroll', onActivity, { passive: true, capture: true });

    document.addEventListener('pointermove', (event) => {
        if (isIdle()) {
            if (event.clientY <= TOP_REVEAL_PX) {
                onActivity();
            }
            return;
        }
        onActivity();
    }, { passive: true });

    document.querySelectorAll('#main-content, .tich-admin__main, .tich-admin-sidebar').forEach((el) => {
        el.addEventListener('scroll', onActivity, { passive: true });
    });

    const observer = new MutationObserver(() => {
        if (isLocked()) {
            showHeader();
        } else {
            scheduleHide();
        }
    });
    observer.observe(header, { attributes: true, attributeFilter: ['class'] });
    observer.observe(body, { attributes: true, attributeFilter: ['class'] });

    // Cache height after layout (module toggle may inject after this script).
    requestAnimationFrame(() => {
        measureChromeHeight();
        window.setTimeout(measureChromeHeight, 100);
    });
    window.addEventListener('resize', () => {
        if (!isIdle()) {
            measureChromeHeight();
        }
    });

    scheduleHide();
});
