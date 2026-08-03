(function () {
    function initPasswordToggles(root) {
        root.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            if (button.dataset.passwordToggleBound === '1') {
                return;
            }

            button.dataset.passwordToggleBound = '1';

            button.addEventListener('click', function () {
                var field = button.closest('.tich-password-field');
                var input = field ? field.querySelector('input') : null;

                if (!input) {
                    return;
                }

                var isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPasswordToggles(document);
    });
})();
