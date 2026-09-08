document.addEventListener('DOMContentLoaded', () => {
    const DURATION_MS = 280;

    const prefersReducedMotion = () =>
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const clearInline = (panel) => {
        panel.style.height = '';
        panel.style.opacity = '';
        panel.style.overflow = '';
        panel.style.transition = '';
    };

    const clearFlyoutPosition = (panel) => {
        panel.style.top = '';
        panel.style.left = '';
        panel.style.maxHeight = '';
    };

    const positionCollapsedFlyout = (toggle, panel) => {
        const rect = toggle.getBoundingClientRect();
        const gap = 8;
        const maxHeight = Math.min(window.innerHeight - 24, 384);
        let top = rect.top;
        let left = rect.right + gap;

        panel.style.maxHeight = `${maxHeight}px`;
        panel.hidden = false;
        const panelRect = panel.getBoundingClientRect();

        if (top + panelRect.height > window.innerHeight - 12) {
            top = Math.max(12, window.innerHeight - panelRect.height - 12);
        }
        if (left + panelRect.width > window.innerWidth - 12) {
            left = Math.max(12, rect.left - panelRect.width - gap);
        }

        panel.style.top = `${top}px`;
        panel.style.left = `${left}px`;
    };

    const setOpenState = (group, toggle, panel, open, animate) => {
        const collapsed = !!group.closest('.tich-admin.is-sidebar-collapsed');
        group.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

        const token = String((Number(panel.dataset.sidebarAnimToken || 0) + 1));
        panel.dataset.sidebarAnimToken = token;

        if (!open) {
            if (!animate || prefersReducedMotion() || collapsed) {
                panel.hidden = true;
                clearInline(panel);
                clearFlyoutPosition(panel);
                return;
            }

            panel.style.overflow = 'hidden';
            panel.style.transition = `height ${DURATION_MS}ms ease, opacity ${DURATION_MS}ms ease`;
            panel.style.height = `${panel.scrollHeight}px`;
            panel.style.opacity = '1';
            void panel.offsetHeight;
            panel.style.height = '0px';
            panel.style.opacity = '0';

            const onEnd = (event) => {
                if (event.propertyName !== 'height') {
                    return;
                }
                if (panel.dataset.sidebarAnimToken !== token) {
                    return;
                }
                panel.removeEventListener('transitionend', onEnd);
                if (!group.classList.contains('is-open')) {
                    panel.hidden = true;
                    clearInline(panel);
                    clearFlyoutPosition(panel);
                }
            };
            panel.addEventListener('transitionend', onEnd);
            return;
        }

        if (collapsed || !animate || prefersReducedMotion()) {
            panel.hidden = false;
            clearInline(panel);
            if (collapsed) {
                positionCollapsedFlyout(toggle, panel);
            } else {
                clearFlyoutPosition(panel);
            }
            return;
        }

        clearFlyoutPosition(panel);
        panel.hidden = false;
        panel.style.overflow = 'hidden';
        panel.style.transition = `height ${DURATION_MS}ms ease, opacity ${DURATION_MS}ms ease`;
        panel.style.height = '0px';
        panel.style.opacity = '0';
        void panel.offsetHeight;
        panel.style.height = `${panel.scrollHeight}px`;
        panel.style.opacity = '1';

        const onEnd = (event) => {
            if (event.propertyName !== 'height') {
                return;
            }
            if (panel.dataset.sidebarAnimToken !== token) {
                return;
            }
            panel.removeEventListener('transitionend', onEnd);
            if (group.classList.contains('is-open')) {
                clearInline(panel);
            }
        };
        panel.addEventListener('transitionend', onEnd);
    };

    document.querySelectorAll('[data-sidebar-group]').forEach((group) => {
        const toggle = group.querySelector(':scope > [data-sidebar-group-toggle]');
        const panel = group.querySelector(':scope > [data-sidebar-group-panel]');

        if (!toggle || !panel) {
            return;
        }

        if (toggle.getAttribute('aria-expanded') === 'true' || !panel.hidden) {
            group.classList.add('is-open');
            panel.hidden = false;
        }

        toggle.addEventListener('click', () => {
            const willOpen = !group.classList.contains('is-open');
            const collapsed = !!group.closest('.tich-admin.is-sidebar-collapsed');
            setOpenState(group, toggle, panel, willOpen, !collapsed);
        });
    });

    window.addEventListener('resize', () => {
        document.querySelectorAll('.tich-admin.is-sidebar-collapsed [data-sidebar-group].is-open').forEach((group) => {
            const toggle = group.querySelector(':scope > [data-sidebar-group-toggle]');
            const panel = group.querySelector(':scope > [data-sidebar-group-panel]');
            if (toggle && panel && !panel.hidden) {
                positionCollapsedFlyout(toggle, panel);
            }
        });
    });
});
