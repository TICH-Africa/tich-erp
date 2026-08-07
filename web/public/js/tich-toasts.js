(function () {
    const dismissToast = (toast) => {
        if (!toast || toast.classList.contains('is-leaving')) {
            return;
        }

        toast.classList.add('is-leaving');

        window.setTimeout(() => {
            toast.remove();

            const stack = toast.closest('.tich-toast-stack');
            if (stack && !stack.querySelector('[data-toast]')) {
                stack.remove();
            }
        }, 220);
    };

    const initToast = (toast) => {
        const dismissButton = toast.querySelector('[data-toast-dismiss]');
        dismissButton?.addEventListener('click', () => dismissToast(toast));

        const autodismiss = Number(toast.getAttribute('data-toast-autodismiss'));
        if (autodismiss > 0) {
            window.setTimeout(() => dismissToast(toast), autodismiss);
        }
    };

    document.querySelectorAll('[data-toast]').forEach(initToast);
})();
