document.addEventListener('DOMContentLoaded', () => {
    initCarousel();
    initMobileNav();
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
        slides.forEach((slide, i) => slide.classList.toggle('is-active', i === current));
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

    restart();
}

function initMobileNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const drawer = document.querySelector('[data-nav-drawer]');
    if (!toggle || !drawer) return;

    toggle.addEventListener('click', () => {
        const isHidden = drawer.hasAttribute('hidden');
        if (isHidden) {
            drawer.removeAttribute('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            drawer.setAttribute('hidden', 'hidden');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}
