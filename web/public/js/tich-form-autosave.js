/**
 * Platform form draft autosave (browser localStorage only).
 * Survives refresh/back/offline; does not sync across devices.
 * Does NOT clear drafts on successful submit (per product decision).
 *
 * Opt-in:  data-tich-autosave on <form>
 * Opt-out: data-no-autosave on <form>
 * Skips: login, search, filter bars, GET forms, multipart file-only flows without fields.
 */
(function () {
    'use strict';

    var STORAGE_PREFIX = 'tich.form-draft.v1:';
    var SAVE_DEBOUNCE_MS = 600;
    var SKIP_NAME_RE = /^(password|password_confirmation|_token|_method|_submit_nonce)$/i;
    var SKIP_TYPE_RE = /^(password|file|submit|button|reset|image)$/i;

    function shouldSkipForm(form) {
        if (!form || form.tagName !== 'FORM') return true;
        if (form.getAttribute('data-no-autosave') != null) return true;
        if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') return true;

        var action = (form.getAttribute('action') || '').toLowerCase();
        var id = (form.id || '').toLowerCase();
        var cls = (form.className || '').toString().toLowerCase();

        if (form.getAttribute('data-tich-autosave') != null) {
            return false;
        }

        // Auto-detect authenticated/public POST forms with a real submit control.
        if (!form.querySelector('button[type="submit"], input[type="submit"]')) return true;
        if (/login|sign-?in|logout|search|filter|csrf/.test(id + ' ' + cls + ' ' + action)) return true;
        if (form.closest('.tich-search, .tich-filters, [data-search], [role="search"]')) return true;

        // Require at least one durable field.
        var fields = form.querySelectorAll('input, textarea, select');
        var durable = 0;
        fields.forEach(function (el) {
            if (SKIP_TYPE_RE.test(el.type || '') || SKIP_NAME_RE.test(el.name || '')) return;
            if (!el.name) return;
            durable += 1;
        });
        return durable < 1;
    }

    function storageKey(form) {
        var custom = form.getAttribute('data-autosave-key');
        if (custom) return STORAGE_PREFIX + custom;
        var action = form.getAttribute('action') || window.location.pathname;
        var method = (form.getAttribute('method') || 'post').toLowerCase();
        var id = form.id || '';
        return STORAGE_PREFIX + method + ':' + action + ':' + id + ':' + window.location.pathname;
    }

    function collect(form) {
        var data = {};
        form.querySelectorAll('input, textarea, select').forEach(function (el) {
            var name = el.name;
            if (!name || SKIP_NAME_RE.test(name) || SKIP_TYPE_RE.test(el.type || '')) return;
            if (el.disabled) return;

            if (el.type === 'checkbox') {
                if (!Object.prototype.hasOwnProperty.call(data, name)) data[name] = [];
                if (el.checked) data[name].push(el.value || '1');
                return;
            }
            if (el.type === 'radio') {
                if (el.checked) data[name] = el.value;
                return;
            }
            if (el.tagName === 'SELECT' && el.multiple) {
                data[name] = Array.prototype.slice.call(el.selectedOptions).map(function (o) { return o.value; });
                return;
            }
            data[name] = el.value;
        });
        return data;
    }

    function applyValue(el, value) {
        if (el.type === 'checkbox') {
            var list = Array.isArray(value) ? value : [value];
            el.checked = list.indexOf(el.value || '1') !== -1 || (list.indexOf('1') !== -1 && !el.value);
            return;
        }
        if (el.type === 'radio') {
            el.checked = String(el.value) === String(value);
            return;
        }
        if (el.tagName === 'SELECT' && el.multiple && Array.isArray(value)) {
            Array.prototype.forEach.call(el.options, function (opt) {
                opt.selected = value.indexOf(opt.value) !== -1;
            });
            return;
        }
        el.value = value == null ? '' : value;
    }

    function restore(form, data) {
        if (!data || typeof data !== 'object') return;
        form.querySelectorAll('input, textarea, select').forEach(function (el) {
            var name = el.name;
            if (!name || !Object.prototype.hasOwnProperty.call(data, name)) return;
            if (SKIP_NAME_RE.test(name) || SKIP_TYPE_RE.test(el.type || '')) return;
            applyValue(el, data[name]);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function bindForm(form) {
        if (shouldSkipForm(form) || form.getAttribute('data-autosave-bound') === '1') return;
        form.setAttribute('data-autosave-bound', '1');

        var key = storageKey(form);
        var timer = null;

        try {
            var raw = window.localStorage.getItem(key);
            if (raw) {
                var parsed = JSON.parse(raw);
                if (parsed && parsed.fields) {
                    restore(form, parsed.fields);
                }
            }
        } catch (e) {
            // Ignore corrupt drafts.
        }

        function save() {
            try {
                window.localStorage.setItem(key, JSON.stringify({
                    savedAt: Date.now(),
                    fields: collect(form),
                }));
            } catch (e) {
                // Quota / private mode — ignore.
            }
        }

        function scheduleSave() {
            if (timer) window.clearTimeout(timer);
            timer = window.setTimeout(save, SAVE_DEBOUNCE_MS);
        }

        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', scheduleSave);
        // Keep draft after submit (do not clear).
    }

    function scan() {
        document.querySelectorAll('form').forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan);
    } else {
        scan();
    }

    // Late-rendered forms (modals / turbo-ish partials).
    if (window.MutationObserver) {
        var obs = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                var nodes = mutations[i].addedNodes;
                for (var j = 0; j < nodes.length; j++) {
                    var node = nodes[j];
                    if (node.nodeType !== 1) continue;
                    if (node.tagName === 'FORM') bindForm(node);
                    else if (node.querySelectorAll) node.querySelectorAll('form').forEach(bindForm);
                }
            }
        });
        obs.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
