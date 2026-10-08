document.addEventListener('DOMContentLoaded', () => {
    const STORAGE_WIDTH = 'tich.adminSidebar.width';
    const STORAGE_COLLAPSED = 'tich.adminSidebar.collapsed';
    const STORAGE_SCROLL = 'tich.adminSidebar.scroll.';
    const DEFAULT_WIDTH = 272;
    const MIN_WIDTH = 200;
    const MAX_WIDTH = 420;
    const COLLAPSED_WIDTH = 72;
    const COLLAPSE_SNAP = 150;

    const desktopMq = window.matchMedia('(min-width: 1024px)');

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

    const readStoredWidth = () => {
        const raw = Number.parseInt(window.localStorage.getItem(STORAGE_WIDTH) || '', 10);
        return Number.isFinite(raw) ? clamp(raw, MIN_WIDTH, MAX_WIDTH) : DEFAULT_WIDTH;
    };

    const readStoredCollapsed = () => window.localStorage.getItem(STORAGE_COLLAPSED) === '1';

    const scrollStorageKey = (sidebar) => {
        const id = (sidebar.id || '').trim();
        if (id) {
            return STORAGE_SCROLL + id;
        }
        const title = (sidebar.querySelector('.tich-admin-sidebar__title')?.textContent || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
        return STORAGE_SCROLL + (title || 'default');
    };

    const persistSidebarScroll = (sidebar) => {
        try {
            window.sessionStorage.setItem(scrollStorageKey(sidebar), String(Math.round(sidebar.scrollTop)));
        } catch (e) {
            // ignore quota / private mode
        }
    };

    const restoreSidebarScroll = (sidebar) => {
        let raw = '';
        try {
            raw = window.sessionStorage.getItem(scrollStorageKey(sidebar)) || '';
        } catch (e) {
            return;
        }
        const top = Number.parseInt(raw, 10);
        if (!Number.isFinite(top) || top <= 0) {
            return;
        }
        const applyScroll = () => {
            const max = Math.max(0, sidebar.scrollHeight - sidebar.clientHeight);
            sidebar.scrollTop = Math.min(top, max);
        };
        applyScroll();
        window.requestAnimationFrame(applyScroll);
    };

    const ensureTooltips = (sidebar) => {
        sidebar.querySelectorAll('.tich-admin-sidebar__link, .tich-admin-sidebar__group-toggle').forEach((el) => {
            if (el.getAttribute('title')) {
                return;
            }
            const label = el.querySelector('.tich-admin-sidebar__label');
            const text = (label?.textContent || el.textContent || '').replace(/\s+/g, ' ').trim();
            if (text) {
                el.setAttribute('title', text);
            }
        });
    };

    const closeOpenGroups = (sidebar) => {
        sidebar.querySelectorAll('[data-sidebar-group].is-open').forEach((group) => {
            const toggle = group.querySelector(':scope > [data-sidebar-group-toggle]');
            const panel = group.querySelector(':scope > [data-sidebar-group-panel]');
            group.classList.remove('is-open');
            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
            }
            if (panel) {
                panel.hidden = true;
                panel.style.height = '';
                panel.style.opacity = '';
                panel.style.overflow = '';
                panel.style.transition = '';
                panel.style.top = '';
                panel.style.left = '';
                panel.style.maxHeight = '';
            }
        });
    };

    document.querySelectorAll('.tich-admin').forEach((admin) => {
        const sidebar = admin.querySelector('.tich-admin-sidebar');
        if (!sidebar || admin.dataset.sidebarLayoutReady === 'true') {
            return;
        }
        admin.dataset.sidebarLayoutReady = 'true';

        let width = readStoredWidth();
        let collapsed = readStoredCollapsed();
        let dragging = false;

        const apply = () => {
            if (!desktopMq.matches) {
                admin.classList.remove('is-sidebar-collapsed', 'is-sidebar-resizing');
                admin.style.removeProperty('--tich-admin-sidebar-width');
                return;
            }

            admin.classList.toggle('is-sidebar-collapsed', collapsed);
            admin.style.setProperty(
                '--tich-admin-sidebar-width',
                `${collapsed ? COLLAPSED_WIDTH : width}px`
            );

            const toggle = admin.querySelector('[data-sidebar-collapse-toggle]');
            if (toggle) {
                toggle.setAttribute('aria-pressed', collapsed ? 'true' : 'false');
                toggle.setAttribute(
                    'aria-label',
                    collapsed ? 'Expand sidebar' : 'Collapse sidebar to icons'
                );
                toggle.title = collapsed ? 'Expand sidebar' : 'Collapse sidebar';
                const label = toggle.querySelector('[data-sidebar-collapse-label]');
                if (label) {
                    label.textContent = collapsed ? 'Expand' : 'Collapse';
                }
                const icon = toggle.querySelector('.tich-admin-sidebar__collapse-icon');
                if (icon) {
                    icon.textContent = collapsed ? '›' : '‹';
                }
            }
        };

        const persist = () => {
            window.localStorage.setItem(STORAGE_WIDTH, String(width));
            window.localStorage.setItem(STORAGE_COLLAPSED, collapsed ? '1' : '0');
        };

        const setCollapsed = (next) => {
            collapsed = !!next;
            if (collapsed) {
                closeOpenGroups(sidebar);
            }
            persist();
            apply();
        };

        const setWidth = (next, { snapCollapse = false } = {}) => {
            if (snapCollapse && next < COLLAPSE_SNAP) {
                setCollapsed(true);
                return;
            }
            width = clamp(next, MIN_WIDTH, MAX_WIDTH);
            if (collapsed && next >= MIN_WIDTH) {
                collapsed = false;
            }
            persist();
            apply();
        };

        ensureTooltips(sidebar);
        restoreSidebarScroll(sidebar);

        // Isolate sidebar wheel scrolling from the main content pane.
        if (sidebar.dataset.sidebarScrollBound !== 'true') {
            sidebar.dataset.sidebarScrollBound = 'true';
            let scrollPersistTimer = 0;
            const scheduleScrollPersist = () => {
                if (scrollPersistTimer) {
                    window.clearTimeout(scrollPersistTimer);
                }
                scrollPersistTimer = window.setTimeout(() => {
                    scrollPersistTimer = 0;
                    persistSidebarScroll(sidebar);
                }, 80);
            };

            sidebar.addEventListener('scroll', scheduleScrollPersist, { passive: true });

            sidebar.addEventListener(
                'click',
                (event) => {
                    const link = event.target.closest('a.tich-admin-sidebar__link, a[href]');
                    if (!link || !sidebar.contains(link)) {
                        return;
                    }
                    persistSidebarScroll(sidebar);
                },
                true
            );

            window.addEventListener('pagehide', () => persistSidebarScroll(sidebar));

            sidebar.addEventListener(
                'wheel',
                (event) => {
                    if (!desktopMq.matches && !admin.classList.contains('is-sidebar-open')) {
                        return;
                    }

                    const maxScroll = sidebar.scrollHeight - sidebar.clientHeight;
                    if (maxScroll <= 1) {
                        return;
                    }

                    const atTop = sidebar.scrollTop <= 0;
                    const atBottom = sidebar.scrollTop >= maxScroll - 1;
                    if ((event.deltaY < 0 && atTop) || (event.deltaY > 0 && atBottom)) {
                        event.preventDefault();
                    }
                },
                { passive: false }
            );
        }

        if (!sidebar.querySelector('[data-sidebar-resize-handle]')) {
            const handle = document.createElement('div');
            handle.className = 'tich-admin-sidebar__resize';
            handle.setAttribute('data-sidebar-resize-handle', '');
            handle.setAttribute('role', 'separator');
            handle.setAttribute('aria-orientation', 'vertical');
            handle.setAttribute('aria-label', 'Resize sidebar');
            handle.setAttribute('title', 'Drag to resize');
            sidebar.appendChild(handle);

            handle.addEventListener('pointerdown', (event) => {
                if (!desktopMq.matches || event.button !== 0) {
                    return;
                }
                event.preventDefault();
                dragging = true;
                admin.classList.add('is-sidebar-resizing');
                handle.setPointerCapture(event.pointerId);
                const startX = event.clientX;
                const startWidth = collapsed ? COLLAPSED_WIDTH : width;
                let frame = 0;
                let latestX = startX;

                const paint = () => {
                    frame = 0;
                    if (!dragging) {
                        return;
                    }
                    const delta = latestX - startX;
                    const next = startWidth + delta;
                    if (collapsed && next > COLLAPSE_SNAP) {
                        collapsed = false;
                        width = clamp(next, MIN_WIDTH, MAX_WIDTH);
                        admin.classList.remove('is-sidebar-collapsed');
                        admin.style.setProperty('--tich-admin-sidebar-width', `${width}px`);
                        return;
                    }
                    if (!collapsed && next < COLLAPSE_SNAP) {
                        admin.classList.add('is-sidebar-collapsed');
                        admin.style.setProperty('--tich-admin-sidebar-width', `${COLLAPSED_WIDTH}px`);
                        return;
                    }
                    if (!collapsed) {
                        width = clamp(next, MIN_WIDTH, MAX_WIDTH);
                        admin.style.setProperty('--tich-admin-sidebar-width', `${width}px`);
                    }
                };

                const onMove = (moveEvent) => {
                    if (!dragging) {
                        return;
                    }
                    latestX = moveEvent.clientX;
                    if (!frame) {
                        frame = window.requestAnimationFrame(paint);
                    }
                };

                const onUp = (upEvent) => {
                    dragging = false;
                    if (frame) {
                        window.cancelAnimationFrame(frame);
                        frame = 0;
                    }
                    admin.classList.remove('is-sidebar-resizing');
                    try {
                        handle.releasePointerCapture(upEvent.pointerId);
                    } catch (e) {
                        // ignore
                    }
                    handle.removeEventListener('pointermove', onMove);
                    handle.removeEventListener('pointerup', onUp);
                    handle.removeEventListener('pointercancel', onUp);

                    const rect = sidebar.getBoundingClientRect();
                    setWidth(rect.width, { snapCollapse: true });
                };

                handle.addEventListener('pointermove', onMove);
                handle.addEventListener('pointerup', onUp);
                handle.addEventListener('pointercancel', onUp);
            });

            handle.addEventListener('dblclick', () => {
                if (!desktopMq.matches) {
                    return;
                }
                width = DEFAULT_WIDTH;
                collapsed = false;
                persist();
                apply();
            });
        }

        if (!sidebar.querySelector('[data-sidebar-collapse-toggle]')) {
            const controls = document.createElement('div');
            controls.className = 'tich-admin-sidebar__controls';
            controls.innerHTML = `
                <button type="button" class="tich-admin-sidebar__collapse-btn" data-sidebar-collapse-toggle aria-pressed="false" title="Collapse sidebar">
                    <span class="tich-admin-sidebar__collapse-icon" aria-hidden="true">‹</span>
                    <span class="tich-admin-sidebar__label" data-sidebar-collapse-label>Collapse</span>
                </button>
            `;
            sidebar.appendChild(controls);

            const collapseBtn = controls.querySelector('[data-sidebar-collapse-toggle]');
            collapseBtn?.addEventListener('click', () => {
                if (!desktopMq.matches) {
                    return;
                }
                setCollapsed(!collapsed);
            });
        }

        // Accordion flyouts while icon-collapsed: only one open group at a time.
        sidebar.addEventListener('click', (event) => {
            if (!admin.classList.contains('is-sidebar-collapsed')) {
                return;
            }
            const toggle = event.target.closest('[data-sidebar-group-toggle]');
            if (!toggle || !sidebar.contains(toggle)) {
                return;
            }
            const group = toggle.closest('[data-sidebar-group]');
            if (!group) {
                return;
            }
            window.requestAnimationFrame(() => {
                if (!group.classList.contains('is-open')) {
                    return;
                }
                sidebar.querySelectorAll('[data-sidebar-group].is-open').forEach((other) => {
                    if (other === group || group.contains(other) || other.contains(group)) {
                        return;
                    }
                    const otherToggle = other.querySelector(':scope > [data-sidebar-group-toggle]');
                    const otherPanel = other.querySelector(':scope > [data-sidebar-group-panel]');
                    other.classList.remove('is-open');
                    if (otherToggle) {
                        otherToggle.setAttribute('aria-expanded', 'false');
                    }
                    if (otherPanel) {
                        otherPanel.hidden = true;
                    }
                });
            });
        });

        document.addEventListener('click', (event) => {
            if (!admin.classList.contains('is-sidebar-collapsed')) {
                return;
            }
            if (sidebar.contains(event.target)) {
                return;
            }
            closeOpenGroups(sidebar);
        });

        const onMq = () => apply();
        if (typeof desktopMq.addEventListener === 'function') {
            desktopMq.addEventListener('change', onMq);
        } else if (typeof desktopMq.addListener === 'function') {
            desktopMq.addListener(onMq);
        }

        apply();
    });
});
