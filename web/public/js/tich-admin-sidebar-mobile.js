document.addEventListener('DOMContentLoaded', () => {
    const header = document.getElementById('site-header');
    const headerInner = header?.querySelector('.tich-header__inner');

    const syncHeaderHeight = () => {
        if (!header || !headerInner) {
            return;
        }

        // Measure only the top nav row for bar height. Full chrome = bar + module menu.
        // Never write the combined height back into --tich-header-bar-height (that
        // previously inflated the top navbar min-height and made it huge).
        const barHeight = Math.ceil(headerInner.offsetHeight || 0);
        const moduleToggle = header.querySelector('.tich-admin-sidebar-mobile__toggle');
        const toggleVisible = moduleToggle
            && window.getComputedStyle(moduleToggle).display !== 'none'
            && !header.classList.contains('is-nav-open');
        const toggleHeight = toggleVisible ? Math.ceil(moduleToggle.offsetHeight || 0) : 0;
        const chromeHeight = barHeight + toggleHeight;

        if (barHeight > 0) {
            document.documentElement.style.setProperty('--tich-header-bar-height', `${barHeight}px`);
            document.documentElement.style.setProperty('--tich-header-height', `${chromeHeight}px`);
        }
    };

    syncHeaderHeight();
    window.addEventListener('resize', syncHeaderHeight);

    if (typeof ResizeObserver !== 'undefined' && headerInner) {
        new ResizeObserver(syncHeaderHeight).observe(headerInner);
    }

    document.querySelectorAll('.tich-admin').forEach((adminRoot) => {
        const sidebar = adminRoot.querySelector('.tich-admin-sidebar');

        if (!sidebar || adminRoot.dataset.adminSidebarReady === 'true') {
            return;
        }

        adminRoot.dataset.adminSidebarReady = 'true';

        const titleEl = sidebar.querySelector('.tich-admin-sidebar__title');
        const moduleLabel = titleEl?.textContent?.trim() || 'Module menu';

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'tich-admin-sidebar-mobile__toggle';
        toggle.setAttribute('data-admin-sidebar-toggle', '');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', sidebar.id || 'admin-sidebar-panel');
        toggle.innerHTML = `
            <span class="tich-admin-sidebar-mobile__toggle-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </span>
            <span class="tich-admin-sidebar-mobile__toggle-label">${moduleLabel}</span>
        `;

        const shell = document.createElement('div');
        shell.className = 'tich-admin-sidebar-mobile__shell';
        shell.setAttribute('data-admin-sidebar-shell', '');

        const backdrop = document.createElement('div');
        backdrop.className = 'tich-admin-sidebar-mobile__backdrop';
        backdrop.setAttribute('data-admin-sidebar-backdrop', '');
        backdrop.setAttribute('aria-hidden', 'true');

        if (!sidebar.id) {
            sidebar.id = 'admin-sidebar-panel';
        }

        // Attach module menu directly under the site nav bar (inside sticky header).
        if (header && headerInner) {
            headerInner.insertAdjacentElement('afterend', toggle);
        } else {
            adminRoot.insertBefore(toggle, sidebar);
        }

        adminRoot.insertBefore(shell, sidebar);
        shell.appendChild(backdrop);
        shell.appendChild(sidebar);

        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(syncHeaderHeight).observe(toggle);
        }
        syncHeaderHeight();

        const isOpen = () => adminRoot.classList.contains('is-sidebar-open');

        const closeSidebar = () => {
            adminRoot.classList.remove('is-sidebar-open');
            document.body.classList.remove('is-admin-sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', `Open ${moduleLabel}`);
        };

        const openSidebar = () => {
            syncHeaderHeight();
            adminRoot.classList.add('is-sidebar-open');
            document.body.classList.add('is-admin-sidebar-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', `Close ${moduleLabel}`);
        };

        toggle.setAttribute('aria-label', `Open ${moduleLabel}`);

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            if (isOpen()) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        backdrop.addEventListener('click', closeSidebar);

        shell.querySelectorAll('a, button[type="submit"]').forEach((element) => {
            element.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && isOpen()) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024 && isOpen()) {
                closeSidebar();
            }
        });
    });
});
