document.addEventListener('DOMContentLoaded', () => {
    initCarousel();
    initHeaderOverHero();
    initHomeReveal();
});

function initCarousel() {
    const hero = document.querySelector('[data-carousel]');
    if (!hero) {
        return;
    }

    const track = hero.querySelector('[data-carousel-track]');
    const realSlides = [...hero.querySelectorAll('[data-carousel-slide]')];
    const panels = [...hero.querySelectorAll('[data-carousel-panel]')];
    const dots = [...hero.querySelectorAll('[data-carousel-dot]')];
    const prev = hero.querySelector('[data-carousel-prev]');
    const next = hero.querySelector('[data-carousel-next]');
    const realTotal = realSlides.length;

    if (!track || realTotal === 0) {
        return;
    }

    // Clone first slide so last → first can keep sliding left-to-right.
    let cloneSlide = null;
    if (realTotal > 1) {
        cloneSlide = realSlides[0].cloneNode(true);
        cloneSlide.setAttribute('aria-hidden', 'true');
        cloneSlide.removeAttribute('data-carousel-slide');
        cloneSlide.dataset.carouselClone = 'true';
        track.appendChild(cloneSlide);
    }

    const trackSlides = [...track.children];
    let index = 0;
    let isFirst = true;
    let timer = null;
    let isJumping = false;
    const interval = 6000;
    const animMs = 850;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function logicalIndex(i) {
        return ((i % realTotal) + realTotal) % realTotal;
    }

    function setTrackPosition(i, instant) {
        if (instant) {
            track.style.transition = 'none';
        }
        track.style.transform = `translateX(-${i * 100}%)`;
        if (instant) {
            void track.offsetWidth;
            track.style.transition = '';
        }
    }

    function syncVideo(logical) {
        realSlides.forEach((slide, i) => {
            const video = slide.querySelector('video');
            if (!video) {
                return;
            }
            if (i === logical) {
                video.play().catch(() => {});
            } else {
                video.pause();
            }
        });
        if (cloneSlide) {
            const cloneVideo = cloneSlide.querySelector('video');
            if (cloneVideo) {
                cloneVideo.pause();
            }
        }
    }

    function updatePanels(fromLogical, toLogical, direction) {
        const outgoing = panels[fromLogical];
        const incoming = panels[toLogical];

        if (isFirst || prefersReducedMotion || !outgoing || !incoming || direction === 0) {
            panels.forEach((panel, j) => {
                panel.classList.remove('from-right', 'from-left', 'exit-left', 'exit-right');
                panel.classList.toggle('is-active', j === toLogical);
                panel.setAttribute('aria-hidden', j === toLogical ? 'false' : 'true');
            });
            return;
        }

        panels.forEach((panel) => {
            if (panel !== outgoing && panel !== incoming) {
                panel.classList.remove('from-right', 'from-left', 'exit-left', 'exit-right', 'is-active');
                panel.setAttribute('aria-hidden', 'true');
            }
        });

        if (outgoing !== incoming) {
            outgoing.classList.remove('from-right', 'from-left', 'exit-left', 'exit-right');
            requestAnimationFrame(() => {
                outgoing.classList.remove('is-active');
                outgoing.classList.add(direction === 1 ? 'exit-left' : 'exit-right');
                outgoing.setAttribute('aria-hidden', 'true');
            });
        }

        incoming.classList.remove('exit-left', 'exit-right', 'is-active');
        incoming.classList.add(direction === 1 ? 'from-right' : 'from-left');
        void incoming.offsetWidth;
        incoming.classList.add('is-active');
        incoming.setAttribute('aria-hidden', 'false');

        window.setTimeout(() => {
            panels.forEach((panel, j) => {
                panel.classList.remove('from-right', 'from-left', 'exit-left', 'exit-right');
                panel.classList.toggle('is-active', j === toLogical);
                panel.setAttribute('aria-hidden', j === toLogical ? 'false' : 'true');
            });
        }, animMs);
    }

    function updateDots(logical) {
        dots.forEach((dot, j) => {
            const active = j === logical;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function updateSlideAria(trackIndex) {
        trackSlides.forEach((slide, j) => {
            const active = j === trackIndex || (trackIndex === realTotal && j === 0);
            slide.setAttribute('aria-hidden', active && j < realTotal ? 'false' : 'true');
        });
    }

    function goTo(targetTrackIndex, direction) {
        if (isJumping) {
            return;
        }

        const nextTrackIndex = targetTrackIndex;
        if (nextTrackIndex === index && !isFirst) {
            return;
        }

        const fromLogical = logicalIndex(index);
        const toLogical = logicalIndex(nextTrackIndex);
        const animDirection = isFirst || prefersReducedMotion ? 0 : direction;

        setTrackPosition(nextTrackIndex, false);
        updateSlideAria(nextTrackIndex);
        updatePanels(fromLogical, toLogical, animDirection);
        updateDots(toLogical);

        index = nextTrackIndex;
        isFirst = false;
        syncVideo(toLogical);

        // After animating onto the clone, snap back to the real first slide.
        if (cloneSlide && nextTrackIndex === realTotal) {
            window.setTimeout(() => {
                isJumping = true;
                setTrackPosition(0, true);
                index = 0;
                updateSlideAria(0);
                isJumping = false;
            }, prefersReducedMotion ? 0 : animMs);
        }
    }

    function nextSlide() {
        if (realTotal < 2) {
            return;
        }
        // Always advance left-to-right, including last → first via the clone.
        if (index >= realTotal - 1) {
            goTo(realTotal, 1);
        } else {
            goTo(index + 1, 1);
        }
    }

    function prevSlide() {
        if (realTotal < 2) {
            return;
        }
        if (index === 0 && cloneSlide) {
            // Jump to clone instantly, then slide back to last real slide.
            isJumping = true;
            setTrackPosition(realTotal, true);
            index = realTotal;
            isJumping = false;
            goTo(realTotal - 1, -1);
            return;
        }
        goTo(index - 1, -1);
    }

    function startAutoplay() {
        stopAutoplay();
        if (realTotal < 2 || prefersReducedMotion) {
            return;
        }
        timer = window.setInterval(nextSlide, interval);
    }

    function stopAutoplay() {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    }

    if (realTotal > 1) {
        next?.addEventListener('click', () => {
            nextSlide();
            startAutoplay();
        });
        prev?.addEventListener('click', () => {
            prevSlide();
            startAutoplay();
        });
        dots.forEach((dot) => {
            dot.addEventListener('click', () => {
                const target = Number(dot.dataset.carouselDot);
                const currentLogical = logicalIndex(index);
                const direction = target >= currentLogical || (currentLogical === realTotal - 1 && target === 0)
                    ? 1
                    : (target < currentLogical ? -1 : 1);
                // Prefer forward when wrapping last → first via dot.
                if (currentLogical === realTotal - 1 && target === 0) {
                    nextSlide();
                } else {
                    goTo(target, direction);
                }
                startAutoplay();
            });
        });

        hero.addEventListener('mouseenter', stopAutoplay);
        hero.addEventListener('mouseleave', startAutoplay);
    }

    goTo(0, 0);
    startAutoplay();
}

function initHeaderOverHero() {
    const header = document.getElementById('site-header');
    const hero = document.getElementById('home-hero');

    if (!header || !header.classList.contains('tich-header--over-hero')) {
        return;
    }

    const update = () => {
        const threshold = 8;
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

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }
            entry.target.classList.add('is-revealed');
            entry.target.querySelectorAll('.tich-home-reveal').forEach((item) => item.classList.add('is-revealed'));
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

    groups.forEach((group) => observer.observe(group));

    window.addEventListener('resize', () => {
        groups.forEach(assignDirections);
    });
}
