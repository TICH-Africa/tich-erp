<style>
    .tich-financial-value {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        max-width: 100%;
    }

    .tich-financial-value__content,
    .tich-financial-cell {
        filter: blur(6px);
        user-select: none;
        transition: filter 0.15s ease;
    }

    .tich-financial-value.is-revealed .tich-financial-value__content,
    .tich-financial-cell.is-revealed,
    [data-financial-col].is-revealed .tich-financial-cell {
        filter: none;
        user-select: auto;
    }

    .tich-financial-value__toggle,
    .tich-financial-col-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        padding: 0;
        border: none;
        border-radius: 999px;
        background: transparent;
        color: var(--tich-text-muted, #64748b);
        cursor: pointer;
        flex-shrink: 0;
    }

    .tich-financial-value__toggle:hover,
    .tich-financial-col-toggle:hover {
        background: var(--tich-surface-muted, #f1f5f9);
        color: var(--tich-blue, #1d4ed8);
    }

    .tich-financial-col-header {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
    }

    .tich-hr-profile-header {
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
        flex-wrap: wrap;
        padding: 1.25rem 1.5rem;
        background: var(--tich-white);
        border: 1px solid var(--tich-neutral-border);
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
    }

    .tich-hr-profile-header--with-progress {
        margin-bottom: 0;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }

    .tich-onboarding-progress {
        margin: 0 0 1.5rem;
        padding: 1rem 1.5rem 1.25rem;
        background: var(--tich-white);
        border: 1px solid var(--tich-neutral-border);
        border-top: 0;
        border-radius: 0 0 var(--radius-md) var(--radius-md);
    }

    .tich-onboarding-progress__top {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.5rem 1rem;
        margin-bottom: 0.65rem;
    }

    .tich-onboarding-progress__title {
        margin: 0;
        font-family: var(--font-heading);
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--tich-text, inherit);
    }

    .tich-onboarding-progress__meta {
        font-size: 0.8125rem;
        color: var(--tich-text-muted, #64748b);
    }

    .tich-onboarding-progress__track {
        height: 0.55rem;
        border-radius: 999px;
        background: var(--tich-surface-muted, #e2e8f0);
        overflow: hidden;
    }

    .tich-onboarding-progress__fill {
        height: 100%;
        border-radius: inherit;
        background: var(--tich-green, #6cab33);
        transition: width 0.25s ease;
    }

    .tich-onboarding-progress__steps {
        display: flex;
        gap: 0.35rem;
        margin: 0.85rem 0 0;
        padding: 0;
        list-style: none;
        overflow-x: auto;
    }

    .tich-onboarding-progress__step {
        flex: 1;
        min-width: 4.5rem;
        text-align: center;
        padding: 0.45rem 0.35rem;
        border-radius: var(--radius-sm, 0.35rem);
        border: 1px solid transparent;
        font-size: 0.7rem;
        color: var(--tich-text-muted, #64748b);
        background: var(--tich-surface-muted, #f8fafc);
    }

    .tich-onboarding-progress__step.is-done {
        color: var(--tich-status-success-text, #15803d);
        border-color: var(--tich-status-success-border, rgba(22, 163, 74, 0.35));
        background: var(--tich-status-success-bg, rgba(22, 163, 74, 0.12));
    }

    .tich-onboarding-progress__step.is-pending {
        color: var(--tich-status-caution-text, #b45309);
        border-color: var(--tich-status-caution-border, rgba(245, 158, 11, 0.35));
        background: var(--tich-status-caution-bg, rgba(245, 158, 11, 0.1));
    }

    .tich-onboarding-checklist {
        margin-top: 1rem;
        display: grid;
        gap: 0.5rem;
    }

    .tich-onboarding-checklist__item {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem 1rem;
        padding: 0.65rem 0.75rem;
        border: 1px solid var(--tich-neutral-border);
        border-radius: var(--radius-sm, 0.35rem);
        background: var(--tich-surface-muted, #f8fafc);
    }

    .tich-onboarding-checklist__item.is-done {
        background: var(--tich-status-success-bg, rgba(22, 163, 74, 0.08));
        border-color: var(--tich-status-success-border, var(--tich-neutral-border));
    }

    .tich-onboarding-checklist__mark {
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        flex-shrink: 0;
        border: 1px solid var(--tich-neutral-border);
        color: var(--tich-text-muted, #64748b);
        background: var(--tich-white);
    }

    .tich-onboarding-checklist__item.is-done .tich-onboarding-checklist__mark {
        border-color: var(--tich-green, #16a34a);
        background: var(--tich-green, #16a34a);
        color: #fff;
    }

    .tich-onboarding-checklist__body {
        flex: 1;
        min-width: 12rem;
    }

    .tich-onboarding-checklist__label {
        display: block;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--tich-text, inherit);
    }

    .tich-onboarding-checklist__hint {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.75rem;
        color: var(--tich-text-muted, #64748b);
    }

    .tich-onboarding-checklist__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-left: auto;
    }

    .tich-onboarding-progress__footer {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        justify-content: space-between;
        margin-top: 1rem;
        padding-top: 0.85rem;
        border-top: 1px solid var(--tich-neutral-border);
    }

    .tich-hr-profile-header__photo {
        width: 6rem;
        height: 6rem;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid var(--tich-neutral-border);
        background: var(--tich-surface-muted, #f1f5f9);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tich-hr-profile-header__photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .tich-hr-profile-header__initials {
        font-family: var(--font-heading);
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--tich-blue);
    }

    .tich-hr-profile-header__main {
        flex: 1;
        min-width: 14rem;
    }

    .tich-hr-profile-header__name {
        font-family: var(--font-heading);
        font-size: 1.35rem;
        font-weight: 700;
        margin: 0;
        color: var(--tich-text, inherit);
    }

    .tich-hr-profile-header__meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        margin-top: 0.35rem;
        color: var(--tich-text-muted, #64748b);
        font-size: 0.875rem;
    }

    .tich-hr-profile-header__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-left: auto;
        align-items: flex-start;
    }

    .tich-detail-grid {
        display: grid;
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .tich-detail-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tich-detail-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    .tich-detail-card {
        padding: 1.25rem;
        background: var(--tich-white);
        border: 1px solid var(--tich-neutral-border);
        border-radius: var(--radius-md);
    }

    .tich-detail-card__title {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--tich-text-muted, #64748b);
        margin: 0 0 0.75rem;
    }

    .tich-dl {
        display: grid;
        gap: 0.65rem;
        margin: 0;
    }

    .tich-dl__row {
        display: grid;
        grid-template-columns: minmax(7rem, 38%) 1fr;
        gap: 0.75rem;
        align-items: baseline;
        padding-bottom: 0.65rem;
        border-bottom: 1px solid var(--tich-neutral-border);
    }

    .tich-dl__row:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .tich-dl__label {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--tich-text-muted, #64748b);
    }

    .tich-dl__value {
        margin: 0;
        font-size: 0.9375rem;
        word-break: break-word;
        color: var(--tich-text, inherit);
    }

    [data-theme="dark"] .tich-hr-profile-header,
    [data-theme="dark"] .tich-onboarding-progress,
    [data-theme="dark"] .tich-detail-card {
        background: var(--tich-surface, #1e293b);
        border-color: var(--tich-neutral-border, #334155);
    }

    [data-theme="dark"] .tich-hr-profile-header__name,
    [data-theme="dark"] .tich-onboarding-progress__title,
    [data-theme="dark"] .tich-onboarding-checklist__label,
    [data-theme="dark"] .tich-dl__value {
        color: var(--tich-text, #e2e8f0);
    }

    [data-theme="dark"] .tich-hr-profile-header__meta,
    [data-theme="dark"] .tich-onboarding-progress__meta,
    [data-theme="dark"] .tich-onboarding-checklist__hint,
    [data-theme="dark"] .tich-detail-card__title,
    [data-theme="dark"] .tich-dl__label {
        color: var(--tich-text-muted, #94a3b8);
    }

    [data-theme="dark"] .tich-hr-profile-header__photo {
        border-color: var(--tich-neutral-border, #334155);
        background: var(--tich-surface-muted, #0f172a);
    }

    [data-theme="dark"] .tich-onboarding-progress__track {
        background: var(--tich-surface-muted, #0f172a);
    }

    [data-theme="dark"] .tich-onboarding-progress__fill {
        background: var(--tich-green, #6cab33);
    }

    [data-theme="dark"] .tich-onboarding-progress__step {
        background: var(--tich-surface-muted, #0f172a);
        color: var(--tich-text-muted, #94a3b8);
        border-color: var(--tich-neutral-border, #334155);
    }

    [data-theme="dark"] .tich-onboarding-progress__step.is-done {
        color: #86efac;
        border-color: rgba(134, 239, 172, 0.35);
        background: rgba(21, 128, 61, 0.2);
    }

    [data-theme="dark"] .tich-onboarding-progress__step.is-pending {
        color: #93c5fd;
        border-color: rgba(147, 197, 253, 0.35);
        background: rgba(29, 78, 216, 0.18);
    }

    [data-theme="dark"] .tich-onboarding-checklist__item {
        background: var(--tich-surface-muted, #0f172a);
        border-color: var(--tich-neutral-border, #334155);
    }

    [data-theme="dark"] .tich-onboarding-checklist__item.is-done {
        background: rgba(21, 128, 61, 0.18);
        border-color: rgba(134, 239, 172, 0.3);
    }

    [data-theme="dark"] .tich-onboarding-checklist__mark {
        background: var(--tich-surface, #1e293b);
        border-color: var(--tich-neutral-border, #334155);
        color: var(--tich-text-muted, #94a3b8);
    }

    [data-theme="dark"] .tich-onboarding-checklist__item.is-done .tich-onboarding-checklist__mark {
        background: var(--tich-green, #6cab33);
        border-color: var(--tich-green, #6cab33);
        color: #fff;
    }

    [data-theme="dark"] .tich-onboarding-progress__footer,
    [data-theme="dark"] .tich-dl__row {
        border-color: var(--tich-neutral-border, #334155);
    }

    [data-theme="dark"] .tich-doc-card,
    [data-theme="dark"] .tich-list-item {
        border-color: var(--tich-neutral-border, #334155);
        color: var(--tich-text, #e2e8f0);
    }
</style>

<script>
    (function () {
        if (window.__tichFinancialPrivacyInit) return;
        window.__tichFinancialPrivacyInit = true;

        function eyeIcon() {
            return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }

        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('.tich-financial-value__toggle');
            if (toggle) {
                var wrapper = toggle.closest('.tich-financial-value');
                if (wrapper) {
                    wrapper.classList.toggle('is-revealed');
                }
                return;
            }

            var colToggle = event.target.closest('.tich-financial-col-toggle');
            if (colToggle) {
                var col = colToggle.getAttribute('data-financial-col');
                if (!col) return;
                var cells = document.querySelectorAll('[data-financial-col="' + col + '"]');
                var reveal = !colToggle.classList.contains('is-revealed');
                colToggle.classList.toggle('is-revealed', reveal);
                cells.forEach(function (cell) {
                    cell.classList.toggle('is-revealed', reveal);
                });
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('th[data-financial-col]').forEach(function (th) {
                if (th.querySelector('.tich-financial-col-toggle')) return;
                var col = th.getAttribute('data-financial-col');
                var label = th.querySelector('.tich-financial-col-header') || th;
                if (!label.classList.contains('tich-financial-col-header')) {
                    var wrap = document.createElement('span');
                    wrap.className = 'tich-financial-col-header';
                    wrap.innerHTML = th.innerHTML;
                    th.innerHTML = '';
                    th.appendChild(wrap);
                    label = wrap;
                }
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tich-financial-col-toggle';
                btn.setAttribute('data-financial-col', col);
                btn.setAttribute('aria-label', 'Show column amounts');
                btn.setAttribute('title', 'Show/hide column');
                btn.innerHTML = eyeIcon();
                label.appendChild(btn);
            });
        });
    })();
</script>
