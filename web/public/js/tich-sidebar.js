document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-sidebar-group]').forEach((group) => {
        const toggle = group.querySelector('[data-sidebar-group-toggle]');
        const panel = group.querySelector('[data-sidebar-group-panel]');

        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', () => {
            const isOpen = group.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            panel.hidden = !isOpen;
        });
    });
});
