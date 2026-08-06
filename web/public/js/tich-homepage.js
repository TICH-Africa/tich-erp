document.addEventListener('DOMContentLoaded', () => {
    initCarousel();
    initHeaderOverHero();
});

function initCarousel() {
    const carousel = document.querySelector('[data-carousel]');
    if (!carousel) return;

    const slides = [...carousel.querySelectorAll('[data-carousel-slide]')];
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
    const prev = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');

    if (slides.length <= 1) return;

    let current = 0;
    let timer = null;

    const show = (index) => {
        current = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const isActive = i === current;
            slide.classList.toggle('is-active', isActive);

            const content = slide.querySelector('[data-carousel-content]');
            if (content) {
                content.classList.remove('is-visible');
                if (isActive) {
                    requestAnimationFrame(() => content.classList.add('is-visible'));
                }
            }
        });

        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
    };

    const nextSlide = () => show(current + 1);
    const prevSlide = () => show(current - 1);

    const restart = () => {
        if (timer) clearInterval(timer);
        timer = setInterval(nextSlide, 6000);
    };

    prev?.addEventListener('click', () => { prevSlide(); restart(); });
    next?.addEventListener('click', () => { nextSlide(); restart(); });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.carouselDot));
            restart();
        });
    });

    carousel.addEventListener('mouseenter', () => timer && clearInterval(timer));
    carousel.addEventListener('mouseleave', restart);
    carousel.addEventListener('focusin', () => timer && clearInterval(timer));
    carousel.addEventListener('focusout', restart);

    restart();
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
