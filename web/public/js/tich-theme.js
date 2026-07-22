(function () {
    const storageKey = 'tich-theme';

    function getPreference() {
        try {
            const stored = localStorage.getItem(storageKey);

            if (stored === 'light' || stored === 'dark' || stored === 'system') {
                return stored;
            }
        } catch (e) {}

        return 'system';
    }

    function systemPrefersDark() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function resolveTheme(preference) {
        if (preference === 'dark') {
            return 'dark';
        }

        if (preference === 'light') {
            return 'light';
        }

        return systemPrefersDark() ? 'dark' : 'light';
    }

    function applyTheme(resolvedTheme) {
        if (resolvedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
        }
    }

    function setPreference(preference) {
        try {
            localStorage.setItem(storageKey, preference);
        } catch (e) {}

        applyTheme(resolveTheme(preference));
        syncToggleState();
    }

    function syncToggleState() {
        const preference = getPreference();
        const resolved = resolveTheme(preference);
        const isDark = resolved === 'dark';

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.classList.toggle('is-dark', isDark);

            if (preference === 'system') {
                button.setAttribute(
                    'aria-label',
                    isDark ? 'Theme: system (dark). Click to use light mode.' : 'Theme: system (light). Click to use dark mode.'
                );
                button.setAttribute('title', 'Following device theme');
            } else {
                button.setAttribute(
                    'aria-label',
                    isDark ? 'Switch to light mode' : 'Switch to dark mode'
                );
                button.removeAttribute('title');
            }
        });
    }

    function initThemeToggle() {
        applyTheme(resolveTheme(getPreference()));
        syncToggleState();

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (getPreference() === 'system') {
                applyTheme(resolveTheme('system'));
                syncToggleState();
            }
        });

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const preference = getPreference();
                const resolved = resolveTheme(preference);

                if (preference === 'system') {
                    setPreference(resolved === 'dark' ? 'light' : 'dark');
                    return;
                }

                setPreference(resolved === 'dark' ? 'light' : 'dark');
            });

            button.addEventListener('dblclick', (event) => {
                event.preventDefault();
                setPreference('system');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initThemeToggle);
    } else {
        initThemeToggle();
    }
})();
