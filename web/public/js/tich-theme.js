(function () {
    const storageKey = 'tich-theme';

    function getTheme() {
        return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function setTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }

        try {
            localStorage.setItem(storageKey, theme);
        } catch (e) {}

        syncToggleState();
    }

    function syncToggleState() {
        const isDark = getTheme() === 'dark';

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.classList.toggle('is-dark', isDark);
            button.setAttribute(
                'aria-label',
                isDark ? 'Switch to light mode' : 'Switch to dark mode'
            );
        });
    }

    function initThemeToggle() {
        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                setTheme(getTheme() === 'dark' ? 'light' : 'dark');
            });
        });

        syncToggleState();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle);
    } else {
        initThemeToggle();
    }
})();
