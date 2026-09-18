/**
 * Enhance POST data forms with unified form chrome (amount bar + section heads).
 * Skips GET/search forms, auth mini-forms, and anything already using .uf-form.
 */
(function () {
    'use strict';

    function fieldCount(form) {
        return form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea').length;
    }

    function shouldSkip(form) {
        if (!(form instanceof HTMLFormElement)) return true;
        if (form.dataset.uf === 'skip' || form.dataset.uf === 'ready' || form.closest('[data-uf="skip"]')) return true;
        if (form.closest('.uf-form')) return true;
        if (form.classList.contains('uf-enhanced')) return true;
        if ((form.getAttribute('method') || 'get').toLowerCase() === 'get') return true;
        if (form.closest('.tich-modal')) return true; // modals restyled via CSS
        if (fieldCount(form) < 2) return true;
        if (form.id === 'logout-form' || form.action.indexOf('/logout') !== -1) return true;
        return false;
    }

    function pageTitle() {
        var h = document.querySelector('.tich-page-toolbar .tich-h3, .tich-page-toolbar h1, [class*="page-toolbar"] h1');
        if (h && h.textContent.trim()) return h.textContent.trim();
        var t = document.title || '';
        return t.split('|')[0].trim() || 'Form';
    }

    function pageMeta() {
        var c = document.querySelector('.tich-page-toolbar .tich-caption');
        return c ? c.textContent.trim() : '';
    }

    function ensureAmountBar(form) {
        if (form.querySelector('.uf-amount-bar')) return;

        var bar = document.createElement('div');
        bar.className = 'uf-amount-bar';
        bar.innerHTML =
            '<div><div class="uf-amount-bar__ref">' + escapeHtml(pageMeta() || 'Platform form') + '</div>' +
            '<div class="uf-amount-bar__sum">' + escapeHtml(pageTitle()) + '</div></div>' +
            '<span class="uf-badge">Edit</span>';

        var target = form;
        if (form.classList.contains('tich-card') || form.classList.contains('tich-form-stack')) {
            form.insertBefore(bar, form.firstChild);
            return;
        }

        var card = form.querySelector(':scope > .tich-card, :scope > .tich-form-stack.tich-card');
        if (card) {
            card.insertBefore(bar, card.firstChild);
            return;
        }

        // Wrap loose fields in a surface
        if (!form.classList.contains('uf-form-surface')) {
            form.classList.add('uf-form-surface');
        }
        form.insertBefore(bar, form.firstChild);
    }

    function promoteSectionHeads(root) {
        root.querySelectorAll('h2.tich-h3, h3.tich-h3').forEach(function (heading) {
            if (heading.classList.contains('uf-section-head') || heading.classList.contains('uf-legacy-section-head')) {
                return;
            }
            if (heading.closest('.uf-amount-bar, .tich-page-toolbar, .tich-modal__header')) return;
            heading.classList.add('uf-legacy-section-head');
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function enhance(form) {
        if (shouldSkip(form)) return;
        form.classList.add('uf-enhanced');
        ensureAmountBar(form);
        promoteSectionHeads(form);
    }

    function run() {
        document.querySelectorAll('form').forEach(enhance);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
