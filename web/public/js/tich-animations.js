/**
 * TICH Platform - Public page entrance animations
 * Uses IntersectionObserver to trigger animations when elements enter viewport.
 */
(function () {
    'use strict';

    var animatedElements = document.querySelectorAll('.tich-animate');
    if (!animatedElements.length) {
        return;
    }

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function show(el) {
        el.classList.add('is-visible');
    }

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        animatedElements.forEach(show);
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }

            show(entry.target);
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.08,
        rootMargin: '0px 0px -8% 0px',
    });

    animatedElements.forEach(function (el) {
        var rect = el.getBoundingClientRect();
        var inView = rect.top < window.innerHeight * 0.92 && rect.bottom > 0;

        // Page intros already on screen should animate in immediately on load.
        if (inView && (el.classList.contains('tich-animate--top') || el.classList.contains('tich-animate--fade'))) {
            requestAnimationFrame(function () {
                show(el);
            });
            return;
        }

        observer.observe(el);
    });
})();
