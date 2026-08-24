/**
 * Prevent double form submissions across the platform.
 * - Injects a one-time _submit_nonce per form
 * - Disables submit controls after the first click/submit
 * Opt out: add data-allow-resubmit on the <form>
 */
(function () {
    function uuid() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function ensureNonce(form) {
        if (form.querySelector('input[name="_submit_nonce"]')) {
            return;
        }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_submit_nonce';
        input.value = uuid();
        form.appendChild(input);
    }

    function lockForm(form) {
        if (form.getAttribute('data-submit-locked') === '1') {
            return false;
        }
        form.setAttribute('data-submit-locked', '1');
        form.setAttribute('aria-busy', 'true');

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (el) {
            el.disabled = true;
            el.setAttribute('aria-disabled', 'true');
            if (el.tagName === 'BUTTON' && !el.dataset.originalLabel) {
                el.dataset.originalLabel = el.textContent;
                el.textContent = el.dataset.busyLabel || 'Saving…';
            }
        });

        return true;
    }

    function unlockForm(form) {
        form.removeAttribute('data-submit-locked');
        form.removeAttribute('aria-busy');
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (el) {
            el.disabled = false;
            el.removeAttribute('aria-disabled');
            if (el.tagName === 'BUTTON' && el.dataset.originalLabel) {
                el.textContent = el.dataset.originalLabel;
                delete el.dataset.originalLabel;
            }
        });
        // Rotate nonce so a retry after unlock is treated as a new submission.
        var nonce = form.querySelector('input[name="_submit_nonce"]');
        if (nonce) {
            nonce.value = uuid();
        }
    }

    function bindForm(form) {
        if (form.method && form.method.toLowerCase() === 'get') {
            return;
        }
        if (form.hasAttribute('data-allow-resubmit')) {
            return;
        }

        ensureNonce(form);

        form.addEventListener('submit', function (event) {
            if (form.getAttribute('data-submit-locked') === '1') {
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            ensureNonce(form);
            lockForm(form);
        }, true);

        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Capture click before HTML5 validation fails and re-enables.
                if (form.checkValidity && !form.checkValidity()) {
                    return;
                }
            });
        });
    }

    function init() {
        document.querySelectorAll('form').forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Unlock forms restored from bfcache so the user can submit again after Back.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }
        document.querySelectorAll('form[data-submit-locked="1"]').forEach(unlockForm);
    });

    // Forms injected later (modals, dynamic panels)
    if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!(node instanceof HTMLElement)) {
                        return;
                    }
                    if (node.matches && node.matches('form')) {
                        bindForm(node);
                    }
                    if (node.querySelectorAll) {
                        node.querySelectorAll('form').forEach(bindForm);
                    }
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
