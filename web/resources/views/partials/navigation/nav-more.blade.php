<div class="tich-nav__more" data-nav-more hidden>
    <button
        type="button"
        class="tich-nav__link tich-nav__more-toggle"
        data-nav-more-toggle
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="site-nav-more-menu"
    >
        <span class="tich-nav__icon" aria-hidden="true">
            @include('partials.navigation.sidebar-icon', ['name' => 'layers'])
        </span>
        <span class="tich-nav__label">More</span>
        <span class="tich-nav__chevron" aria-hidden="true">
            @include('partials.navigation.sidebar-icon', ['name' => 'chevron-down'])
        </span>
    </button>
    <div
        id="site-nav-more-menu"
        class="tich-nav__more-menu"
        data-nav-more-menu
        role="menu"
        hidden
    ></div>
</div>
