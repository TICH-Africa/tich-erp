/**
 * Client UI protection: hide context menu (incl. Inspect) and block common
 * DevTools shortcuts. Deterrent only - it cannot fully prevent inspection.
 * Opt out: set window.TICH_ALLOW_INSPECT = true before this script loads,
 * or data-allow-inspect on <html>/<body>.
 */
(function () {
    if (window.TICH_ALLOW_INSPECT === true) {
        return;
    }
    if (document.documentElement.hasAttribute('data-allow-inspect')
        || (document.body && document.body.hasAttribute('data-allow-inspect'))) {
        return;
    }

    function isEditableTarget(target) {
        if (!target || !(target instanceof Element)) {
            return false;
        }
        var tag = target.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') {
            return true;
        }
        if (target.isContentEditable) {
            return true;
        }
        return !!target.closest('input, textarea, select, [contenteditable="true"]');
    }

    document.addEventListener('contextmenu', function (event) {
        if (isEditableTarget(event.target)) {
            // Allow paste/spellcheck menus in form fields.
            return;
        }
        event.preventDefault();
        event.stopPropagation();
    }, true);

    document.addEventListener('keydown', function (event) {
        var key = event.key || '';
        var lower = key.toLowerCase();
        var ctrl = event.ctrlKey || event.metaKey;
        var shift = event.shiftKey;
        var alt = event.altKey;

        // F12
        if (key === 'F12') {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        // Ctrl+Shift+I / J / C (Inspect / Console / Element picker)
        if (ctrl && shift && (lower === 'i' || lower === 'j' || lower === 'c')) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }

        // Ctrl+U (view source), Ctrl+S (save page)
        if (ctrl && !shift && !alt && (lower === 'u' || lower === 's')) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);

    document.addEventListener('dragstart', function (event) {
        if (isEditableTarget(event.target)) {
            return;
        }
        // Reduce casual image/text scrape via drag; forms untouched.
        if (event.target && event.target.tagName === 'IMG') {
            event.preventDefault();
        }
    }, true);
})();
