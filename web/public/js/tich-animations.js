/**
 * TICH Platform - Public page entrance animations
 * Uses IntersectionObserver to trigger animations when elements enter viewport.
 */
(function () {
    'use strict';

    if (!('IntersectionObserver' in window)) {
        return;
    }

    var animatedElements = document.querySelectorAll('.tich-animate');
    if (!animatedElements.length) {
        return;
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, {
        threshold: 0.12,
        rootMargin: '0px 0px -24px 0px',
    });

    animatedElements.forEach(function (el) {
        observer.observe(el);
    });
})();
