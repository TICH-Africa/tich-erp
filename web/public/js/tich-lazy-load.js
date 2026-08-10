/**
 * TICH platform lazy loading — YouTube-style progressive media loading.
 * - Images: native lazy + shimmer placeholder, fade-in on load
 * - Hero carousel: only the active slide loads immediately; others defer until shown
 * - Iframes: src deferred until near the viewport
 * - Dynamic content: MutationObserver rescans after AJAX / portal section updates
 */
(function () {
    'use strict';

    var PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    var ROOT_MARGIN = '240px 0px';
    var THRESHOLD = 0.01;

    var eagerSelector = [
        '[data-lazy="eager"]',
        '.tich-header img',
        '.tich-brand img',
        '.tich-auth-aside img',
        '.tich-hero-carousel__slide.is-active img',
        '#photo-preview',
        '#photo-crop-source',
    ].join(',');

    var observer = null;

    function isEager(el) {
        if (!el) {
            return false;
        }

        if (el.dataset.lazy === 'eager') {
            return true;
        }

        if (el.closest('[data-lazy-eager]')) {
            return true;
        }

        try {
            return el.matches(eagerSelector);
        } catch (error) {
            return false;
        }
    }

    function markLoaded(el) {
        el.classList.add('is-loaded');
        el.classList.remove('is-loading');
    }

    function markLoading(el) {
        el.classList.add('tich-lazy-media', 'is-loading');
    }

    function bindLoadEvents(el) {
        if (el.complete && (el.tagName !== 'IMG' || el.naturalWidth > 0)) {
            markLoaded(el);
            return;
        }

        el.addEventListener('load', function () {
            markLoaded(el);
        }, { once: true });

        el.addEventListener('error', function () {
            el.classList.add('is-error');
            el.classList.remove('is-loading');
        }, { once: true });
    }

    function ensureObserver() {
        if (observer) {
            return observer;
        }

        observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                revealElement(entry.target);
                observer.unobserve(entry.target);
            });
        }, {
            rootMargin: ROOT_MARGIN,
            threshold: THRESHOLD,
        });

        return observer;
    }

    function revealElement(el) {
        if (el.tagName === 'IMG') {
            revealImage(el);
            return;
        }

        if (el.tagName === 'IFRAME') {
            revealIframe(el);
        }
    }

    function revealImage(img) {
        var deferred = img.dataset.src;

        if (deferred && (!img.getAttribute('src') || img.getAttribute('src') === PLACEHOLDER)) {
            img.src = deferred;
        }

        bindLoadEvents(img);
    }

    function revealIframe(iframe) {
        var deferred = iframe.dataset.src;

        if (!deferred || iframe.getAttribute('src')) {
            return;
        }

        iframe.setAttribute('src', deferred);
        bindLoadEvents(iframe);
    }

    function observeDeferred(el) {
        if (!el.dataset.src) {
            return;
        }

        ensureObserver().observe(el);
    }

    function enhanceImage(img) {
        if (img.dataset.lazyBound === '1') {
            return;
        }

        img.dataset.lazyBound = '1';

        if (isEager(img)) {
            markLoading(img);
            bindLoadEvents(img);
            return;
        }

        if (img.dataset.src) {
            markLoading(img);
            observeDeferred(img);
            return;
        }

        img.loading = 'lazy';
        img.decoding = 'async';
        markLoading(img);
        bindLoadEvents(img);
    }

    function deferIframe(iframe) {
        if (iframe.dataset.lazyBound === '1') {
            return;
        }

        var src = iframe.getAttribute('src');

        if (!src || src === 'about:blank') {
            return;
        }

        iframe.dataset.lazyBound = '1';

        if (isEager(iframe)) {
            markLoading(iframe);
            bindLoadEvents(iframe);
            return;
        }

        iframe.removeAttribute('src');
        iframe.dataset.src = src;
        iframe.classList.add('tich-lazy-iframe');
        markLoading(iframe);
        observeDeferred(iframe);
    }

    function initCarousel(carousel) {
        if (carousel.dataset.lazyCarouselBound === '1') {
            return;
        }

        carousel.dataset.lazyCarouselBound = '1';

        var slides = carousel.querySelectorAll('[data-carousel-slide]');

        slides.forEach(function (slide) {
            var img = slide.querySelector('img[src], img[data-src]');

            if (!img || img.dataset.lazyBound === '1') {
                return;
            }

            if (slide.classList.contains('is-active')) {
                enhanceImage(img);
                return;
            }

            if (!img.dataset.src && img.getAttribute('src') && img.getAttribute('src') !== PLACEHOLDER) {
                img.dataset.src = img.getAttribute('src');
                img.setAttribute('src', PLACEHOLDER);
            }

            img.dataset.lazyBound = '1';
            markLoading(img);
        });

        var slideObserver = new MutationObserver(function () {
            slides.forEach(function (slide) {
                if (!slide.classList.contains('is-active')) {
                    return;
                }

                var img = slide.querySelector('img[data-src]');

                if (img) {
                    revealImage(img);
                }
            });
        });

        slides.forEach(function (slide) {
            slideObserver.observe(slide, {
                attributes: true,
                attributeFilter: ['class'],
            });
        });
    }

    function scan(root) {
        root = root || document;

        if (root.nodeType !== 1) {
            return;
        }

        var scope = root.matches('img, iframe, [data-carousel]') ? root.parentElement || document : root;

        scope.querySelectorAll('[data-carousel]').forEach(initCarousel);

        scope.querySelectorAll('img').forEach(function (img) {
            if (img.closest('[data-carousel]') && !img.closest('.tich-hero-carousel__slide.is-active')) {
                return;
            }

            enhanceImage(img);
        });

        scope.querySelectorAll('iframe[src]').forEach(deferIframe);
        scope.querySelectorAll('iframe[data-src]:not([src])').forEach(observeDeferred);
        scope.querySelectorAll('img[data-src]').forEach(function (img) {
            if (img.dataset.lazyBound !== '1') {
                img.dataset.lazyBound = '1';
                markLoading(img);
            }

            observeDeferred(img);
        });

        if (root.matches && root.matches('img')) {
            enhanceImage(root);
        }

        if (root.matches && root.matches('iframe[src]')) {
            deferIframe(root);
        }
    }

    function init() {
        scan(document);

        var domObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        scan(node);
                    }
                });
            });
        });

        if (document.body) {
            domObserver.observe(document.body, {
                childList: true,
                subtree: true,
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    window.TichLazyLoad = {
        scan: scan,
        reveal: revealElement,
    };
})();
