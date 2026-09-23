(function () {
    'use strict';

    function ensureLightbox() {
        return document.getElementById('tich-photo-lightbox');
    }

    function openLightbox(src, alt) {
        var root = ensureLightbox();
        if (!root || !src) {
            return;
        }
        var img = root.querySelector('[data-photo-lightbox-img]');
        var caption = root.querySelector('[data-photo-lightbox-caption]');
        if (!img) {
            return;
        }
        img.src = src;
        img.alt = alt || 'Photo';
        if (caption) {
            if (alt) {
                caption.textContent = alt;
                caption.hidden = false;
            } else {
                caption.textContent = '';
                caption.hidden = true;
            }
        }
        root.hidden = false;
        root.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        var closeBtn = root.querySelector('.tich-photo-lightbox__close');
        if (closeBtn) {
            closeBtn.focus();
        }
    }

    function closeLightbox() {
        var root = ensureLightbox();
        if (!root || root.hidden) {
            return;
        }
        var img = root.querySelector('[data-photo-lightbox-img]');
        root.hidden = true;
        root.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        if (img) {
            img.removeAttribute('src');
            img.alt = '';
        }
    }

    document.addEventListener('click', function (event) {
        var closeTrigger = event.target.closest('[data-photo-lightbox-close]');
        if (closeTrigger) {
            event.preventDefault();
            closeLightbox();
            return;
        }

        var trigger = event.target.closest('[data-photo-lightbox]');
        if (!trigger) {
            return;
        }

        var src = trigger.getAttribute('data-photo-src')
            || (trigger.tagName === 'IMG' ? trigger.getAttribute('src') : null)
            || (trigger.querySelector && trigger.querySelector('img')
                ? trigger.querySelector('img').getAttribute('src')
                : null);

        if (!src) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        openLightbox(src, trigger.getAttribute('data-photo-alt') || trigger.getAttribute('aria-label') || '');
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });
})();
