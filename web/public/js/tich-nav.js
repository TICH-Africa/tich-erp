document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initNavOverflow();
});

function initMobileNav() {
    const header = document.getElementById('site-header');
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-drawer]');
    const backdrop = document.querySelector('[data-nav-drawer-backdrop]');

    if (!header || !toggle || !drawer) {
        return;
    }

    const isOpen = () => !drawer.hasAttribute('hidden');

    const openDrawer = () => {
        drawer.removeAttribute('hidden');
        header.classList.add('is-nav-open');
        document.body.classList.add('is-nav-open');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close menu');
    };

    const closeDrawer = () => {
        drawer.setAttribute('hidden', 'hidden');
        header.classList.remove('is-nav-open');
        document.body.classList.remove('is-nav-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open menu');
    };

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (isOpen()) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    backdrop?.addEventListener('click', closeDrawer);

    drawer.querySelectorAll('a, button[type="submit"]').forEach((element) => {
        element.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                closeDrawer();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) {
            closeDrawer();
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024 && isOpen()) {
            closeDrawer();
        }
    });
}

function initNavOverflow() {
    const nav = document.querySelector('.tich-nav--desktop');
    const linksContainer = document.querySelector('[data-nav-links]');
    const moreWrap = document.querySelector('[data-nav-more]');
    const moreMenu = document.querySelector('[data-nav-more-menu]');
    const moreToggle = document.querySelector('[data-nav-more-toggle]');

    if (!nav || !linksContainer || !moreWrap || !moreMenu || !moreToggle) {
        return;
    }

    const overflowItems = [...linksContainer.querySelectorAll('[data-nav-overflow-item]')];
    let resizeTimer = null;
    let isOpen = false;
    let isLayoutScheduled = false;

    const closeMoreMenu = () => {
        isOpen = false;
        moreWrap.classList.remove('is-open');
        moreMenu.setAttribute('hidden', 'hidden');
        moreToggle.setAttribute('aria-expanded', 'false');
    };

    const openMoreMenu = () => {
        if (moreMenu.children.length === 0) {
            return;
        }

        isOpen = true;
        moreWrap.classList.add('is-open');
        moreMenu.removeAttribute('hidden');
        moreToggle.setAttribute('aria-expanded', 'true');
    };

    moreToggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (isOpen) {
            closeMoreMenu();
        } else {
            openMoreMenu();
        }
    });

    document.addEventListener('click', (event) => {
        if (!isOpen) {
            return;
        }

        if (!moreWrap.contains(event.target)) {
            closeMoreMenu();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMoreMenu();
        }
    });

    const cloneLinkForMore = (item) => {
        const link = item.querySelector('.tich-nav__link');
        if (!link) {
            return null;
        }

        const clone = link.cloneNode(true);
        clone.classList.remove('tich-nav__link');
        clone.classList.add('tich-nav__more-link');
        clone.setAttribute('role', 'menuitem');
        clone.addEventListener('click', closeMoreMenu);

        const icon = clone.querySelector('.tich-nav__icon');
        if (icon) {
            icon.hidden = false;
            icon.style.display = '';
        }

        return clone;
    };

    const fits = () => linksContainer.scrollWidth <= linksContainer.clientWidth + 1;

    const layout = () => {
        isLayoutScheduled = false;
        const wasOpen = isOpen;
        closeMoreMenu();
        moreMenu.innerHTML = '';

        overflowItems.forEach((item) => {
            item.hidden = false;
        });

        moreWrap.hidden = true;
        moreWrap.classList.remove('is-active');

        if (window.innerWidth < 1024) {
            return;
        }

        let guard = 0;

        while (!fits() && guard < overflowItems.length + 2) {
            guard += 1;

            const visibleItems = overflowItems.filter((item) => !item.hidden);
            if (visibleItems.length === 0) {
                break;
            }

            moreWrap.hidden = false;

            const lastItem = visibleItems[visibleItems.length - 1];
            const clone = cloneLinkForMore(lastItem);

            if (!clone) {
                break;
            }

            moreMenu.prepend(clone);
            lastItem.hidden = true;
        }

        if (moreMenu.children.length === 0) {
            moreWrap.hidden = true;
            closeMoreMenu();
        } else {
            moreWrap.hidden = false;

            if (moreMenu.querySelector('.is-active')) {
                moreWrap.classList.add('is-active');
            }

            if (wasOpen) {
                openMoreMenu();
            }
        }
    };

    const scheduleLayout = () => {
        if (isLayoutScheduled) {
            return;
        }

        isLayoutScheduled = true;
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            window.requestAnimationFrame(layout);
        }, 80);
    };

    window.addEventListener('resize', scheduleLayout);

    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(scheduleLayout);
        observer.observe(nav);

        const primary = document.querySelector('.tich-nav__primary');
        if (primary) {
            observer.observe(primary);
        }

        observer.observe(linksContainer);

        const headerInner = document.querySelector('.tich-header__inner');
        if (headerInner) {
            observer.observe(headerInner);
        }
    }

    layout();
}
