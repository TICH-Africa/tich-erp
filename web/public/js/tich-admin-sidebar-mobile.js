document.addEventListener('DOMContentLoaded', () => {
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

        adminRoot.insertBefore(toggle, sidebar);
        adminRoot.insertBefore(shell, sidebar);
        shell.appendChild(backdrop);
        shell.appendChild(sidebar);

        const isOpen = () => adminRoot.classList.contains('is-sidebar-open');

        const closeSidebar = () => {
            adminRoot.classList.remove('is-sidebar-open');
            document.body.classList.remove('is-admin-sidebar-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', `Open ${moduleLabel}`);
        };

        const openSidebar = () => {
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
