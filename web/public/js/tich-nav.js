document.addEventListener('DOMContentLoaded', () => {
    initMobileNav();
    initNavOverflow();
});

function initMobileNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-drawer]');
    if (!toggle || !drawer) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isHidden = drawer.hasAttribute('hidden');
        if (isHidden) {
            drawer.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            drawer.setAttribute('hidden', 'hidden');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function initNavOverflow() {
    const linksContainer = document.querySelector('[data-nav-links]');
    const moreWrap = document.querySelector('[data-nav-more]');
    const moreMenu = document.querySelector('[data-nav-more-menu]');
    const moreToggle = document.querySelector('[data-nav-more-toggle]');

    if (!linksContainer || !moreWrap || !moreMenu || !moreToggle) {
        return;
    }

    const items = [...linksContainer.querySelectorAll('[data-nav-item]')];
    let resizeTimer = null;
    let isOpen = false;

    const closeMoreMenu = () => {
        isOpen = false;
        moreWrap.classList.remove('is-open');
        moreMenu.hidden = true;
        moreToggle.setAttribute('aria-expanded', 'false');
    };

    const openMoreMenu = () => {
        isOpen = true;
        moreWrap.classList.add('is-open');
        moreMenu.hidden = false;
        moreToggle.setAttribute('aria-expanded', 'true');
    };

    moreToggle.addEventListener('click', (event) => {
        event.stopPropagation();
        if (isOpen) {
            closeMoreMenu();
        } else {
            openMoreMenu();
        }
    });

    document.addEventListener('click', (event) => {
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

        return clone;
    };

    const layout = () => {
        closeMoreMenu();
        moreMenu.innerHTML = '';

        items.forEach((item) => {
            item.hidden = false;
        });

        moreWrap.hidden = true;
        moreWrap.classList.remove('is-active');

        if (window.innerWidth < 1024) {
            return;
        }

        const fits = () => linksContainer.scrollWidth <= linksContainer.clientWidth + 1;

        if (!fits()) {
            moreWrap.hidden = false;
        }

        let guard = 0;

        while (!fits() && items.filter((item) => !item.hidden).length > 1 && guard < items.length + 2) {
            guard += 1;

            const visibleItems = items.filter((item) => !item.hidden);
            const lastItem = visibleItems[visibleItems.length - 1];
            const clone = cloneLinkForMore(lastItem);

            if (!clone) {
                break;
            }

            moreMenu.prepend(clone);
            lastItem.hidden = true;
            moreWrap.hidden = false;
        }

        if (moreMenu.children.length === 0) {
            moreWrap.hidden = true;
            closeMoreMenu();
        } else if (moreMenu.querySelector('.is-active')) {
            moreWrap.classList.add('is-active');
        }
    };

    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(layout, 120);
    });

    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(() => layout());
        observer.observe(linksContainer);
        const actions = document.querySelector('.tich-nav__actions');
        if (actions) {
            observer.observe(actions);
        }
    }

    layout();
}
