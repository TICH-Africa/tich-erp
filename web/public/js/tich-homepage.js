document.addEventListener('DOMContentLoaded', () => {
    initCarousel();
    initHeaderOverHero();
    initHomeReveal();
});

function initCarousel() {
    const carousel = document.querySelector('[data-carousel]');
    if (!carousel) return;

    const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');

    if (slides.length === 0) return;

    let current = 0;
    let autoplayTimer = null;
    const SLIDE_MS = 5500;

    const clearAutoplay = () => {
        if (autoplayTimer) {
            window.clearTimeout(autoplayTimer);
            autoplayTimer = null;
        }
    };

    const scheduleAutoplay = () => {
        clearAutoplay();

        if (slides.length < 2) {
            return;
        }

        autoplayTimer = window.setTimeout(() => {
            show(current + 1);
        }, SLIDE_MS);
    };

    const show = (index) => {
        current = (index + slides.length) % slides.length;
        clearAutoplay();

        slides.forEach((slide, i) => {
            const isActive = i === current;
            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');

            const content = slide.querySelector('[data-carousel-content]');
            if (!content) {
                return;
            }

            content.classList.remove('is-visible');

            if (isActive) {
                // Force reflow so entrance animations restart on every slide change.
                void content.offsetWidth;
                requestAnimationFrame(() => {
                    content.classList.add('is-visible');
                });
            }
        });

        dots.forEach((dot, i) => {
            const active = i === current;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', active ? 'true' : 'false');
        });

        scheduleAutoplay();
    };

    if (slides.length > 1) {
        prev?.addEventListener('click', () => show(current - 1));
        next?.addEventListener('click', () => show(current + 1));
        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                show(Number(dot.dataset.carouselDot));
            });
        });
    }

    show(0);
}

function initHeaderOverHero() {
    const header = document.getElementById('site-header');
    const hero = document.getElementById('home-hero');

    if (!header || !header.classList.contains('tich-header--over-hero')) {
        return;
    }

    const update = () => {
        const threshold = hero ? Math.max(80, hero.offsetHeight * 0.12) : 80;
        header.classList.toggle('tich-header--solid', window.scrollY > threshold);
    };

    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
}

function initHomeReveal() {
    const groups = [...document.querySelectorAll('[data-home-reveal]')];
    if (groups.length === 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const assignDirections = (group) => {
        const items = [...group.querySelectorAll(':scope > .tich-home-reveal, :scope .tich-home-reveal')].filter((item) => {
            return item.closest('[data-home-reveal]') === group;
        });

        const desktopCols = Number(group.getAttribute('data-home-reveal-cols') || 3);
        const width = window.innerWidth;
        let cols = 1;

        if (width >= 1024) {
            cols = desktopCols;
        } else if (width >= 640) {
            cols = Math.min(2, desktopCols);
        }

        items.forEach((item, index) => {
            item.classList.remove(
                'tich-home-reveal--from-left',
                'tich-home-reveal--from-right',
                'tich-home-reveal--from-bottom'
            );

            let direction = 'from-bottom';

            if (cols >= 3) {
                const pos = index % 3;
                direction = pos === 0 ? 'from-left' : (pos === 1 ? 'from-bottom' : 'from-right');
            } else if (cols === 2) {
                direction = index % 2 === 0 ? 'from-left' : 'from-right';
            } else {
                // Mobile single column: alternate sides.
                direction = index % 2 === 0 ? 'from-left' : 'from-right';
            }

            item.classList.add(`tich-home-reveal--${direction}`);
        });
    };

    groups.forEach(assignDirections);

    if (prefersReducedMotion) {
        groups.forEach((group) => {
            group.classList.add('is-revealed');
            group.querySelectorAll('.tich-home-reveal').forEach((item) => item.classList.add('is-revealed'));
        });
        return;
    }

    if (!('IntersectionObserver' in window)) {
        groups.forEach((group) => {
            group.classList.add('is-revealed');
            group.querySelectorAll('.tich-home-reveal').forEach((item) => item.classList.add('is-revealed'));
        });
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            const group = entry.target;
            group.classList.add('is-revealed');
            group.querySelectorAll('.tich-home-reveal').forEach((item) => {
                if (item.closest('[data-home-reveal]') === group) {
                    item.classList.add('is-revealed');
                }
            });
            observer.unobserve(group);
        });
    }, {
        threshold: 0.18,
        rootMargin: '0px 0px -8% 0px',
    });

    groups.forEach((group) => observer.observe(group));

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            groups.forEach((group) => {
                if (!group.classList.contains('is-revealed')) {
                    assignDirections(group);
                }
            });
        }, 120);
    });
}
