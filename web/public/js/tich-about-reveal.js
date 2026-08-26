/**
 * About page: slide copy + images inward as sections enter the viewport.
 */
(function () {
    function reveal(el) {
        el.classList.add('is-revealed');
    }

    function init() {
        var nodes = document.querySelectorAll('[data-about-reveal]');
        if (!nodes.length) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            nodes.forEach(reveal);
            return;
        }

        if (! ('IntersectionObserver' in window)) {
            nodes.forEach(reveal);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (! entry.isIntersecting) {
                    return;
                }

                reveal(entry.target);
                observer.unobserve(entry.target);
            });
        }, {
            root: null,
            rootMargin: '0px 0px -12% 0px',
            threshold: 0.18,
        });

        nodes.forEach(function (node) {
            observer.observe(node);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
