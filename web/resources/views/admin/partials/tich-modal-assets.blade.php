<style>
    .tich-modal {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }

    .tich-modal.is-open {
        display: flex;
    }

    .tich-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(73, 76, 80, 0.55);
    }

    .tich-modal__dialog {
        position: relative;
        width: min(100%, 32rem);
        max-height: calc(100vh - 3rem);
        overflow: auto;
        background: var(--tich-surface, #fff);
        border-radius: 0.5rem;
        box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, 0.18);
        border-top: 4px solid var(--tich-green, #6cab33);
    }

    .tich-modal__dialog--wide {
        width: min(100%, 40rem);
    }

    .tich-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem 0;
    }

    .tich-modal__close {
        border: 0;
        background: transparent;
        font-size: 1.75rem;
        line-height: 1;
        color: #6b6e72;
        cursor: pointer;
        padding: 0.25rem 0.5rem;
    }

    .tich-modal__close:hover {
        color: var(--tich-text, #494c50);
    }

    .tich-modal__body {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .tich-modal__errors {
        margin-bottom: 1rem;
        padding: 0.875rem 1rem;
        border: 1px solid #c0392b;
        border-radius: 0.375rem;
        background: rgba(192, 57, 43, 0.06);
    }

    .tich-modal__footer {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--tich-border, #e2e4e5);
    }

    .tich-squircle-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        padding: 0;
        border: 1px solid var(--tich-border, #e2e4e5);
        border-radius: 0.65rem;
        background: var(--tich-surface, #fff);
        color: var(--tich-blue, #1669a6);
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .tich-squircle-btn:hover {
        background: rgba(22, 105, 166, 0.08);
        border-color: var(--tich-blue, #1669a6);
    }

    .tich-squircle-btn:focus-visible {
        outline: 2px solid var(--tich-blue, #1669a6);
        outline-offset: 2px;
    }
</style>

<script>
(function () {
    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    window.tichOpenModal = openModal;
    window.tichCloseModal = closeModal;

    document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openModal(trigger.getAttribute('data-open-modal'));
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            closeModal(trigger.getAttribute('data-close-modal'));
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('.tich-modal.is-open').forEach(function (modal) {
            closeModal(modal.id);
        });
    });
})();
</script>
