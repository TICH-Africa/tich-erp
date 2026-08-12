<header id="site-header" class="tich-header{{ request()->routeIs('home') ? ' tich-header--over-hero' : '' }}">
    <div class="tich-container tich-header__inner">
        @include('partials.brand-logo', ['variant' => request()->routeIs('home') ? 'light' : 'default'])

        <button type="button" class="tich-nav-toggle" aria-label="Open menu" aria-expanded="false" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>

        <nav class="tich-nav tich-nav--desktop" aria-label="Primary navigation">
            <div class="tich-nav__primary">
                <div class="tich-nav__links" data-nav-links>
                    @foreach ($headerMenu as $item)
                        <div class="tich-nav__item" data-nav-item data-nav-overflow-item>
                            @include('partials.navigation.menu-item', ['item' => $item])
                        </div>
                    @endforeach
                </div>

                @include('partials.navigation.nav-more')
            </div>

            @auth
                <div class="tich-nav__pinned">
                    @include('partials.navigation.auth-portal-links')
                </div>
            @endauth

            <div class="tich-nav__actions">
                @include('partials.theme-toggle')

                @auth
                    <form method="POST" action="{{ route('logout') }}" class="tich-nav__logout">
                        @csrf
                        <button type="submit" class="tich-nav__action-btn tich-nav__action-btn--ghost">
                            <span class="tich-nav__icon" aria-hidden="true">
                                @include('partials.navigation.sidebar-icon', ['name' => 'log-out'])
                            </span>
                            <span class="tich-nav__label">Sign out</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="tich-nav__action-btn tich-nav__action-btn--ghost">
                        <span class="tich-nav__icon" aria-hidden="true">
                            @include('partials.navigation.sidebar-icon', ['name' => 'log-in'])
                        </span>
                        <span class="tich-nav__label">Sign in</span>
                    </a>
                    <a href="{{ route('apply.index') }}" class="tich-nav__action-btn tich-nav__action-btn--primary">
                        <span class="tich-nav__icon" aria-hidden="true">
                            @include('partials.navigation.sidebar-icon', ['name' => 'user-plus'])
                        </span>
                        <span class="tich-nav__label">Apply now</span>
                    </a>
                @endauth
            </div>
        </nav>
    </div>

    <div class="tich-nav-drawer" data-nav-drawer hidden>
        <div class="tich-nav-drawer__backdrop" data-nav-drawer-backdrop aria-hidden="true"></div>
        <div class="tich-nav-drawer__panel">
            <nav class="tich-container tich-nav-drawer__inner" aria-label="Mobile navigation">
                @foreach ($headerMenu as $item)
                    @include('partials.navigation.menu-item', ['item' => $item, 'mobile' => true])
                @endforeach

                @auth
                    @include('partials.navigation.auth-portal-links', ['mobile' => true])
                @endauth

                <div class="tich-nav-drawer__actions">
                    <div class="tich-nav-drawer__theme">
                        @include('partials.theme-toggle')
                        <span class="tich-caption">Appearance</span>
                    </div>
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="tich-btn tich-btn-ghost tich-btn-block">
                                Sign out
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="tich-btn tich-btn-blue tich-btn-block">Sign in</a>
                        <a href="{{ route('apply.index') }}" class="tich-btn tich-btn-primary tich-btn-block">Apply now</a>
                    @endauth
                </div>
            </nav>
        </div>
    </div>
</header>
