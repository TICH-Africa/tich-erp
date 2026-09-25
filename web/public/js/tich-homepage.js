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

    let cloneSlide = null;
    if (realTotal > 1) {
        cloneSlide = realSlides[0].cloneNode(true);
        cloneSlide.setAttribute('aria-hidden', 'true');
        cloneSlide.removeAttribute('data-carousel-slide');
        cloneSlide.dataset.carouselClone = 'true';
        track.appendChild(cloneSlide);
    }

    let index = 0;
    let timer = null;
    let animToken = 0;
    let isAnimating = false;
    const interval = 6000;
    const animMs = 850;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function logicalIndex(i) {
        if (i >= realTotal) {
            return 0;
        }
        return ((i % realTotal) + realTotal) % realTotal;
    }

    function setTrackPosition(i, instant) {
        if (instant) {
            track.style.transition = 'none';
        } else {
            track.style.transition = '';
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

    function showPanel(logical, direction) {
        const token = ++animToken;

        panels.forEach((panel, j) => {
            const isTarget = j === logical;
            panel.classList.remove('from-right', 'from-left', 'exit-left', 'exit-right');

            if (!direction || prefersReducedMotion) {
                panel.classList.toggle('is-active', isTarget);
                panel.setAttribute('aria-hidden', isTarget ? 'false' : 'true');
                return;
            }

            if (isTarget) {
                panel.classList.remove('is-active');
                panel.classList.add(direction === 1 ? 'from-right' : 'from-left');
                void panel.offsetWidth;
                panel.classList.add('is-active');
                panel.setAttribute('aria-hidden', 'false');
            } else if (panel.classList.contains('is-active')) {
                panel.classList.add(direction === 1 ? 'exit-left' : 'exit-right');
                panel.classList.remove('is-active');
                panel.setAttribute('aria-hidden', 'true');
            } else {
                panel.setAttribute('aria-hidden', 'true');
            }
        });

        window.setTimeout(() => {
            if (token !== animToken) {
                return;
            }
            panels.forEach((panel, j) => {
                const isTarget = j === logical;
                panel.classList.remove('from-right', 'from-left', 'exit-left', 'exit-right');
                panel.classList.toggle('is-active', isTarget);
                panel.setAttribute('aria-hidden', isTarget ? 'false' : 'true');
            });
        }, prefersReducedMotion ? 0 : animMs);
    }

    function updateDots(logical) {
        dots.forEach((dot, j) => {
            const active = j === logical;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    }

    function finishCloneSnap() {
        if (index !== realTotal) {
            return;
        }
        setTrackPosition(0, true);
        index = 0;
        isAnimating = false;
    }

    function goTo(targetTrackIndex, direction) {
        if (isAnimating && targetTrackIndex === index) {
            return;
        }

        // Resolve out of the clone position before any new move.
        if (index === realTotal && targetTrackIndex !== realTotal) {
            setTrackPosition(0, true);
            index = 0;
        }

        let nextTrackIndex = targetTrackIndex;
        if (nextTrackIndex < 0) {
            nextTrackIndex = realTotal - 1;
        }

        if (nextTrackIndex === index) {
            showPanel(logicalIndex(index), 0);
            updateDots(logicalIndex(index));
            return;
        }

        const toLogical = logicalIndex(nextTrackIndex);
        const animDirection = prefersReducedMotion ? 0 : direction;

        isAnimating = true;
        setTrackPosition(nextTrackIndex, false);
        showPanel(toLogical, animDirection);
        updateDots(toLogical);
        syncVideo(toLogical);
        index = nextTrackIndex;

        window.setTimeout(() => {
            if (index === realTotal) {
                finishCloneSnap();
            } else {
                isAnimating = false;
            }
        }, prefersReducedMotion ? 0 : animMs);
    }

    function nextSlide() {
        if (realTotal < 2) {
            return;
        }
        if (isAnimating) {
            return;
        }
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
        if (isAnimating) {
            return;
        }
        if (index === 0 && cloneSlide) {
            setTrackPosition(realTotal, true);
            index = realTotal;
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
        timer = window.setInterval(() => {
            if (!isAnimating) {
                nextSlide();
            }
        }, interval);
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
                if (isAnimating) {
                    return;
                }
                const target = Number(dot.dataset.carouselDot);
                const currentLogical = logicalIndex(index);
                if (currentLogical === realTotal - 1 && target === 0) {
                    nextSlide();
                } else {
                    const direction = target > currentLogical ? 1 : -1;
                    goTo(target, direction);
                }
                startAutoplay();
            });
        });

        // Pause only while the pointer is over controls, not the whole hero
        // (full-hero pause made autoplay feel broken).
        const controls = [
            ...dots,
            prev,
            next,
        ].filter(Boolean);
        controls.forEach((el) => {
            el.addEventListener('mouseenter', stopAutoplay);
            el.addEventListener('mouseleave', startAutoplay);
            el.addEventListener('focus', stopAutoplay);
            el.addEventListener('blur', startAutoplay);
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                if (index === realTotal) {
                    finishCloneSnap();
                }
                isAnimating = false;
                showPanel(logicalIndex(index), 0);
                startAutoplay();
            }
        });
    }

    showPanel(0, 0);
    updateDots(0);
    syncVideo(0);
    startAutoplay();
}

function initHeaderOverHero() {
    const header = document.getElementById('site-header');

    if (!header || !header.classList.contains('tich-header--over-hero')) {
        return;
    }

    const update = () => {
        header.classList.toggle('tich-header--solid', window.scrollY > 8);
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
