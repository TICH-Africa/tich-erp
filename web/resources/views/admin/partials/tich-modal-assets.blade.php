<script>
(function () {
    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }

        modal.removeAttribute('hidden');
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
        modal.setAttribute('hidden', '');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    window.tichOpenModal = openModal;
    window.tichCloseModal = closeModal;

    if (!window.__tichModalListenersBound) {
        window.__tichModalListenersBound = true;

        document.addEventListener('click', function (event) {
            var openTrigger = event.target.closest('[data-open-modal]');
            if (openTrigger) {
                openModal(openTrigger.getAttribute('data-open-modal'));
                return;
            }

            var closeTrigger = event.target.closest('[data-close-modal]');
            if (closeTrigger) {
                event.preventDefault();
                closeModal(closeTrigger.getAttribute('data-close-modal'));
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.tich-modal.is-open').forEach(function (modal) {
                closeModal(modal.id);
            });
        });
    }
})();
</script>
