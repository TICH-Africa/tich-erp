document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initNavSubmenus();
    initNavOverflow();
});

const navSubmenus = [];

function closeNavSubmenu(entry) {
    if (!entry) {
        return;
    }

    window.clearTimeout(entry.timer);
    entry.timer = null;
    entry.item.classList.remove('is-open');
    entry.panel.hidden = true;
    entry.toggle?.setAttribute('aria-expanded', 'false');
}

function closeAllNavSubmenus() {
    navSubmenus.forEach(closeNavSubmenu);
}

function initNavSubmenus() {
    document.querySelectorAll('.tich-nav__links [data-nav-submenu]').forEach((panel) => {
        const item = panel.closest('.tich-nav__item');
        if (!item) {
            return;
        }

        const entry = { item, panel, toggle: item.querySelector('[data-nav-submenu-toggle]'), timer: null };
        navSubmenus.push(entry);

        const position = () => {
            const link = item.querySelector('.tich-nav__link');
            if (!link) {
                return;
            }

            const rect = link.getBoundingClientRect();
            const width = panel.offsetWidth || 224;
            panel.style.left = `${Math.max(12, Math.min(rect.left, window.innerWidth - width - 12))}px`;
            panel.style.top = `${rect.bottom + 6}px`;
        };

        const open = () => {
            closeAllNavSubmenus();
            panel.hidden = false;
            position();
            item.classList.add('is-open');
            entry.toggle?.setAttribute('aria-expanded', 'true');
        };

        const scheduleClose = () => {
            window.clearTimeout(entry.timer);
            entry.timer = window.setTimeout(() => closeNavSubmenu(entry), 160);
        };

        entry.toggle?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (item.classList.contains('is-open')) {
                closeNavSubmenu(entry);
            } else {
                open();
            }
        });

        item.addEventListener('mouseenter', () => {
            if (window.innerWidth >= 1024 && !item.hidden) {
                open();
            }
        });
        item.addEventListener('mouseleave', scheduleClose);
        panel.addEventListener('mouseenter', () => window.clearTimeout(entry.timer));
        panel.addEventListener('mouseleave', scheduleClose);
        panel.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeNavSubmenu(entry)));

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeNavSubmenu(entry);
            }
        });
    });

    document.addEventListener('click', (event) => {
        navSubmenus.forEach((entry) => {
            if (!entry.item.contains(event.target)) {
                closeNavSubmenu(entry);
            }
        });
    });

    window.addEventListener('resize', closeAllNavSubmenus);
}

function initMobileNav() {
    const header = document.getElementById('site-header');
    const headerInner = header?.querySelector('.tich-header__inner');
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-drawer]');
    const backdrop = document.querySelector('[data-nav-drawer-backdrop]');

    if (!header || !toggle || !drawer) {
        return;
    }

    const syncHeaderBarHeight = () => {
        if (!headerInner) {
            return;
        }
        const barHeight = Math.ceil(headerInner.offsetHeight || 0);
        if (barHeight > 0) {
            document.documentElement.style.setProperty('--tich-header-bar-height', `${Math.min(barHeight, Math.round(window.innerHeight * 0.3))}px`);
        }
    };

    syncHeaderBarHeight();
    window.addEventListener('resize', syncHeaderBarHeight);

    const isOpen = () => !drawer.hasAttribute('hidden');

    const openDrawer = () => {
        syncHeaderBarHeight();
        drawer.removeAttribute('hidden');
        header.classList.add('is-nav-open');
        document.body.classList.add('is-nav-open');
        document.body.style.top = `-${window.scrollY}px`;
        document.body.dataset.navScrollY = String(window.scrollY);
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Close menu');
    };

    const closeDrawer = () => {
        const scrollY = Number(document.body.dataset.navScrollY || '0');
        drawer.setAttribute('hidden', 'hidden');
        header.classList.remove('is-nav-open');
        document.body.classList.remove('is-nav-open');
        document.body.style.top = '';
        delete document.body.dataset.navScrollY;
        window.scrollTo(0, scrollY);
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open menu');
        syncHeaderBarHeight();
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

    // A dropped parent would otherwise take its dropdown links out of reach.
    const cloneNestedForMore = (item) => [...item.querySelectorAll('[data-nav-submenu] .tich-nav__link')]
        .map((link) => {
            const clone = link.cloneNode(true);
            clone.classList.remove('tich-nav__link');
            clone.classList.add('tich-nav__more-link', 'tich-nav__more-link--nested');
            clone.setAttribute('role', 'menuitem');
            clone.addEventListener('click', closeMoreMenu);

            return clone;
        });

    const fits = () => linksContainer.scrollWidth <= linksContainer.clientWidth + 1;

    const layout = () => {
        isLayoutScheduled = false;
        const wasOpen = isOpen;
        closeMoreMenu();
        closeAllNavSubmenus();
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

            [clone, ...cloneNestedForMore(lastItem)].reverse().forEach((node) => {
                moreMenu.prepend(node);
            });
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
